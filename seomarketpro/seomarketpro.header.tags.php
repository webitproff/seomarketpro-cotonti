<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=header.tags
[END_COT_EXT]
==================== */

/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0 
 * Purpose: Переопределение мета-тегов HEADER_META_DESCRIPTION и HEADER_META_KEYWORDS для шаблона header.tpl.
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.header.tags.php
 * @package SeoMarketPro
 * @version 2.1.1
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.ccom/users/webitproff
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
// Формирование описания страницы ($page_description) для meta name="description"
// ----------------------------------------------------------------------------
$page_description = '';
if (isset($item) && is_array($item)) {
    $page_description = !empty($item['fieldmrkt_metadesc'])
        ? $item['fieldmrkt_metadesc']
        : strip_tags(html_entity_decode($item['fieldmrkt_text'] ?? ''));
}


// описание товара для meta name="description"
$descriptionText = $page_description;
$page_description = cot_string_truncate(seomarketpro_descriptionText_cleaning($descriptionText), 150, true, false);


// ----------------------------------------------------------------------------
// Формирование ключевых слов ($page_keywords)
// ----------------------------------------------------------------------------
$page_keywords = '';
if (isset($item) && is_array($item)) {
    $page_keywords = !empty($item['fieldmrkt_keywords'])
        ? cot_string_truncate($item['fieldmrkt_keywords'], 255)
        : cot_extract_keywords_marketpro($item['fieldmrkt_text'] ?? '', 10);
}

// ----------------------------------------------------------------------------
// Присваивание мета-тегов в шаблон
// ----------------------------------------------------------------------------
global $env, $m;
if ($env['location'] === 'market' && ($m ?? 'main') === 'main') {
    $safe_description = htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8');
    $safe_keywords = htmlspecialchars($page_keywords, ENT_QUOTES, 'UTF-8');
    /** @var cotpl_block $t */
    $t->assign([
        'HEADER_META_DESCRIPTION' => $safe_description,
        'HEADER_META_KEYWORDS' => $safe_keywords
    ]);
}
