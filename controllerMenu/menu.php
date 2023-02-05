<?php
namespace \Rest\Controller\Services;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Rest\Constants;
use Rest\Traits\HasModuleOption;
use Rest\Traits\HasParameters;
use Exception;

/////////////////////////////////////////////////////////////////////////
//в api.php

/** Меню - получение списка типов меню */
//$routes->get(Constants::REST_API_MENU_LIST, [Menu::class, "getMenuTypes"]);

/** Меню - получение меню */
//$routes->get(Constants::REST_API_MENU_GET, [Menu::class, "getMenu"]);

/////////////////////////////////////////////////////////////////////////

/**
 * Класс-контроллер для работы с меню
 */
class Menu extends Controller {
    use HasParameters,
        HasModuleOption;

    public const DEFAULT_DEPTH_LVL = 1;

    /**
     * Настройка префильтров
     *
     * @return \array[][]
     */
    public function configureActions() {
        return [
            "getMenuTypes" => [
                "prefilters" => []
            ],
            "getMenu" => [
                "prefilters" => []
            ]
        ];
    }

    /**
     * Получение типов меню
     *
     * @return array|false
     */
    public function getMenuTypesAction() {
        try {
            $menuTypes = GetMenuTypes();
            return $menuTypes;

        } catch (Exception $ex) {
            $this->addError(new Error($ex->getMessage()));
        }

        return false;

    }

    /**
     * Получение меню
     *
     * @return array|false
     */
    public function getMenuAction() {
        global $APPLICATION;
        try {

            $typeMenu = $this->getParameter("typemenu");
            $depthLevel = intval($this->getParameter("depth_lvl"));

            if (empty($typemenu)) {
                throw new Exception(Loc::getMessage("CITFACT_REST_SERVICE_MENU_NOT_FOUND"));
            }
            if (empty($depthLevel)) {
                $depthLevel = self::DEFAULT_DEPTH_LVL;
            }

            $result = $APPLICATION->IncludeComponent("bitrix:menu", "api",
                [
                    "ALLOW_MULTI_SELECT" => "N",
                    "CHILD_MENU_TYPE" => "left",
                    "DELAY" => "N",
                    "MAX_LEVEL" => $depthLevel,
                    "MENU_CACHE_GET_VARS" => "",
                    "MENU_CACHE_TIME" => "3600000",
                    "MENU_CACHE_TYPE" => "A",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "MENU_THEME" => "green",
                    "ROOT_MENU_TYPE" => $typeMenu,
                    "USE_EXT" => "Y",
                    "COMPOSITE_FRAME_MODE" => "A",
                    "COMPOSITE_FRAME_TYPE" => "STATIC",
                    'RETURN' => 'Y'
                ],
                false
            );
            $menu = [];
            foreach ($result as $item) {
                $menu[] = [
                    'text' => $item['TEXT'],
                    'link' => $item['LINK'],
                    'depth_level' => $item['DEPTH_LEVEL'],
                    'is_parent' => $item['IS_PARENT']
                ];
            }
            return $menu;
        } catch (Exception $ex) {
            $this->addError(new Error($ex->getMessage()));
        }

        return false;

    }


}
