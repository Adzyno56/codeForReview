<?
private
function importCatalog()
{
    $header = [];
    $productProps = [];
    $filePath = $_SERVER['DOCUMENT_ROOT'] . self::PRODUCTS_PATH;

    if (file_exists($filePath)) {
        $reader = new XlsxReader($filePath);
        $data = $reader->read();
        if (!empty($data)) {
            foreach ($data as $countRow => $row) {
                if ($countRow == 0) {
                    foreach ($row as $col => $propName) {
                        $propName = str_replace('_', '', trim($propName));
                        $propCode = $this->getPropCode($propName, $productProps);
                        $productProps[$propCode] = $this->catalogProps[$propCode] ?: [];
                        $header[$col] = ['NAME' => $propName, 'CODE' => $propCode];
                    }
                    $this->createCatalogProps($productProps);
                    continue;
                }
                $this->createProduct($row, $header, $productProps);
            }
            unlink($filePath);

            $fileObj = new File($filePath);
            if (!Directory::isDirectoryExists($_SERVER['DOCUMENT_ROOT'] . self::PRODUCTS_PATH_ARCHIVE)) {
                Directory::createDirectory($_SERVER['DOCUMENT_ROOT'] . self::PRODUCTS_PATH_ARCHIVE);
            }
            $fileObj->rename($_SERVER['DOCUMENT_ROOT'] . self::PRODUCTS_PATH_ARCHIVE . $fileObj->getName() . '_' . date('Y-m-d_H:i:s'));

// Для пересчета полей разделов: ACTIVE, GLOBAL_ACTIVE
            \CIBlockSection::ReSort($this->catalogIblockId);
        }
    }
}
