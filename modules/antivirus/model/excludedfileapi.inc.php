<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model;


use Antivirus\Model\Orm\Event;
use Antivirus\Model\Orm\ExcludedFile;
use RS\Module\AbstractModel\EntityList;
use RS\Orm\Request;

class ExcludedFileApi extends EntityList
{
    static private $instance;

    static public function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    function __construct()
    {
        parent::__construct(new ExcludedFile);
    }

    public function isFileExcluded($path,  $component)
    {
        $where = array(
            'file' => $path,
            'component' => $component,
        );
        $exists = (boolean) $this->getCleanQueryObject()->where($where)->count();
        return $exists;
    }

    public function add($path, $component)
    {
        if($this->isFileExcluded($path, $component)) return;

        $excludedFile               = new ExcludedFile();
        $excludedFile['dateof']     = date("Y-m-d H:i:s");
        $excludedFile['file']       = $path;
        $excludedFile['component']  = $component;
        $excludedFile->insert();
    }

    public function remove($path, $component)
    {
        $where = array(
            'file' => $path,
            'component' => $component,
        );
        Request::make()->from(new ExcludedFile())->where($where)->delete()->exec();
    }

    /**
     * @return array
     */
    public function getAllFiles()
    {
        $this->clearFilter();
        return $this->getAssocList('file', 'file');
    }
}