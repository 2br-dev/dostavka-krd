<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model;

use Catalog\Controller\Block\SearchLine as CatalogSearchLine;
use RS\Config\Loader as ConfigLoader;
use RS\Event\Manager as EventManager;
use RS\Helper\Transliteration;
use Search\Model\SearchApi;

/**
* API для запросов поисковой строки
*/
class SearchLineApi
{
    /**
     * @var \Catalog\Model\Api $api
     */
    public $api;
    /**
     * @var \Catalog\Model\Dirapi $dirapi
     */
    public $dirapi;
    /**
     * @var \Catalog\Model\BrandApi $dirapi
     */
    public $brand_api;

    public function __construct()
    {
        $this->api       = new Api();
        $this->dirapi    = new Dirapi();
        $this->brand_api = new BrandApi();
    }

    /**
     * Возвращает результаты поиска по категориям в зависимости от запроса
     *
     * @param string $query - строка для поиска
     * @param integer $limit - лимит результатов поиска
     *
     * @return \Catalog\Model\Orm\Dir[]
     */
    function getSearchQueryCategoryResults($query, $limit = 1)
    {
        $list = $this->dirapi
                        ->setFilter('name', $query, '%like%')
                        ->setFilter('public', 1)
                        ->getList(1, $limit);

        //Если не нашли результаты, то посмотрим странслитом
        if (empty($list)){
            $query = Transliteration::puntoSwitchWord($query);
            $list = $this->dirapi->clearFilter()->setFilter('name', "%$query%",'like')
                ->getList(1, $limit);
        }
        return $list;
    }

    /**
     * Возвращает результаты поиска по брендам в зависимости от запроса
     *
     * @param string $query - строка для поиска
     * @param integer $limit - лимит результатов поиска
     *
     * @return \Catalog\Model\Orm\Brand[]
     */
    public function getSearchQueryBrandsResults($query, $limit = 1)
    {
        $list = $this->brand_api
                            ->setFilter('title', $query, '%like%')
                            ->setFilter('public', 1)
                            ->getList(1, $limit);

        //Если не нашли результаты, то посмотрим странслитом
        if (empty($list)){
            $query = Transliteration::puntoSwitchWord($query);
            $list = $this->brand_api->clearFilter()->setFilter('title', "%$query%",'like')
                               ->getList(1, $limit);
        }
        return $list;
    }

    /**
     * Подготавливает поиск по товаром в зависимости от запроса
     *
     * @param string $query - строка для поиска
     * @param \Catalog\Controller\Block\SearchLine $controller - контроллер строки поиска
     * @param string $order_field - колонка для сортировки
     * @param string $order_direction - лимит результатов поиска
     * @return \Catalog\Model\Orm\Product[]
     */
    public function prepareSearchQueryProduct($query, $controller, $order_field, $order_direction)
    {
        $q = $this->api->queryObj();
        $q->select = 'A.*';

        $search = SearchApi::currentEngine();
        $search->setFilter('B.result_class', 'Catalog\Model\Orm\Product');
        $search->setQuery($query);
        $search->joinQuery($q);

        $this->api->setFilter('public', 1);

        if ($order_field != CatalogSearchLine::SORT_RELEVANT) {
            $this->api->setSortOrder($order_field, $order_direction);
        }

        if (ConfigLoader::byModule($this)->hide_unobtainable_goods == 'Y') {
            $this->api->setFilter('num', '0', '>');
        }

        EventManager::fire('init.api.catalog-front-listproducts', $controller);
    }

    /**
     * Возвращает результаты поиска по товарам
     *
     * @param integer $limit - лимит результатов поиска
     * @return \Catalog\Model\Orm\Product[]
     */
    public function getSearchQueryProductResults($limit = 1)
    {
        $list = $this->api->getList(1, $limit);
        $list = $this->api->addProductsPhotos($list);
        $list = $this->api->addProductsCost($list);

        return $list;
    }

    /**
     * Возвращает количество результатов поиска по товарам
     *
     * @return int
     */
    public function getSearchQueryProductCount()
    {
        return $this->api->getListCount();
    }
}
