<?php
namespace OnePageOrder\Config;
use \RS\Orm\Type;

class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            'default_template' => new Type\Varchar(array(
                'description' => t('Шаблон для страницы оформления по умолчанию'),
                'hint' => t('Если у вас нестандартная тема, то будет использован шаблон для указанной здесь темы'),
                'list' => array(array('\OnePageORder\Model\Api', 'staticGetDefaultTemplates')),
            ))
        ));
    }
    
}