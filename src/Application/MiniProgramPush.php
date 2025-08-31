<?php

namespace Wanphp\Plugins\MiniProgram\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Wanphp\Libray\Slim\Action;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\MiniProgram;

class MiniProgramPush extends Action
{

  protected MiniProgram $miniProgram;
  protected LoggerInterface $logger;

  protected string $message_token;

  public function __construct(MiniProgram $miniProgram, Setting $setting, LoggerInterface $logger)
  {
    $this->miniProgram = $miniProgram;
    $this->logger = $logger;
    $options = $setting->get('wechat.miniprogram');
    $this->message_token = $options['token'];
  }

  protected function action(): Response
  {
    $queryParams = $this->request->getQueryParams();

    if (!isset($queryParams["signature"])) {
      $this->response->getBody()->write('no access');
      return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }

    $tmpArr = [$this->message_token, $queryParams["timestamp"] ?? '', $queryParams["nonce"] ?? ''];
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode($tmpArr);
    $signature = sha1($tmpStr);

    if ($queryParams["signature"] == $signature) {
      if ($this->request->getMethod() == 'POST') {
        // todo 接收到小程序推送信息
        $this->logger->info('post', $this->getFormData());
      }

      $this->response->getBody()->write($queryParams["echostr"]);
      return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }
    $this->response->getBody()->write('no access');
    return $this->response->withHeader('Content-Type', 'text/plain')->withStatus(200);
  }
}