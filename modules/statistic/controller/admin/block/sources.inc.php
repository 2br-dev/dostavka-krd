<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin\Block;

use Statistic\Model\Providers\SourcesProvider;

/**
* Блок "Источники пользователей"
*/
class Sources extends AbstractPlotWithList
{
    protected 
        $action_var   = 'ltv',
        $title        = 'Источники пользователей';

    function __construct($param = array())
    {
        parent::__construct($param);
        $this->data_provider = new SourcesProvider();
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
