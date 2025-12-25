<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=market.tags
Tags=market.tpl:{PAGE_READ_TIME},{PAGE_URL},{PAGE_AUTHOR},{RELATED_PAGES},{RELATED_ROW_URL},{RELATED_ROW_TITLE},{RELATED_ROW_DESC},{RELATED_ROW_LINK_MAIN_IMAGE},{RELATED_ROW_AUTHOR}
[END_COT_EXT]
==================== */

/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0 
 * Purpose: Обработка тегов для шаблона market.tpl, включая время чтения, URL, автора, связанные страницы, их авторов и пользовательские теги.
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.market.tags.php
 * @package SeoMarketPro
 * @version 2.2.7
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.com/users/webitproff
 * @license BSD
 */


// === Секция 1: hooks, meta, tags, etc ===
defined('COT_CODE') or die('Wrong URL');

// Импорт пространства имен: используем класс UsersHelper из Cotonti для работы с пользователями
use cot\users\UsersHelper;

// === Секция 2: Подключение зависимостей ===
global $db, $db_market, $db_i18n4marketpro_pages, $db_users;

require_once cot_incfile('seomarketpro', 'plug');

require_once cot_langfile('seomarketpro', 'plug');



// === Секция 3: Инициализация переменных ===
$page_id = 0;
$page_read_time = 1;
// Имя автора для Schema.org
$page_author_name = '';
// URL профиля автора для Schema.org (author.url)
$page_author_url = '';
$page_url = '';

// ЧАСТЬ ПЕРВАЯ ===== SEOMARKETPRO_PAGE (единственное число)

// === Секция 4: Формирование данных для тегов текущей страницы товара и ее данных ===
if (isset($item) && is_array($item)) {
    $page_id = isset($item['fieldmrkt_id']) ? (int)$item['fieldmrkt_id'] : 0;

    if (isset($item['fieldmrkt_text']) && !empty($item['fieldmrkt_text'])) {
        $page_read_time = cot_estimate_read_time_marketpro($item['fieldmrkt_text']);
        $page_read_time = max(1, $page_read_time);
    }

	// --------------------------------------------------------
	// автор или продавец, владелец
	// --------------------------------------------------------
    // Создаем экземпляр UsersHelper для работы с данными автора
    $usersHelper = UsersHelper::getInstance();

    // Если указан владелец товара, получаем его полное имя и URL профиля
    if (!empty($item['fieldmrkt_ownerid'])) {
        $page_author_name = $usersHelper->getFullName($item);
        $page_author_url = $usersHelper->getUrl($item, '', false, true);
    } else {
        $page_author_name = Cot::$L['seomarketpro_unknown_author'];
        $page_author_url = '';
    }
}

// === Секция 5: Назначение тегов именно для текущей страницы товара в шаблоне market.tpl ===
// шаблоны .tpl именно в плагине мы не создаем, поэтому используем именно $t который сейчас = market.tpl 
// если бы создавали свой шаблон типа seomarketpro.related.market.tpl тогда $t использовать нельзя и писали бы $t1, $tt1 и в таком духе.
/** @var cotpl_block $t */
$t->assign([
    'SEOMARKETPRO_PAGE_OWNER' => $page_author_name, 
    'SEOMARKETPRO_PAGE_OWNER_URL' => $page_author_url, 
    'SEOMARKETPRO_PAGE_READ_TIME' => $page_read_time . ' ' . Cot::$L['seomarketpro_read_time'],
]);



// ЧАСТЬ ВТОРАЯ ===== SEOMARKETPRO_RELATED_PAGES (множественное число)
// 
// === Секция 6: Обработка связанных страниц или товаров ===
// работаем на странице текущего товара со страницами, которые хотим здесь показать, в том же market.tpl, но выводим уже в другом блоке.
// берем максимальное количество из настроек плагина и берем наши страницы из БД сортируя по дате, новые выше в списке
$max_related = max((int)Cot::$cfg['plugin']['seomarketpro']['maxrelated'], 1);
$related_pages = [];
if (isset($item['fieldmrkt_cat']) && !empty($item['fieldmrkt_cat'])) {
    $related_pages = $db->query(
        "SELECT * FROM $db_market WHERE fieldmrkt_cat = ? AND fieldmrkt_id != ? AND fieldmrkt_state = 0 ORDER BY fieldmrkt_date DESC LIMIT ?",
        [$item['fieldmrkt_cat'], $page_id,  $max_related])->fetchAll();
}


// переходим к циклу. список связаных
if (!empty($related_pages)) {
    $t->assign('SEOMARKETPRO_RELATED_PAGES', true);
    static $author_cache = [];

    foreach ($related_pages as $related_page) {
        // === Поддержка i18n4marketpro для каждой связанной страницы ===
        $related_id = (int)($related_page['fieldmrkt_id'] ?? 0);
        if (cot_plugin_active('i18n4marketpro') && $current_locale !== Cot::$cfg['defaultlang'] && $related_id > 0) {
            $rel_translation = $db->query(
                "SELECT ipage_title, ipage_desc, ipage_text 
                 FROM $db_i18n4marketpro_pages 
                 WHERE ipage_id = ? AND ipage_locale = ?",
                [$related_id, $current_locale]
            )->fetch(PDO::FETCH_ASSOC);

            if ($rel_translation) {
                if (!empty($rel_translation['ipage_title'])) $related_page['fieldmrkt_title'] = $rel_translation['ipage_title'];
                if (!empty($rel_translation['ipage_desc']))  $related_page['fieldmrkt_desc']  = $rel_translation['ipage_desc'];
                if (!empty($rel_translation['ipage_text']))  $related_page['fieldmrkt_text']  = $rel_translation['ipage_text'];
            }
        }
        $related_url = (isset($related_page['fieldmrkt_alias']) && !empty($related_page['fieldmrkt_alias']))
            ? cot_url('market', 'c=' . $related_page['fieldmrkt_cat'] . '&al=' . $related_page['fieldmrkt_alias'])
            : cot_url('market', 'id=' . ($related_page['fieldmrkt_id'] ?? 0));

        $related_image = get_seomarketpro_main_first_image($related_page['fieldmrkt_id'] ?? 0);

        $related_author_name = '';
        if (isset($related_page['fieldmrkt_ownerid']) && $related_page['fieldmrkt_ownerid'] > 0) {
            $related_owner_id = (int)$related_page['fieldmrkt_ownerid'];
            if (isset($author_cache[$related_owner_id])) {
                $related_author_data = $author_cache[$related_owner_id];
            } else {
                $related_author_data = $db->query(
                    "SELECT * FROM $db_users WHERE user_id = ?",
                    [$related_owner_id]
                )->fetch(PDO::FETCH_ASSOC);
                $author_cache[$related_owner_id] = $related_author_data ?: [];
            }

            if ($related_author_data) {
                if (!empty($related_author_data['user_firstname']) && !empty($related_author_data['user_lastname'])) {
                    $related_author_name = htmlspecialchars(
                        $related_author_data['user_firstname'] . ' ' . $related_author_data['user_lastname'],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                } elseif (!empty($related_author_data['user_name'])) {
                    $related_author_name = htmlspecialchars($related_author_data['user_name'], ENT_QUOTES, 'UTF-8');
                } else {
                    $related_author_name = Cot::$L['seomarketpro_unknown_author'];
                }
            } else {
                $related_author_name = Cot::$L['seomarketpro_unknown_author'];
            }
        } else {
            $related_author_name = Cot::$L['seomarketpro_unknown_author'];
        }

        $t->assign([
            'SEOMARKETPRO_RELATED_ROW_URL' => htmlspecialchars($related_url, ENT_QUOTES, 'UTF-8'),
            'SEOMARKETPRO_RELATED_ROW_TITLE' => htmlspecialchars($related_page['fieldmrkt_title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'SEOMARKETPRO_RELATED_ROW_DESC' => htmlspecialchars(
                cot_string_truncate(strip_tags($related_page['fieldmrkt_text'] ?? ''), 170, true, false),
                ENT_QUOTES,
                'UTF-8'
            ),
            'SEOMARKETPRO_RELATED_ROW_LINK_MAIN_IMAGE' => htmlspecialchars($related_image, ENT_QUOTES, 'UTF-8'),
            'SEOMARKETPRO_RELATED_ROW_AUTHOR' => $related_author_name,
        ]);

        $t->parse('MAIN.SEOMARKETPRO_RELATED_PAGES.SEOMARKETPRO_RELATED_ROW');
    }
	        if (!empty($related_page)) {

            $t->parse('MAIN.SEOMARKETPRO_RELATED_PAGES');
        }
}
