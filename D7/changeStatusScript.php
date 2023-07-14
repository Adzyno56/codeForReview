<?php

use Bitrix\Iblock\Iblock;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use \Rest\Constants;
use \SiteCore\Core;
use \SiteCore\User\UserTable;

try {
    $affiliateIblockId = Core::getInstance()->getIblockId(Constants::IBLOCK_AFFILIATE_CODE);
    $affiliateClass = Iblock::wakeUp($affiliateIblockId)->getEntityDataClass();

    $orders = \Bitrix\Sale\Order::loadByFilter([
        'select' => ['ID'],
        'filter' => ['AFFILIATE.XML_ID' => 'spb'],
        'runtime' => [
            new ReferenceField(
                'USER',
                UserTable::class,
                Join::on("this.USER_ID", "ref.ID")
            ),
            new ReferenceField(
                'AFFILIATE',
                $affiliateClass,
                Join::on("this.USER.CONTRAGENT.UF_AFFILIATE", "ref.ID")
            )
        ]
    ]);

    foreach($orders as $order) {
        if (stripos($order->getField('STATUS_ID'), 'RF') === false) {
            $order->setField('STATUS_ID', 'F');
            $order->save();
        }
    }
} catch (\Exception $e) {
    return $e->getMessage();
}
