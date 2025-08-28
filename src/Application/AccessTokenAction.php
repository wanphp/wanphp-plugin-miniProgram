<?php

namespace Wanphp\Plugins\MimiProgram\Application;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Psr16Cache;
use Wanphp\Libray\Slim\Action;
use Wanphp\Libray\Slim\RedisCacheFactory;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\MiniProgram;
use Wanphp\Plugins\MimiProgram\Domain\UserInterface;

class AccessTokenAction extends Action
{
  private MiniProgram $miniProgram;
  private CacheInterface $storage;
  private UserInterface $user;

  public function __construct(
    MiniProgram       $miniProgram,
    Setting           $setting,
    RedisCacheFactory $cacheFactory,
    UserInterface     $user)
  {
    $this->miniProgram = $miniProgram;
    $options = $setting->get('wechat.miniprogram');
    $this->storage = new Psr16Cache($cacheFactory->create($options['database'] ?? 0, $options['prefix'] ?? 'miniProgramToken'));
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
  public function authQr(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $obfuscatory = new SessionIdObfuscatory($this->storage);

    $scene = $obfuscatory->encode(session_id());
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
      if ($session_id != $ssid) {
        session_unset();
        session_destroy();
        session_id($ssid);
        session_start();
      }
      $_SESSION['user_id'] = $uid;
    }
    return $this->respondWithData();
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