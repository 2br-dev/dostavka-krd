<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Controller\Admin;
use Crm\Model\DealApi;

/**
 * Контроллер, поддерживающий выпадающие списки. Обеспечивает поиск объектов по строке
 */
class AjaxList extends \RS\Controller\Admin\Front
{
    /**
     * Ищет Сделки по входящей строке данных
     *
     * @return string
     */
    function actionAjaxSearchDeal()
    {
        $api = new DealApi();

        $term = $this->url->request('term', TYPE_STRING);
        $list = $api->search($term, array('id', 'deal_num', 'title', 'client_name', 'user'), 8);

        $json = array();
        foreach ($list as $deal) {

            $user = $deal->getClientUser();
            $json[] = array(
                'label' => t('Сделка №%num от %date', array(
                    'num' => $deal['deal_num'],
                    'date' => date('d.m.Y', strtotime($deal['date_of_create']))
                )),
                'id' => $deal['id'],
                'desc' => $deal['title'].'<br>'.t('Клиент').':'.$user->getFio().
                    ($user['id'] ? "({$user['id']})" : '').
                    ($user['company'] ? t(" ; {$user['company']}(ИНН:{$user['company_inn']})") : '')
            );
        }

        return json_encode($json);
    }
}