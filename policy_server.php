#!/usr/bin/php
<?php

require_once('/home/jiggmin/blossom/blossom_server/BlossomServer.php');

$address = '208.78.96.138';
$port = '843';

$admin_port = $_SERVER['argv'][4];

$server = new BlossomServer();
$server->listen($address, $port, 'PolicySocket');
$server->listen($address, $admin_port, 'AdminSocket', false);

$server->start();

?>