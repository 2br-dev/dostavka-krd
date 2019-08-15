<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Filter;
use RS\Config\UserFieldsManager;

/**
 * Фильтр по пользовательским полям
 */
class DocumentProductFilter extends \RS\Html\Filter\Type\Product
{
    protected
        $search_type = 'ids';

    function __construct($key, $title, array $options = array())
    {
        parent::__construct($key, $title, $options);
    }

    /**
     * Устанавливает тип документов, которые следует отбирать
     *
     * @param strign $document_type
     */
    function setDocumentType($document_type)
    {
        $this->document_type = $document_type;
    }


    /**
     * Возвращает выражение для поиска по кастомным полям
     *
     * @return string
     */
    protected function where_ids()
    {
        $value = $this->getValue();
        if ($value) {

        }
    }

    public function getParts($current_filter_values, $exclude_keys = array())
    {
        return array();

//        return array(array(
//            'title' => 'test',
//            'value' => '123',
//            ),
//        );
    }

}
