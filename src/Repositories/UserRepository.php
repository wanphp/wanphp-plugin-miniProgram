<?php

namespace Wanphp\Plugins\MiniProgram\Repositories;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Libray\Weixin\MiniProgram;
use Wanphp\Plugins\MiniProgram\Domain\UserInterface;
use Wanphp\Plugins\MiniProgram\Entities\UserEntity;

class UserRepository extends BaseRepository implements UserInterface
{
  private MiniProgram $miniProgram;

  public function __construct(Database $database, MiniProgram $miniProgram)
  {
    $this->miniProgram = $miniProgram;
    parent::__construct($database, self::TABLE_NAME, UserEntity::class);
  }

  /**
   * @param array $openidArr
   * @param string $template_id
   * @param array $msgData
   * @param string $page
   * @return array|string[]
   * @throws Exception
   */
  public function subscribeMessageSend(array $openidArr, string $template_id, array $msgData, string $page): array
  {
    if (empty($msgData)) return ['errCode' => '1', 'msg' => '无模板信息内容'];
    if (!empty($openidArr)) {
      if (empty($template_id)) return ['errCode' => '1', 'msg' => '无模板ID,请先获取模板ID'];
      $ok = 0;
      foreach ($openidArr as $openid) {
        try {
          $this->miniProgram->subscribeMessageSend($openid, $template_id, $msgData, $page);
          $ok++;
        } catch (Exception $exception) {
          $error[] = $exception->getMessage();
        }
      }
      return ['errCode' => '0', 'ok' => $ok, 'msg' => $error ?? ''];
    } else {
      return ['errCode' => '1', 'msg' => '未检测到用户ID'];
    }
  }

  /**
   * @param int $uid
   * @return array
   * @throws Exception
   */
  public function getUser(int $uid): array
  {
    return $this->get('shareUid,openid,nickName,avatarUrl,name,tel', ['id' => $uid]);
  }

  /**
   * @param $uidArr
   * @return array
   * @throws Exception
   */
  public function getUsers($uidArr): array
  {
    return $this->select('id,shareUid,openid,nickName,avatarUrl,name,tel', ['id' => $uidArr]) ?: [];
  }

  /**
   * @param array $data
   * @return array
   * @throws Exception
   */
  public function addUser(array $data): array
  {
    throw new Exception('小程序端不允许添加用户！');
  }

  /**
   * @param int $uid
   * @param array $data
   * @return array
   * @throws Exception
   */
  public function updateUser(int $uid, array $data): array
  {
    $data = [];
    if (isset($post['nickName'])) $data['nickName'] = $post['nickName'];
    if (isset($post['avatarUrl'])) $data['avatarUrl'] = $post['avatarUrl'];
    if (isset($post['name'])) $data['name'] = $post['name'];
    if (isset($post['tel'])) $data['tel'] = $post['tel'];
    if (empty($data)) throw new Exception('无可更新的用户数据！');
    if ($uid > 0) $upNum = $this->update($data, ['id' => $uid]);
    return ['upNum' => $upNum ?? 0];
  }

  /**
   * @param string $keyword
   * @param int $page
   * @return array
   * @throws Exception
   */
  public function searchUsers(string $keyword, int $page = 0): array
  {
    $where = [];
    $where['OR'] = [
      'name[~]' => $keyword,
      'nickName[~]' => $keyword,
      'tel[~]' => $keyword
    ];
    $total = $this->count('id', $where);
    $page = (max($page, 1) - 1) * 10;
    $where['LIMIT'] = [$page, 10];
    $where['ORDER'] = ['id' => 'DESC'];

    return [
      'users' => $this->select('id,shareUid,openid,nickName,avatarUrl,name,tel', $where),
      'total' => $total
    ];
  }

  /**
   * @param array $uidArr
   * @param array $msgData
   * @return array
   * @throws Exception
   */
  public function sendMessage(array $uidArr, array $msgData): array
  {
    if (empty($msgData)) return ['errCode' => '1', 'msg' => '无模板信息内容'];
    //取用户openid
    if (!empty($uidArr)) {
      if (empty($msgData['template_id'])) return ['errCode' => '1', 'msg' => '无模板ID,请先获取模板ID'];
      $openId = $this->select('openid', ['id' => $uidArr]);
      if ($openId) {
        return $this->subscribeMessageSend($openId, $msgData['template_id'], $msgData['data'], $msgData['page']);
      } else {
        return ['errCode' => '1', 'msg' => '用户ID无效'];
      }
    } else {
      return ['errCode' => '1', 'msg' => '未检测到用户ID'];
    }
  }

  /**
   * @throws Exception
   */
  public function membersTagging(string $uid, int $tagId): array
  {
    throw new Exception('小程序端无此操作！');
  }

  /**
   * @throws Exception
   */
  public function membersUnTagging(string $uid, int $tagId): array
  {
    throw new Exception('小程序端无此操作！');
  }

  public function userLogin(string $account, string $password): int|string
  {
    return '系统是默认使用微信授权用户，无注册用户，需要注册用户，需继承后重写';
  }

  public function oauthRedirect(Request $request, Response $response): Response
  {
    $response->getBody()->write('<h1>无法跳转到小程序！</h1>');
    return $response;
  }

  public function getOauthAccessToken(string $code, string $redirect_uri): string
  {
    throw new Exception('小程序端无此操作！');
  }

  public function getOauthUserinfo(string $access_token): array
  {
    throw new Exception('小程序端无此操作！');
  }

  public function updateOauthUser(string $access_token, array $data): array
  {
    throw new Exception('小程序端无此操作！');
  }

  /**
   * @inheritDoc
   */
  public function checkOauthUser(): string
  {
    throw new Exception('小程序端无此操作！');
  }
}