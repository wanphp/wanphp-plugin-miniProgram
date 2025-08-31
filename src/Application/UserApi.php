<?php

namespace Wanphp\Plugins\MiniProgram\Application;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Libray\Slim\Action;
use Wanphp\Plugins\MiniProgram\Domain\UserInterface;

class UserApi extends Action
{
  /**
   * @var UserInterface
   */
  protected UserInterface $user;

  /**
   * @param UserInterface $user
   */
  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  protected function action(): Response
  {
    if ($this->request->getMethod() == 'PATCH') {
      $uid = (int)$this->request->getAttribute('auth_user_id');
      if ($uid < 1) return $this->respondWithError('授权超时', 422);
      // 用户自己修改信息
      return $this->respondWithData($this->user->updateUser($uid, $this->getFormData()), 201);
    }
    return $this->respondWithError('禁止访问', 403);
  }

  /**
   * 用户自助注销账号
   * @throws Exception
   */
  public function logOutAccount(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $uid = (int)$this->request->getAttribute('auth_user_id');
    if ($uid < 1) return $this->respondWithError('授权超时', 422);

    $res = $this->user->update(['nickName' => '', 'avatarUrl' => '', 'name' => '', 'tel' => '', 'status' => '-'], ['id' => $uid]);
    // todo 删除其它用户数据

    if ($res > 0) return $this->respondWithData(['msg' => '账号注销成功！'], 201);
    else return $this->respondWithError('账号已注销！');
  }

  /**
   * 用户基本信息
   * @throws Exception
   */
  public function userProfile(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $uid = (int)$this->request->getAttribute('auth_user_id');
    if ($uid < 1) return $this->respondWithError('授权超时', 422);

    $user = $this->user->get('nickName,avatarUrl,name,tel', ['id' => $uid]);
    if ($user) return $this->respondWithData($user);
    else return $this->respondWithError('用户不存在');
  }

}
