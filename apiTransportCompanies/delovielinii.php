<?php

namespace SiteCore\TransportCompany\DelovyeLinii;

class DelovyeLiniiKabinet {
    private $session, $appKey;

    function __construct(){
        $this->appKey = '5555555555555555555555';
    }

    /**
     * Запрос данных и получение результата
     */
    public function request($op, $params = array()){
        $url = 'https://api.dellin.ru/v2/'.$op.'.json';
        $body = $params;
        $body["appKey"] = $this->appKey;
        if (isset($this->session)){
            $body["sessionID"] = $this->session;
        }
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json",
                'content' => json_encode($body)
            )
        );
        $result = file_get_contents($url, false, stream_context_create($opts));

        $this->result = (array)json_decode($result);

    }

    /**
     * Авторизация в сессию
     */
    function auth($login, $password){
        $url = 'https://api.dellin.ru/v1/customers/login.json';
        $body = array(
            'login' => $login,
            'password' => $password,
            'appKey' => $this->appKey
        );
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json",
                'content' => json_encode($body)
            )
        );
        $result = file_get_contents($url, false, stream_context_create($opts));
        $res = (array)json_decode($result);

        $this->session = $res['sessionID'];
    }
}
