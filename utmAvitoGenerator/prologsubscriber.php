<?

/*public static function getSubscribedEvents() {

    return [
        ['module' => 'main', 'event' => 'OnBeforeProlog', 'method' => 'addButton'],
    ];
}*/

// Создание кнопки для менеджеров
public function addButton() {
    global $USER;
    $userId = $USER->GetID();
    if (!empty($userId)) {
        $core = Core::getInstance();
        $arGroupsUser = \CUser::GetUserGroup($userId);
        $rsGroups = \CGroup::GetList(($by="c_sort"), ($order="desc"), ["STRING_ID" => $core::MANAGER_AVITO]);
        $arGroup = $rsGroups->Fetch();
        $checkManager = in_array($arGroup["ID"], $arGroupsUser);
        if ($checkManager) {
            global $APPLICATION;
            echo "
                    <script>
                        function avitoUrl() {
                            let userId = $userId
                            let href = window.location.href;
                            let subd = getSubdomain(window.location.hostname);
                            if (!subd) {
                                subd = 'spb';
                            } else {
                                let sub = subd.split('.');
                                if (sub) {
                                    subd = sub[0];
                                } 
                            }
                            if (window.location.search) {
                                href += '&' + 'utm_source=avito&utm_medium=cpc&utm_campaign=' + subd + '&utm_term=' + userId + '&rs=avito_' + subd + '_' + userId;
                            } 
                            else {
                               href += '?' + 'utm_source=avito&utm_medium=cpc&utm_campaign=' + subd + '&utm_term=' + userId + '&rs=avito_' + subd + '_' + userId; 
                            }
                            window.navigator.clipboard.writeText(href);
                            if(window.Notification && Notification.permission !== 'denied') {
                                Notification.requestPermission(function(status) {
                                    var n = new Notification('Ссылка с utm-меткой', {
                                        body: href
                                    });
                                    setTimeout(function(){
                                        n.close();
                                    },12000);
                                });
                            }
                        }
                        function getSubdomain(hostname) {
                            var regexParse = new RegExp('[a-z\-0-9]{2,63}\.[a-z\.]{2,5}$');
                            var urlParts = regexParse.exec(hostname);
                            return hostname.replace(urlParts[0],'').slice(0, -1);
                        }
                    </script>
                ";

            $APPLICATION->AddPanelButton(
                Array(
                    "ID" => "avitourl",
                    "TEXT" => "Авито ссылка",
                    "TYPE" => "BIG",
                    "MAIN_SORT" => 100,
                    "SORT" => 10,
                    "HREF" => "javascript:avitoUrl()",
                    "ICON" => "icon-class",
                    "SRC" => "/bitrix/images/fileman/panel/web_form.gif",
                    "HINT" => array(
                        "TITLE" => "Ссылка с utm-меткой",
                        "TEXT" => "Кликните по кнопке для копирования ссылки"
                    ),
                ),
                );
        }
    }
}