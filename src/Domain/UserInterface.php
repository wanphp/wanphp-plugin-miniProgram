<?php

namespace Wanphp\Plugins\MiniProgram\Domain;

use Wanphp\Libray\Mysql\BaseInterface;
use Wanphp\Libray\Slim\WpUserInterface;

interface UserInterface extends BaseInterface, WpUserInterface
{
  const TABLE_NAME = "min_program_users";

  /**
   * 通过微信服务号发送模板消息
   * @param array $openidArr
   * @param string $template_id
   * @param array $msgData
   * @param string $page
   * @return array
   */
  public function subscribeMessageSend(array $openidArr, string $template_id, array $msgData, string $page): array;
}