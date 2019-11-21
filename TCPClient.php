<?php

require_once 'Sockets.php';

set_time_limit(0);
ob_implicit_flush();

$sockets = new Sockets();
$sockets->createClientTCP();