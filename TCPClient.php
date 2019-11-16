<?php

require_once 'Sockets.php';

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$sockets = new Sockets();
$sockets->createClientTCP();