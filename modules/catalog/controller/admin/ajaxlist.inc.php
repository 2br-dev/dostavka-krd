<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Admin;

/**
 * Контроллер отдает список пользователей для компонента JQuery AutoComplete
 * @ingroup Users
 */
class AjaxList extends \RS\Controller\Admin\Front
{
    public
        $api;


    function init()
    {
        $this->api = new \Catalog\Model\Api();
    }


    function actionAjaxProduct()
    {
        $term = $this->url->request('term', TYPE_STRING);
        $api = $this->api;
        $list = $api->getLike($term, array('title', 'barcode', 'sku'));
        $json = array();
        $i=0;
        foreach ($list as $product)
        {
            if ($i >= 5) break;
            $i= $i+1;
            $json[] = array(
                'label' => $product['title'],
                'id' => $product['id'],
                'desc' => '',
            );
        }
        return json_encode($json);
    }
}

