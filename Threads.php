<?php

require_once 'Sockets.php';

class Threads extends Thread {

    public function __construct($arg) {
        $this->arg = $arg;
    }

    public function run() {
        if ($this->arg) {

            switch($this->arg) {

                case 'SERVER_UDP':

                    $socket = new Sockets();
                    $socket->createServerUDP();

                    break;

                case 'CLIENT_UDP':

                        $socket = new Sockets();
                        $socket->createClientUDP();
    
                        break;

                default:

                    #criar socket para cliente
                    // $socket = new Sockets($this->arg);
                    // $socket->createClientUDP();

                    break;

            }   
        }
    }
    
}