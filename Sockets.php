<?php

require_once 'Helper.php';

class Sockets {
    
    private $port;
    private $address;

    function __construct($adress = null, $port = 29000) 
    {
        
        $this->port = $port;
        $this->address = isset($adress) ? $adress : getHostByName(getHostName());

    }

    public function getPort(){
        return $this->port;
    }

    public function getAddress(){
        return $this->address;
    }

    public function setPort($value){
        $this->port = $value;
    }

    public function setAddress($value){
        $this->address = $value;
    }

    public function createServerUDP() {

        if (!($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao criar o socket: [$errorcode] $errormsg \n");
        }
        
        echo " Socket serverUDP criado! Meu server: $this->address - $this->port \n";

        if (!socket_bind($sock, $this->address, $this->port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao fazer o bind do ip e porta: [$errorcode] $errormsg \n");
        }

        do {

            clearstatcache();

            $r = socket_recvfrom($sock, $buf, 2045, 0, $remote_ip, $remote_port);

            echo "Server recebeu requisicao de: $remote_ip : $remote_port -- $buf \n" ;

            $return = "Recebido!";

            if( !socket_sendto($sock, $return, strlen($return) , 0 , $remote_ip , $remote_port) )
            {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                
                echo "Erro ao mandar de volta para o cliente!\n";
            }
        
        } while (true);
        
        socket_close($sock);

    }

    public function createClientUDP() {

        $server = $this->address;
        $port = $this->port;

        if (!($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            die("Nao foi possivel criar o socket do cliente ($this->address) : [$errorcode] $errormsg \n");
        }

        socket_set_nonblock( $sock );

        echo "Socket do cliente UDP $this->address criado! \n";

        $pacotes = 1;
        while(1) {

            clearstatcache();
            
            $input = Helper::generateRandomString(100);

            try{

                for ($i = 0; $i < $pacotes; $i++) {
                    socket_sendto($sock, $input , strlen($input) , 0 , $server , $port);
                }
                
                $recebidos = 0;
                for ($i = 0; $i < $pacotes; $i++) {

                    if( socket_recv($sock, $reply, 1000, 0) !== FALSE ) {
                        echo "Recebido: $pacotes \n";
                        $recebidos++;
                    }

                }

                if ($recebidos != $pacotes) throw new Exception("Não recebido alguns pacotes");

                $pacotes *= 2;

            } catch (Exception $e) {

                echo "ERRO\n $pacotes\n";
                $pacotes = 1;

            }
        }
    }

    public function createServerTCP() {

        if (!($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao criar o socket: [$errorcode] $errormsg \n");
        }
        
        echo " Socket serverTCP criado! Meu server: $this->address - $this->port \n";

        if (!socket_bind($sock, $this->address, $this->port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao fazer o bind do ip e porta: [$errorcode] $errormsg \n");
        }

        socket_listen($sock);

        $client = socket_accept($sock);

        do {

            clearstatcache();

            unset($buf);

            $input = socket_recv($client, $buf, sizeof($buf), 0);

            var_dump("Recebi $input $buf");

            socket_send($client, $input, sizeof($input), 0);

            sleep(3);
        
        } while (true);
        
        socket_close($sock);

    }

    public function createClientTCP() {

        $server = $this->address;
        $port = $this->port;

        if (!($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            die("Nao foi possivel criar o socket do cliente ($this->address) : [$errorcode] $errormsg \n");
        }

        echo "Socket do cliente TCP $this->address criado! \n";

        if ( socket_connect($sock, $this->address, $this->port) === false ) {
            die("Nao foi possivel criar a conexão de controle com o socket! \n");
        }

        $pacotes = 1;
        while(1) {

            try {

                clearstatcache();
                
                $input = Helper::generateRandomString(100);

                for ( $i = 0; $i < $pacotes; $i++) {

                    $length = socket_send($sock, $input, sizeof($input), 0);

                }

                $receive = socket_recv($sock, $buf, sizeof($buf), 0);

                var_dump("Recebi de volta $buf $receive");
                
                $pacotes *= 2;

                sleep(5);

            } catch (Exception $e) {

                
                echo "ERRO\n $pacotes\n";
                $pacotes = 1;

                exit;

            }
        }
    }

}