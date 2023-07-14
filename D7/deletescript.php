<?php

use Bitrix\Iblock\Iblock;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use \Api\Entity\Contacts\ContactsTable;
use \Api\Entity\Contracts\ContractsTable;
use \Api\Entity\TradePoints\TradePointsTable;
use \Rest\Constants;
use \SiteCore\Core;

$affiliateIblockId = Core::getInstance()->getIblockId(Constants::IBLOCK_AFFILIATE_CODE);
$affiliateClass = Iblock::wakeUp($affiliateIblockId)->getEntityDataClass();

$companies = ContactsTable::query()
    ->where('AFFILIATE.XML_ID', 'sam')
    ->setSelect([
        'CONTRAGENT_ID' => 'ID',
        'CONTRACT_ID' => "CONTRACT.ID",
        'ADDRESS_ID' => 'ADDRESS.ID'
    ])
    ->registerRuntimeField(
        'AFFILIATE',
        new ReferenceField(
            'AFFILIATE',
            $affiliateClass,
            Join::on('this.UF_AFFILIATE', 'ref.ID')
        )
    )
    ->registerRuntimeField(
        'CONTRACT',
        new ReferenceField(
            'CONTRACT',
            ContractsTable::class,
            Join::on('this.ID', 'ref.UF_CONTACT_ID')
        )
    )
    ->registerRuntimeField(
        'ADDRESS',
        new ReferenceField(
            'ADDRESS',
            TradePointsTable::class,
            Join::on('this.ID', 'ref.UF_CONTACT_ID')
        )
    )
    ->exec();

$arAddresses = [];
$arContracts = [];
while ($company = $companies->fetch()) {
    if(!empty($company['CONTRACT_ID'])) {
        $arContracts[$company['CONTRACT_ID']] = $company['CONTRACT_ID'];
    }
    if(!empty($company['ADDRESS_ID'])) {
        $arAddresses[$company['ADDRESS_ID']] = $company['ADDRESS_ID'];
    }
}
foreach($arContracts as $idContract) {
    ContractsTable::delete($idContract);
}
foreach($arAddresses as $idAddress) {
    TradePointsTable::delete($idAddress);
}
