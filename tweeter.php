#!/usr/bin/env php
<?php
date_default_timezone_set('America/Chicago');

require __DIR__.'/vendor/autoload.php';

use Memtech\Console\Command\SocialExportCommand;
use Memtech\Console\Command\TweeterCommand;
use Symfony\Component\Console\Application;

try
{
    // Set up the environment variables
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    $dotenv->required([
        'TWITTER_KEY',
        'TWITTER_SECRET',
        'TWITTER_TOKEN',
        'TWITTER_TOKEN_SECRET',
        'MEETUP_KEY',
        'GOOGLE_SHORTEN_KEY'
    ]);
}
catch (\Dotenv\Exception\ValidationException $e)
{
    exit($e->getMessage());
}

$application = new Application();
$application->add(new TweeterCommand());
$application->add(new SocialExportCommand());
$application->run();
