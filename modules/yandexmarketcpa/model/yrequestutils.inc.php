<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model;

class YRequestUtils extends \RS\Module\AbstractModel\BaseModel
{
    public $yandex_api_url = 'https://api.partner.market.yandex.ru/v2/';
    private $oauth_token;
    private $oauth_client_id;

    /**
     * Конструктор
     *
     * @param string $oauth_token oAuth Token
     * @param string $oauth_client_id oAuth Client ID
     */
    function __construct($oauth_token, $oauth_client_id)
    {
        $this->oauth_token = $oauth_token;
        $this->oauth_client_id = $oauth_client_id;
    }

    /**
     * Возвращает Oauth token
     * @return string
     */
    public function getOauthToken()
    {
        return $this->oauth_token;
    }

    /**
     * Возвращает ID приложения
     *
     * @return string
     */
    public function getOauthClientId()
    {
        return $this->oauth_client_id;
    }

    /**
     * Возвращает список ID кампаний на яндексе
     *
     * @return array | false
     */
    public function getCampaignsId()
    {
        $json = $this->requester('campaigns.json');
        if ($json) {
            $result = array();
            foreach($json['campaigns'] as $campaign) {
                $result[] = $campaign['id'];
            }
            return $result;
        }
        return false;
    }

    public function getCampaignSettings($campaign_id)
    {
        return $this->requester("campaigns/{$campaign_id}/settings.json");
    }

    /**
     * Выполняет запрос на сервер Яндекса
     *
     * @return array | false
     */
    private function requester($api_method, $params = array(), $method = "GET")
    {
        $context = stream_context_create(array(
            'http'=>array(
                'method' => $method,
                'timeout' => 10,
                'ignore_errors' => true
            )
        ));

        $url = $this->yandex_api_url.$api_method.'?oauth_token='.$this->getOauthToken().'&oauth_client_id='.$this->getOauthClientId();

        Log::write('---------');
        Log::write('[out] Start method request: '.$api_method);
        Log::write('[out] URL: '.$url);

        $response = @file_get_contents($url, null, $context);

        Log::write('[out] Response: '.$response);

        $json = @json_decode($response, true);
        if ($response === false) {
            return $this->addError(t('Не удалось выполнить запрос.'));
        }

        if ($json === false) {
            return $this->addError(t('Не удалось распознать данные json'));
        }

        if (isset($json['error'])) {
            return $this->addError($json['error']['code'].':'.$json['error']['message']);
        }

        return $json;
    }
}