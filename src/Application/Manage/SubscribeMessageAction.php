<?php

namespace Wanphp\Plugins\MiniProgram\Application\Manage;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Slim\Action;
use Wanphp\Libray\Weixin\MiniProgram;

/**
 * Class SubscribeMessageAction
 * @title 小程序订阅消息模板
 * @route /admin/miniProgram/subscribeMessage
 * @package Wanphp\Plugins\MiniProgram\Application\Manage
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
}