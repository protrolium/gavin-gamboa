<?php namespace ProcessWire;

$config->rockdevtools = true;

/** @var Config $config */
$config->debug = true;
$config->advanced = true;
$config->dbHost = 'localhost';
$config->dbName = 'gavingamboa';
$config->dbUser = 'root';
$config->dbPass = 'root';
$config->dbPort = '3306';
$config->userAuthSalt = 'c68e1b88570123e6df5290697b0ab8126eb74386'; 
$config->tableSalt = 'ffb4c5932a7af071ed3ff2436e7c28f30b6b7fc8'; 
$config->httpHosts = array('localhost:8888', 'localhost:8888');

// this prevents logout when switching between
// desktop and mobile in chrome devtools
$config->sessionFingerprint = false;

// RockFrontend
$config->livereload = 1;

// RockMigrations
// $config->filesOnDemand = 'https://your-live.site/';
// $config->rockmigrations = [
//   'syncSnippets' => true,
// ];

// tracy config for ddev development
// $config->tracy = [
//   'outputMode' => 'development',
//   'guestForceDevelopmentLocal' => true,
//   'forceIsLocal' => true,
//   'localRootPath' => '/Users/xyz/code/yourproject/',
//   'numLogEntries' => 100, // for RockMigrations
// ];

// $config->rockpagebuilder = [
//   "createView" => "latte",
// ];