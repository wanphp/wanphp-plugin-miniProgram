<?php

namespace Wanphp\Plugins\MimiProgram\Domain;

use Wanphp\Libray\Mysql\BaseInterface;

interface UserInterface extends BaseInterface
{
  const TABLE_NAME = "min_program_users";

  /**
   * 通过微信服务号发送模板消息
   * @param array $uidArr
   * @param string $template_id
   * @param array $msgData
   * @param string $page
   * @return array
   */
  public function subscribeMessageSend(array $uidArr, string $template_id, array $msgData,string $page): array;
}