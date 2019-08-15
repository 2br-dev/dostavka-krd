<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;


class SignVerifier
{
    public $signatures_file_path = 'config/signatures.xml';

    private $public_key;

    public function __construct($public_key = null)
    {
        if($public_key === null)
        {
            $public_key = __DIR__.'/public.key';
        }

        if(is_file($public_key))
        {
            $this->public_key = file_get_contents($public_key);
        }
        else
        {
            $this->public_key = $public_key;
        }
    }

    /**
     * Проверка подписи всех файлов модуля.
     * Возвращает массив поврежденных файлов.
     *
     * @param string $module_path
     * @param int $offset
     * @param int $limit
     * @return array массив повржеденных файлов
     * @throws \Exception
     */
    public function verifyModule($module_path, $offset = 0, $limit = 65536)
    {
        $signatures_file = $module_path . '/' . $this->signatures_file_path;

        if(!file_exists($signatures_file))
        {
            //Файл с подписями не найден
            return array(
                'corrupted_files' => array($this->signatures_file_path),
                'broke_at' => -1,
                'files_checked_count' => 1,
            );
        }

        if(!$this->verifyMasterSign($signatures_file))
        {
            // Файл с подписями поврежден
            return array(
                'corrupted_files' => array($this->signatures_file_path),
                'broke_at' => -1,
                'files_checked_count' => 1,
            );
        }

        $simpleXml = new \SimpleXMLElement(file_get_contents($signatures_file));
        $corrupted_files = array();
        $total_files = count($simpleXml->file);
        $counter = 0;
        $has_been_broken = false;

        for($i = $offset; $i < $total_files; $i++)
        {
            $node = $simpleXml->file[$i];
            $ok = $this->verifyFile($module_path . '/' . $node->path, $node->sign);

            if(!$ok)
            {
                $corrupted_files[] = (string) $node->path;
            }

            if(++$counter == $limit)
            {
                $has_been_broken = true;
                break;
            }
        }

        return array(
            'corrupted_files' => $corrupted_files,                     // Список полных путей файлов с несовпадающей подписью
            'broke_at' => $has_been_broken ? $offset + $counter : -1,  // Позиция, с которой должна начаться следующая проверка
            'files_checked_count' => $counter,
        );
    }


    /**
     * Проверка финальной подписи файла с подписями
     *
     * @param $signatures_file
     * @return bool
     */
    public function verifyMasterSign($signatures_file)
    {
        libxml_use_internal_errors(true);
        try
        {
            $simpleXml = new \SimpleXMLElement(file_get_contents($signatures_file));
        }
        catch(\Exception $e)
        {
            return false;
        }
        $glued_signs_and_paths = "";

        foreach($simpleXml->file as $node)
        {
            $glued_signs_and_paths .= (string)$node->path . (string) $node->sign;
        }

        return openssl_verify($glued_signs_and_paths, pack("H*" , $simpleXml->masterSign), $this->public_key) === 1;
    }


    /**
     * Проверка подписи файла
     *
     * @param string $file_path
     * @param string $signature
     *
     * @return bool
     */
    public function verifyFile($file_path, $signature)
    {
        if(!file_exists($file_path))
        {
            return false;
        }

        return sha1_file($file_path) === (string)$signature;
    }
}