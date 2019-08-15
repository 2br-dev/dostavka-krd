<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Export\Model\ExportType\Facebook;

use Export\Model\ExportType\Facebook\OfferType\Standard;
use Export\Model\Orm\ExportProfile;
use RS\Orm\Type;

class Facebook extends \Export\Model\ExportType\AbstractType
{
    function _init()
    {
        return parent::_init()
            ->append(array(
                t('Основные'),
                    'full_description' => new Type\Integer(array(
                        'description' => t('Всегда выгружать полное описание?'),
                        'hint' => 'Полное описание не может содержать более 5000 знаков<br>По умолчанию выгружается короткое описание',
                        'checkboxView' => array(1,0),
                    )),
            ));
    }

    public function getTitle()
    {
        return t('Facebook');
    }

    public function getShortName()
    {
        return 'facebook';
    }

    public function getDescription()
    {
        return t('Экспорт товаров в Facebook');
    }

    public function export(ExportProfile $profile)
    {
        $writer = new \Export\Model\MyXMLWriter();
        $writer->openURI($profile->getCacheFilePath());
        $writer->startDocument('1.0', self::CHARSET);
        $writer->setIndent(true);
        $writer->setIndentString("    ");
        $writer->startElement('rss');
        $writer->writeAttribute('version', '2.0');
        $writer->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $writer->startElement($this->getRootTag());
        $site = \RS\Site\Manager::getSite();
        //Запись основных сведений
        $writer->writeElement('title', t("Facebook"));
        $writer->writeElement('link', \RS\Http\Request::commonInstance()->getDomain(true));
        $writer->writeElement('description', t("Экспорт товаров в Facebook из %0", array($site['full_title'])));

        $this->exportOffers($profile, $writer);
        $this->fireAfterAllOffersEvent('afteroffersexport',$profile,$writer);
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
        return file_get_contents($profile->getCacheFilePath());
    }

    public function getOfferTypesClasses()
    {
        return array(
            new Standard(),
        );
    }

    protected function getRootTag()
    {
        return "channel";
    }
}