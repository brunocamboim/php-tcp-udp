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

                case 'SERVER_TCP':

                    $socket = new Sockets();
                    $socket->createServerTCP();

                    break;

                case 'CLIENT_TCP':

                    $socket = new Sockets();
                    $socket->createClientTCP();

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