#!/usr/bin/php
<?php

require_once('/home/jiggmin/blossom/blossom_server/BlossomServer.php');


$address = $_SERVER['argv'][1];
$user_port = $_SERVER['argv'][2];
$query_port = $_SERVER['argv'][3];
$admin_port = $_SERVER['argv'][4];
$encryption_key = $_SERVER['argv'][5];
$db_id = $_SERVER['argv'][6];



$server = new BlossomServer();

$server->listen($address, $user_port, 'UserSocket');
$server->listen($address, $query_port, 'LocalQuerySocket', false);
$server->listen($address, $admin_port, 'AdminSocket', false);

$server->set_key($encryption_key);
$server->set_query_server_path("/home/jiggmin/blossom/query_server.php $address $query_port $db_id > /dev/null &");

$server->start();

?>