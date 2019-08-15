<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Controller\Block;

use Partnership\Model\Api as PartnershipApi;
use RS\Config\Loader as ConfigLoader;
use RS\Controller\Block;

/**
 * Блок-контроллер Краткие контакты
 */
class ShortContacts extends Block
{
    protected static $controller_title = 'Краткие контакты партнера';
    protected static $controller_description = 'Отображает краткие контакты для соответствующего партнера';

    function actionIndex()
    {
        $partner = PartnershipApi::getCurrentPartner();
        if (!empty($partner['short_contacts'])) {
            return $partner['short_contacts'];
        }
        $config = ConfigLoader::byModule($this);
        return $config['main_short_contacts'];
    }
}
