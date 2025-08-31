<?php
declare(strict_types=1);

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    Wanphp\Libray\Weixin\MiniProgram::class => \DI\autowire(Wanphp\Libray\Weixin\MiniProgram::class),
    Wanphp\Plugins\MiniProgram\Domain\UserInterface::class => \DI\autowire(Wanphp\Plugins\MiniProgram\Repositories\UserRepository::class),
  ]);
};
