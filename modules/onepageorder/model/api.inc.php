<?php
namespace OnePageOrder\Model;

use Catalog\Model\WareHouseApi;
use RS\Application\Auth;
use RS\Config\Loader as ConfigLoader;
use RS\Exception;
use RS\Helper\Tools as HelperTools;
use RS\Http\Request as HttpRequest;
use RS\Site\Manager as SiteManager;
use Shop\Model\AddressApi;
use Shop\Model\Orm\Address;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\Region;
use Shop\Model\RegionApi;
use Users\Model\Orm\User;

/**
 * Api для работы оформления заказа на одной странице
 */
class Api
{
    protected $adresses_by_user = array(); //Массивы адресов с ключом по пользователю

    /**
     * Возвращает доступные склады для типа доставки - самовывоз
     */
    public function getWarehouses()
    {
        return WareHouseApi::getPickupWarehousesPoints();
    }

    /**
     * Проверяет сведения о полях пользователя и сведения о адресе, и если есть ошибки добавляет их в объект заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \RS\Config\UserFieldsManager $order_fields_manager - менеджер полей заказа
     * @param \RS\Config\UserFieldsManager $reg_fields_manager - менеджер полей регистрации
     * @return void
     * @throws Exception
     */
    public function checkUserAndAddressFields(Order $order, $order_fields_manager, $reg_fields_manager)
    {
        $sysdata = array('step' => 'address');
        $work_fields = $order->useFields($sysdata + $_POST);

        if ($order['only_pickup_points']) { //Если только самовывоз то исключим поля
            $work_fields = array_diff($work_fields, array('addr_country_id', 'addr_region', 'addr_region_id', 'addr_city', 'addr_zipcode', 'addr_address', 'use_addr'));
            $order['use_addr'] = 0;
        }

        $order->setCheckFields($work_fields);
        $order->checkData($sysdata, null, null, $work_fields);
        $order['userfields'] = serialize($order['userfields_arr']);

        //Авторизовываемся
        if ($order['user_type'] == 'user') {
            if (!Auth::login($order['login'], $order['password'])) {
                $order->addError(t('Неверный логин или пароль'), 'login');
            } else {
                $order['user_type'] = '';
                $order['__code']->setEnable(false);
            }
        }

        $login = HttpRequest::commonInstance()->request('ologin', TYPE_BOOLEAN); //Предварительная авторизация
        if (!$login) {
            //Проверяем пароль, если пользователь решил задать его вручную. (при регистрации)
            if (in_array($order['user_type'], array('person', 'company')) && !$order['reg_autologin']) {
                if (($pass_err = User::checkPassword($order['reg_openpass'])) !== true) {
                    $order->addError($pass_err, 'reg_openpass');
                }

                if (strcmp($order['reg_openpass'], $order['reg_pass2'])) {
                    $order->addError(t('Пароли не совпадают'), 'reg_openpass');
                }
            }

            //Сохраняем дополнительные сведения о пользователе
            $uf_err = $reg_fields_manager->check($order['regfields']);
            if (!$uf_err) {
                //Переносим ошибки в объект order
                foreach ($reg_fields_manager->getErrors() as $form => $errortext) {
                    $order->addError($errortext, $form);
                }
            }

            //Регистрируем пользователя, если нет ошибок
            if (in_array($order['user_type'], array('person', 'company'))) {

                $new_user = new User();
                $allow_fields = array('reg_name', 'reg_surname', 'reg_midname', 'reg_fio', 'reg_phone', 'reg_e_mail', 'reg_openpass', 'reg_company', 'reg_company_inn');
                $reg_fields = array_intersect_key($order->getValues(), array_flip($allow_fields));

                $new_user->getFromArray($reg_fields, 'reg_');
                $new_user['data'] = $order['regfields'];
                $new_user['is_company'] = (int)($order['user_type'] == 'company');

                if (!$new_user->validate()) {
                    foreach ($new_user->getErrorsByForm() as $form => $errors) {
                        $order->addErrors($errors, 'reg_' . $form);
                    }
                }

                if (!$order->hasError()) {
                    if ($order['reg_autologin']) {
                        $new_user['openpass'] = HelperTools::generatePassword(6);
                    }

                    if ($new_user->create()) {
                        if (Auth::login($new_user['login'], $new_user['pass'], true, true)) {
                            $order['user_type'] = ''; //Тип регитрации - не актуален после авторизации
                            $order['__code']->setEnable(false);
                        } else {
                            throw new Exception(t('Не удалось авторизоваться под созданным пользователем.'));
                        }
                    } else {
                        $order->addErrors($new_user->getErrorsByForm('e_mail'), 'reg_e_mail');
                        $order->addErrors($new_user->getErrorsByForm('login'), 'reg_login');
                    }
                }
            }

            //Если заказ без регистрации пользователя
            if ($order['user_type'] == 'noregister') {
                //Получим данные
                $shop_config = $this->getShopModuleConfig();
                $order['user_fio'] = HttpRequest::commonInstance()->request('user_fio', TYPE_STRING);
                $order['user_email'] = HttpRequest::commonInstance()->request('user_email', TYPE_STRING);
                $order['user_phone'] = HttpRequest::commonInstance()->request('user_phone', TYPE_STRING);

                //Проверим данные
                if (empty($order['user_fio'])) {
                    $order->addError(t('Укажите, пожалуйста, Ф.И.О.'), 'user_fio');
                }
                if ($shop_config['require_email_in_noregister'] && !filter_var($order['user_email'], FILTER_VALIDATE_EMAIL)) {
                    $order->addError(t('Укажите, пожалуйста, E-mail'), 'user_email');
                }
                if ($shop_config['require_phone_in_noregister'] && empty($order['user_phone'])) {
                    $order->addError(t('Укажите, пожалуйста, Телефон'), 'user_phone');
                }
            }

            //Сохраняем дополнительные сведения
            $uf_err = $order_fields_manager->check($order['userfields_arr']);
            if (!$uf_err) {

                //Переносим ошибки в объект order
                foreach ($order_fields_manager->getErrors() as $form => $errortext) {
                    $order->addError($errortext, $form);
                }
            }

            //Сохраняем адрес
            $current_user = Auth::getCurrentUser();
            if (!$order->hasError() && $order['use_addr'] == 0 && !$order['only_pickup_points']) {

                $address = new Address();
                $address->getFromArray($order->getValues(), 'addr_');
                $address['user_id'] = $current_user['id'];
                if ($address->insert()) {
                    $order['use_addr'] = $address['id'];
                }
            }

            //Все успешно, присвоим этого пользователя заказу
            if (!$order->hasError()) {
                $order['user_id'] = $current_user['id'];
            }
        }
    }

    /**
     * Устанавливает данные об адресе для заказа на основе входящих данных от формы
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \RS\Http\Request $url - объект запроса
     * @return Address
     */
    public function getOrderRightAddress(Order $order, HttpRequest $url)
    {
        $use_address = $url->request('use_addr', TYPE_STRING, false);// Флаг - использовать существующий адрес

        if ($use_address === "0") {
            $tmp_adress = new Address();
            //Установка страны
            $tmp_adress['country_id'] = $url->request('addr_country_id', TYPE_STRING, null);
            if ($tmp_adress['country_id']) {
                $country = new Region($tmp_adress['country_id']);
                $tmp_adress['country'] = $country['title'];
            }
            $tmp_adress['country_id'] = $url->request('addr_country_id', TYPE_STRING, null);
            //Установка региона
            $tmp_adress['region_id'] = $url->request('addr_region_id', TYPE_STRING, null);
            if ($tmp_adress['region_id']) {
                $region = new Region($tmp_adress['region_id']);
                $tmp_adress['region'] = $region['title'];
            }
            $tmp_adress['zipcode'] = $url->request('addr_zipcode', TYPE_STRING, null);
            $tmp_adress['city'] = $url->request('addr_city', TYPE_STRING, null);
            $tmp_adress['address'] = $url->request('addr_address', TYPE_STRING, null);

            //Поищем id города и запишем
            $api = new RegionApi();
            $api->setFilter('title', $tmp_adress['city']);
            $api->setFilter('site_id', SiteManager::getSiteId());
            $api->setFilter('is_city', 1);
            $city = $api->getFirst();
            $tmp_adress['city_id'] = $city ? $city['id'] : null;

            return $tmp_adress;
        } elseif ($use_address > 0) {//Если передан через запос точный адрес, то подгрузим его
            return new Address($use_address);
        } else { //Если ничего не передавалось
            //Смотрим, а есть ли у нас уже готовые адреса
            if (Auth::isAuthorize()) {
                $user = Auth::getCurrentUser();
                $adress_list = $this->getOrderAdressesByUserId($user['id']);
                //Возмём только первый адрес из списка
                return $adress_list ? $adress_list[0] : new Address;
            }
            //Для остальных случаев
            $address = $order->getAddress(); //Получим текущий установленный адрес или пустой объект
            return $address;
        }
    }

    /**
     * Проверяет сведения о полях доставки, и если есть ошибки добавляет их в объект заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param array|null $delivery_extra - дополнительные данные по доставке для добавления в заказ
     * @param integer $warehouse_id - id выбранного склада
     * @return void
     */
    public function checkDeliveryFields(Order $order, $delivery_extra = null, $warehouse_id = 0)
    {
        $sysdata = array('step' => 'delivery');
        $work_fields = $order->useFields($sysdata + $_POST);
        $order->setCheckFields($work_fields);

        if ($order->checkData($sysdata, null, null, $work_fields)) {
            if ($delivery_extra) {
                $order->addExtraKeyPair('delivery_extra', $delivery_extra);
            }
            $order['warehouse'] = $warehouse_id;
        }
    }

    /**
     * Проверяет сведения о полях оплаты, и если есть ошибки добавляет их в объект заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @return void
     */
    public function checkPaymentFields(Order $order)
    {
        $sysdata = array('step' => 'pay');
        $work_fields = $order->useFields($sysdata + $_POST);
        $order->setCheckFields($work_fields);
        $order->checkData($sysdata, null, null, $work_fields);
    }

    /**
     * Возвращает список доступых объектов адресов для данного пользователя
     *
     * @param integer $user_id - id пользователя
     * @return Address[]
     */
    public function getOrderAdressesByUserId($user_id)
    {
        if (!isset($this->adresses_by_user[$user_id])) {
            $address_api = new AddressApi();
            $address_api->setFilter('user_id', $user_id);
            $address_api->setFilter('deleted', 0);
            $this->adresses_by_user[$user_id] = $address_api->getList();
        }
        return $this->adresses_by_user[$user_id];
    }

    /**
     * Возвращает ID Страны по умолчанию
     *
     * @return int
     */
    public function getFirstCountryId()
    {
        $region_api = new RegionApi();
        $region_api->setFilter('parent_id', 0);
        return $region_api->queryObj()->limit(1)->exec()->getOneField('id');
    }

    /**
     * Возвращает ID Региона по умолчанию
     *
     * @param int $country_id - id страны
     * @return int|null
     */
    public function getFirstRegionId($country_id)
    {
        if ($country_id == 0) return null;

        $region_api = new RegionApi();
        $region_api->setFilter('parent_id', $country_id);
        return $region_api->queryObj()->limit(1)->exec()->getOneField('id');
    }

    /**
     * Возвращает объект с конфигурацией модуля
     *
     * @return \OnePageOrder\Config\File
     */
    public function getModuleConfig()
    {
        return ConfigLoader::byModule($this);
    }

    /**
     * Возвращает объект с конфигурацией модуля магазин
     *
     * @return \Shop\Config\File
     */
    public function getShopModuleConfig()
    {
        return ConfigLoader::byModule('shop');
    }

    /**
     * Возвращает статический список с темами оформления по умолчанию
     *
     * @return string[]
     */
    public static function staticGetDefaultTemplates()
    {
        return array(
            'default' => t('Классическая'),
            'perfume' => t('Воздушная'),
            'fashion' => t('Молодежная'),
            'young' => t('Детская'),
            'flatlines' => t('Современная'),
        );
    }
}
