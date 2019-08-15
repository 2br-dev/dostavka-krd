<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Block;

use Statistic\Model\Providers\MostProfitableDataProvider;

/**
* Блок "Самые доходные товары"
*/
class MostProfitableProducts extends AbstractPlotWithList
{
    protected 
        $action_var = 'mpp',
        $title      = 'Самые доходные товары';

    function __construct($param = array())
    {
        parent::__construct($param);
        $this->data_provider = new MostProfitableDataProvider();
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
            'filters' => $filters
        ));

        return parent::actionIndex();
    }


}
