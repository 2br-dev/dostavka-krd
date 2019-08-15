<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model\Libs\Manul;

class XmlValidator
{
    private function libxmlDisplayError($error)
    {
        $return = array();
        $return[] = "<br/>\n";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return[] = t('Предупреждение %0', array($error->code));
                break;
            case LIBXML_ERR_ERROR:
                $return[] = t('Ошибка %0', array($error->code));
                break;
            case LIBXML_ERR_FATAL:
                $return[] = t('Критическая ошибка %0', array($error->code));
                break;
        }
        $return .= trim($error->message);
        if ($error->file) {
            $return[] = t('в %0', array($error->file));
        }
        $return[] = t('в строке %0', array($error->line));
        return join(' ', $return);
    }

    private function libxmlDisplayErrors()
    {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            print $this->libxmlDisplayError($error);
        }
        libxml_clear_errors();
    }

    public function validate($xmlStr, $schemaPath)
    {
        libxml_use_internal_errors(true);
        $xml = new \DOMDocument();

        $xml->loadXML($xmlStr);

        if (!$xml->schemaValidate($schemaPath)) {
            print t('В функции DOMDocument::schemaValidate() возникла ошибка!');
            $this->libxmlDisplayErrors();
            return false;
        }
        return true;
    }
}

