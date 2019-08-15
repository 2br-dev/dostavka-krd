<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Controller\Admin;

use RS\Application\Application;

class Dashboard extends \RS\Controller\Admin\Front
{
    /**
     * @var \RS\Controller\Admin\Helper\CrudCollection $helper
     */
    protected $helper;
    /**
     * @var \Statistic\Config\File $config
     */
    protected $config;

    /**
     * Инициализация контроллера
     *
     * @throws \RS\Exception
     */
    function init()
    {
        $this->config = \RS\Config\Loader::byModule($this);

        $this->helper = new \RS\Controller\Admin\Helper\CrudCollection($this, null, $this->url);
        $this->helper->setTopTitle(t('Статистика'));        
        $this->helper->setTopHelp(t('В данном разделе отображаются основные показатели вашего интернет-магазина. Все отчеты содержат информацию для текущего сайта. Для корректного отображения в отчетах информации о доходности, необходимо указать Закупочную цену, а затем пересчитать доходность заказов в <a href="%0"><u>настройках модуля Магазин</u></a>.', 
        array( $this->router->getAdminUrl('edit', array('mod' => 'shop'), 'modcontrol-control') )));

        $this->helper->viewAsAny();
    }

    /**
     * Показ статистики
     *
     * @return \RS\Controller\Result\Standard
     * @throws \Exception
     * @throws \SmartyException
     */
    function actionIndex()
    {
        //Текущая выбранная секция
        $section = $this->url->get('section', TYPE_STRING, 'keyindicatorsbywaves');
        $this->view->assign('section', $section);
        $this->helper['form'] = $this->view->fetch('%statistic%/dashboard.tpl');
        $this->helper->active();
        return $this->result->setTemplate($this->helper['template']);
    }

    /**
     * Редирект на нужный товар
     * @return void
     * @throws \RS\Controller\ExceptionPageNotFound
     */
    function actionRedirectToProduct()
    {
        $id = $this->url->request('id', TYPE_INTEGER);
        $product = new \Catalog\Model\Orm\Product($id);
        if ($product['id']) {
            Application::getInstance()->redirect($product->getUrl());
        }
        $this->e404(t('Товар не найден'));
    }
}
