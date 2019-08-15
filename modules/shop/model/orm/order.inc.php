<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model\Orm;

use Catalog\Model\Orm\WareHouse;
use Catalog\Model\WareHouseApi;
use RS\Config\Loader as ConfigLoader;
use RS\Db\Exception as DbException;
use RS\Event\Exception as EventException;
use RS\Exception as RSException;
use RS\Module\Manager as ModuleManager;
use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Type;
use Shop\Model\AddressApi;
use Shop\Model\Cart;
use Shop\Model\OrderApi;
use Shop\Model\UserStatusApi;

/**
* ORM Объект - заказ.
*/
class Order extends OrmObject
{
    const USER_TYPE_NOREGISTER = 'noregister';
    const USER_TYPE_USER = 'user';
    const USER_TYPE_COMPANY = 'company';
    const USER_TYPE_PERSON = 'person';
    const EXTRAINFOLINE_TYPE_DEFAULT = 'default';
    const EXTRAINFOLINE_TYPE_DELIVERY = 'delivery';
    const DOCUMENT_TYPE_ORDER = 'order';
    const ORDER_SESS_VAR = 'ORDER-ORMOBJECT';
        
    protected $use_generated_order_num = null; //Флаг использовать уникальный номер заказа
    protected $products_hash = null; //хэш от товаров в заказе
    protected $cache_weigth = array(); // кэшированный вес

    /** @var Address $address */
    public $address;
    /** @var Cart $order_cart */
    public $order_cart;
    /** @var Cart $session_cart */
    public $session_cart;
    /** @var Order $this_before_write */
    public $this_before_write;
    
    protected static $currency;
    protected static $table = 'order';
    
    function _init()
    {
        $properties = parent::_init()
            ->groupSet('condition', array('step' => 'init'))
            ->append(array(
                'site_id' => new Type\CurrentSite(),
                'order_num' => new Type\Varchar(array(
                    'maxLength' => '20',
                    'description' => t('Уникальный идентификатор номера заказа'),
                    'meVisible' => false,
                )),
                'step' => new Type\MixedType(array(
                    'meVisible' => false,
                )),
                'user_id' => new Type\User(array(
                    'allowEmpty' => false,
                    'maxLength' => '11',
                    'attr' => array(array(
                        'data-autocomplete-body' => '1'
                    )),
                    'description' => t('ID покупателя'),
                    'meVisible' => false,
                )),
                'basket' => new Type\MixedType(array(
                    'description' => t('Объект - корзина'),
                    'meVisible' => false,
                )),
                'currency' => new Type\Varchar(array(
                    'maxLength' => '5',
                    'description' => t('Трехсимвольный идентификатор валюты на момент оформления заказа'),
                    'meVisible' => false,
                )),
                'currency_ratio' => new Type\Real(array(
                    'description' => t('Курс относительно базовой валюты'),
                    'meVisible' => false,
                )),
                'currency_stitle' => new Type\Varchar(array(
                    'description' => t('Символ валюты'),
                    'maxLength' => 10,
                    'meVisible' => false,
                )),
                'ip' => new Type\Varchar(array(
                    'maxLength' => '15',
                    'description' => t('IP'),
                    'meVisible' => false,
                )),
                'notify_user' => new Type\Integer(array(
                    'runtime' => true,
                    'description' => t('Уведомлять пользователя об изменении в заказе'),
                    'appVisible' => false,
                    'meVisible' => true,
                    'visible' => false,
                    'checkboxView' => array(1,0),
                )),
                'manager_user_id' => new Type\Integer(array(
                    'index' => true,
                    'description' => t('Менеджер заказа'),
                    'hint' => t('Заказу не назначен менеджер'),
                    'list' => array(array('\Shop\Model\OrderApi', 'getUsersManagersName'), array(0 => t('Не задан'))),
                    'allowEmpty' => false,
                    'meVisible' => false,
                )),
                //Поля, которые отображаются только в определенном статусе заказа
                'create_refund_receipt' => new Type\Integer(array(
                    'description' => t('Выбить чек возврата'),
                    'hint' => t('Будет отправлен запрос на выписку чека возврата провайдеру ККТ'),
                    'visible' => false,
                    'runtime' => false,
                    'dependVisible' => true,
                    'attr' => array(array(
                        //Через запятую указываем с каким статусом связано поле
                        'data-depend-status' => implode(',', array(UserStatus::STATUS_CANCELLED))
                    )),
                    'checkboxview' => array(1, 0),
                    'meVisible' => false,
                )),
        
            //Подтверждение
                'dateof' => new Type\Datetime(array(
                    'description' => t('Дата заказа'),
                    'meVisible' => false,
                )),
                'dateofupdate' => new Type\Datetime(array(
                    'description' => t('Дата обновления'),
                    'meVisible' => false,
                )),
                'totalcost' => new Type\Decimal(array(
                    'allowEmpty' => false,
                    'maxLength' => '15',
                    'decimal' => 2,
                    'description' => t('Общая стоимость'),
                    'meVisible' => false,
                )),
                'profit' => new Type\Decimal(array(
                    'maxLength' => '15',
                    'decimal' => 2,
                    'description' => t('Доход'),
                    'meVisible' => false,
                )),
                'user_delivery_cost' => new Type\Decimal(array(
                    'maxLength' => '15',
                    'decimal' => 2,
                    'description' => t('Стоимость доставки, определенная администратором'),
                    'meVisible' => false,
                )),
                'is_payed' => new Type\Integer(array(
                    'maxLength' => '1',
                    'description' => t('Заказ полностью оплачен?'),
                    'CheckBoxView' => array(1,0),
                    'meVisible' => false,
                )),
                'status' => new Type\Integer(array(
                    'maxLength' => '11',
                    'description' => t('Статус'),
                    'index' => true,
                    'meVisible' =>true,
                    'tree' => array(array('\Shop\Model\UserStatusApi',"staticTreeList")),
                )),
                'admin_comments' => new Type\Text(array(
                    'description' => t('Комментарии администратора (не отображаются пользователю)'),
                    'Attr' => array(array('class' => 'fullwide')),
                    'meVisible' => false,
                )),
                'user_text' => new Type\Richtext(array(
                    'description' => t('Текст для покупателя'),
                    'Attr' => array(array('class' => 'fullwide')),
                    'meVisible' => false,
                )),
                '_serialized' => new Type\Text(array(
                    'description' => t('Дополнительные сведения'),
                    'appVisible' => false,
                    'meVisible' => false,
                )),
                'userfields' => new Type\Text(array(
                    'description' => t('Дополнительные сведения'),
                    'condition' => array('step' => 'address'),
                    'visible' => false,
                    'meVisible' => false,
                )),
                'extra' => new Type\ArrayList(array(
                    'visible' => false,
                    'meVisible' => false,
                )),
                'hash' => new Type\Varchar(array(
                    'maxLength' => '32',
                    'meVisible' => false,
                )),
                'expired' => new Type\MixedType(),
                'is_exported' => new Type\Integer(array(
                    'maxLength' => '1',
                    'default' => 0,
                    'description' => t('Выгружен ли заказ'),
                    'visible' => false,
                    'meVisible' => false,
                )),
                'delivery_order_id' => new Type\Varchar(array(
                    'description' => t('Идентификатор заказа доставки'),
                    'visible' => false,
                    'meVisible' => false,
                )),
                'delivery_shipment_id' => new Type\Varchar(array(
                    'description' => t('Идентификатор партии заказов доставки'),
                    'visible' => false,
                    'meVisible' => false,
                )),
                'trigger_cart_change' => new Type\Integer(array(
                    'description' => t('Применить обработчики "изменений в корзине"'),
                    'CheckboxView' => array(1, 0),
                    'default' => 0,
                    'userVisible' => true,
                    'runtime' => true,
                    'meVisible' => false,
                )),
                'track_number' => new Type\Varchar(array(
                    'maxLength' => 30,
                    'description' => t('Трек-номер'),
                    'deliveryVisible' => true,
                    'meVisible' => false,
                )),
        ))->cancelGroupSet();
        
        $this->addIndex(array('site_id', 'order_num'),self::INDEX_UNIQUE);
        
        //поля для регистрации пользователя
        $chk_condition = array(
            'user_type' => array('person', 'company'),
            'step' => 'address'
        );        
        
        $properties
            ->append(array(
                'user_type' => new Type\Varchar(array(
                    'maxLength' => '30',
                    'condition' => array('step' => 'address'),
                    'runtime' => true,
                    'appVisible' => false,
                    'meVisible' => false,
                ))
            ))
            ->groupSet('condition', $chk_condition)
            ->groupSet('runtime', true)
            ->groupSet('appVisible', false)
            ->append(array(    
                'reg_name' => new Type\Varchar(array(
                    'description' => t('Имя'),
                    'maxLength' => '200',
                    'meVisible' => false,
                )),
                'reg_surname' => new Type\Varchar(array(
                    'description' => t('Фамилия'),
                    'maxLength' => '200',
                    'meVisible' => false,
                )),
                'reg_midname' => new Type\Varchar(array(
                    'description' => t('Отчество'),
                    'maxLength' => '200',
                    'meVisible' => false,
                )),
                'reg_phone' => new Type\Varchar(array(
                    'description' => t('Телефон'),
                    'maxLength' => '100',
                    'meVisible' => false,
                )),
                'reg_e_mail' => new Type\Varchar(array(
                    'description' => t('E-mail'),
                    'maxLength' => '100',
                    'meVisible' => false,
                    'trimString' => true,
                )),
                'reg_autologin' => new Type\Integer(array(
                    'maxLength' => '1',
                    'CheckBoxView' => array(1,0),
                    'meVisible' => false,
                )),
                'reg_login' => new Type\Varchar(array(
                    'description' => t('Логин'),
                    'maxLength' => '70',
                    'meVisible' => false,
                )),
                'reg_openpass' => new Type\Varchar(array(
                    'description' => t('Пароль'),
                    'maxLength' => '70',
                    'attr' => array(array('type' => 'password')),
                    'meVisible' => false,
                )),
                'reg_pass2' => new Type\Varchar(array(
                    'maxLength' => '70',
                    'attr' => array(array('type' => 'password')),
                    'meVisible' => false,
                )),
                'regfields' => new Type\ArrayList(array(
                    'description' => t('Дополнительные сведения'),
                    'meVisible' => false,
                ))
            ));
            
            
        // поля для регистрации предприятия
        $chk_condition = array(
            'user_type' => 'company',
            'step' => 'address'
        );
        $properties
            ->groupSet('condition', $chk_condition)
            ->append(array(
                'reg_company' => new Type\Varchar(array(
                    'description' => t('Наименование компании'),
                    'maxLength' => '255',
                    'meVisible' => false,
                )),
                'reg_company_inn' => new Type\Varchar(array(
                    'description' => t('ИНН'),
                    'maxLength' => '50',
                    'meVisible' => false,
                )),
            ));
            
        
        // поля для авторизации пользователя
        $chk_condition = array(
            'user_type' => 'user',
            'step' => 'address'
        );
        $properties
            ->groupSet('condition', $chk_condition)            
            ->append(array(
                'login' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'meVisible' => false,
                )),
                'password' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'Attr' => array(array('type' => 'password')),
                    'meVisible' => false,
                ))
            ))
            ->cancelGroupSet();
        
        // Поля для адреса доставки
        $properties
            ->append(array(
                'contact_person' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Контактное лицо'),
                    'condition' => array('step' => 'address'),
                    'meVisible' => false,
                )),
                'use_addr' => new Type\Integer(array(
                    'maxLength' => '11',
                    'description' => t('ID адреса доставки'),
                    'condition' => array('step' => 'address'),
                    'meVisible' => false,
                )),
                'only_pickup_points' => new Type\Integer(array(
                    'maxLength' => '1',
                    'description' => t('Использовать только самовывоз'),
                    'runtime' => true,
                    'meVisible' => false,
                ))
            ));

        $chk_condition = array(
            'use_addr' => '0',
            'step' => 'address'
        );
        
        $properties
            ->groupSet('condition', $chk_condition)
            ->groupSet('runtime', true)
            ->groupSet('appVisible', false)
            ->append(array(
                'addr_country_id' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'description' => t('Страна'),
                    'Checker' => array(function($object, $value, $error)
                    {
                        $config = ConfigLoader::byModule($object);
                        if ($config['require_country'] && empty($value)) {
                            return $error;
                        }
                        return true;
                    }, t('Страна - обязательное поле')),
                    'List' => array(array('\Shop\Model\RegionApi', 'countryList')),
                    'Attr' => array(array('size' => 1)),
                    'meVisible' => false,
                )),
                'addr_region' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'meVisible' => false,
                )),
                'addr_region_id' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'description' => t('Область/Край'),
                    'Checker' => array(function($object, $value, $error)
                    {
                        $config = ConfigLoader::byModule($object);
                        if ($config['require_region'] && empty($value)) {
                            return $error;
                        }
                        return true;
                    }, t('Область/Край - обязательное поле')),
                    'List' => array(array(__CLASS__, 'regionList')),
                    'Attr' => array(array('size' => 1)),
                    'meVisible' => false,
                )),
                'addr_city' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'description' => t('Город'),
                    'Checker' => array(function($object, $value, $error)
                    {
                        $config = ConfigLoader::byModule($object);
                        if ($config['require_city'] && empty($value)) {
                            return $error;
                        }
                        return true;
                    }, t('Город - обязательное поле')),
                    'meVisible' => false,
                )),
                'addr_zipcode' => new Type\Varchar(array(
                    'maxLength' => '20',
                    'description' => t('Индекс'),
                    'Checker' => array(function($object, $value, $error) 
                    {
                        $config = ConfigLoader::byModule($object);
                        if ($config['require_zipcode'] && empty($value)) {
                            return $error;
                        }
                        return true;
                    }, t('Индекс - обязательное поле')),
                    'meVisible' => false,
                )),
                'addr_address' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Адрес'),
                    'Checker' => array(function($object, $value, $error) 
                    {
                        $config = ConfigLoader::byModule($object);
                        if ($config['require_address'] && empty($value)) {
                            return $error;
                        }
                        return true;
                    }, t('Адрес - обязательное поле')),
                    'meVisible' => false,
                ))
            ))->cancelGroupSet();

        $properties->append(array(
            'userfields_arr' => new Type\ArrayList(array(
                'description' => t('Дополнительные сведения'),
                'condition' => array('step' => 'address'),
                'meVisible' => false,
            )),
            'code' => new Type\Captcha(array(
                'enable' => false,
                'condition' => array('step' => 'address'),
                'meVisible' => false,
            )),
            
        //Шаг 2
            'delivery' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Доставка'),
                'condition' => array('step' => 'delivery'),
                'Checker' => array('chkEmpty', t('Укажите тип доставки')),
                'List' => array(array('\Shop\Model\DeliveryApi', 'staticSelectList')),
                'meVisible' => false,
            )),
            'deliverycost' => new Type\Decimal(array(
                'maxLength' => '15',
                'decimal' => 2,
                'description' => t('Стоимость доставки'),
                'condition' => array('step' => 'delivery'),
                'meVisible' => false,
            )),
            'courier_id' => new Type\Integer(array(
                'description' => t('Курьер'),
                'allowEmpty' => false,
                'default' => 0,
                'list' => array(array('\Shop\Model\DeliveryApi', 'getCourierList')),
                'meVisible' => false,
            )),
            
        //Шаг 2.2 - только при самовывозе
            'warehouse' => new Type\Integer(array(
                'maxLength' => '11',
                'description' => t('Склад'),
                'condition' => array('step' => 'warehouses'),
                'Checker' => array('chkEmpty', t('Укажите склад для забора товара')),
                'List' => array(array('\Catalog\Model\WarehouseApi', 'staticSelectList')),
                'meVisible' => false,
            )),
            
        //Шаг 3
            'payment' => new Type\Integer(array(
                'description' => t('Тип оплаты'),
                'condition' => array('step' => 'pay'),
                'Checker' => array('chkEmpty', t('Укажите способ оплаты')),
                'List' => array(array('\Shop\Model\PaymentApi', 'staticSelectList')),
                'meVisible' => false,
            )),
            
        //Шаг 4
            'comments' => new Type\Text(array(
                'description' => t('Комментарий'),
                'condition' => array('step' => 'confirm'),
                'meVisible' => false,
            )),

            //Поля, которые отображаются только в определенном статусе заказа
            'substatus' => new Type\Integer(array(
                'description' => t('Причина отклонения заказа'),
                'hint' => t('Настраивается в настройках модуля Магазин'),
                'list' => array(array('Shop\Model\SubStatusApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                'visible' => false,
                'dependVisible' => true,
                'attr' => array(array(
                    //Через запятую указываем с каким статусом связано поле
                    'data-depend-status' => implode(',', array(UserStatus::STATUS_CANCELLED))
                ))
            ))
        ));
        
        //Поля для заказа без авторизации или регистрации
        $properties->append(array(
            'user_fio' => new Type\Varchar(array(
                'description' => t('Ф.И.О.'),
                'Attr' => array(array(
                    'size' => 40,
                )),
                'maxLength' => 255,
                'meVisible' => false,
            )),
            'user_email' => new Type\Varchar(array(
                'description' => t('E-mail'),
                'Attr' => array(array(
                    'size' => 40,
                )),
                'maxLength' => 255,
                'meVisible' => false,
            )),
            'user_phone' => new Type\Varchar(array(
                'description' => t('Телефон'),
                'Attr' => array(array(
                    'size' => 40,
                )),
                'maxLength' => 255,
                'meVisible' => false,
            )),
            'is_mobile_checkout' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Оформлен через мобильное приложение?'),
                'checkboxView' => array(1,0),
                'listenPost' => false,
                'meVisible' => false,
            ))
        ));
    }
    
    /**
    * Фукнция срабатывает после записи объекта в БД
    *
    * @return string
    */
    function getProductsHash()
    {
        if (!$this['id']) {
            $products = $this->getCart()->getProductItems();
            $arr = array();
            foreach($products as $uniq=>$item){
               $product  = $item['product'];
               $cartitem = $item['cartitem'];
               $arr[]    = $product['title']."_".$product['id']."_".$cartitem['offer']."_".$cartitem['amount'];
            } 
            sort($arr);
            return md5(serialize($arr));
        }else{
            $cart_data=$this->getCart()->getPriceItemsData();
            $cart_data['checkcount'] = count($cart_data['items']);
            return md5(serialize($cart_data));
        }
    }


    /**
     * Функция срабатывает перед записью заказа
     *
     * @param string $flag - insert или update
     * @return null|bool
     * @throws RSException
     * @throws DbException
     * @throws EventException
     */
    function beforeWrite($flag)
    {
        $this->this_before_write = new Order($this['id'], false);

        $delivery_before_write = $this->this_before_write->getDelivery();
        $delivery = $this->getDelivery();
        if ($delivery['id'] != $delivery_before_write['id']){
          $this->removeExtraInfoLine(null,'delivery');
        }

        $cart = $this->getCart();
        if($this->this_before_write->getCart())
            $this->this_before_write['old_items'] = $this->this_before_write->getCart()->getProductItems();

        if ($this['id'] < 0) {
            $this['_tmpid'] = $this['id'];
            unset($this['id']);
        }
        
        $config = ConfigLoader::byModule($this);
        
        //Проверка уникального идентификатора номера заказа
        if (empty($this['order_num']) && $config['use_generated_order_num']) {
            $api = new OrderApi();
            $this['order_num'] = $api->generateOrderNum($this);
        }     

        if ($flag == self::INSERT_FLAG) {
            //Проверяем наличие необходимого количества товаров.
            if ($config['check_quantity']) {
                $pnum_check = $this->checkProductsNum();
                if ($pnum_check !== true) {
                    return $this->addError($pnum_check);
                }
            }

            $pay = $this->getPayment();
            $pay->getTypeObject()->onCreateOrder();

            if (!$this['status']) {
                //Устанавливаем первоначальный статус заказа
                if (!empty($config['first_order_status'])) {
                    $status = $config['first_order_status'];
                } else {
                    $status = UserStatusApi::getStatusIdByType(UserStatus::STATUS_NEW);
                }

                if ($pay['first_status'] > 0) {
                    $status = $pay['first_status'];
                }

                if ($delivery['first_status'] > 0) {
                    $status = $delivery['first_status'];
                }
                $this['status'] = $status;
            }

            $this['ip']     = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $this['dateof'] = date('Y-m-d H:i:s');
            $this['hash']   = md5(uniqid(mt_rand(), true));
            if(!$this->isModified('is_exported')){
                $this['is_exported'] = 0;
            }

            //Создаем корзину к заказу
            if ($this['basket']) {
                //Подготавливаем корзину к переносу в заказ
                if ($cart && ($error = $cart->makeOrderCart()) !== true) {
                    return $this->addError($error);
                }
            }
            
            //Если есть массив дополнительных данных, то сохраним их
            if (!empty($this['order_extra'])){
                foreach ($this['order_extra'] as $order_key_step=>$order_step){
                    $m=0;
                    foreach ($order_step as $extra_title=>$extra_value){
                        $extra_uniq_key = $order_key_step."_".$m;
                        $this->addExtraInfoLine($extra_title, $extra_value, null, $extra_uniq_key); 
                        $m++;
                    }
                }
            }
            
            // Установим персонального менеджера пользователя или случайного менеджера (если такая опция включена)
            if (!$this['manager_user_id']) {
                $user = $this->getUser();
                if ($user['manager_user_id']) {
                    $this['manager_user_id'] = $user['manager_user_id'];
                } elseif ($config['set_random_manager']) {
                    $managers_ids = array_keys(OrderApi::getUsersManagers());
                    if ($managers_ids) {
                        $this['manager_user_id'] = $managers_ids[rand(0, count($managers_ids) - 1)];
                    }
                }
            }
        }
        
        if ($flag == self::UPDATE_FLAG) { //При обновлении заказа 
            //Подгрузим данные для дальнейшей сверки с новыми
            //Прежняя корзина
            $this->this_before_write['cart_md5'] = $this->this_before_write->getProductsHash();
        }

        if ($cart) {
            $this['profit'] = $cart->getOrderProfit();
        }
        
        $this['dateofupdate'] = date('c');
        $this['_serialized'] = serialize($this['extra']);
        $this['userfields']  = serialize($this['userfields_arr']);

        return null;
    }

    function afterWrite($flag)
    {
        $config = ConfigLoader::byModule($this);

        if ($flag == self::INSERT_FLAG) { //При вставке
            if($this->session_cart){
                $this->session_cart->saveOrderData(); //Создаем корзину к заказу
            }
            
            if($this->session_cart){
                //Отмечаем, что скидочный купон использован, если он был привязан к корзине
                $coupons = $this->session_cart->getCouponItems();
                foreach($coupons as $coupon) {
                    /** @var Discount $coupon_obj */
                    $coupon_obj = $coupon['coupon'];
                    $coupon_obj->incrementUse();
                }
            }
            
            if (!$config['use_generated_order_num']) {
            //Устнавливаем номер заказа, равный ID заказа, в случае, если выключена опция генерации
                OrmRequest::make()
                    ->update($this)
                    ->set('order_num = id')
                    ->where(array('id' => $this['id']))
                    ->exec();
                $this['order_num'] = $this['id'];
            }
        }

        if ($flag == self::UPDATE_FLAG){
        
            if ($this['notify_user'] && $this->canUserNotify()) {
                //Отправляем уведомление пользователю об изменении заказа
                $notice = new \Shop\Model\Notice\OrderChange();
                $notice->init($this);
                \Alerts\Model\Manager::send($notice);
            }

            //Посмотрим, если появился трекномер, то отправим его по SMS
            if (empty($this->this_before_write['track_number']) && !empty($this['track_number']) ||
                (!empty($this['track_number']) && ($this->this_before_write['track_number'] != $this['track_number']))){
                $notice = new \Shop\Model\Notice\TrackNumberToUser();
                $notice->init($this);
                \Alerts\Model\Manager::send($notice);
            }
        }

        //Выполняем действия с доставками и оплатами, если у этого типа доставок и оплат поддерживаются такие действия и включён флаг на разрешение
        if ($this['delivery_new_query']){ //Если доставке нужно делать запрос при создании или редактировании заказа
            $delivery_type = $this->getDelivery()->getTypeObject();
            $delivery_type->onOrderCreate($this, $this->getAddress());
        }
        
        if ($this['payment_new_query']){ //Если оплате нужно делать запрос при создании или редактировании заказа
            $payment_type = $this->getPayment()->getTypeObject();
            $payment_type->onOrderCreate($this, $this->getAddress()); 
        }    
        
        //Отправим уведомления
        if ($flag == self::INSERT_FLAG) { //При вставке
            if (empty($this['disable_checkout_notice'])) { //Если не стоит запрет на отправку уведомлений
                
                //Отправляем уведомление покупателю
                $notice = new \Shop\Model\Notice\CheckoutUser();
                $notice->init($this);
                \Alerts\Model\Manager::send($notice);  
                
                $site_config = \RS\Config\Loader::getSiteConfig();
                
                //Отправляем уведомление администратору
                $notice = new \Shop\Model\Notice\CheckoutAdmin();
                $notice->init($this);
                \Alerts\Model\Manager::send($notice);  
            } 
        }
        
        //Посмотрим, есть ли адреса заказов, которые были не присвоины и новому идентификатору
        if ($this['_tmpid']<0){
            $address_api = new AddressApi();
            $address_api->setFilter('order_id', $this['_tmpid']);
            $address_list = $address_api->getList();
            
            if ($address_list){
                foreach ($address_list as $address){
                    $address['order_id'] = $this['id'];
                    $address->update(); 
                }
            }
        }
        
        if ($this['courier_id'] && $this->this_before_write['courier_id'] != $this['courier_id']) {
            //Отправляем уведомление о назначении заказа курьеру
            $notice = new \Shop\Model\Notice\AssignOrderToCourier();
            $notice->init($this);
            \Alerts\Model\Manager::send($notice);  
        }

        $cancelled = \Shop\Model\UserStatusApi::getStatusesIdByType(UserStatus::STATUS_CANCELLED);

        //Если нужно сделать возврат средств за заказы
        if ($config['cashregister_class'] && $this['create_refund_receipt'] && in_array($this['status'], $cancelled)) {
            //Проверим был ли воврат ранее
            $transaction = \Shop\Model\ReceiptApi::getTransactionForRefundReceiptByOrderId($this['id']);

            if (!$transaction['id']) { //Если такой успешной транзакции нет то сделать возврат
                //Если заказ уже оплачен
                $cashregister_api = new \Shop\Model\CashRegisterApi();
                $cashregister_api->makeOrderRefund($this);
            }
        }
        $catalog_config = ConfigLoader::byModule('catalog');

        if ($catalog_config['inventory_control_enable'] || $config['check_quantity']) {
            $stock_manager = \Catalog\Model\StockManager::getInstance();
            $stock_manager->updateRemainsFromOrder($this, $flag, $this['back_warehouse']);
        }
        \RS\Event\Manager::fire('order.change', array('order_before' => $this->this_before_write, 'order' => $this));
    }

    /**
     * Возвращает склад на котором должно происходить изменение остатков
     *
     * @return WareHouse
     * @throws RSException
     */
    public function getStockWarehouse()
    {
        if ($this['warehouse']) {
            $warehouse = new WareHouse($this['warehouse']);
            if (!$warehouse['dont_change_stocks']) {
                return $warehouse;
            }
        }

        // Если включена опция "Ограничить остатки товара только остатками на складах выбранного филиала", то вернет склад филиала
        return ($this->getStockAffiliateWarehouse()) ?:  WareHouseApi::getDefaultWareHouse();
    }

    /**
     * @deprecated
     */
    public function gerStockWarehouse()
    {
        $this->getStockWarehouse();
    }

    /**
     * Возвращает склад филиала на котором должно происходить изменение остатков
     *
     * @return WareHouse|bool
     * @throws RSException
     */
    public function getStockAffiliateWarehouse()
    {
        $config = ConfigLoader::byModule('catalog');

        if (ModuleManager::staticModuleExists('affiliate') && $config['affiliate_stock_restriction']) {
            $warehouse_ids = WareHouseApi::getAvailableWarehouses(false,false,true);
            return reset($warehouse_ids);
        }
        return false;
    }

    /**
     * Удаляет объект из хранилища
     * @return boolean - true, в случае успеха
     */
    function delete()
    {
        $link_manager = new \Catalog\Model\DocumentLinkManager($this['id'], self::DOCUMENT_TYPE_ORDER);
        $link_manager->deleteLinkedDocuments();
        return parent::delete();
    }
    
    /**
    * Функция срабатывает после загрузки объекта
    * 
    */
    function afterObjectLoad()
    {
        $this['extra'] = @unserialize($this['_serialized']);
        $this['userfields_arr'] = @unserialize($this['userfields']);
    }
    
    /**
    * Возвращает true, если в заказе произошли изменения, о которых следует сообщить пользователю
    * 
    * @return bool
    */
    function canUserNotify()
    {
        $changed = false;
        if ($this->this_before_write !== null) {
            $old_order = $this->this_before_write;
            $changed =  (float)$this['totalcost'] != (float)$old_order['totalcost']  //Проверяем сумму заказа
                        || ($old_order['status'] != $this['status'])                 //Проверяем статус
                        || ($old_order['cart_md5'] != $this->getProductsHash()) //Проверяем состав товаров
                        || (($this['before_address'] !== null) && ($this->before_address->getLineView() != $this->getAddress()->getLineView())) //Проверяем адрес доставки
                        || ($old_order['delivery'] != $this['delivery'])             //Проверяем способ доставки
                        || ($old_order['contact_person'] != $this['contact_person']) //Проверяем контактное лицо
                        || ($old_order['warehouse'] != $this['warehouse'])           //Проверяем склад
                        || ($old_order['payment'] != $this['payment'])               //Проверяем способ оплаты
                        || ($old_order['user_text'] != $this['user_text'])           //Проверяем текст для пользователя
                        || ($old_order['is_payed'] != $this['is_payed']);            //Проверяем флаг оплаты
        }
        
        return $changed;
    }
    
    /**
    * Привязывает корзину к заказу
    * 
    * @param \Shop\Model\Cart $cart - загруженный объект корзины в режиме PREORDER или EMPTY
    * @return Order
    */
    function linkSessionCart(\Shop\Model\Cart $cart)
    {
        $this['basket'] = serialize($cart);
        return $this;
    }
    
    /**
    * Сохраняет параметры валюты, в которой оформляется заказ
    * 
    * @param \Catalog\Model\Orm\Currency $currency
    * @return Order
    */
    function setCurrency(\Catalog\Model\Orm\Currency $currency)
    {
        if ($currency['ratio'] > 0) {
            $calculated_ratio = \Catalog\Model\CurrencyApi::getBaseCurrency()->ratio / $currency['ratio'];
        } else {
            $calculated_ratio = 1;
        }
        
        $this['currency'] = $currency['title'];
        $this['currency_ratio'] = $calculated_ratio;
        $this['currency_stitle'] = $currency['stitle'];        
        return $this;
    }

    /**
    * Проверяет наличие всех товаров в корзине
    */
    function checkProductsNum()
    {
        $num_error = false;
        if(!$this['basket']) return true;
        $bitems = $this->getCart()->getProductItems(false);
        
        
        foreach ($bitems as $n => $item) {
            $real_num = $item['product']->getNum($item['cartitem']['offer']); 
            if ($item['cartitem']['amount'] > $real_num) {
                $num_error = true;
                $bitems[$n]['product']['num'] = $real_num;
            }
        }
        if ($num_error) {
            return t('Извините, некоторых товаров уже нет в наличии');
        }
        return true;
    }
    
    /**
    * Возвращает экземпляр класса текущей корзины
    * @return Order
    */
    public static function currentOrder()
    {
        $order = new self();
        $order->getFromSession();
        return $order;
    }
    
    /**
    * Загружает объект данными из сессии. 
    * После вызова данного метода, любые изменения в объект будут сохраняться в сессию
    * 
    * @return void
    */
    function getFromSession() 
    {
        if (!isset($_SESSION[self::ORDER_SESS_VAR])) {
            $_SESSION[self::ORDER_SESS_VAR] = array();
        }
        $this->_values = &$_SESSION[self::ORDER_SESS_VAR];
    }

    /**
     * Удаляет чекеры, которые используются при оформлении заказа в клиентской части
     *
     * @return void
     */
    function removeConditionCheckers()
    {
        foreach($this->getProperties() as $key => $property) {
            if (isset($property->condition)) {
                $property->removeAllCheckers();
            }
        }
    }
    
    
    /**
    * Возвращает поля, которые удовлетворяют условиям condition.
    * Условия задают в каком случае поля должны запрашиваться и проверяться из POST
    * 
    * @return array
    */
    function useFields($post)
    {
        $result = array();
        foreach($this->getProperties() as $key=>$property) {
            $rule = isset($property->condition) ? $property->condition : null;
            $include = true;            
            if ($rule) {
                foreach($rule as $field => $need) {
                    if (!isset($post[$field])) {
                        $include = false;
                    } else {
                        if (is_array($need)) {
                            if (!in_array($post[$field], $need)) $include = false;
                        } else {
                            if ($post[$field] != $need) $include = false;
                        }
                    }
                }
            }
            if ($include) $result[] = $key;
        }
        return $result;        
    }    
    
    
    
    /**
    * Возвращает объект, управляющий дополнительными полями, заданными в настройках модуля
    * 
    * @return \RS\Config\UserFieldsManager
    */
    function getFieldsManager()
    {
        $order_fields_manager  = \RS\Config\Loader::byModule($this)->getUserFieldsManager();
        $order_fields_manager->setErrorPrefix('orderfield_');
        $order_fields_manager->setArrayWrapper('userfields_arr');
        
        $data = @unserialize($this['userfields']);
       
        if (!empty($data)) $order_fields_manager->setValues($data);
        
        return $order_fields_manager;
    }    
    
    /**
    * Возвращает список регионов в стране
    * 
    */
    public static function regionList()
    {
        $_this = self::currentOrder();
        $parent = $_this['addr_country_id'];
        $api = new \Shop\Model\RegionApi();
        if ($parent<1) {
            $countries = $api->countryList();
            $array_countries_keys = array_keys($countries);
            if (count($countries)) $parent = reset($array_countries_keys);
        }
        if ($parent>0) {
            $api->setFilter('parent_id', $parent);
            $regions = $api->getAssocList('id', 'title');
        } else {
            $regions = array();
        }
        return $regions;
    }
    
    /**
    * Возвращает объект адреса доставки
    * 
    * @return \Shop\Model\Orm\Address
    */
    function getAddress($cache = true)
    {
        if ($this->address === null || !$cache) {
            $this->address = new Address($this['use_addr']);
        }
        return $this->address;
    }
    
    /**
    * Устанавливает объект адреса доставки, в случае если адрес доставки еще не существует в БД
    * 
    * @param Address $address
    * @return Order
    */
    function setAddress(Address $address)
    {
        $this->address = $address;
        return $this;
    }
    
    
    /**
    * Возращает вес заказа в граммах
    * 
    * @param null|string $weight_unit - идентификатор единицы измерения, в которй нужно получить вес (соотношение к граммам)
    * @param bool $cache - использовать кэш
    * @return float
    */
    function getWeight($weight_unit = null, $cache = true)
    {
        if (!$cache || !isset($this->cache_weigth[$weight_unit])) {
            $this->cache_weigth[$weight_unit] = $this->getCart()->getTotalWeight($weight_unit);
        }
        return $this->cache_weigth[$weight_unit];
    }
    
    /**
    * Возвращает объект способа доставки
    * 
    * @return Delivery
    */
    function getDelivery()
    {
        return new Delivery($this['delivery']);
    }
    
    /**
    * Возвращает объект способа оплаты
    * 
    * @return Payment
    */
    function getPayment()
    {
        return new Payment($this['payment'], true, $this);
    }
    
    /**
    * Возвращает объект выбранного склада
    * 
    * @return \Catalog\Model\Orm\WareHouse
    */
    function getWarehouse()
    {
        return new \Catalog\Model\Orm\WareHouse($this['warehouse']);
    }
    
    /**
    * Возвращает стоимсть доставки для текущего заказа и заданного типа доставки
    * 
    * @param Delivery $delivery
    * @return string
    */
    function getDeliveryCostText(Delivery $delivery)
    {
        return $delivery->getDeliveryCostText($this, $this->getAddress());
    }
    
    function getDeliveryExtraText(Delivery $delivery)
    {
        return $delivery->getDeliveryExtraText($this, $this->getAddress());
    }

    /**
    * Применяет валюту заказа к заданной цене
    * 
    * @param float $price
    * @return double
    */
    function applyMyCurrency($price)
    {
        return round($price * $this['currency_ratio'], 2);
    }
    
    /**
    * Возвращает валюту, в которой был оформлен заказ
    * 
    * @return \Catalog\Model\Orm\Currency
    */
    function getMyCurrency()
    {
        if (self::$currency === null) {
            self::$currency = \Catalog\Model\CurrencyApi::getByUid($this['currency']);
            if (!self::$currency) {
                self::$currency = \Catalog\Model\CurrencyApi::getBaseCurrency();
            }
        }
        return self::$currency;
    }
    
    /**
    * Возвращает пользователя, оформившего заказ
    * 
    * @return \Users\Model\Orm\User
    */
    function getUser()
    {
        if ($this['user_id']>0){
            return new \Users\Model\Orm\User($this['user_id']);
        }
        $user = new \Users\Model\Orm\User();
        $fio = explode(" ", $this['user_fio']);
        if (isset($fio[0])){
            $user['surname'] = $fio[0];
        }
        if (isset($fio[1])){
            $user['name'] = $fio[1];
        }
        if (isset($fio[2])){
            $user['midname'] = $fio[2];
        }
        $user['e_mail'] = $this['user_email'];
        $user['phone']  = $this['user_phone'];
        return $user;
    }
    
    /**
    * Возвращает объект с позициями оформленного заказа
    * 
    * @return \Shop\Model\Cart
    */
    function getCart()
    {
        if ($this['id']) {
            if ($this->order_cart === null) {
                $this->order_cart = \Shop\Model\Cart::orderCart($this);
            }
            return $this->order_cart;            
        } else {
            if ($this->session_cart === null) {
                $this->session_cart = @unserialize($this['basket']);
            }
            if ($this->session_cart){
               $this->session_cart->setOrder($this);    
            }          
            return $this->session_cart;
        }
    }
    
    /**
    * Возможно ли редактирование заказа. 
    * Возвращает false если были удалены налоги либо скидки, идентфикаторы которых присутсвуют в этом заказе
    * 
    * @return bool
    */
    function canEdit()
    {
        // Проверям, не удалены ли налоги
        $items   = $this->getCart()->getCartItemsByType(\Shop\Model\Cart::TYPE_TAX);
        foreach($items as $one){
            $obj = new \Shop\Model\Orm\Tax;
            if(!$obj->exists($one['entity_id'])){
                return false;
            }
        }

        // Проверям, не удалены ли скидочные купоны
        $items   = $this->getCart()->getCartItemsByType(\Shop\Model\Cart::TYPE_COUPON);
        foreach($items as $one){
            $obj = new \Shop\Model\Orm\Discount;
            if(!$obj->exists($one['entity_id'])){
                return false;
            }
        }
        
        return true;
    }
    
    /**
    * Возвращает объект статуса заказа
    * 
    * @return UserStatus
    */
    function getStatus()
    {
        return new UserStatus($this['status']);
    }
    
    /**
    * Возвращает общую стоимость заказа 
    * 
    * @param bool $format - Если true, то стоимость будет отформатирована
    * @param bool $use_currency - Если true, то стоимость будет возвращена, в валюте в которой оформлялся заказ
    * @return float
    */
    function getTotalPrice($format = true, $use_currency = false)
    {
        $price = $this['totalcost'];
        if ($use_currency) {
            $price = $this->applyMyCurrency($price);
        }
        
        if ($format) {
            $currency = $use_currency ? $this['currency_stitle'] : \Catalog\Model\CurrencyApi::getBaseCurrency()->stitle;
            $price = \RS\Helper\CustomView::cost($price, $currency);
        }
        return $price;
    }
    
    /**
    * Возвращает успешно выполненные транзакции заказа
    * 
    */
    function getSuccessTransactions()
    {
        $transactrions_api = new \Shop\Model\TransactionApi();
        return $transactrions_api->setFilter('order_id', $this['id'])
                          ->setFilter('status', \Shop\Model\Orm\Transaction::STATUS_SUCCESS)
                          ->setOrder('dateof DESC')
                          ->getList();
    }
    
    /**
    * Возвращает список из базовой валюты и валюты в которой оформлен заказ
    * 
    * @return array
    */
    function getAllowCurrencies()
    {
        $base_currency = \Catalog\Model\CurrencyApi::getBaseCurrency();
        $result = array(
            '0' => $base_currency->title
        );
        
        if ($base_currency->title == $this['currency']) {
            $my = 0;
        } else {
            $result = array('1' => $this['currency']) + $result;
            $my = 1;
        }
        
        $result[$my] .= t(' (заказ оформлен в этой валюте)');
        return $result;
    }
    
    /**
    * Возвращает дополнительные пары ключ => значение для отображения в админ. панели в разделе "Информация о заказе"
    * 
    * @return array
    */
    function getExtraInfo()
    {
        $data = $this['extra'];
        if (isset($data['extrainfo']) && is_array($data['extrainfo'])) {  
            return $data['extrainfo'];
        }
        return array();
    }
    
    /**
    * Добавляет дополнительную информацию к заказу
    * 
    * @param string $title - Название информации
    * @param mixed $value - Значение
    * @param mixed $data - доп. сведения (если есть)
    * @param mixed $key - уникальный идентификатор информации
    * @param mixed $type - тип инфостроки
     *
    * @return Order
    */
    function addExtraInfoLine($title, $value, $data = null, $key = null,$type = self::EXTRAINFOLINE_TYPE_DEFAULT)
    {

        $extra = $this['extra'];
        $item = array(
            'title' => $title,
            'value' => $value,
            'data' => $data,
            'type'=>$type
        );
        
        if ($key === null) {
            $extra['extrainfo'][] = $item;
        } else {
            $extra['extrainfo'][$key] = $item;
        }



        $this['extra'] = $extra;
        $this['_serialized'] = serialize($extra);
        return $this;
    }
    
    /**
    * Возвращет данные из секции "extrainfo"
    * 
    * @param string $key - ключ в секции extrainfo, если не указан, то возвращает всю секцию
    * @return mixed
    */
    function getExtraInfoLine($key = null)
    {
        $extra = $this['extra'];
        if (!isset($extra['extrainfo'])) {
            return array();
        } 
        if ($key === null) {
            return $extra['extrainfo'];
        }else{
            return isset($extra['extrainfo'][$key]) ? $extra['extrainfo'][$key] : false;
        }
    }

    /**
    * Удаляет данные из секции "extrainfo"
    *
    * @param string $key - ключ в секции extrainfo
     * @param string $type - тип инфолинии
    * @return \Shop\Model\Orm\Order
    */
    function removeExtraInfoLine($key = null ,$type = null)
    {
        $extra = $this['extra'];
        if (($key || $type) && isset($extra['extrainfo'])){
            if (isset($extra['extrainfo'][$key])) {
                unset($extra['extrainfo'][$key]);
            }
            if(isset($extra['extrainfo'])) {
                foreach ($extra['extrainfo'] as $index => $one) {
                    if ($one['type'] == $type) {
                        if (!$key){
                            unset($extra['extrainfo'][$index]);
                        }elseif($key == $index){
                            unset($extra['extrainfo'][$index]);
                        }
                    }
                }
            }
            $this['extra'] = $extra;
            $this['_serialized'] = serialize($extra);
        }
        return $this;
    }
    
    /**
    * Добавляет в скрытую(которая не будет выводится) секцию с данными
    * ваши данные по ключу
    * 
    * @param string $key  - ключ
    * @param mixed $value - значение для сохранения
    * @return \Shop\Model\Orm\Order
    */
    function addExtraKeyPair($key, $value)
    {
        $extra = $this['extra'];
        $extra['extrakeypair'][$key] = $value;
        
        $this['extra'] = $extra;
        $this['_serialized'] = serialize($extra);
        return $this;
    }
    
    /**
    * Возвращет данные из секции "extrakeypair"
    * 
    * @param string $key - ключ в секции extrakeypair, если не указан, то возвращает всю секцию
    * @return mixed
    */
    function getExtraKeyPair( $key = null )
    {
       $extra = $this['extra'];
       if (!isset($extra['extrakeypair'])) {
           return array();
       } 
       if ($key === null) {
           return $extra['extrakeypair'];
       }else{
           return isset($extra['extrakeypair'][$key]) ? $extra['extrakeypair'][$key] : false;
       } 
    }
    
    /**
    * Удаляет в скрытую секцию с данными по ключу
    * 
    * @param string $key - ключ
    * @return \Shop\Model\Orm\Order
    */
    function removeExtraKeyPair($key)
    {
        $extra = $this['extra'];
        if (isset($extra['extrakeypair'][$key])) {
            unset($extra['extrakeypair'][$key]);
            
            $this['extra'] = $extra;
            $this['_serialized'] = serialize($extra);
        }
        return $this;
    }
    
    /**
    * Возвращает список объектов для печати текущего заказа
    * 
    * @return array
    */
    function getPrintForms()
    {
        return \Shop\Model\PrintForm\AbstractPrintForm::getList();
    }
    
    /**
    * Возвращает объект компании(с реквизитами), которая поставляет услуги для данного заказа
    * 
    * @return \Shop\Model\Orm\Company
    */
    function getShopCompany()
    {
        if (($company = $this->getPayment()->getTypeObject()->getCompany()) == false) {
            $company = new Company();
            $company->getFromArray( \RS\Config\Loader::getSiteConfig($this['site_id'])->getValues() );            
        }
        return $company;
    }
    
    /**
    * Возвращает true если для этого заказа возможна online-оплата
    * 
    */
    function canOnlinePay()
    {
        if(!$this->getPayment()->getTypeObject()->canOnlinePay()) { 
            return false;
        }

        if(!in_array($this['status'], \Shop\Model\UserStatusApi::getStatusesIdByType(UserStatus::STATUS_WAITFORPAY))) {
            return false;
        }
        
        if($this->is_payed){
            return false;
        }
        
        return true;
    }

    /**
     * Проверяет можно ли сменить оплату в заказе
     *
     * @return bool
     */
    function canChangePayment()
    {
        return (!$this['is_payed'] && in_array($this['status'], \Shop\Model\UserStatusApi::getStatusesIdByType(\Shop\Model\Orm\UserStatus::STATUS_WAITFORPAY)));
    }
    
    /**
    * Возвращайет URL для оплаты заказа в случае выбора online способа оплаты
    * 
    * @param bool $absolute - Если true, то будет возвращен абсолютный URL
    * @return string
    */
    function getOnlinePayUrl($absolute = false)
    {
        $router = \RS\Router\Manager::obj();
        return $router->getUrl('shop-front-onlinepay', array("Act" => "doPay", "order_id" => $this['order_num']), $absolute);
    }
    
    /**
    * Возвращает список файлов, прикрепленных к заказу
    * 
    * @param string | array $access - уровень доступа
    * @param bool $include_product_files - если true, то в результатах будет выведены и список файлов товаров, 
    * доступных после оплаты
    * @return \Files\Model\Orm\File[]
    */
    function getFiles($access = array('visible', 'afterpay'), $include_product_files = true)
    {
        $result = array();
        if ($this['id'] && \RS\Module\Manager::staticModuleExists('files')) {
            $file_api = new \Files\Model\FileApi();            
            if (!is_array($access)) {
                $access = (array)$access;
            }
            
            if (!$this['is_payed'] && in_array('afterpay', $access)) {
                $access = array_diff($access, array('afterpay'));
            }
            
            if ($access) {
                //Получаем файлы заказа
                $file_api->setFilter(array(
                    'link_type_class' => 'files-shoporder',
                    'link_id' => $this['id']
                ));
                $file_api->setFilter('access', $access, 'in');
                $result = $file_api->getList();
            }
            
            if ($include_product_files && in_array('afterpay', $access)) {
                //Получаем файлы товаров
                $cartitems = $this->getCart()->getCartItemsByType(\Shop\Model\Cart::TYPE_PRODUCT);
                $ids = array();
                foreach($cartitems as $cartitem) {
                    $ids[] = $cartitem['entity_id'];
                }
                
                if ($ids) {
                    $file_api->clearFilter();
                    $file_api->setFilter(array(
                        'link_type_class' => 'files-catalogproduct',
                        'access' => 'afterpay'
                    ));
                    $file_api->setFilter('link_id', $ids, 'in');
                    $result = array_merge($result, $file_api->getList());
                }
            }
        }
        return $result;
    }
    
    /**
    * Возвращает стоимость доставки, у существующего заказа
    * 
    * @return float
    */
    function getDeliveryCost()
    {
        return $this['user_delivery_cost'] ?: 0;
    }

    /**
     * Устанавливает адрес по умолчанию, если это возможно
     *
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function setDefaultAddress()
    {
        $config = \RS\Config\Loader::byModule($this);
        if ($config['use_geolocation_address']){ //Если нужно отпредление по геолокации
            //Попробуем установить адрес из города по IP адресу
            $this->setAddressFromGeoCity();
        }else{ //Если нужно простое указание адреса
            $address = new \Shop\Model\Orm\Address();

            if (!$config['require_city'] && $config['default_city']) {
                $city = new \Shop\Model\Orm\Region($config['default_city']);
                $address['city']    = $city['title'];
                $address['city_id'] = $city['city_id'];
                $this['addr_city']  = $city['title'];
            }
            if (!$config['require_region'] && $config['default_region']) {
                $region = new \Shop\Model\Orm\Region($config['default_region']);
                $this['addr_region_id'] =  $address['region_id'] = $region['id'];
            }
            if (!$config['require_country'] && $config['default_country']) {
                $country = new \Shop\Model\Orm\Region($config['default_country']);
                $this['addr_country_id'] = $address['country_id'] = $country['id'];
            }
            if (!$config['require_zipcode'] && $config['default_zipcode']){
                $this['addr_zipcode'] = $address['zipcode'] = $config['default_zipcode'];
            }
            $this->setAddress($address);
        }
        \RS\Event\Manager::fire('order.setdefaultaddress', $this);
    }

    /**
     * Пробует установить адрес по городу, который находится по IP адресу
     *
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function setAddressFromGeoCity()
    {
        $config = \RS\Config\Loader::byModule($this);
        //Подгрузим из базы магистральный город и поробуем его найти
        $city = new \Shop\Model\Orm\Region();
        /**
        * @var \Shop\Model\Orm\Region $city
        */
        if ($config['require_city']) { //Если требуется указание города
            $geo_api   = new \Main\Model\GeoIpApi();
            $city_name = $geo_api->getCityByIp($_SERVER['REMOTE_ADDR']); //Получим имя города по IP
            $city = \RS\Orm\Request::make()
                ->from(new \Shop\Model\Orm\Region())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'is_city' => 1,
                    'title' => $city_name
                ))->object();
        }


        if (!$city['id'] && $config['default_city']){ //Если по локации не нашли, но есть город по умолчанию
            $city = new \Shop\Model\Orm\Region($config['default_city']);
        }

        $address = new \Shop\Model\Orm\Address();
        if ($city){ //Если нашли город, то установим данные
            if (!$config['require_region'] && $config['default_region']) {
                $region = new \Shop\Model\Orm\Region($config['default_region']);
            }else{
                $region = $city->getParent();
            }
            if (!$config['require_country'] && $config['default_country']) {
                $country = new \Shop\Model\Orm\Region($config['default_country']);
            }else{
                $country = $region->getParent();
            }

            //Индекс города
            $zipcode = $city['zipcode'];
            if (!$config['require_zipcode'] && $config['default_zipcode']){
                $zipcode = $config['default_zipcode'];
            }
            
            $this['addr_zipcode']    = $zipcode;
            $this['addr_country_id'] = $country['id'];    
            $this['addr_region_id']  = $region['id'];    
            $this['addr_city']       = $city['title']; 
            //Добавим в адрес

            $address['zipcode']    = $zipcode;
            $address['country']    = $country['title'];
            $address['region']     = $region['title'];
            $address['city']       = $city['title'];
            $address['city_id']    = $city['id'];
            $address['region_id']  = $region['id'];
            $address['country_id'] = $country['id'];
        }else{ //Если город не найден, то проверим остальное
            if (!$config['require_region'] && $config['default_region']) {
                $region = new \Shop\Model\Orm\Region($config['default_region']);
                $this['addr_region_id'] =  $address['region_id'] = $region['id'];
            }

            if (!$config['require_country'] && $config['default_country']) {
                $country = new \Shop\Model\Orm\Region($config['default_country']);
                $this['addr_country_id'] = $address['country_id'] = $country['id'];
            }

            if (!$config['require_zipcode'] && $config['default_zipcode']){
                $this['addr_zipcode'] = $address['zipcode'] = $config['default_zipcode'];
            }
        }
        $this->setAddress($address);
    }
    
    /**
    * Возвращает адрес для отслеживания доставки заказа
    * 
    * @return string
    */
    function getTrackUrl()
    {
        return $this->getDelivery()->getTypeObject()->getTrackNumberUrl($this);
    }
    
    /**
    * Возвращает курьера, назначенного на данный заказ
    * 
    * @return \Users\Model\Orm\User
    */
    function getCourierUser()
    {
        return new \Users\Model\Orm\User($this['courier_id']);
    }

    function getLinkedDocuments()
    {
        $manager = new \Catalog\Model\DocumentLinkManager($this['id'], self::DOCUMENT_TYPE_ORDER);
        return $manager->getLinks();
    }

    function getInventoryApi()
    {
        return new \Catalog\Model\Inventory\DocumentApi();
    }
}
