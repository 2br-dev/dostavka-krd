<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Controller\Admin;

use Antivirus\Model\SignVerifier;
use Antivirus\Model\SignVerifyManager;
use Antivirus\Model\VirusDetectManager;
use RS\Controller\Admin\Front;
use RS\Controller\Admin\Helper\CrudCollection;


class Ctrl extends Front
{

    function actionRunFull()
    {
        $already1 = SignVerifyManager::getInstance()->isIntensiveModeEnabled();
        $already2 = VirusDetectManager::getInstance()->isIntensiveModeEnabled();
        if($already1 && $already2)
        {
            $this->result->addEMessage(t('Полная проверка уже выполняется'));
        }
        else
        {
            SignVerifyManager::getInstance()->enableIntensiveMode(true);
            VirusDetectManager::getInstance()->enableIntensiveMode(true);
            $this->result->addMessage(t('Полная проверка запущена'));
        }

        return $this->result->setSuccess(true);
    }

    function actionAjaxShowChangedFiles()
    {
        $helper = new CrudCollection($this);
        $helper->setTopTitle(t('Измененные файлы системы'));
        $helper->viewAsForm();

        $files = array();
        try
        {
            $files = $this->getChangedFiles();
        }
        catch(\Exception $e)
        {
            $files[] = t('Ошибка: ') . $e->getMessage();
        }

        $this->view->assign(array(
            'files' => $files
        ));

        $helper['form'] = $this->view->fetch('show_changed_files.tpl');
        $this->result->setTemplate( $helper['template'] );

        return $this->result;
    }

    /**
     * Получить массив относительных путей измененных файлов (пути относительно корня проекта)
     *
     * @return string[]
     */
    private function getChangedFiles()
    {
        $verifier = new SignVerifier();
        $iterator = new \DirectoryIterator(\Setup::$PATH . \Setup::$MODULE_FOLDER);
        $result = array();

        foreach($iterator as $object)
        {
            if($object->isDir() && !$object->isDot())
            {
                $mod_rel_fold = \Setup::$MODULE_FOLDER  . '/' . $object->getFilename();
                $data = $verifier->verifyModule(\Setup::$PATH . $mod_rel_fold);
                foreach($data['corrupted_files'] as &$one)
                    $one = ltrim($mod_rel_fold, '/') . '/' . $one;
                $result = array_merge($result, $data['corrupted_files']);
            }
        }

        // Проверка модуля core
        $verifier->signatures_file_path = 'core/rs/config/signatures.xml';
        $data = $verifier->verifyModule(\Setup::$PATH);
        $result = array_merge($result, $data['corrupted_files']);

        return $result;
    }

}
