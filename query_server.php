#!/usr/bin/php
<?php

require_once('/home/jiggmin/blossom/blossom_server/BlossomServer.php');

$address = $_SERVER['argv'][1];
$port = $_SERVER['argv'][2];
$db_id = $_SERVER['argv'][3];

$db_server = 'localhost';
$db_user = 'user';
$db_password = 'password';
$db_name = 'server_'.$db_id;

$server = new BlossomServer();
$server->connect($address, $port, 'RemoteQuerySocket');
$server->start();

?>