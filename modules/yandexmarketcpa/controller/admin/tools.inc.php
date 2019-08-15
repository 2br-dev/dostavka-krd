<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Controller\Admin;

use YandexMarketCpa\Model\Log;
use YandexMarketCpa\Model\Oauth;
use YandexMarketCpa\Model\YandexUtils;
use YandexMarketCpa\Model\YRequestUtils;

/**
 * Контроллер обрабатывает системные инструменты модуля
 */
class Tools extends \RS\Controller\Admin\Front
{
    protected
        $log_file;

    function init()
    {
        $this->log_file = Log::getLogFilename();
        $this->wrapOutput(false);
    }

    /**
     * Возвращает диалог тестирования модуля
     */
    function actionRequestTest()
    {
        $this->view->assign(array(
            'yandex_base_url' => YandexUtils::getYandexCpaBaseUrl(),
            'auth_token' => $this->getModuleConfig()->auth_token
        ));

        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this);
        $helper->viewAsAny();
        $helper->setTopTitle(t('Отладка запросов к ReadyScript'));
        $helper['form'] = $this->view->fetch('%yandexmarketcpa%/requesttest.tpl');

        return $this->result->setTemplate( $helper['template'] );
    }

    /**
     * Отправляет команду на запуск самотестирования
     */
    function actionRunSelftest()
    {
        //Вызываем операцию для текущего сайта
        $yutils = new YandexUtils();

        $result =  $yutils->requestYandexSelftest();
        $result = @json_decode($result, true);

        $this->result->setSuccess($result && isset($result['result']));

        if ($this->result->isSuccess()) {
            $this->result->addMessage(t('Запрос успешно отправлен. В течении 15 минут в вашем магазине будут созданы тестовые заказы'));
        } else {
            $this->result->addEMessage(t('Произошла ошибка во время отправки запроса в Яндекс. Причина: %reason', array(
                'reason' => $result['error']['message']
            )));
        }

        return $this->result;
    }

    /**
     * Отображает лог файл
     */
    function actionShowLog()
    {
        if (file_exists($this->log_file)) {
            echo '<pre>';
            readfile($this->log_file);
            echo '</pre>';
        } else {
            return t('Лог файл не найден');
        }
    }

    /**
     * Удаляет лог файл
     */
    function actionDeleteLog()
    {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
            return $this->result->setSuccess(true)->addMessage(t('Лог-файл успешно удален'));
        } else {
            return $this->result->setSuccess(true)->addEMessage(t('Лог-файл отсутствует'));
        }
    }

    /**
     * Возвращает список кампаний в Яндексе
     */
    function actionGetCampaigns()
    {
        $oauth_token = $this->url->request('oauth_token', TYPE_STRING);
        $oauth_client_id = $this->getModuleConfig()->getYandexAppId();

        $request_utils = new YRequestUtils($oauth_token, $oauth_client_id);
        $campaigns = $request_utils->getCampaignsId();
        if ($campaigns) {
            return $this->result->addSection('campaigns', $campaigns);
        } else {
            return $this->result->addEMessage($request_utils->getErrorsStr());
        }
    }

    /**
     * Возвращает авторизационный токен по
     */
    function actionGetOauthTokenByCode()
    {
        $oauth_code = $this->url->request('oauth_code', TYPE_STRING);

        $oauth = new Oauth();
        if ($data = $oauth->getOAuthTokenByCode($oauth_code)) {
            $data['expire'] = date('d.m.Y H:i:s', time() + $data['expires_in']);
            return $this->result->setSuccess(true)
                                ->addSection('data', $data)
                                ->addMessage(t('Токен успешно получен'));
        }

        return $this->result->setSuccess(false)->addEMessage($oauth->getErrorsStr());
    }
}