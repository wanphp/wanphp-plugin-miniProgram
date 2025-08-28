<?php

namespace Wanphp\Plugins\MimiProgram\Application;

use Exception;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;

class SessionIdObfuscatory
{
  private CacheInterface $cache;
  private string $rulesKey = "session_rules"; // Redis key
  private int $ruleTtl; // 规则有效期（秒）
  private int $ruleCount; // 规则数量（比如 16）

  public function __construct(CacheInterface $cache, int $ruleTtl = 7200, int $ruleCount = 16)
  {
    $this->cache = $cache;
    $this->ruleTtl = $ruleTtl;
    $this->ruleCount = $ruleCount;
  }

  /**
   * @return array
   * @throws InvalidArgumentException
   * @throws RandomException
   */
  private function getRules(): array
  {
    $rules = $this->cache->get($this->rulesKey);
    if ($rules) return $rules;

    // 重新生成规则
    $newRules = [];
    $markers = array_map(fn($i) => dechex($i), range(0, $this->ruleCount - 1));

    foreach ($markers as $marker) {
      $positions = [];
      while (count($positions) < 5) {
        $pos = random_int(1, 26); // session_id 长度 26
        $positions[$pos] = true;
      }
      ksort($positions);
      $newRules[$marker] = array_keys($positions);
    }

    $this->cache->set($this->rulesKey, $newRules, $this->ruleTtl);
    return $newRules;
  }

  /**
   * @param string $sid
   * @return string
   * @throws InvalidArgumentException
   * @throws RandomException
   * @throws Exception
   */
  public function encode(string $sid): string
  {
    if (strlen($sid) !== 26) {
      throw new Exception("session_id 必须是 26 位");
    }

    $rules = $this->getRules();
    $markers = array_keys($rules);
    $marker = $markers[random_int(0, count($markers) - 1)];
    $positions = $rules[$marker];

    // 生成 5 个随机字符
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $randoms = [];
    for ($i = 0; $i < 5; $i++) {
      $randoms[] = $chars[random_int(0, strlen($chars) - 1)];
    }

    // 按规则插入
    $encoded = '';
    $rIndex = 0;
    for ($i = 0; $i < strlen($sid); $i++) {
      $encoded .= $sid[$i];
      if (in_array($i + 1, $positions)) {
        $encoded .= $randoms[$rIndex++];
      }
    }

    return $marker . $encoded;
  }

  /**
   * @param string $encoded
   * @return string
   * @throws InvalidArgumentException
   * @throws RandomException
   */
  public function decode(string $encoded): string
  {
    $marker = strtolower($encoded[0]); // 头标记
    $body = substr($encoded, 1);

    $rules = $this->getRules();
    if (!isset($rules[$marker])) return '';

    $positions = $rules[$marker];
    $sid = '';

    $posSet = array_flip($positions);
    $sidIndex = 0;

    for ($i = 0; $i < strlen($body); $i++) {
      $sidIndex++;
      $sid .= $body[$i];
      if (isset($posSet[$sidIndex])) {
        $i++;       // 跳过随机字符
        $sidIndex++;
      }
    }

    return $sid;
  }
}