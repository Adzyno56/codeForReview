<?
namespace Rest\Export;

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Localization\Loc;
use Rest\Constants;
use Rest\Export\Writer\FileWriter;
use Rest\View\Helper;
use Rest\Search\Export\YmlDocument;
use Rest\Search\Export\YmlOffer;
use SiteCore\Entity\Brand\BrandTable;

Loc::loadMessages(__FILE__);

class CatalogYmlExport extends CatalogExport {

    /** @var string расширение файла с которым работает класс */
    const EXTENSION = "xml";

    /** @var \XMLWriter */
    private $writer;

    /** @var int макисмальное количество */
    const MAX_PROPERTY_COUNT = 50;

    /** @var array шапка xml документа */
    const XMLHEADER = [
        'name' => 'ООО Иванов',
        'company' => 'ООО Иванов',
        'url' => 'https://test.ru',
        'email' => 'info@test.ru',
        'platform' => '1C-Битрикс',
        'version' => '1.0'
    ];

    /** @var array шапка товара */
    const OFFERHEADER = [
        'ID' => 'id',
        'Название' => 'name',
        'Раздел' => 'category',
        'Детальное описание' => 'description',
        'Ссылка на товар' => 'url',
        'Код артикула' => 'sku',
        'Статус' => "available",
        "VENDOR" => "vendor",
        "VENDORCODE" => "vendorCode",
        "price" => "price",
        "oldprice" => "oldprice"
    ];

    /** @var string формат даты */
    const DATE_FORMAT = "Y-m-d H:i";

    private $sections;

    public function __construct(string $fileName, int $rowIndex = 1, int $imageSize = 0)
    {
        parent::__construct($fileName, $rowIndex, $imageSize);
        $this->initWriter();
    }

    /**
     * Инициализация писателя
     */
    protected function initWriter()
    {
        $this->writer = new \XMLWriter($this->fileName);
        $this->writer->openMemory();
        $this->writer->startDocument("1.0", 'UTF-8');
        $this->writeHeader();

    }

    protected function writeHeader()
    {
        $this->writer->startElement("yml_catalog");
        $this->writer->startAttribute('date');
        $this->writer->text(date(static::DATE_FORMAT));
        $this->writer->endAttribute();
        $this->writer->startElement("shop");

        foreach (self::XMLHEADER as $headerKey => $headerItem) {
            $this->writer->startElement($headerKey);
            $this->writer->text($headerItem);
            $this->writer->endElement();
        }

        //
        $this->writer->startElement('currencies');
        $this->writer->startElement('currency');

        $this->writer->startAttribute('id');
        $this->writer->text('RUB');
        $this->writer->endAttribute();

        $this->writer->startAttribute('rate');
        $this->writer->text('1');
        $this->writer->endAttribute();


        $this->writer->endElement();
        $this->writer->endElement();

        $this->writeSections();

        $this->writer->startElement("offers");

    }

    protected function writeSections(){
        $arCatalogSections = SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => $this->iblockId,
                'ACTIVE' => 'Y',
            ],
            'order' => [
                'left_margin' => 'asc'
            ],
            'select' => [
                'ID',
                'NAME',
                'DEPTH_LEVEL',
                'IBLOCK_SECTION_ID'
            ]
        ])->fetchAll();

        $this->writer->startElement('categories');

        foreach ($arCatalogSections as $section){
            $this->sections[$section['NAME']] = $section['ID'];
            $this->writer->startElement('category');

            $this->writer->startAttribute('id');
            $this->writer->text($section['ID']);
            $this->writer->endAttribute();

            if($section['IBLOCK_SECTION_ID']){
                $this->writer->startAttribute('parentId');
                $this->writer->text($section['IBLOCK_SECTION_ID']);
                $this->writer->endAttribute();
            }

            $this->writer->text($section['NAME']);

            $this->writer->endElement();
        }

        $this->writer->endElement();
    }

    protected function write(array $iblockElement, int $columnStart = 1)
    {
        $rowData = [];

        // обработка товаров
        if ($this->headerCodes['BREND']) {
            $propertyValue = $iblockElement['BREND'] ?? '';
            if (!empty($propertyValue)) {
                $rowData["VENDOR"] = empty($propertyValue) ? null : $propertyValue;
            }
            $brands = BrandTable::getList([
                "filter" => ["UF_NAME" => $propertyValue],
                "select" => ['ID']
            ]);

            if ($brand = $brands->fetch()) {
                $rowData["VENDORCODE"] = $brand["ID"];
            }
        }
        foreach ($this->headerCodes as $value => $key) {
            $propertyValue = $iblockElement[$value] ?? '';

            // Пока непонятно какую цену брать + цены и статус только для профильных выгрузок
            if ($value == self::PRICE_FIELD) {
                $key = "price";
            } elseif ($value == self::BASE_PRICE_FIELD){
                $key = "oldprice";
            } elseif ($value == self::STATUS_FIELD) {
                $key = Loc::getMessage("CITFACT_REST_CATALOG_EXPORT_STATUS_FIELD_NAME");
            }


            if (!is_array($propertyValue)) {
                $propertyValue = strip_tags($propertyValue);
                if (!empty($propertyValue)) { // - не писать пустые атрибуты в выгрузку
                    $rowData[$key] = empty($propertyValue) ? null : $propertyValue;
                }
                continue;
            }
            if ($value !== $this->properties->getImgProperty()) {
                $propertyValue = $this->setValuesDelimiter($propertyValue);
                $rowData[$key] = empty($propertyValue) ? null : $propertyValue;
                continue;
            }
            //  обработка множественных значений, для изображений
            foreach ($propertyValue as $element) { // множественные значения
                $rowData[$key][] = empty($element) ? $element : Helper::prepareFullWebAddress($element); // Только ли у изображений? Проверить
            }

        }

        if (!empty($rowData)) {
            $this->rowIndex++;
        }

        /**
         * запись в xml
         * @todo Надо подумать над обработкой множественных св-тв,если есть помимо изображений
         */

        $this->writer->startElement("offer");
        if ($rowData["ID"]) {
            $this->writer->startAttribute('id');
            $this->writer->text($rowData["ID"]);
            $this->writer->endAttribute();
            unset($rowData["ID"]);
        }
        if ($rowData['Статус']) {
            if ($rowData['Статус'] == 'В наличии' || $rowData['Статус'] == 'Под заказ') {
                $this->writer->startAttribute('available');
                $this->writer->text("true");
                $this->writer->endAttribute();
            } else {
                $this->writer->startAttribute('available');
                $this->writer->text("false");
                $this->writer->endAttribute();
            }
        }

        foreach ($rowData as $key => $value) {
            if (strlen(self::OFFERHEADER[$key]) > 0) {
                $key = self::OFFERHEADER[$key];
                if ($key == "category") {
                    $arSections = explode('/', $value);
                    $value = $this->sections[array_pop($arSections)];
                    $key = 'categoryId';
                }
                if ($key == "price") {
                    $this->writer->startElement('currencyId');
                    $this->writer->text("RUB");
                    $this->writer->endElement();
                }
                $this->writer->startElement($key);
                $this->writer->text($value);
                $this->writer->endElement();
            }
            else {
                if ($key == "Изображения") {
                    if (is_array($value)) {
                        foreach ($value as $elem) {
                            $this->writer->startElement('picture');
                            $this->writer->text($elem);
                            $this->writer->endElement();
                        }
                    } else {
                        $this->writer->text($value);
                    }
                } else {
                    $this->writer->startElement('param');

                    if (mb_stripos($key, ", ") !== false) {
                        $arKey = explode(", ", $key);
                        $this->writer->startAttribute('unit');
                        $this->writer->text($arKey[1]);
                        $this->writer->endAttribute();
                    }

                    $this->writer->startAttribute('name');
                    $this->writer->text($key);
                    $this->writer->endAttribute();

                    if (is_array($value)) {
                        $this->writer->startElement('values');
                        foreach ($value as $elem) {
                            $this->writer->startElement('value');
                            $this->writer->text($elem);
                            $this->writer->endElement();
                        }
                        $this->writer->endElement();
                    } else {
                        $this->writer->text($value);
                    }
                    $this->writer->endElement();
                }
            }
        }

        $this->writer->endElement();
    }

    protected function saveFile()
    {
        $this->writer->endElement();
        $this->writer->endElement();
        $this->writer->endDocument();

        file_put_contents($this->fileName, print_r($this->writer->outputMemory(), true));
    }
}



?>
