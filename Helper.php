<?php

class Helper {

    public static function removeLineBreaks(array $dados = null): array
    {

        if (empty($dados)) return null;

        $new_data = array();
        foreach ($dados as $key => $value) {
            $new_data[] = preg_replace( "/\r|\n/", "",$value);
        }

        return $new_data;

    }

    public static function generateRandomString($length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

}