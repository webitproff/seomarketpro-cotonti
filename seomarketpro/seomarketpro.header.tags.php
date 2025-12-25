<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=header.tags
[END_COT_EXT]
==================== */

/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Purpose: Переопределение мета-тегов HEADER_TITLE, HEADER_META_DESCRIPTION и HEADER_META_KEYWORDS
 *          для шаблона header.tpl с поддержкой мультиязычности (i18n4marketpro).
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.header.tags.php
 * @package SeoMarketPro
 * @version 2.2.7
 * @copyright (c) webitproff 2025
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

// ----------------------------------------------------------------------------
// Подключение функций плагина
// ----------------------------------------------------------------------------
require_once cot_incfile('seomarketpro', 'plug');

if (!function_exists('cot_string_truncate') || !function_exists('cot_extract_keywords_marketpro')) {
    return;
}

// ----------------------------------------------------------------------------
// Глобальные зависимости для мультиязычности
// ----------------------------------------------------------------------------
global $db, $db_i18n4marketpro_pages;

// ----------------------------------------------------------------------------
// Инициализация переменных страницы
// ----------------------------------------------------------------------------
$page_title = '';
$page_description = '';
$page_keywords = '';

// ----------------------------------------------------------------------------
// Обработка данных товара (если мы на странице market.main)
// ----------------------------------------------------------------------------
if (isset($item) && is_array($item)) {

    // Определяем текущую локаль пользователя
    $current_locale = Cot::$usr['lang'] ?? Cot::$cfg['defaultlang'];

    // ------------------------------------------------------------------------
    // Мультиязычность: подмена данных товара из i18n4marketpro
    // ------------------------------------------------------------------------
    if (
        cot_plugin_active('i18n4marketpro')
        && $current_locale !== Cot::$cfg['defaultlang']
        && !empty($item['fieldmrkt_id'])
    ) {
        // Загружаем перевод title / desc / text для текущей локали
        $translation = $db->query(
            "SELECT ipage_title, ipage_desc, ipage_text
             FROM $db_i18n4marketpro_pages
             WHERE ipage_id = ? AND ipage_locale = ?",
            [$item['fieldmrkt_id'], $current_locale]
        )->fetch(PDO::FETCH_ASSOC);

        // Если перевод найден — переопределяем поля товара
        if ($translation) {
            if (!empty($translation['ipage_title'])) {
                $item['fieldmrkt_title'] = $translation['ipage_title'];
                $item['fieldmrkt_metatitle'] = $translation['ipage_title'];
            }
            if (!empty($translation['ipage_desc'])) {
                $item['fieldmrkt_desc'] = $translation['ipage_desc'];
                $item['fieldmrkt_metadesc'] = $translation['ipage_desc'];
            }
            if (!empty($translation['ipage_text'])) {
                $item['fieldmrkt_text'] = $translation['ipage_text'];
            }
        }
    }

    // ------------------------------------------------------------------------
    // Формирование TITLE
    // ------------------------------------------------------------------------
    $page_title = !empty($item['fieldmrkt_metatitle'])
        ? $item['fieldmrkt_metatitle']
        : strip_tags(html_entity_decode($item['fieldmrkt_title'] ?? ''));

    // ------------------------------------------------------------------------
    // Формирование DESCRIPTION
    // ------------------------------------------------------------------------
    $rawDescription = !empty($item['fieldmrkt_metadesc'])
        ? $item['fieldmrkt_metadesc']
        : ($item['fieldmrkt_text'] ?? '');

    $page_description = cot_string_truncate(
        seomarketpro_descriptionText_cleaning($rawDescription),
        150,
        true,
        false
    );

    // ------------------------------------------------------------------------
    // Формирование KEYWORDS
    // ------------------------------------------------------------------------
    $page_keywords = !empty($item['fieldmrkt_keywords'])
        ? cot_string_truncate($item['fieldmrkt_keywords'], 255)
        : cot_extract_keywords_marketpro($item['fieldmrkt_text'] ?? '', 10);
}

// ----------------------------------------------------------------------------
// Присваивание мета-тегов в шаблон header.tpl
// ----------------------------------------------------------------------------
global $env, $m;

if ($env['location'] === 'market' && ($m ?? 'main') === 'main') {

    // Безопасное экранирование
    $safe_title = htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8');
    $safe_description = htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8');
    $safe_keywords = htmlspecialchars($page_keywords, ENT_QUOTES, 'UTF-8');

    /** @var cotpl_block $t */
    $t->assign([
        'HEADER_TITLE' => $safe_title,
        'HEADER_META_DESCRIPTION' => $safe_description,
        'HEADER_META_KEYWORDS' => $safe_keywords
    ]);
}
