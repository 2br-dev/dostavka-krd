<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Controller\Block;

use RS\Config\Loader as ConfigLoader;
use RS\Controller\Block;
use Partnership\Model\Api as PartnershipApi;

/**
* Блок-контроллер Контакты
*/
class Contacts extends Block
{
    protected static $controller_title = 'Контакты партнера';
    protected static $controller_description = 'Отображает раздел контакты для соответствующего партнера';

    function actionIndex()
    {
        $partner = PartnershipApi::getCurrentPartner();
        if (!empty($partner['contacts'])) {
            return $partner['contacts'];
        }
        $config = ConfigLoader::byModule($this);
        return $config['main_contacts'];
    } 
}
