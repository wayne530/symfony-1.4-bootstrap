<?php


require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$env = isset($_SERVER['SYMFONY_ENV']) ? $_SERVER['SYMFONY_ENV'] : 'prod';
$debug = isset($_SERVER['SYMFONY_DEBUG']) ? ($_SERVER['SYMFONY_DEBUG'] === 'true') : preg_match('/^dev/i', $env);
$configuration = ProjectConfiguration::getApplicationConfiguration('frontend', $env, $debug);
sfContext::createInstance($configuration)->dispatch();
