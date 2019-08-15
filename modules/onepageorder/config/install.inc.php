<?php
namespace OnePageOrder\Config;

/**
* Класс отвечает за установку и обновление модуля
*/
class Install extends \RS\Module\AbstractInstall
{
    /**
    * Функция обновления модуля, вызывается только при обновлении
    */
    function update()
    {
        $result = parent::update();
        if ($result) {
            if (\RS\Module\Manager::staticModuleExists('partnership') && \RS\Module\Manager::staticModuleEnabled('partnership')){
                $partner = new \Partnership\Model\Orm\Partner();
                $partner->dbUpdate();
            }
        }
        return $result;
    }     
}
