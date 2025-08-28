<?php

namespace Wanphp\Plugins\MimiProgram\Entities;

use Wanphp\Libray\Mysql\EntityTrait;

class UserEntity implements \JsonSerializable
{

  use EntityTrait;

  /**
   * @DBType({"key":"PRI","type":"int NOT NULL"})
   * @var integer|null
   * @OA\Property(format="int64", description="用户ID")
   */
  private ?int $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(29) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="小程序openid")
   */
  private string $openid;
  /**
   * @DBType({"type":"int NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(description="推荐用户ID")
   */
  private int $shareUid;
  /**
   * @DBType({"key":"UNI","type":"varchar(29) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户昵称")
   */
  private string $nickName;
  /**
   * @DBType({"key":"UNI","type":"varchar(29) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户头像")
   */
  private string $avatarUrl;
  /**
   * @DBType({"key":"MUL","type":"varchar(30) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户姓名")
   */
  protected string $name;
  /**
   * @DBType({"key":"UNI","type":"varchar(30) NULL DEFAULT NULL"})
   * @var string|null
   * @OA\Property(description="用户联系电话")
   */
  protected ?string $tel;
  /**
   * @DBType({"type":"char(1) NOT NULL DEFAULT '0'"})
   * @OA\Property(description="用户状态，1为禁用,-为注销")
   * @var string
   */
  private string $status;
  /**
   * @DBType({"type":"char(10) NOT NULL DEFAULT '0'"})
   * @OA\Property(description="加入时间")
   * @var string
   */
  private string $ctime;
}