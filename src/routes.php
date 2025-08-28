<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Server\MiddlewareInterface as Middleware;

return function (App $app, Middleware $PermissionMiddleware, Middleware $OAuthServerMiddleware) {
  // 小程序推送接口，使用时按实际情况重写方法
  // $app->map(['GET', 'POST'], '/miniPush', \Wanphp\Plugins\MimiProgram\Application\MiniProgramPush::class);

  $app->group('/auth', function (Group $group) use ($OAuthServerMiddleware) {
    // 小程序用户登录
    $group->post('/accessToken', \Wanphp\Plugins\MimiProgram\Application\AccessTokenAction::class);
    // 小程序授权码
    $group->get('/authCode', \Wanphp\Plugins\MimiProgram\Application\AccessTokenAction::class . ':getMimiProgramAuthCode');
    // 授权确认
    $group->post('/confirmAuth', \Wanphp\Plugins\MimiProgram\Application\AccessTokenAction::class . ':confirmAuth')->addMiddleware($OAuthServerMiddleware);
  });
  // 后台管理
  $app->group('/admin/miniProgram', function (Group $group) {
    // 用户基本信息管理
    $group->map(['GET', 'PUT'], '/user[/{id:[0-9]+}]', \Wanphp\Plugins\MimiProgram\Application\Manage\UserAction::class);
    $group->get('/user/search', \Wanphp\Plugins\MimiProgram\Application\Manage\UserAction::class. ':searchUser');
    // 订阅消息模板
    $group->map(['GET', 'POST', 'DELETE'], '/subscribeMessage[/{id}]', \Wanphp\Plugins\MimiProgram\Application\Manage\SubscribeMessageAction::class);
  })->addMiddleware($PermissionMiddleware);
  // Api 接口
  $app->group('/api', function (Group $group) use ($PermissionMiddleware) {
    // 取当前用户信息
    $group->get('/userProfile', \Wanphp\Plugins\MimiProgram\Application\UserApi::class . ':userProfile');
    // 更新当前信息
    $group->patch( '/user', \Wanphp\Plugins\MimiProgram\Application\UserApi::class);
    // 用户注销
    $group->post('/logOutAccount', \Wanphp\Plugins\MimiProgram\Application\UserApi::class . ':logOutAccount');
    // 发送消息
    //$group->post('/user/sendMsg', \Wanphp\Plugins\MimiProgram\Application\Manage\SubscribeMessageAction::class.':subscribeMessageSend');
  })->addMiddleware($OAuthServerMiddleware);
};


