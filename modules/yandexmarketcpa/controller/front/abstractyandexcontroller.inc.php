<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Controller\Front;

use RS\Controller\Front;
use YandexMarketCpa\Model\Log;

/**
 * Абстрактный контроллер, ведущий логирование запросов и ответов от Яндекса
 */
abstract class AbstractYandexController extends Front
{
    function init()
    {
        $this->wrapOutput(false);
        $this->app->headers->addHeader('Content-Type', 'application/json');
    }

    function exec($returnAsIs = false)
    {
        Log::write('---------');
        Log::write('[in] Start action '.$this->getAction());

        $json = file_get_contents('php://input');
        Log::write('[in] Request url: '.$this->url->getSelfUrl());
        Log::write('[in] Request data: '.$json);

        $auth_token = $this->url->get('auth-token', TYPE_STRING);
        $config_auth_token = $this->getModuleConfig()->auth_token;
        if ($auth_token != $config_auth_token || $config_auth_token == '') {
            Log::write('[in] Check auth-token failed... ');
            $this->app->headers->setStatusCode(403);
            if ($config_auth_token == '') {
                return t('Не указан авторизационный токен в настройках модуля');
            }
            return;
        }

        ob_start(); //Чтобы собрать все notice'ы, если они есть сохраняем буфер
        try {
            $result = parent::exec($returnAsIs);
        } catch(\Exception $e) {
            Log::write('[in] Exception:'.$e->getMessage());
            Log::write('[in] Exception code:'.$e->getCode());
            Log::write('[in] Exception file:'.$e->getFile());
            Log::write('[in] Exception line:'.$e->getLine());
            Log::write('[in] Exception StackTrace:'.$e->getTraceAsString());

            throw $e;
        }

        $for_log_result = ob_get_contents().$result;
        Log::write('[in] Your response: '.$for_log_result);

        ob_end_flush();
        return $result;
    }
}