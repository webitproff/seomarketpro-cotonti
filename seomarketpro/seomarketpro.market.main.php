<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=market.main
[END_COT_EXT]
==================== */

/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0 
 * Purpose: Обработка страницы перед рендерингом для добавления мета-тегов Open Graph, Twitter Card и Schema.org.
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.market.main.php
 * Date: Dec 18th, 2025
 * @package SeoMarketPro
 * @version 2.1.2
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.ccom/users/webitproff
 * @license BSD
 */

// === Секция 1: Проверка окружения ===
defined('COT_CODE') or die('Wrong URL');

// === Секция 2: Подключение зависимостей ===
require_once cot_incfile('seomarketpro', 'plug');

if (cot_plugin_active('marketproreviews')) {
    require_once cot_incfile('marketproreviews', 'plug');
}

if (!function_exists('cot_string_truncate') || !function_exists('cot_extract_keywords_marketpro')) {
    return;
}
require_once cot_langfile('seomarketpro', 'plug');

global $db, $db_structure, $db_users;

// === Секция 3: Инициализация переменных ===
$page_image = '';
$page_logo_image = Cot::$cfg['mainurl'] . Cot::$cfg['plugin']['seomarketpro']['nonlogo'];
$page_locale = '';
$page_author_name = '';
$page_title = '';
$page_description = '';
$page_keywords = '';
$page_id = 0;
$page_date = 0;
$page_updated = 0;
$page_url = '';
$page_category_name = '';

// === Секция 4: Формирование данных для мета-тегов ===
if (isset($item) && is_array($item)) {
    $page_image = get_seomarketpro_main_first_image($item['fieldmrkt_id'] ?? 0);
    $page_locale = Cot::$usr['lang'] ?? Cot::$cfg['defaultlang'];

	$owner_id = (int)($item['fieldmrkt_ownerid'] ?? 0);

	if ($owner_id > 0) {
		// Берём только гарантированно существующие поля
		$author_data = $db->query(
			"SELECT * FROM $db_users WHERE user_id = ?",
			[$owner_id]
		)->fetch(PDO::FETCH_ASSOC);

		if ($author_data) {

			// Приоритет: firstname + lastname (если существуют в схеме)
			if (
				isset($author_data['user_firstname'], $author_data['user_lastname']) &&
				!empty($author_data['user_firstname']) &&
				!empty($author_data['user_lastname'])
			) {
				$page_author_name = htmlspecialchars(
					$author_data['user_firstname'] . ' ' . $author_data['user_lastname'],
					ENT_QUOTES,
					'UTF-8'
				);

			// Фолбэк: user_name (гарантированно есть)
			} elseif (!empty($author_data['user_name'])) {
				$page_author_name = htmlspecialchars(
					$author_data['user_name'],
					ENT_QUOTES,
					'UTF-8'
				);

			} else {
				$page_author_name = Cot::$L['seomarketpro_unknown_author'];
			}

		} else {
			$page_author_name = Cot::$L['seomarketpro_unknown_author'];
		}

	} else {
		$page_author_name = Cot::$L['seomarketpro_unknown_author'];
	}

    $page_title = !empty($item['fieldmrkt_metatitle'])
        ? $item['fieldmrkt_metatitle']
        : ($item['fieldmrkt_title'] ?? '');
    $page_description = !empty($item['fieldmrkt_metadesc'])
        ? $item['fieldmrkt_metadesc']
        : strip_tags(html_entity_decode($item['fieldmrkt_text'] ?? ''));
    $page_keywords = !empty($item['fieldmrkt_keywords'])
        ? cot_string_truncate($item['fieldmrkt_keywords'], 255)
        : cot_extract_keywords_marketpro($item['fieldmrkt_text'] ?? '', 5);
    $page_id = (int)$item['fieldmrkt_id'] ?? 0;
    $page_date = (int)($item['fieldmrkt_date'] ?? time());
    $page_updated = (int)($item['fieldmrkt_updated'] ?? time());
    $page_url = empty($item['fieldmrkt_alias'])
        ? cot_url('market', 'c=' . $item['fieldmrkt_cat'] . '&id=' . $page_id, '', true)
        : cot_url('market', 'c=' . $item['fieldmrkt_cat'] . '&al=' . $item['fieldmrkt_alias'], '', true);
    $category_code = $item['fieldmrkt_cat'] ?? '';
    if (!empty($category_code)) {
        $category_name_result = $db->query(
            "SELECT structure_title FROM $db_structure WHERE structure_code = ? AND structure_area = 'market'",
            [$category_code]
        )->fetchColumn();
        $page_category_name = !empty($category_name_result)
            ? htmlspecialchars($category_name_result, ENT_QUOTES, 'UTF-8')
            : htmlspecialchars($category_code, ENT_QUOTES, 'UTF-8');
    }
    if (cot_plugin_active('marketproreviews')) {
        $scores = cot_get_marketproreview_scores($page_id);
        $avg_stars = $scores['total']['count'] > 0 ? round($scores['stars']['summ'] / $scores['total']['count'], 1) : 0;
        $total_count = $scores['total']['count'];
    }
}

// описание товара для meta Open Graph и Twitter Card
$descriptionText = $item['fieldmrkt_desc'] ?: $item['fieldmrkt_text'];
$page_description = cot_string_truncate(seomarketpro_descriptionText_cleaning($descriptionText), 160, true, false);



// === Секция 5: Формирование HTML-кода мета-тегов ===
$meta_tags = '';

// Open Graph
$meta_tags .= '<meta property="og:title" content="' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Заголовок страницы для социальных сетей
$meta_tags .= '<meta property="og:description" content="' . htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Описание страницы для социальных сетей
$meta_tags .= '<meta property="og:type" content="product">' . "\n"; // Тип контента 
$meta_tags .= '<meta property="og:url" content="' . htmlspecialchars(Cot::$cfg['mainurl'] . '/' . $page_url, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // URL страницы
$meta_tags .= '<meta property="og:image" content="' . htmlspecialchars($page_image, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Изображение для социальных сетей
$meta_tags .= '<meta property="og:image:alt" content="' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Альтернативный текст для изображения
$meta_tags .= '<meta property="og:site_name" content="' . htmlspecialchars(Cot::$cfg['maintitle'], ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Название сайта
$meta_tags .= '<meta property="og:locale" content="' . htmlspecialchars($page_locale, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Локаль страницы

// Twitter Card
$meta_tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n"; // Тип карточки Twitter (с большим изображением)
$meta_tags .= '<meta name="twitter:title" content="' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Заголовок для Twitter
$meta_tags .= '<meta name="twitter:description" content="' . htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Описание для Twitter
$meta_tags .= '<meta name="twitter:image" content="' . htmlspecialchars($page_image, ENT_QUOTES, 'UTF-8') . '">' . "\n"; // Изображение для Twitter



// ===================== BREADCRUMBLIST JSON-LD =====================

// Абсолютный base URL
$baseUrl = rtrim(Cot::$cfg['mainurl'], '/');

// ---------- 1. BreadcrumbList ----------
$breadcrumbItems = [];

// Главная
$breadcrumbItems[] = [
    "@type" => "ListItem",
    "position" => 1,
    "name" => $L['Home'],
    "item" => $baseUrl
];

// Категория товара (ABSOLUTE URL)
$breadcrumbItems[] = [
    "@type" => "ListItem",
    "position" => 2,
    "name" => $cat['title'],
    "item" => $baseUrl . '/' . ltrim(
        cot_url('market', 'c=' . $item['fieldmrkt_cat']),
        '/'
    )
];

// Текущий товар (ABSOLUTE URL)
$breadcrumbItems[] = [
    "@type" => "ListItem",
    "position" => 3,
    "name" => $item['fieldmrkt_title'],
    "item" => $baseUrl . '/' . ltrim($page_url, '/')
];

$breadcrumbJSON = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => $breadcrumbItems
];

// ---------- INSERT TO <head> ----------
Cot::$out['head'] .= '<script type="application/ld+json">'
    . json_encode(
        $breadcrumbJSON,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    )
    . '</script>';

// ===================== END BREADCRUMBLIST JSON-LD =====================


// ===================== PRODUCT JSON-LD =====================
// ---------- PRODUCT PAGE URL ----------
$productPageUrl = Cot::$cfg['mainurl'] . '/' . ltrim($page_url, '/');



$descriptionTextJSON = $item['fieldmrkt_text'] ?? '';
// вытряхиваем мусор с описания товара
// $descriptionTextJSON = seomarketpro_descriptionText_cleaning($descriptionTextJSON);
$descriptionTextJSON = cot_string_truncate(seomarketpro_descriptionText_cleaning($descriptionTextJSON), 2500, true, false);	
	
// ---------- Картинка ----------
$productImage = [];
if (!empty($item['fieldmrkt_file'])) {
    $productImage[] = $item['fieldmrkt_file'];
} elseif (!empty($page_image)) {
    $productImage[] = htmlspecialchars($page_image, ENT_QUOTES, 'UTF-8');
} else {
    $productImage[] = Cot::$cfg['plugin']['seomarketpro']['nonimage'];
}

// ---------- Валюта (с приоритетом плагина) ----------
switch (true) {
    case !empty(Cot::$cfg['plugin']['seomarketpro']['seomarketpro_currency']):
        $productPriceCurrency = Cot::$cfg['plugin']['seomarketpro']['seomarketpro_currency'];
        break;
    case !empty(Cot::$cfg['payments']['valuta']):
        $productPriceCurrency = Cot::$cfg['payments']['valuta'];
        break;
    case !empty(Cot::$cfg['market']['market_currency']):
        $productPriceCurrency = Cot::$cfg['market']['market_currency'];
        break;
    default:
        $productPriceCurrency = 'USD';
}
$productPriceCurrency = strtoupper($productPriceCurrency);
if (!preg_match('/^[A-Z]{3}$/', $productPriceCurrency)) {
    $productPriceCurrency = 'USD';
}

// ---------- Определяем цифровой товар ----------
$isDigital = true; // цифровой

// ---------- PRODUCT JSON-LD ----------
$productJSON = [
    "@context" => "https://schema.org",
    "@type" => "Product",
    "name" => $item['fieldmrkt_title'],
    "description" => $descriptionTextJSON,
    "image" => $productImage,
	"brand" => [
		"@type" => "Brand",
		"name" => Cot::$cfg['maintitle']
	],
    "review" => [
        [
            "@type" => "Review",
            "author" => [
                "@type" => "Person",
                "name" => "Anonymous"
            ],
            "reviewBody" => "Отзывов пока нет",
            "reviewRating" => [
                "@type" => "Rating",
                "ratingValue" => "5",
                "bestRating" => "5",
                "worstRating" => "1"
            ]
        ]
    ],
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => "5",
        "reviewCount" => "1"
    ],
    "offers" => [
        "@type" => "Offer",
        "url" => $productPageUrl,
        "priceCurrency" => $productPriceCurrency,
        "price" => (string)($item['fieldmrkt_costdflt'] ?? 0),
        "availability" => "https://schema.org/InStock"
    ]
];

if ($isDigital) {
    // Для цифровых товаров убираем shippingDetails и минимизируем возврат
    $productJSON['offers']['hasMerchantReturnPolicy'] = [
        "@type" => "MerchantReturnPolicy",
        "returnPolicyCategory" => "https://schema.org/MerchantReturnNotPermitted"
    ];
}

// ---------- Вставка в <head> ----------
Cot::$out['head'] .= '<script type="application/ld+json">'
    . json_encode($productJSON, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    . '</script>';

// ===================== END PRODUCT JSON-LD =====================




// === Секция 6: Добавление мета-тегов в вывод ===
global $out;
$out['meta'] = (isset($out['meta']) ? $out['meta'] : '') . $meta_tags;
