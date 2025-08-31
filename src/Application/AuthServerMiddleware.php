<?php

namespace Wanphp\Plugins\MiniProgram\Application;


use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

class AuthServerMiddleware implements MiddlewareInterface
{
  protected CacheInterface $storage;

  /**
   * @param CacheInterface $storage
   */
  public function __construct(CacheInterface $storage)
  {
    $this->storage = $storage;
  }

  /**
   * @throws Exception
   */
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    if ($request->hasHeader('authorization') === false) {
      throw new Exception('header中缺少"Authorization"');
    }

    $header = $request->getHeader('authorization');
    $token = \trim((string)\preg_replace('/^\s*Bearer\s/', '', $header[0]));

    $user = $this->storage->get($token);
    if (!empty($user['id'])) {
      $request->withAttribute('auth_user_id', $user['id'])
        ->withAttribute('openid', $user['openid'])
        ->withAttribute('session_key', $user['session_key']);
      return $handler->handle($request);
    }
    throw new Exception('授权已过期');
  }

}
