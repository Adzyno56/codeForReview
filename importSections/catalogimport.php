<?php
/** Установка разделов товарам */
private
function setSectionsForProducts()
{

    $filePath = $_SERVER['DOCUMENT_ROOT'] . self::SECTIONS_PRODUCT_PATH;

    if (file_exists($filePath)) {

        $reader = new XlsxReader($filePath);
        $data = $reader->read();

        $headers = [];
        $arSections = [];
        $count = 0;

// Собираем в массив ид разделов товара, сортируем по артикулу
        foreach ($data as $key => $row) {
            $countSections = 0;
            if ($key == 0) {
                $headers = $row;
            } else {
                foreach ($row as $index => $value) {
                    if ($headers[$index] === 'Категория') {
                        if (!empty($value)) {
                            $arSections[$row[1]][$count][] = $this->getSectionId($value, $arSections[$row[1]][$count], $countSections);
                            $countSections++;
                        }
                    }
                }
            }
            $count++;
        }

// Формируем массив только с разделами 3 уровня
        $arProductSections = [];
        foreach ($arSections as $article => $arRowSections) {
            foreach ($arRowSections as $arSectionsIds) {
                $arProductSections[$article][] = array_pop($arSectionsIds);
            }
        }

        $this->getExistsProducts();

// Обновляем разделы у товаров
        foreach ($arProductSections as $article => $arSectionsIds) {
            $productId = $this->productsId[$article];
            $arFields = [
                'IBLOCK_ID' => $this->catalogIblockId,
                'IBLOCK_SECTION' => $arSectionsIds
            ];
            $updateIsSuccess = $this->element->Update($productId, $arFields);

            if (!$updateIsSuccess) {
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/catalog/errorsSections.log', print_r([
                    'productId' => $productId,
                    'arFields' => $arFields,
                    'ERROR' => $this->element->LAST_ERROR,
                    'DateTime' => date('Y-m-d_H:i:s'),
                ], true), FILE_APPEND | LOCK_EX);
            }
        }
        $fileObj = new File($filePath);
        if (!Directory::isDirectoryExists($_SERVER['DOCUMENT_ROOT'] . self::SECTIONS_PRODUCT_PATH_ARCHIVE)) {
            Directory::createDirectory($_SERVER['DOCUMENT_ROOT'] . self::SECTIONS_PRODUCT_PATH_ARCHIVE);
        }
        $fileObj->rename($_SERVER['DOCUMENT_ROOT'] . self::SECTIONS_PRODUCT_PATH_ARCHIVE . $fileObj->getName() . '_' . date('Y-m-d_H:i:s'));
    }
}
