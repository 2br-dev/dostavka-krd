<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin;

/**
* Содержит действия по обслуживанию
*/
class Tools extends \RS\Controller\Admin\Front
{
    /**
     * Удаление несвязанных характеристик
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxUpdateSourceTypes()
    {
        $api = new \Statistic\Model\SourceTypesApi();
        $api->updateSourceTypesInAllSources();
        
        return $this->result->setSuccess(true)->addMessage(t('Типы источников обновлены'));
    }


}