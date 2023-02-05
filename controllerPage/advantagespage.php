<?php
namespace Rest\Controller\Pages;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use CFile;
use Rest\View\Helper;
use SiteCore\Core;
use CMain;
use Rest\Constants;
use Rest\Core\Cache;
use Rest\Traits\HasModuleOption;
use Rest\Traits\HasParameters;
use Exception;

///////////////////////////////////////////////////////////////////////////////////////
/// В файле web.php
///
//$routes->get(Constants::REST_API_ADVANTAGES, [AdvantagesPage::class, "getData"]);
///////////////////////////////////////////////////////////////////////////////////////

Loc::loadMessages(__FILE__);

/**
 * Класс для получения данных страницы Преимущества
 *
 * Class AdvantagesPage
 * @package Rest\Controller\Catalog
 */
class AdvantagesPage extends Controller
{
    use HasParameters, HasModuleOption;

    /** @var string директория хранения кеша */
    const CACHE_DIR = "/web/advantages/";

    /** @var string название кеша*/
    const ID_CACHE_SECTIONS = "advantages_page";

    private array $postData = [];

    private int $limit = 0;

    private int $elementId;
    private Core $core;
    private int $iblockIdBanner;
    private int $iblockIdAdvanLK;
    private int $iblockIdVideos;


    /**
     * Установка префильтров
     *
     * @return \array[][]
     */
    public function configureActions()
    {
        return [
            "getData" => [
                "prefilters" => []
            ]
        ];
    }


    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->core = Core::getInstance();
        $this->iblockIdBanner = $this->core->getIblockId(Constants::IBLOCK_ADVANTAGES_BANNER_CODE);
        $this->iblockIdAdvanLK = $this->core->getIblockId(Constants::IBLOCK_ADVANTAGES_ADVAN_LK_CODE);
        $this->iblockIdVideos = $this->core->getIblockId(Constants::IBLOCK_ADVANTAGES_VIDEOS_CODE);
        
    }


    /**
     * Получение информации для страницы гарантии
     *
     * @return false|mixed|null
     */
    public function getDataAction()
    {
        try {
            return [
              "bannerBlock" => $this->getBannerBlock(),
              "advantagesBlock" =>  $this->getAdvantagesBlock(),
              "videosBlock" => $this->getVideosBlock()
            ];
        }
        catch (Exception $ex) {
            $this->addError(new Error($ex->getMessage()));
        }

    }

    /**
     * Получение баннера страница преимуществ
     *
     * @return false|mixed|null
     */
    private function getBannerBlock() {
        try {
            $iblockId = $this->iblockIdBanner;

            return Cache::keepAlways(
                md5(serialize([$iblockId])),
                self::CACHE_DIR,
                function() use ($iblockId) {
                    $elements = Helper::getIblockInfo(
                        $iblockId,
                        [
                            "filter" => ['ACTIVE' => "Y"],
                            "select" => [
                                "title" => "NAME",
                                "image" => "PREVIEW_PICTURE",
                                'mobile_image' => "IMAGE_MOBILE.FILE.ID"
                            ],
                            "order" => ["SORT" => "ASC", "DATE_CREATE" => "DESC"],
                            "limit" => $this->limit
                        ]);
                    $result = [];

                    foreach($elements as $element) {
                        $result[] = [
                            "title" => $element['title'],
                            "image" => $element['image']["SRC"],
                            "image_webp" => $element['image_webp'],
                            "mobile_image" => $element['mobile_image']
                        ];
                    }
                    return $result;
                },
                "iblock_id_{$iblockId}"
            );

        } catch (Exception $ex) {
            $this->addError(new Error($ex->getMessage()));
        }

        return false;
    }

    /**
     * Получение преимуществ
     *
     * @return false|mixed|null
     */
    private function getAdvantagesBlock() {
        try {
            $iblockId = $this->iblockIdAdvanLK;

            return Cache::keepAlways(
                md5(serialize([$iblockId])),
                self::CACHE_DIR,
                function() use ($iblockId) {
                    $elements = Helper::getIblockInfo(
                        $iblockId,
                        [
                            "filter" => ['ACTIVE' => "Y"],
                            "select" => [
                                "title" => "NAME",
                                "icon_png" => "PREVIEW_PICTURE",
                                "text" => "PREVIEW_TEXT",
                                "icon_svg" => "IMAGE.FILE.ID"
                            ],
                            "order" => ["SORT" => "ASC", "DATE_CREATE" => "DESC"],
                        ]);
                    $result = [];
                    foreach ($elements as $element) {
                        $result[] = [
                            "title" => $element['title'],
                            "text" => $element['text'],
                            "icon_svg" => $element['icon_svg'],
                            "icon_png" => $element['icon_png']["SRC"],
                            "icon_png_webp" => $element['icon_png_webp']
                        ];
                    }

                    return $result;
                },
                "iblock_id_{$iblockId}"
            );

        } catch (Exception $ex) {
            $this->addError(new Error($ex->getMessage()));
        }

        return false;
    }

    /**
     * Получение блока видео
     *
     * @return false|mixed|null
     */
    private function getVideosBlock() {
        try {
            $iblockId = $this->iblockIdVideos;

            return Cache::keepAlways(
                md5(serialize([$iblockId])),
                self::CACHE_DIR,
                function() use ($iblockId) {
                    $elements = Helper::getIblockInfo(
                        $iblockId,
                        [
                            "filter" => ['ACTIVE' => "Y"],
                            "select" => [
                                "title" => "NAME",
                                "link" => "ADVAN_LINK.VALUE",
                                "image" => "PREVIEW_PICTURE",
                                "channel" => "CHANNEL_LINK.VALUE",
                                "fileMp4" => "FILE_MP4.FILE.ID",
                                "fileWebm" => "FILE_WEBM.FILE.ID",
                            ],
                            "order" => ["SORT" => "ASC", "DATE_CREATE" => "DESC"],
                        ]);

                    $result = [];
                    foreach ($elements as $element) {
                        $result[] = [
                            "title" => $element['title'],
                            "video" => $element['link'],
                            "image" => $element['image'],
                            "fileMp4" => $element['fileMp4'],
                            "fileWebm" => $element['fileWebm'],
                            "channel" => $element['channel']
                        ];
                    }
                    return $result;
                },
                "iblock_id_{$iblockId}"
            );

        } catch (Exception $ex) {
            $this->addError(new Error($ex->getMessage()));
        }

        return false;
    }
}
?>