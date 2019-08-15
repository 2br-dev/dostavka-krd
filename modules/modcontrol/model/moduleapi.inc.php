<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace ModControl\Model;

use RS\Html\Filter;
use RS\Module\Manager as ModuleManager;

class ModuleApi
{
    const SORT_BY_MODULE_NAME = 'name';
    const SORT_BY_MODULE_ID = 'id';

    protected $filter;

    function tableData($sort = null)
    {
        if ($sort === null) {
            $sort = self::SORT_BY_MODULE_ID;
        }

        $module_manager = new ModuleManager();
        $list = $module_manager->getAllConfig();

        $table_rows = array();
        $i = 0;
        foreach ($list as $alias => $module)
        {
            $i++;

            $disable = $module['is_system'] ? array('disabled' => 'disabled') : null;
            $highlight = (time() - $module['lastupdate']) < 60*60*24 ? array('class' => 'highlight_new') : null;
            $module['class'] = $alias;

            if ($this->filter) {
                foreach($this->filter as $key => $val) {
                    if ($val != '' && mb_stripos($module[$key], $val) === false) {
                        continue 2;
                    }
                }
            }

            $table_rows[] = array(
                'num' => $i,
                'name' => $module['name'],
                'description' => $module['description'],
                'checkbox_attribute' => $disable,
                'row_attributes' => $highlight
            ) + $module->getValues();
        }

        usort($table_rows, function($a, $b) use ($sort) {
            switch($sort) {
                case ModuleApi::SORT_BY_MODULE_NAME: $field = 'name'; break;
                case ModuleApi::SORT_BY_MODULE_ID: $field = 'class'; break;
                default: return 0;
            }

            return strcmp($a[$field], $b[$field]);
        });

        return $table_rows;
    }

    function addTableControl()
    {
    }

    function addFilterControl(Filter\Control $filter_control)
    {
        $key_val = $filter_control->getKeyVal();
        $this->filter = $key_val;
        return $this;
    }
}
