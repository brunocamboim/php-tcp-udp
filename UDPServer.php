<?php

require_once 'Threads.php';

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$tasks = array("SOCKET_SERVER");

# instancia as threads
foreach ( $tasks as $i ) {
    $stack[] = new Threads($i);
}

# inicia as threads
foreach ( $stack as $t ) {
    $t->start();
}

exit;