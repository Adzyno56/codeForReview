<?php

namespace SiteCore\TransportCompany\Baikal;

use \Bitrix\Main\Application;
use \Bitrix\Main\Web\Uri;
use \Bitrix\Main\Web\HttpClient;

class BaikalService
{
    private const APP_KEY = '5555555555555555555555';
    private const MAIN_URL = 'https://api.baikalsr.ru/v1/';
    private const API_URL = 'tracking?number=';

    private $url;

    public function __construct()
    {
        $uri = new Uri(self::MAIN_URL . self::API_URL);
        $this->url = $uri->getUri();
    }

    /**
     * Проверка статуса доставки
     * @param $nomenclature
     * @return true|false
     */

    public function getOrderStatus($nomenclature)
    {
        $httpClient = new HttpClient();
        $httpClient->setCharset("utf-8");
        $httpClient->setHeader('Content-type', 'application/json', true);
        $httpClient->setAuthorization(self::APP_KEY, '');
        $url = $this->url;
        $url = $url . $nomenclature;
        $httpClient->query('GET', $url);

        $result = $httpClient->getResult();

        return json_decode($result);

    }
}
