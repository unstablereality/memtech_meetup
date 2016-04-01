#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Memtech\Console\Command\TweeterCommand;
use Symfony\Component\Console\Application;

// Set up the environment variables
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['TWITTER_KEY','TWITTER_SECRET','TWITTER_TOKEN','TWITTER_TOKEN_SECRET','MEETUP_KEY','GOOGLE_SHORTEN_KEY']);

$application = new Application();
$application->add(new TweeterCommand());
$application->run();
