<?php

declare(strict_types=1);

use PicPay\Application\Bootstrap;
use PicPay\Infrastructure\Container\ContainerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

$container = ContainerFactory::create();
$app = Bootstrap::create($container);

$app->run();

