<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model;
use \Exchange\Model\XMLTools;
use \Exchange\Model\Log;

/**
* Класс, производящий применение классов Импортеров к XML-данным
* Здесь производится поиск соответсвий xml-нодам классов импортеров
* При нахождении подходящего импортера у него вызывается метод import()
*/

class Matcher
{
    static private $inst = null;

    private $config;

    // Массив статистики вида array ( 'Имя_класса_импортера' => число_успешных_итераций_импорта)
    private $statistic = array();

    
    private function __construct()
    {
        $this->config = \RS\Config\Loader::byModule($this);
    }
    
    static public function getInstance()
    {
        if(self::$inst == null){
            self::$inst = new self();
        }
        return self::$inst;
    }
    
    /**
    * Применение списка Импортеров к XML-файлу. Производит собственно импорт
    * 
    * @param string $file_path Полный путь к XML-файлу для импорта
    * @param array $importers Массив имен классов наследников AbstractImporter
    * @param int $offset смещение в XML-нодах от начала XML-файла
    * @param int $max_exec_time Максимальное время выполнения в секундах
    * @return boolean смещение от начала XML-файла в XML-нодах, на котором был прерван импорт . True в случае окончания импорта
    */
    public function applyImporters($file_path, array $importers, $offset = 0, $max_exec_time = 0)
    {
        $start_time = time();
        
        $reader = new \XMLReader();
        $reader->open($file_path);
        
        $stack = array();
        $attributes_stack = array();
        $index = -1;
        
        while ($reader->read()) {
            switch ($reader->nodeType) {
                case \XMLReader::ELEMENT:
                    if($reader->isEmptyElement) continue;
                
                    $index++;
                    array_push($stack, $reader->name);
                    array_push($attributes_stack, XMLTools::getAttributes($reader));
                    
                    //Log::w("stack = ". Log::arr2str($stack));

                    if($offset > $index) continue;
                    
                    // Проверяем подходит ли один из импортеров к этому XML-тэгу
                    foreach($importers as $class_name){
                        // Если импортер подошел к текущему xml-тэгу
                        if($class_name::match($stack, $attributes_stack)){
                            // Создаем экземпляр импортера
                            $importer = new $class_name($reader, $stack, $attributes_stack);
                            
                            $importer->import($reader); // Импортируем!
                            
                            // Сохраняем статистику
                            if(!isset($this->statistic[$class_name])){
                                $this->statistic[$class_name] = 0;
                            }
                            $this->statistic[$class_name] ++;
                        }
                    }
                    
                    // Если превышен лимит времени исполнения
                    if($max_exec_time && time() >= $start_time + $max_exec_time){
                        return $index;
                    }
                    break;
                case \XMLReader::END_ELEMENT:
                    array_pop($stack);
                    array_pop($attributes_stack);
                    break;
            }
        }
        return true;
    }
    
    
    /**
    * Получить массив статистики
    * @return array
    */
    public function getStatistic()
    {
        return $this->statistic;
    }
    
    /**
    * Получить читаемое представление статистики
    * @return string
    */
    public function getStatisticText()
    {
        $interpretations = array(
            '\Exchange\Model\Importers\CatalogProduct'  => t('Импортировано товаров'),
            '\Exchange\Model\Importers\CatalogGroup'    => t('Импортировано основных групп'),
            '\Exchange\Model\Importers\CatalogProperty' => t('Импортировано свойств'),
            '\Exchange\Model\Importers\Offer'           => t('Импортировано товарных предложений'),
            '\Exchange\Model\Importers\PriceType'       => t('Импортировано типов цен'),
        );
        
        $ret = array();
        foreach($this->statistic as $class_name => $iterations){
            $ret[] = $interpretations[$class_name].': '.$iterations;
        }
        return join("\n", $ret);
    }

}
?>
