<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Block;
use RS\Helper\Paginator;
use Statistic\Model\Providers\BestsellersDataProvider;

/**
* Блок "Самые продаваемые товары за период"
*/
class Bestsellers extends AbstractPlotWithList
{
    protected 
        $action_var = 'bs',
        $title      = 'Самые продаваемые товары';

    function __construct($param = array())
    {
        parent::__construct($param);
        $this->data_provider = new BestsellersDataProvider();
    }

    /**
     * Отработка показа блока
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionIndex()
    {

        $filters = $this->data_provider->getFilterStructure();
        $filters->fill(); //Заполним фильтры
        $this->view->assign(array(
            'filters' => $filters,
        ));

        return parent::actionIndex();
    }

}
