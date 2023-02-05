<?php
require_once 'config.php';
ini_set('memory_limit', '512M');
use Bitrix\Main\Loader;
use Rest\Export\Writer\CSVWriter;
use SiteCore\Core;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\ElementTable;

Loader::includeModule('rest');


$dirImages = "/upload/export_images";

$headers = [
    'Имя файла',
    'Артикул товара'
];
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $dirImages)) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . $dirImages, 0755, true);
}

$writer = new CSVWriter($_SERVER["DOCUMENT_ROOT"] . $dirImages . '/export_images.csv');
$writer->useBOM = false;
$writer->writeData($headers);

$iCatalogIblockID = Core::getInstance()->getIblockId(Core::IBLOCK_CODE_CATALOG);

$arCatalogSections = SectionTable::getList([
    'filter' => [
        'IBLOCK_ID' => $iCatalogIblockID,
    ],
    'order' => [
        'left_margin' => 'asc'
    ],
    'select' => [
        'ID',
        'CODE',
        'NAME',
        'DEPTH_LEVEL',
        'IBLOCK_SECTION_ID'
    ]
])->fetchAll();

$resProducts = CIBlockElement::GetList(
    [],
    ['IBLOCK_ID' => $iCatalogIblockID],
    false, false,
    ['ID', 'IBLOCK_ID', 'PROPERTY_KOD_ARTIKULA', 'IBLOCK_SECTION_ID']
);
$arProducts = [];
while ($obProducts = $resProducts->GetNextElement()) {
    $arFields = $obProducts->GetFields();
    $arProducts[$arFields['ID']] = $arFields;
    $photos = $obProducts->GetProperty('MORE_PHOTO');
    $arProducts[$arFields['ID']]['MORE_PHOTO'] = $photos['VALUE'];
}

$pathDepth1 = '';
$pathDepth2 = '';
$pathDepth3 = '';
$countProdsWithImages = 0;
foreach ($arCatalogSections as $key => $arSection) {
    if ($arSection['DEPTH_LEVEL'] == 1) {
        $depth1ID = $arSection['ID'];
        $pathDepth1 = $_SERVER['DOCUMENT_ROOT'] . $dirImages . '/' . $arSection['NAME'];
        mkdir($pathDepth1, 0755, true);
        foreach($arProducts as $key => $product) {
            if ($arSection['ID'] == $product['IBLOCK_SECTION_ID'] && !empty($product['MORE_PHOTO'])) {
                $countProdsWithImages++;
                $pathProduct = $pathDepth1 . '/' . $product['PROPERTY_KOD_ARTIKULA_VALUE'] . '_' . $product['ID'];
                mkdir($pathProduct, 0755);
                $counter = 1;
                foreach ($product['MORE_PHOTO'] as $image) {
                    $imagePath = \CFile::GetPath($image);
                    if (!empty($imagePath)) {
                        copyImage($writer, $imagePath, $pathProduct, $product['PROPERTY_KOD_ARTIKULA_VALUE'], $counter);
                        $counter++;
                    }
                }
            }
        }
    } elseif ($arSection['DEPTH_LEVEL'] == 2) {
        if ($arSection['IBLOCK_SECTION_ID'] == $depth1ID) {
            $depth2ID = $arSection['ID'];
            $pathDepth2 = $pathDepth1 . '/' . $arSection['NAME'];
            mkdir($pathDepth2, 0755, true);
            foreach($arProducts as $key => $product) {
                if ($arSection['ID'] == $product['IBLOCK_SECTION_ID'] && !empty($product['MORE_PHOTO'])) {
                    $countProdsWithImages++;
                    $pathProduct = $pathDepth2 . '/' . $product['PROPERTY_KOD_ARTIKULA_VALUE'] . '_' . $product['ID'];
                    mkdir($pathProduct, 0755);
                    $counter = 1;
                    foreach ($product['MORE_PHOTO'] as $image) {
                        $imagePath = \CFile::GetPath($image);
                        if (!empty($imagePath)) {
                            copyImage($writer, $imagePath, $pathProduct, $product['PROPERTY_KOD_ARTIKULA_VALUE'], $counter);
                            $counter++;
                        }
                    }
                }
            }
        }
    } elseif ($arSection['DEPTH_LEVEL'] == 3) {
        if ($arSection['IBLOCK_SECTION_ID'] == $depth2ID) {
            $pathDepth3 = $pathDepth2 . '/' . $arSection['NAME'];
            mkdir($pathDepth3, 0755, true);
            foreach($arProducts as $key => $product) {
                if ($arSection['ID'] == $product['IBLOCK_SECTION_ID'] && !empty($product['MORE_PHOTO'])) {
                    $countProdsWithImages++;
                    $pathProduct = $pathDepth3 . '/' . $product['PROPERTY_KOD_ARTIKULA_VALUE'] . '_' . $product['ID'];
                    mkdir($pathProduct, 0755);
                    $counter = 1;
                    foreach ($product['MORE_PHOTO'] as $image) {
                        $imagePath = \CFile::GetPath($image);
                        if (!empty($imagePath)) {
                            copyImage($writer, $imagePath, $pathProduct, $product['PROPERTY_KOD_ARTIKULA_VALUE'], $counter);
                            $counter++;
                        }
                    }
                }
            }
        }
    }
}
$writer->writeData(['Кол-во товаров с изображениями', $countProdsWithImages]);
$writer->saveFile();

function copyImage($writer, $imagePath, $pathProduct, $productArticle, $counter = 0) {
    if (mb_strripos($imagePath, 'jpg') !== false) {
        copy($_SERVER['DOCUMENT_ROOT'] . $imagePath, $pathProduct . '/' . $productArticle . '_' . $counter . '.jpg');
        $writer->writeData([$productArticle . '_' . $counter . '.jpg', $productArticle]);
    } elseif (mb_strripos($imagePath, 'png') !== false) {
        copy($_SERVER['DOCUMENT_ROOT'] . $imagePath, $pathProduct . '/' . $productArticle . '_' . $counter . '.png');
        $writer->writeData([$productArticle . '_' . $counter . '.png', $productArticle]);
    } elseif (mb_strripos($imagePath, 'jpeg') !== false) {
        copy($_SERVER['DOCUMENT_ROOT'] . $imagePath, $pathProduct . '/' . $productArticle . '_' . $counter . '.jpeg');
        $writer->writeData([$productArticle . '_' . $counter . '.jpeg', $productArticle]);
    }
}

?>