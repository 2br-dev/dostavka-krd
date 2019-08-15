<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;


use Antivirus\Model\Libs\Manul\MalwareDetector;
use Antivirus\Model\Libs\Manul\MalwareInfo;


class VirusDetector
{
    protected $malware_detector;

    public function __construct()
    {
        $this->malware_detector = new MalwareDetector();
    }

    /**
     * Установить максимальный размер сканируемого файла
     *
     * @param int $size_in_bytes максимальный размер файла в байтах
     */
    public function setMaxFileSize($size_in_bytes)
    {
        $this->malware_detector->MAX_FILESIZE = $size_in_bytes;
    }

    /**
     * Поиск вирусов во всех файлах папки.
     * Возвращает массив проблемных файлов.
     *
     * @param string $path
     * @param int $offset
     * @param int $limit
     * @return array вида [infected_files => массив проблемных файлов, files_checked_count => количество проверенных файлов]
     * @throws \Exception
     */
    public function scanFolder($path, $offset = 0, $limit = 65536)
    {

        $recIterator    = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS);
        $objects        = new \RecursiveIteratorIterator($recIterator);

        /**
         * @var MalwareInfo[] $infected_files
         */
        $infected_files = array();
        $counter        = 0;
        $index          = 0;

        foreach($objects as $filename => $value)
        {
            // Пропуск всех элементов до $offset
            if($index++ < $offset) continue;

            $filename = str_replace('\\', '/', $filename);

            $fragment   = "";
            $pos        = 0;
            $result = $this->malware_detector->detectMalware($filename, $fragment, $pos, time(), 30);

            if($result instanceof MalwareInfo)
            {
                $infected_files[] = $result;
            }

            if(++$counter == $limit)
            {
                break;
            }
        }


        return array(
            'infected_files' => $infected_files,    // Список полных путей файлов с обнаруженными угрозами
            'files_checked_count' => $counter,      // Количество проверенных файлов за этот проход
        );
    }

}