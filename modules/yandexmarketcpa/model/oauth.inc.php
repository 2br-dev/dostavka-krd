<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;

use RS\Config\Loader;

/**
 * Класс содержит методы для работы с OAuth авторизацией от Яндекса
 */
class Oauth extends \RS\Module\AbstractModel\BaseModel
{
    private
        $config;

    function __construct()
    {
        $this->config = Loader::byModule($this);
    }

    /**
     * Проверяет соль сайта при выдаче токена OAuth
     *
     * @param string $state - соль для проверки
     * @return bool
     */
    function checkOAuthState($state)
    {
        return ($state==self::getOAuthState());
    }

    /**
     * Возвращает секретный идентификатор текущего магазина
     *
     * @return string
     */
    public static function getOAuthState()
    {
        return md5(\Setup::$SECRET_KEY." yandexmarketcpa ".\Setup::$SECRET_SALT);
    }

    /**
     * Отправляет запрос на получение токена OAuth
     *
     * @param string $code - код подтверждения выданный Яндекс
     * @return string | false
     */
    function getOAuthTokenByCode($code)
    {
        $postdata = http_build_query(array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config->getYandexAppId(),
            'client_secret' => $this->config->getYandexAppSecret(),
        ));

        //Получаем токен
        $opts = array(
            'http'=>array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "Content-Length: ".strlen($postdata)."\r\n".
                    "User-Agent:MyAgent/1.0\r\n",
                'method' => "POST",
                'content' => $postdata,
                'ignore_errors' => true
            )
        );

        $context = stream_context_create($opts);

        //Делаем запрос
        $response = file_get_contents('https://oauth.yandex.ru/token', false, $context);
        if (!$response){
            $this->addError(t('Не удалось сделать запрос'));
        }
        $result   = @json_decode($response, true);
        if (isset($result['error'])){
            return $this->addError($result['error_description']);
        }

        return $result ? $result : false;
    }
}