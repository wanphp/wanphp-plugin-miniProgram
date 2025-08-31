<?php

namespace Wanphp\Plugins\MiniProgram\Application\Manage;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Libray\Slim\Action;
use Wanphp\Libray\Slim\HttpTrait;
use Wanphp\Plugins\MiniProgram\Domain\UserInterface;

/**
 * Class UserAction
 * @title 小程序用户管理
 * @route /admin/miniProgram/user
 * @package Wanphp\Plugins\MiniProgram\Application\Manage
 */
class UserAction extends Action
{
  use HttpTrait;

  private UserInterface $user;

  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'PUT':
        $data = $this->request->getParsedBody();
        if (empty($data)) return $this->respondWithError('无用户数据');
        $num = $this->user->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['upNum' => $num], 201);
      case 'GET':
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $params = $this->request->getQueryParams();
          $where = [];
          if (isset($params['shareUid']) && $params['shareUid'] > 0) $where['shareUid'] = intval($params['shareUid']);

          $recordsTotal = $this->user->count('id', $where);
          if (!empty($params['search']['value'])) {
            $keyword = trim($params['search']['value']);
            $keyword = addcslashes($keyword, '*%_');
            $where['OR'] = [
              'nickName[~]' => $keyword,
              'name[~]' => $keyword,
              'tel[~]' => $keyword
            ];
          }

          $where['ORDER'] = ['status' => 'DESC', 'lastLoginTime' => 'DESC'];
          $recordsFiltered = $this->user->count('id', $where);
          $limit = $this->getLimit();
          if ($limit) $where['LIMIT'] = $limit;
          $users = $this->user->select('id,openid,nickName,avatarUrl,name,tel,status,ctime', $where);

          $data = [
            "draw" => $params['draw'],
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            'data' => $users
          ];

          return $this->respondWithData($data);
        } else {
          $data = [
            'title' => '用户管理',
          ];
          return $this->respondView('@miniprogram/userList.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

  /**
   * @param Request $request
   * @param Response $response
   * @param array $args
   * @return Response
   * @throws Exception
   */
  public function searchUser(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $params = $this->request->getQueryParams();
    if (isset($params['q']) && $params['q'] != '') {
      $keyword = trim($params['q']);
    } else {
      return $this->respondWithError('关键词不能为空！');
    }
    $page = intval($params['page'] ?? 1);

    $where = [];
    $where['OR'] = [
      'name[~]' => $keyword,
      'nickName[~]' => $keyword,
      'tel[~]' => $keyword
    ];
    $total = $this->user->count('id', $where);
    $start = (max($page, 1) - 1) * 10;
    $where['LIMIT'] = [$start, 10];
    $where['ORDER'] = ['id' => 'DESC'];

    $data = [
      'users' => $this->user->select('id,openid,nickName,avatarUrl,name,tel', $where),
      'total' => $total
    ];
    return $this->respondWithData($data);
  }
}
