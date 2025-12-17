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
 * @version 2.1.1
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.ccom/users/webitproff
 * @license BSD
 */


// === Секция 1: hooks, meta, tags, etc ===
defined('COT_CODE') or die('Wrong URL');

// === Секция 2: Подключение зависимостей ===
global $db, $db_market, $db_users;

require_once cot_incfile('seomarketpro', 'plug');

// если я тупанул - прерываем
if (!function_exists('cot_estimate_read_time_marketpro') || !function_exists('cot_string_truncate') || !function_exists('get_seomarketpro_main_first_image')) {
    return;
}

require_once cot_langfile('seomarketpro', 'plug');



// === Секция 3: Инициализация переменных ===
$page_id = 0;
$page_read_time = 1;
$page_owner_name = '';
$page_url = '';

// === Секция 4: Формирование данных для тегов текущей страницы ===
if (isset($item) && is_array($item)) {
    $page_id = isset($item['fieldmrkt_id']) ? (int)$item['fieldmrkt_id'] : 0;

    if (isset($item['fieldmrkt_text']) && !empty($item['fieldmrkt_text'])) {
        $page_read_time = cot_estimate_read_time_marketpro($item['fieldmrkt_text']);
        $page_read_time = max(1, $page_read_time);
    }

    if (isset($item['fieldmrkt_ownerid']) && $item['fieldmrkt_ownerid'] > 0) {
        $owner_id = (int)$item['fieldmrkt_ownerid'];
        $author_data = $db->query("SELECT * FROM $db_users WHERE user_id = ?", [$owner_id])->fetch(PDO::FETCH_ASSOC);

        if ($author_data) {
            if (!empty($author_data['user_firstname']) && !empty($author_data['user_lastname'])) {
                $page_owner_name = htmlspecialchars($author_data['user_firstname'] . ' ' . $author_data['user_lastname'], ENT_QUOTES, 'UTF-8');
            } elseif (!empty($author_data['user_name'])) {
                $page_owner_name = htmlspecialchars($author_data['user_name'], ENT_QUOTES, 'UTF-8');
            } else {
                $page_owner_name = Cot::$L['seomarketpro_unknown_author'];
            }
        } else {
            $page_owner_name = Cot::$L['seomarketpro_unknown_author'];
        }
    } else {
        $page_owner_name = Cot::$L['seomarketpro_unknown_author'];
    }

    global $out;
    $page_url = (isset($out['canonical_uri']) && !empty($out['canonical_uri']))
        ? COT_ABSOLUTE_URL . $out['canonical_uri']
        : cot_url('market', 'id=' . $page_id);
}

// === Секция 5: Назначение тегов именно для текущей страницы товара в шаблоне market.tpl ===
// шаблоны .tpl именно в плагине мы не создаем, поэтому используем именно $t который сейчас = market.tpl 
// если бы создавали свой шаблон типа seomarketpro.related.market.tpl тогда $t использовать нельзя и писали бы $t1, $tt1 и в таком духе.
/** @var cotpl_block $t */
$t->assign([
    'PAGE_URL' => htmlspecialchars($page_url, ENT_QUOTES, 'UTF-8'), // пока пусть будет
    'PAGE_AUTHOR' => $page_owner_name, // пока пусть будет. в селедующей версии поправлю
    'PAGE_READ_TIME' => $page_read_time . ' ' . Cot::$L['seomarketpro_read_time'],
]);

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
    $t->assign('RELATED_PAGES', true);
    static $author_cache = [];

    foreach ($related_pages as $related_page) {
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
            'RELATED_ROW_URL' => htmlspecialchars($related_url, ENT_QUOTES, 'UTF-8'),
            'RELATED_ROW_TITLE' => htmlspecialchars($related_page['fieldmrkt_title'] ?? '', ENT_QUOTES, 'UTF-8'),
            'RELATED_ROW_DESC' => htmlspecialchars(
                cot_string_truncate(strip_tags($related_page['fieldmrkt_text'] ?? ''), 170, true, false),
                ENT_QUOTES,
                'UTF-8'
            ),
            'RELATED_ROW_LINK_MAIN_IMAGE' => htmlspecialchars($related_image, ENT_QUOTES, 'UTF-8'),
            'RELATED_ROW_AUTHOR' => $related_author_name,
        ]);

        $t->parse('MAIN.RELATED_PAGES.RELATED_ROW');
    }
	        if (!empty($related_page)) {

            $t->parse('MAIN.RELATED_PAGES');
        }
}
