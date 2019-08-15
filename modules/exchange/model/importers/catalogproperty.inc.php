<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;

use Catalog\Model\Orm\Property\Item as PropertyItem;
use Exchange\Model\Log;
use Exchange\Model\Task;
use RS\Helper\Tools as Tools;
use RS\Helper\Transliteration;
use RS\Orm\Request as OrmRequest;
use RS\Site\Manager as SiteManager;

/**
 * Импорт справочника свойств товаров (не путать с характеристиками).
 * В этом классе происходит импорт возможных названий свойств у товаров (справочник свойств).
 * Импорт значений свойств для каждого конкретного товара происходит в другом месте при импорте товара.
 */

class CatalogProperty extends AbstractImporter
{
    static public $pattern = array(
        '2.04' => '/Классификатор\/Свойства\/Свойство$/i',
        '2.03' => '/Классификатор\/Свойства\/СвойствоНоменклатуры$/i',
    );
    static public $title    = 'Импорт Свойств';

    const SESSION_PROP_ALLOWED_VALUES_KEY = 'SESSION_PROP_ALLOWED_VALUES_KEY';

    public function import(\XMLReader $reader)
    {
        static $cache_existed_aliases = null;
        if ($cache_existed_aliases === null) {
            $cache_existed_aliases = OrmRequest::make()
                ->from(new PropertyItem())
                ->where(array('site_id' => SiteManager::getSiteId()))
                ->where('xml_id > ""')
                ->exec()->fetchSelected('alias', 'xml_id');
        }

        $config = $this->getConfig();

        Log::w(t("Импорт свойства: ").(string) $this->getSimpleXML()->Наименование);

        // Если совойство содержит справочник возможных значений, то сохраняем его в сессию
        if($this->getSimpleXML()->ВариантыЗначений->Справочник){
            // Восстанавливаем массив значений из сессии
            $stored_prop_values = (array) Task\TaskQueue::getSessionVar(self::SESSION_PROP_ALLOWED_VALUES_KEY);

            foreach($this->getSimpleXML()->ВариантыЗначений->Справочник as $one){
                $stored_prop_values[(string)$one->ИдЗначения] = (string)$one->Значение;
            }

            // Сохраняем массив обратно в сессию
            Task\TaskQueue::setSessionVar(self::SESSION_PROP_ALLOWED_VALUES_KEY, $stored_prop_values);
        }

        $property_type = PropertyItem::TYPE_STRING;
        if ($this->getSimpleXML()->ТипЗначений) {
            switch ((string) $this->getSimpleXML()->ТипЗначений) {
                case 'Справочник':
                    $property_type = PropertyItem::TYPE_LIST;
                    break;
                case 'Число':
                    $property_type = PropertyItem::TYPE_NUMERIC;
                    break;
            }
        } else if ($this->getSimpleXML()->ВариантыЗначений->Справочник) {
            $property_type = PropertyItem::TYPE_LIST;
        }

        $product_property = new PropertyItem();
        $product_property['site_id'] = SiteManager::getSiteId();
        $product_property['type']    = $property_type;
        $product_property['title']   = Tools::toEntityString($this->getSimpleXML()->Наименование);
        $product_property['xml_id']  = (string) $this->getSimpleXML()->Ид;

        $alias = Transliteration::str2url($product_property['title']);
        if (isset($cache_existed_aliases[$alias]) && $cache_existed_aliases[$alias] != $product_property['xml_id']) {
            $alias .= substr(md5(rand()), 0, 5);
        }
        $product_property['alias'] = $alias;

        $on_duplicate_update_fields = ['title'];
        // Исключаем поля, которые помечены как "не обновлять" в настройках модуля
        $on_duplicate_update_fields = array_diff($on_duplicate_update_fields, $config['dont_update_prop_fields'] ?? []);

        if (empty($on_duplicate_update_fields)) {
            $product_property->insert(true);
        } else {
            $product_property->insert(false, $on_duplicate_update_fields, ['xml_id', 'site_id']);
        }
        $cache_existed_aliases[$product_property['alias']] = $product_property['xml_id'];
    }

    /**
     * Возвращает допустимое значение свойства из справочника допустимых значений, хранящегося в сессии.
     *
     * @param string $xml_id Идентификатор значения в системе 1C
     * @return array|null
     */
    static public function getPropertyAllowedValueByXmlId($xml_id)
    {
        $stored_prop_values = (array) Task\TaskQueue::getSessionVar(self::SESSION_PROP_ALLOWED_VALUES_KEY);
        return isset($stored_prop_values[$xml_id]) ? $stored_prop_values[$xml_id] : null;
    }
}
