<?php

require_once 'Helper.php';

error_reporting(E_ERROR | E_PARSE);

class Sockets {
    
    private $port;
    private $address;

    function __construct($adress = "192.168.0.19", $port = 29000) 
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

            unset($buf);

            clearstatcache();

            $r = socket_recvfrom($sock, $buf, 2045, 0, $remote_ip, $remote_port);

            $buf = explode(";", $buf);
            $ACK = $buf[0] . "\n";
            
            if( !socket_sendto($sock, $ACK, strlen($ACK) , 0 , $remote_ip , $remote_port) )
            {
                throw new Exception("Nao enviado!"); 
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

        socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0) );

        echo "Socket do cliente UDP $this->address criado! \n";

        $pacotes = 1;
        $sequencia = 0;
        $pacotes_nao_enviados = array();
        $had_loss = false;
        while(1) {

            clearstatcache();

            try{

                if (!empty($pacotes_nao_enviados)) {
                    foreach ($pacotes_nao_enviados as $value) {
                        $message = "ACK:".($value).";\n" . Helper::generateRandomString(100) . "";
                        socket_sendto($sock, $message , strlen($message) , 0 , $server , $port);
                    }

                    for ($i = 0; $i < sizeof($pacotes_nao_enviados); $i++) {
                        if( socket_recv($sock, $buf, 1000, 0) === FALSE ) {
                            $pacotes_nao_enviados = array();
                            throw new Exception("Erro ao reenviar pacote!");
                        }
                    }

                    sleep(1);
                    
                    $pacotes_nao_enviados = array();
                    echo "Pacotes nao enviados anteriormente, agora foram enviados \n";
                }

                $last_send = array();
                for ($i = 0; $i < $pacotes; $i++) {
                    $message = "ACK:".($sequencia).";\n" . Helper::generateRandomString(100) . "";
                    socket_sendto($sock, $message , strlen($message) , 0 , $server , $port);

                    $last_send[] = $sequencia++;
                }

                $recebidos = 0;
                for ($i = 0; $i < $pacotes; $i++) {

                    if ( socket_recv($sock, $buf, 1000, 0) === FALSE ) break;

                    $buf = explode("\n", $buf);
                    array_pop($buf);

                    $buf = array_map(function($buf){
                        $value = explode(":", $buf);
                        return $value[1];
                    }, $buf);

                    $key = array_search($buf[0], $last_send);
                    if ($key === false) {
                        $sequencia = $buf[0];
                        $pacotes = (int) $pacotes / 2;
                        break;
                    } else {
                        $teste = $last_send[$key];
                        unset($last_send[$key]);
                    }

                    $recebidos++;

                }

                if ($recebidos != $pacotes) {
                    foreach ($last_send as $value) {
                        $pacotes_nao_enviados[] = $value; 
                    }
                    $pacotes = (int) $pacotes / 2;
                    $had_loss = true;
                    echo "Ocorreu uma perda ao enviar $pacotes pacotes, sera reenviado os perdidos\n";     
                } else {     
                    echo "Pacotes $pacotes enviados e ACK ate " . ($sequencia - 1) ."\n";               
                    $pacotes = $had_loss ? $pacotes + 1 : $pacotes *= 2;
                }

                sleep(1);

            } catch (Exception $e) {

                $pacotes = (int) $pacotes / 2;

                sleep(1);

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
        
        socket_set_option( $sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 2,"usec" => 0) );

        echo "Socket do cliente TCP $this->address criado! \n";
        
        if ( socket_connect($sock, $this->address, $this->port) === false ) {
            die("Nao foi possivel criar a conexão de controle com o socket! \n");
        }

        // socket_set_nonblock( $sock );

        $sequencia = 0;
        $pacotes = 1;
        $had_loss = false;
        while(1) {

            try {

                $last_send = array();

                for ($i = 0; $i < $pacotes; $i++) {

                    $message = "ACK:".($sequencia).";\n" 
                        . Helper::generateRandomString(100);
                    if( ! socket_send ( $sock , $message , strlen($message) , 0))
                    {
                        throw new Exception("Nao enviado!");    
                    }

                    $last_send[] = $sequencia++;
                }

                sleep(1);

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
                                $had_loss = true;
                                $pacotes = floor($pacotes / 2);
                                echo "Pacotes perdidos, sera reiniciado a partir do ACK $sequencia e enviara $pacotes pacotes!\n";
                                break;
                            }
                        }
                    } else {
                        echo "Recebido $pacotes pacotes e sequencia ate " .($sequencia - 1) . " \n";
                        $pacotes = $had_loss ? $pacotes + 1 : $pacotes * 2;
                    }                    
                }
            
            } catch (Exception $e) {
                
                echo "Erro ao enviar $pacotes pacotes!\n";
                $pacotes = floor($pacotes / 2);
                $sequencia = $sequencia - (sizeof($pacotes)); 

            }
        }

        socket_close($sock);

    }

}