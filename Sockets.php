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
        
        echo "Socket serverUDP criado! Meu server: $this->address - $this->port \n";

        if (!socket_bind($sock, $this->address, $this->port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao fazer o bind do ip e porta: [$errorcode] $errormsg \n");
        }

        do {

            clearstatcache();

            $r = socket_recvfrom($sock, $buf, 2045, 0, $remote_ip, $remote_port);

            // echo "Server recebeu requisicao de: $remote_ip : $remote_port -- $buf \n" ;

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

        // socket_set_nonblock( $sock );

        socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1,"usec" => 0) );

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
                        // echo "Recebido: $pacotes \n";
                        $recebidos++;
                    } else {
                        throw new Exception("Nao recebido alguns pacotes");
                    }

                }

                if ($recebidos != $pacotes) throw new Exception("Nao recebido alguns pacotes");

                echo "Recebidos... Nr pacotes: $pacotes \n";
                sleep(1);

                $pacotes *= 2;

            } catch (Exception $e) {

                echo $e->getMessage()."... Nr. pacotes: $pacotes\n\n";
                $pacotes = 1;

                sleep(3);

            }
        }
    }

    public function createServerTCP() {

        if (!($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao criar o socket: [$errorcode] $errormsg \n");
        }
        
        echo "Socket serverTCP criado! Meu server: $this->address - $this->port \n";

        if (!socket_bind($sock, $this->address, $this->port)) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            
            die("Erro ao fazer o bind do ip e porta: [$errorcode] $errormsg \n");
        }

        socket_listen($sock, 10);
        
        echo "Socket listening... \n";

        $client = socket_accept($sock);

        do {

            try {

                clearstatcache();

                if (socket_recv ( $client , $buf , 2045 , 0 ) === FALSE)
                {
                    throw new Exception("Nao recebido!");  
                }

                $buf = explode(";", $buf);
                $ACK = $buf[0] . "\n";

                if( ! socket_send ( $client , $ACK, strlen($ACK) , 0))
                {
                    throw new Exception("Nao enviado!");    
                }

            } catch (Exception $e) {
                
                echo $e->getMessage();
                break;

            }
        
        } while (true);
        
        socket_close($client);
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

        // socket_set_nonblock( $sock );

        $sequencia = 0;
        $pacotes = 1;
        while(1) {

            try {

                $last_send = array();

                for ($i = 0; $i < $pacotes; $i++) {

                    $message = "ACK:".($sequencia).";\n" . Helper::generateRandomString(10) . "";
                    if( ! socket_send ( $sock , $message , strlen($message) , 0))
                    {
                        throw new Exception("Nao enviado!");    
                    }

                    $last_send[] = $sequencia;
                    $sequencia++;
                }

                echo "Enviados...\n";
                sleep(2);

                if (socket_recv ( $sock , $buf , 9000 , 0 ) === FALSE){

                    throw new Exception("Nao recebido!");

                } else {

                    $buf = explode("\n", $buf);
                    array_pop($buf);

                    if (sizeof($buf) != sizeof($last_send)) {

                        $buf = array_map(function($buf){
                            $value = explode(":", $buf);
                            return $value[1];
                        }, $buf);
                        
                        foreach ($last_send as $key => $value) {
                            if (!in_array($value, $buf)) {
                                $sequencia = $value;
                                $pacotes = 1;
                                break;
                            }
                        }
                    } else {
                        $pacotes *= 2;
                    }                    
                }
            
            } catch (Exception $e) {
                
                echo "ERRO\n $pacotes\n";
                $pacotes = 1;
                $sequencia = $sequencia - (sizeof($pacotes)); 

            }
        }

        socket_close($sock);

    }

}