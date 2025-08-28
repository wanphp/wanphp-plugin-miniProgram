<?php

namespace Wanphp\Plugins\MimiProgram\Application\Manage;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Libray\Slim\Action;
use Wanphp\Libray\Weixin\MiniProgram;

/**
 * Class SubscribeMessageAction
 * @title 小程序订阅消息模板
 * @route /admin/miniProgram/subscribeMessage
 * @package Wanphp\Plugins\MimiProgram\Application\Manage
 */
class SubscribeMessageAction extends Action
{
  private MiniProgram $miniProgram;

  public function __construct(MiniProgram $miniProgram)
  {
    $this->miniProgram = $miniProgram;
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'POST':
        $post = $this->request->getParsedBody();
        $tid = $post['tid'];
        $kidList = $post['kidList'];
        $sceneDesc = $post['sceneDesc'];
        return $this->respondWithData($this->miniProgram->addTemplate($tid, $kidList, $sceneDesc));
      case 'DELETE':
        return $this->respondWithData($this->miniProgram->deleteTemplate($this->resolveArg('id')));
      case 'GET':
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $template = $this->miniProgram->getTemplateList();
          if (count($template['data']) > 0) foreach ($template['data'] as $template) {
            $template['content'] = nl2br($template['content']);
            $template['example'] = nl2br($template['example']);
            $list[] = $template;
          }
          return $this->respondWithData([
            'data' => $list ?? []
          ]);
        } else {
          try {
            $category = $this->miniProgram->getCategory();
          } catch (Exception) {
            $category = [];
          }
          $data = [
            'title' => '订阅消息管理',
            'category' => $category
          ];
          return $this->respondView('@miniprogram/template.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

  /**
   * 发送订阅消息
   * @throws Exception
   */
  public function subscribeMessageSend(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $post = $this->request->getParsedBody();
    if (!isset($post['users'])) return $this->respondWithData(['errCode' => '1', 'msg' => '未检测到用户ID']);
    if (!isset($post['data'])) return $this->respondWithData(['errCode' => '1', 'msg' => '无模板信息内容']);
    if (!isset($post['template_id'])) return $this->respondWithData(['errCode' => '1', 'msg' => '无模板ID']);
    $ok = 0;
    foreach ($post['users'] as $openid) {
      try {
        $this->miniProgram->subscribeMessageSend($openid, $post['template_id'], $post['data'], $post['page']);
        $ok++;
      } catch (\Exception $exception) {
        $error[] = $exception->getMessage();
      }
    }
    return $this->respondWithData(['errCode' => '0', 'ok' => $ok, 'msg' => $error ?? '']);
  }

}