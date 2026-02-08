<?php

declare(strict_types=1);

namespace PicPay\Application;

use DI\Container;
use PicPay\Application\Controller\TransferController;
use PicPay\Application\Middleware\ErrorHandlerMiddleware;
use PicPay\Application\Middleware\JsonBodyParserMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;

class Bootstrap
{
    public static function create(Container $container): App
    {
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Middleware
        $app->add(new JsonBodyParserMiddleware());
        $app->add($container->get(ErrorHandlerMiddleware::class));

        // Routes
        $app->post('/transfer', [TransferController::class, 'transfer']);

        return $app;
    }
}

