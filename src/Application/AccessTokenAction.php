<?php

namespace Wanphp\Plugins\MiniProgram\Application;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Libray\Slim\Action;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\MiniProgram;
use Wanphp\Plugins\MiniProgram\Domain\UserInterface;
use Wanphp\Plugins\MiniProgram\SessionIdObfuscatory;

class AccessTokenAction extends Action
{
  private Database $db;
  private LoggerInterface $logger;
  private MiniProgram $miniProgram;
  private CacheInterface $storage;
  private UserInterface $user;

  public function __construct(
    Database        $database,
    LoggerInterface $logger,
    MiniProgram     $miniProgram,
    Setting         $setting,
    UserInterface   $user)
  {
    $this->db = $database;
    $this->logger = $logger;
    $this->miniProgram = $miniProgram;
    $this->storage = $setting->get('AuthCodeStorage');
    $this->user = $user;
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    $params = $this->getFormData();
    $code = $params['code'] ?? '';
    $token = $params['token'] ?? '';
    // 删除旧的token
    if (empty($token)) $this->storage->delete($token);

    $data = $this->miniProgram->code2Session($code);

    $token = bin2hex(random_bytes(16));
    $this->storage->set($token, [
      'openid' => $data['openid'],
      'session_key' => $data['session_key'],
      'id' => $this->getAuthUserId($data['openid'])
    ]);

    return $this->respondWithData(['access_token' => $token]);
  }

  /**
   * 小程序授权码
   * @throws Exception
   */
  public function getMiniProgramAuthCode(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $obfuscatory = new SessionIdObfuscatory($this->storage, 7200, 48);
    $type = $this->request->getQueryParams()['type'] ?? '';
    $scene = match ($type) {
      2 => $obfuscatory->encode(session_id(), 2),
      1 => $obfuscatory->encode(session_id(), 1),
      default => $obfuscatory->encode(session_id()),
    };
    $result = $this->miniProgram->getUnlimitedQRCode($scene, 'pages/auth/confirm');
    $this->response->getBody()->write($result['body']);
    return $this->response->withHeader('Content-Type', 'image/jpeg')->withStatus(200);
  }

  /**
   * 小程序确认授权
   * @throws Exception
   */
  public function confirmAuth(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $uid = (int)$this->request->getAttribute('auth_user_id', 0);
    if ($uid > 0) {
      $params = $this->getFormData();
      $code = $params['code'] ?? '';
      $obfuscatory = new SessionIdObfuscatory($this->storage);
      $ssid = $obfuscatory->decode($code);
      $session_id = session_id();
      if ($session_id != $ssid['session_id']) {
        session_unset();
        session_destroy();
        session_id($ssid['session_id']);
        session_start();
      }
      switch ($ssid['type']) {
        case 1:
          // 确认绑定授权
          if (isset($_SESSION['login_id']) && is_numeric($_SESSION['login_id'])) {
            $admin = $this->db->get('admini', ['id', 'account'], ['uid' => $uid]);
            if ($admin) {
              if ($admin['id'] == $_SESSION['login_id']) {
                return $this->respondWithError('重复绑定，您应该使用新的微信扫码');
              } else {
                return $this->respondWithError('您的微信已与”' . $admin['account'] . '“帐号绑定，需先解除才能绑定！！');
              }
            }
            // 扫码微信未绑定过
            $user = $this->user->get('name,tel', ['id' => $uid]);
            $data = ['uid' => $uid, 'name' => $user['name']];
            if ($user['tel']) $data['tel'] = $user['tel'];
            $up = $this->db->update('admini', $data, ['id' => $_SESSION['login_id']]);
            if ($up > 0) {
              // 记录扫码微信uid，登录登录帐号uid为$_SESSION['user_id']，注意区分
              $_SESSION['login_user_id'] = $uid;
              return $this->respondWithData(['msg' => '绑定成功！']);
            } else {
              return $this->respondWithError('绑定失败，请重试！！');
            }
          }
          return $this->respondWithError('绑定账号登录超时！！');
        case 2:
          // 取消绑定授权
          $admin_id = $_SESSION['login_id'] ?? 0;
          $bindUid = $_SESSION['user_id'] ?? 0;
          // 检查绑定管理员
          if ($admin_id > 0 && $bindUid == $uid) {
            $account = $this->db->get('admini', 'account', ['uid' => $uid]);
            if ($account) {
              $this->db->update('admini', ['uid' => 0, 'name' => '', 'tel' => ''], ['id' => $admin_id]);
              return $this->respondWithData(['msg' => '与“' . $account . '”解除绑定成功，可以绑定到其它账号！！']);
            } else {
              return $this->respondWithError('重复解绑操作，您的微信当前未绑定此账号！！');
            }
          } else {
            return $this->respondWithError('绑定帐号与当前授权用户不是一个用户！！');
          }
        default:
          // 授权登录
          $admin = $this->db->get('admini', ['id', 'role_id', 'groupId', 'account', 'status'], ['uid' => $uid]);
          if (!$admin) return $this->respondWithError('微信尚未绑定帐号，请使用密码登录！');
          if ($admin['status'] == 1) {
            $_SESSION['login_id'] = $admin['id'];
            $_SESSION['role_id'] = $admin['role_id'];
            $_SESSION['groupId'] = $admin['groupId'];
            $_SESSION['user_id'] = $uid;
            $this->logger->log(0, '通过小程序授权登录系统；授权用户UID' . $uid);
            return $this->respondWithData(['res' => '授权登录成功！！']);
          } else {
            return $this->respondWithError('帐号已被锁定，无法登录！！');
          }
      }
    }
    return $this->respondWithError('小程序认证超时！');
  }

  /**
   * @param string $openid
   * @return int
   * @throws Exception
   */
  private function getAuthUserId(string $openid): int
  {
    //用户基本数据
    $userinfo = $this->user->get('id,status', ['openid' => $openid]);
    if (empty($userinfo)) {
      $id = $this->user->insert(['openid' => $openid]);
    } else {
      if ($userinfo['status'] == 1) {// 用户已禁用
        throw new Exception('系统已禁止你使用！');
      } else {
        // 注销用户再次登录
        if ($userinfo['status'] == '-') $this->user->update(['status' => 0], ['openid' => $openid]);
        $id = $userinfo['id'];
      }
    }
    return $id;
  }
}