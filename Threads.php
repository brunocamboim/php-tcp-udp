<?php

require_once 'Sockets.php';

class Threads extends Thread {

    public function __construct($arg) {
        $this->arg = $arg;
    }

    public function run() {
        if ($this->arg) {

            switch($this->arg) {

                case 'SOCKET_SERVER':

                    $socket = new Sockets();
                    $socket->createSocket();

                    break;

                default:

                    #criar socket para cliente
                    $socket = new Sockets($this->arg);
                    $socket->createSocketClient();

                    break;

            }   
        }
    }
    
}