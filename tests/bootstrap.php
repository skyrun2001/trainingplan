<?php

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

if (file_exists(dirname(__DIR__).'/.env.test')) {
    (new Symfony\Component\Dotenv\Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/.env.test');
}
