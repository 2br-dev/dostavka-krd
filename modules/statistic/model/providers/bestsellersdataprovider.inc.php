<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model\Providers;

use RS\Html\Paginator\Element;
use RS\Html\Table\Element as TableElement;
use RS\Html\Table\Type as TableType;
use Catalog\Model\Orm\Product;
use RS\Html\Filter;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\OrderItem;
use Statistic\Model\UnitColumn;

class BestsellersDataProvider extends AbstractDataProvider
{
    /**
     * Возвращает запрос для отображения данных
     *
     * @return \RS\Orm\Request
     * @throws \RS\Exception
     */
    private function makeQueryObj()
    {
        $req = \RS\Orm\Request::make()->from(new OrderItem(), 'OI')
                    ->select('OI.barcode, OI.entity_id, concat(OI.title, " ", OI.model) as title, P.brand_id, sum(OI.amount) as cnt')
                    ->leftjoin(new Order(), 'OI.order_id = O.id', 'O')
                    ->leftjoin(new Product(), 'OI.entity_id = P.id', 'P')
                    ->where("O.dateof >= '#date_from' AND O.dateof <= '#date_to'", array(
                        'date_from' => $this->date_from,
                        'date_to' => $this->date_to.' 23:59:59'
                    ))
                    ->where(array(
                        'O.site_id' => $this->site_id,
                        'OI.type' => 'product'
                    ))
                    ->groupby('entity_id, offer')
                    ->orderby('cnt desc');

        $this->setAdditionalFilters($req);
        $this->filterOrderByStatus($req);

        return $req;
    }

    /**
     * Возвращает массив данных для оторажения долей
     *
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function getPlotData()
    {
        $items_in_plot = 6;

        $req = $this->makeQueryObj();
        $req->limit($items_in_plot);
        $rows = $req->exec()->fetchAll();

        $items = array_map(function($row){
            return array(
                'data' => (int)$row['cnt'],
                'label' => $row['title']
                //'color' => '#'.Utils::randomColor()
            );
        }, $rows);

        $total_count = $this->getListTotalCount();

        // Считаем "Остальные"

        if ($total_count && $total_count - $items_in_plot > 0)
        {
            $items[] = array(
                'data' => $this->getListTotalCount() - $items_in_plot,
                'label' => t('Остальные'),
            );
        }

        return $items;
    }
    
    /**
    * Возвращает единицу измерения данных в диаграмме
    * 
    * @return string
    */
    public function getPlotUnit()
    {
        return t('шт.');        
    }

    /**
     * Возвращает данных отфильтрованные по странице
     *
     * @param Element $paginator - объект пагинатора
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function getListData(Element $paginator)
    {

        $req = $this->makeQueryObj();
        $req->limit($paginator->page_size);
        $req->offset(($paginator->page - 1) * $paginator->page_size);
        $data = $req->exec()->fetchAll();
        return $data;
    }

    /**
     * Возвращает общее количество результата запроса
     *
     * @return integer
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function getListTotalCount()
    {
        $req = $this->makeQueryObj();
        $count = $req->exec()->rowCount();
        return $count;
    }

    /**
     * Возвращает структуру для отображения таблицы
     *
     * @return TableElement
     */
    public function getTableStructure()
    {
        $router = \RS\Router\Manager::obj();
        $this->table  = new TableElement(array(
            'Columns' => array(
                new TableType\Text('title', t('Товар'), array(
                    'href' => $router->getAdminPattern('redirectToProduct', array(':id' => '@entity_id'), 'statistic-dashboard'),
                    'linkAttr' => array('target' => 'blank')
                )),
                new TableType\Userfunc('entity_id', t('Фото'), function ($value, \RS\Html\Table\Type\Userfunc $col){
                    $product = new \Catalog\Model\Orm\Product($value);
                    return '<span class="cell-image" data-preview-url="'. $product->getMainImage()->getUrl(200, 200, 'xy').'"><img src="'. $product->getMainImage()->getUrl(30, 30, 'xy').'"></span>';
                }),
                new TableType\Text('barcode', t('Артикул')),
                new TableType\Userfunc('brand_id', t('Бренд'), function ($value, \RS\Html\Table\Type\Userfunc $col) {
                    $brand = new \Catalog\Model\Orm\Brand($value);
                    return $brand['title'];
                }),
                new UnitColumn('cnt', t('Кол-во'), array('unit' => $this->getPlotUnit())),
                new TableType\Actions('entity_id', array(
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('Открыть товар в админ. панели'),
                                'attr' => array(
                                    'target' => '_blank',
                                    '@href' => $router->getAdminPattern('edit', array(':id' => '@entity_id'), 'catalog-ctrl'),
                                )
                            ),
                            array(
                                'title' => t('Открыть товар на сайте'),
                                'attr' => array(
                                    'target' => '_blank',
                                    '@href' => $router->getAdminPattern('redirectToProduct', array(':id' => '@entity_id'), 'statistic-dashboard'),
                                )
                            )
                        ))
                ))
            )
        ));

        return $this->table;
    }

    /**
     * Возвращает структуру фильтров для таблицы
     *
     * @return Filter\Control
     */
    public function getFilterStructure()
    {
        $this->filters = new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('items' => array(
                            new \Statistic\Model\HtmlFilterType\ProductText('barcode', t('Артикул'), array('SearchType' => '%like%', 'FieldPrefix' => 'OI')),
                            new \Statistic\Model\HtmlFilterType\ProductSelect('brand_id', t('Бренд'), array('' => t('-Не выбрано-')) + \Catalog\Model\BrandApi::staticSelectList(false), array('FieldPrefix' => 'P')),
                    )))
                )
            )),
            'Caption' => t('Поиск по товарам'),
            'UpdateContainer' => '#updatableDashboard'
        ));
        return $this->filters;
    }
}