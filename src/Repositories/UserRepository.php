<?php

namespace Wanphp\Plugins\MimiProgram\Repositories;

use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Libray\Weixin\MiniProgram;
use Wanphp\Plugins\MimiProgram\Domain\UserInterface;
use Wanphp\Plugins\MimiProgram\Entities\UserEntity;

class UserRepository extends BaseRepository implements UserInterface
{
  private MiniProgram $miniProgram;

  public function __construct(Database $database, MiniProgram $miniProgram)
  {
    $this->miniProgram = $miniProgram;
    parent::__construct($database, self::TABLE_NAME, UserEntity::class);
  }


  public function subscribeMessageSend(array $uidArr, string $template_id, array $msgData, string $page): array
  {
    if (empty($msgData)) return ['errCode' => '1', 'msg' => '无模板信息内容'];
    //取用户openid
    if (!empty($uidArr)) {
      if (empty($template_id)) return ['errCode' => '1', 'msg' => '无模板ID,请先获取模板ID'];
      $ok = 0;
      foreach ($uidArr as $openid) {
        try {
          $this->miniProgram->subscribeMessageSend($openid, $template_id, $msgData, $page);
          $ok++;
        } catch (\Exception $exception) {
          $error[] = $exception->getMessage();
        }
      }
      return ['errCode' => '0', 'ok' => $ok, 'msg' => $error ?? ''];
    } else {
      return ['errCode' => '1', 'msg' => '未检测到用户ID'];
    }
  }
}