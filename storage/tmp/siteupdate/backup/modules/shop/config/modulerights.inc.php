<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Config;

use \RS\AccessControl\Right;
use \RS\AccessControl\RightGroup;

class ModuleRights extends \RS\AccessControl\DefaultModuleRights
{
    const
        RIGHT_ADD_FUNDS = 'add_funds',
        RIGHT_SEND_RECEIPT = 'send_receipt',
        RIGHT_CORRECTION_RECEIPT = 'correction_receipt',
        RIGHT_REFUND_RECEIPT = 'refund_receipt';
    
    protected function getSelfModuleRights()
    {
        return array(
            new Right(self::RIGHT_READ, t('Чтение')),
            new Right(self::RIGHT_CREATE, t('Создание')),
            new Right(self::RIGHT_UPDATE, t('Изменение')),
            new Right(self::RIGHT_DELETE, t('Удаление')),
            new Right(self::RIGHT_ADD_FUNDS, t('Начисление средств')),
            new RightGroup('group_receipt', t('Операции с чеками'), array(
                new Right(self::RIGHT_SEND_RECEIPT, t('Отправка чека')),
                new Right(self::RIGHT_CORRECTION_RECEIPT, t('Отправка чека коррекции')),
                new Right(self::RIGHT_REFUND_RECEIPT, t('Отправка чека возврата')),
            )),
        );
    }
}
