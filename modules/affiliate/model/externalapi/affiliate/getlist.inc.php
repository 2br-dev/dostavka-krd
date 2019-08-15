<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Model\ExternalApi\Affiliate;

/**
* Возвращает список филлиалов в городах
*/
class GetList extends \ExternalApi\Model\AbstractMethods\AbstractGetTreeList
{
    const
        RIGHT_LOAD = 1;
    
    protected
        $token_require = false;
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return array(
            self::RIGHT_LOAD => t('Загрузка списка Филлиалов в городах.')
        );
    }
    
    /**
    * Возвращает возможные значения для сортировки
    * 
    * @return array
    */
    public function getAllowableOrderValues()
    {
        return array('id', 'id desc', 'sortn', 'sortn DESC', 'title', 'title desc', 'alias', 'alias DESC');
    }
    
    /**
    * Возвращает возможный ключи для фильтров
    * 
    * @return [
    *   'поле' => [
    *       'title' => 'Описание поля. Если не указано, будет загружено описание из ORM Объекта'
    *       'type' => 'тип значения',
    *       'func' => 'постфикс для функции makeFilter в текущем классе, которая будет готовить фильтр, например eq',
    *       'values' => [возможное значение1, возможное значение2]
    *   ]
    * ]
    */
    public function getAllowableFilterKeys()
    {
        return array(
            'id' => array(
                'title' => t('ID филиала. Одно значение или массив значений'),
                'type' => 'integer[]',
                'func' => self::FILTER_TYPE_EQ
            ),
            'title' => array(
                'title' => t('Название филиала'),
                'type' => 'string',
                'func' => self::FILTER_TYPE_LIKE
            ),
            'alias' => array(
                'title' => t('Артикул'),
                'type' => 'string',
                'func' => self::FILTER_TYPE_EQ
            )
        );
    } 
    
    /**
    * Возвращает объект, который позволит производить выборку товаров
    * 
    * @return \Catalog\Model\Api
    */
    public function getDaoObject()
    {
        $dao = new \Affiliate\Model\AffiliateApi();
        $dao->setFilter('public', 1);
        return $dao;
    }


    
    /**
    * Возвращает список филлиалов в городах
    * 
    * @param string $token Авторизационный токен
    * @param integer $parent_id Родитель
    * @param array $filter фильтр категорий по параметрам. Возможные ключи: #filters-info
    * @param string $sort Сортировка категорий по параметрам. Возможные значения #sort-info
    * 
    * @example GET /api/methods/affiliate.getlist?token=2bcbf947f5fdcd0f77dc1e73e73034f5735de4868&parent_id=1<br/>
    * GET /api/methods/affiliate.getlist?token=2bcbf947f5fdcd0f77dc1e73e73034f5735de4868&parent_id=1&filter[title]='Краснодар' или часть слова<br/>
    * GET /api/methods/affiliate.getlist
    * Ответ:
    * <pre>
    * {
    *      "response": {
    *          "list": [
    *              {
    *                  "id": "1",
    *                  "title": "Краснодарский край",
    *                  "alias": "krasnodarskiy-kray",
    *                  "parent_id": "0",
    *                  "clickable": "0", //Можно ли выбирать филлиал
    *                  "cost_id": "0",   //id цены установленной для филлиала. 0 - Не выбрано, значит используется по умолчанию
    *                  "short_contacts": "Этот регион называется Краснодарский край",
    *                  "contacts": "<p>В нашем региона ресположено множество филиалов нашей сети</p>",
    *                  "_geo": null,
    *                  "skip_geolocation": "0",
    *                  "is_default": "0",
    *                  "is_highlight": "0",
    *                  "public": "1",
    *                  "meta_title": "",
    *                  "meta_keywords": "",
    *                  "meta_description": "",
    *                  "child": [
    *                      {
    *                          "id": "2",
    *                          "title": "Краснодар",
    *                          "alias": "krasnodar",
    *                          "parent_id": "1",
    *                          "clickable": "1",  //Можно ли выбирать филлиал
    *                          "cost_id": "0", //id цены установленной для филлиала. 0 - Не выбрано, значит используется по умолчанию
    *                          "short_contacts": "Основной склад",
    *                          "contacts": "<p>В Краснодаре нохотся штаб квартира нашей компании</p>",
    *                          "_geo": null,
    *                          "skip_geolocation": "0",
    *                          "is_default": "1",
    *                          "is_highlight": "1",
    *                          "public": "1",
    *                          "meta_title": "",
    *                          "meta_keywords": "",
    *                          "meta_description": "",
    *                          "child": []
    *                        }
    *                    ]
    *                },
    *                ....
    *            ]
    *      }
    * }
    * </pre>
    * 
    * @return array Возвращает список объектов и связанные с ним сведения.
    */
    protected function process($token = null, 
                               $parent_id = 0, 
                               $filter = array(), 
                               $sort = 'sortn')
    {
        $response = parent::process($token, $parent_id, $filter, $sort);

        return $response;
    }
}