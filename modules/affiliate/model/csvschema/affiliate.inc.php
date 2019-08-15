<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Model\CsvSchema;
use \RS\Csv\Preset,
    \Affiliate\Model\Orm\Affiliate as AffiliateOrm;
    
/**
* Схема импорта-экспорта филиалов
*/
class Affiliate extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(new Preset\Base(array(
            'ormObject' => new AffiliateOrm(),
            'excludeFields' => array('site_id', 'id', 'parent_id'),
            'multisite' => true,
            'searchFields' => array('title','parent_id'),
            'selectRequest' => \RS\Orm\Request::make()
                ->from(new AffiliateOrm())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                ))
                ->orderby('parent_id')
        )), array(
            new Preset\TreeParent(array(
                'ormObject' => new AffiliateOrm(),
                'titles' => array(
                    'title' => t('Родитель')
                ),
                'idField' => 'id',
                'parentField' => 'parent_id',
                'treeField' => 'title',
                'rootValue' => 0,
                'multisite' => true,                
                'linkForeignField' => 'parent_id',
                'linkPresetId' => 0
            ))
        ));    
    }
}
