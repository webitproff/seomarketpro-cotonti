<?php
/* ==================== 
[BEGIN_COT_EXT]
Hooks=market.main
[END_COT_EXT]
==================== */
// Начало конфигурации расширения Cotonti: указываем хук, в котором плагин будет работать (market.main - карточка товара, главное)


// Документация плагина: описание, версия, автор и лицензия
/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * + Поддержка мультиязычности через i18n4marketpro
 * Purpose: Обработка страницы перед рендерингом для добавления мета-тегов Open Graph, Twitter Card и Schema.org.
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.market.main.php
 * @package SeoMarketPro
 * @version 2.2.7
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.com/users/webitproff
 * @license BSD
 */

// Импорт пространства имен: используем класс UsersHelper из Cotonti для работы с пользователями
use cot\users\UsersHelper;

// Секция 1: 
// Проверка окружения — убеждаемся, что скрипт запущен внутри Cotonti, иначе выходим
defined('COT_CODE') or die('Wrong URL');

// Секция 2: 
// Подключение зависимостей — подключаем основной файл плагина seomarketpro
require_once cot_incfile('seomarketpro', 'plug');
// Подключаем языковой файл плагина для использования локализованных строк
require_once cot_langfile('seomarketpro', 'plug');

// Проверяем, активен ли плагин marketproreviews, и если да, подключаем его файл
if (cot_plugin_active('marketproreviews')) {
    require_once cot_incfile('marketproreviews', 'plug');
}

// Проверяем наличие необходимых функций; если их нет, выходим из плагина
if (!function_exists('cot_string_truncate') 
	|| !function_exists('seomarketpro_descriptionText_cleaning')
    || !function_exists('get_seomarketpro_main_first_image')
	) {
		return;
	}



// Объявляем глобальные переменные базы данных, включая таблицы для мультиязычности i18n4marketpro
global $db, $db_structure, $db_users, $db_i18n4marketpro_pages, $db_i18n4marketpro_structure;

// Секция 3: Инициализация переменных — задаем начальные значения для данных страницы
// Базовый URL сайта без слеша в конце (используется в JSON-LD и ссылках на автора)
$baseUrl = rtrim(Cot::$cfg['mainurl'], '/');
// Переменная для первого изображения страницы
$page_image = '';
// Полный URL логотипа сайта из настроек плагина (для publisher.logo в Schema.org)
$page_logo_image = '';
// Текущая локаль страницы (ru, en и т.д.)
$page_locale = '';
// Имя автора для Schema.org
$page_author_name = '';
// URL профиля автора для Schema.org (author.url)
$page_author_url = '';
$page_title = '';
$page_description = '';
$page_keywords = '';
$page_id = 0;
$page_date = 0;
$page_updated = 0;
$page_url = '';
$page_category_name = '';
$avg_stars = 0;
$total_count = 0;

// Секция 4: Формирование данных для мета-тегов — обрабатываем данные товара, если они доступны
if (isset($item) && is_array($item)) {
	// --------------------------------------------------------
	// Многоязычность
	// --------------------------------------------------------
	// === Поддержка плагина i18n (Content Internationalization) ===
    // Определяем текущую локаль пользователя (язык) или используем дефолтный
    $current_locale = Cot::$usr['lang'] ?? Cot::$cfg['defaultlang'];

    // Устанавливаем локаль страницы на текущую
    $page_locale = $current_locale;

    // Поддержка мультиязычности: если плагин i18n4marketpro активен и локаль не дефолтная, загружаем переводы товара
    if (cot_plugin_active('i18n4marketpro') && $current_locale !== Cot::$cfg['defaultlang'] && !empty($item['fieldmrkt_id'])) {

        // Выполняем запрос к таблице переводов для получения title, desc и text на текущем языке
        $translation = $db->query(
            "SELECT ipage_title, ipage_desc, ipage_text
             FROM $db_i18n4marketpro_pages
             WHERE ipage_id = ? AND ipage_locale = ?",
            [$item['fieldmrkt_id'], $current_locale]
        )->fetch(PDO::FETCH_ASSOC);

        // Если перевод найден, переопределяем поля товара переведенными значениями
        if ($translation) {
            if (!empty($translation['ipage_title'])) {
                $item['fieldmrkt_title'] = $translation['ipage_title'];
                $item['fieldmrkt_metatitle'] = $translation['ipage_title'];
            }
            if (!empty($translation['ipage_desc'])) {
                $item['fieldmrkt_desc'] = $translation['ipage_desc'];
            }
            if (!empty($translation['ipage_text'])) {
                $item['fieldmrkt_text'] = $translation['ipage_text'];
            }
        }
    }
	// --------------------------------------------------------
	// изображение товара
	// --------------------------------------------------------
    // Получаем первое изображение товара с помощью функции из плагина
    $page_image = get_seomarketpro_main_first_image($item['fieldmrkt_id'] ?? 0);
	
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

    // Устанавливаем заголовок: сначала metatitle, если есть, иначе обычный title (уже с переводом)
    $page_title = !empty($item['fieldmrkt_metatitle'])
        ? $item['fieldmrkt_metatitle']
        : ($item['fieldmrkt_title'] ?? '');

    // Устанавливаем описание: сначала metadesc, иначе очищенный текст (уже с переводом)
    $page_description = !empty($item['fieldmrkt_metadesc'])
        ? $item['fieldmrkt_metadesc']
        : strip_tags(html_entity_decode($item['fieldmrkt_text'] ?? ''));

    // Устанавливаем ключевые слова: если заданы, обрезаем; иначе извлекаем из текста (уже с переводом)
    $page_keywords = !empty($item['fieldmrkt_keywords'])
        ? cot_string_truncate($item['fieldmrkt_keywords'], 255)
        : cot_extract_keywords_marketpro($item['fieldmrkt_text'] ?? '', 5);

    // Устанавливаем ID товара как целое число
    $page_id = (int)$item['fieldmrkt_id'] ?? 0;

    // Устанавливаем дату публикации товара
    $page_date = (int)($item['fieldmrkt_date'] ?? time());

    // Устанавливаем дату обновления товара
    $page_updated = (int)($item['fieldmrkt_updated'] ?? time());

    // Формируем URL товара: с alias, если есть, иначе с ID
    $page_url = empty($item['fieldmrkt_alias'])
        ? cot_url('market', 'c=' . $item['fieldmrkt_cat'] . '&id=' . $page_id, '', true)
        : cot_url('market', 'c=' . $item['fieldmrkt_cat'] . '&al=' . $item['fieldmrkt_alias'], '', true);

    // Получаем код категории товара
    $category_code = $item['fieldmrkt_cat'] ?? '';

    // Если код категории задан, получаем ее название с поддержкой перевода
    if (!empty($category_code)) {

        // Поддержка перевода категории: если i18n4marketpro активен и локаль не дефолтная, загружаем перевод
        if (cot_plugin_active('i18n4marketpro') && $current_locale !== Cot::$cfg['defaultlang']) {

            // Выполняем запрос к таблице переводов категорий
            $cat_translation = $db->query(
                "SELECT istructure_title FROM $db_i18n4marketpro_structure
                 WHERE istructure_code = ? AND istructure_locale = ?",
                [$category_code, $current_locale]
            )->fetchColumn();

            // Если перевод найден, используем его как название категории
            if ($cat_translation) {
                $page_category_name = htmlspecialchars($cat_translation, ENT_QUOTES, 'UTF-8');
            }
        }

        // Если перевода нет, берем название из основной таблицы structure
        if (empty($page_category_name)) {
            $category_name_result = $db->query(
                "SELECT structure_title FROM $db_structure WHERE structure_code = ? AND structure_area = 'market'",
                [$category_code]
            )->fetchColumn();

            $page_category_name = !empty($category_name_result)
                ? htmlspecialchars($category_name_result, ENT_QUOTES, 'UTF-8')
                : htmlspecialchars($category_code, ENT_QUOTES, 'UTF-8');
        }
    }

    // Если активен плагин отзывов, получаем средний рейтинг и количество отзывов
    if (cot_plugin_active('marketproreviews')) {
        $scores = cot_get_marketproreview_scores($page_id);
        $avg_stars = $scores['total']['count'] > 0 ? round($scores['stars']['summ'] / $scores['total']['count'], 2) : 0;
        $total_count = $scores['total']['count'];
    }
}

// Формируем финальное описание: берем desc или text, очищаем и обрезаем до 160 символов
$descriptionText = $item['fieldmrkt_desc'] ?: $item['fieldmrkt_text'];
$page_description = cot_string_truncate(seomarketpro_descriptionText_cleaning($descriptionText), 160, true, false);




// Определяем тип товара: здесь всегда цифровой (true)
$isDigital = true; // цифровой

// Секция 5: Формирование HTML-кода мета-тегов — создаем строку с мета-тегами
$meta_tags = '';

// Добавляем OG: заголовок
$meta_tags .= '<meta property="og:title" content="' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем OG: описание
$meta_tags .= '<meta property="og:description" content="' . htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем OG: тип (website)
$meta_tags .= '<meta property="og:type" content="website">' . "\n";

// Добавляем OG: URL страницы
$meta_tags .= '<meta property="og:url" content="' . htmlspecialchars(Cot::$cfg['mainurl'] . '/' . $page_url, ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем OG: изображение (с фолбэком, если нет)
$meta_tags .= '<meta property="og:image" content="' . htmlspecialchars($page_image, ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем OG: alt для изображения
$meta_tags .= '<meta property="og:image:alt" content="' . htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем OG: название сайта
$meta_tags .= '<meta property="og:site_name" content="' . htmlspecialchars(Cot::$cfg['maintitle'], ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем OG: локаль
$meta_tags .= '<meta property="og:locale" content="' . htmlspecialchars($page_locale, ENT_QUOTES, 'UTF-8') . '">' . "\n";

// Добавляем Twitter: тип карточки (product)
$meta_tags .= '<meta name="twitter:card" content="product">' . "\n";

// Добавляем Twitter: сайт
$meta_tags .= '<meta name="twitter:site" content="@abuyfile">' . "\n";

// Добавляем Twitter: цена
$meta_tags .= '<meta name="twitter:data1" content="$' . ($item['fieldmrkt_costdflt'] ?? 0) . ' ' . strtoupper(Cot::$cfg['market']['market_currency'] ?? 'USD') . '">' . "\n";

// Добавляем Twitter: лейбл для цены
$meta_tags .= '<meta name="twitter:label1" content="Price">' . "\n";

// Добавляем Twitter: маркетплейс
$meta_tags .= '<meta name="twitter:data2" content="' . Cot::$cfg['maintitle'] . '">' . "\n";

// Добавляем Twitter: лейбл для маркетплейса
$meta_tags .= '<meta name="twitter:label2" content="Marketplace">' . "\n";

// Добавляем Twitter: домен
$meta_tags .= '<meta name="twitter:domain" content="' . parse_url(Cot::$cfg['mainurl'], PHP_URL_HOST) . '">' . "\n";

// Формируем JSON-LD для WebSite
$websiteJSON = [
    "@context" => "http://schema.org",
    "@type" => "WebSite",
    "name" => Cot::$cfg['maintitle'],
    "url" => Cot::$cfg['mainurl']
];

// Добавляем JSON-LD WebSite в head страницы
Cot::$out['head'] .= '<script type="application/ld+json">' . json_encode($websiteJSON, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';

// Получаем базовый URL сайта без слеша в конце
$baseUrl = rtrim(Cot::$cfg['mainurl'], '/');

// Формируем элементы хлебных крошек для BreadcrumbList
$breadcrumbItems = [
    ["@type" => "ListItem", "position" => 1, "name" => $L['Home'], "item" => $baseUrl],
    ["@type" => "ListItem", "position" => 2, "name" => $page_category_name, "item" => $baseUrl . '/' . ltrim(cot_url('market', 'c=' . $item['fieldmrkt_cat']), '/')],
    ["@type" => "ListItem", "position" => 3, "name" => $item['fieldmrkt_title'], "item" => $baseUrl . '/' . ltrim($page_url, '/')]
];

// Формируем JSON для BreadcrumbList
$breadcrumbJSON = [
    "@context" => "http://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => $breadcrumbItems
];

// Добавляем JSON-LD BreadcrumbList в head страницы
Cot::$out['head'] .= '<script type="application/ld+json">' . json_encode($breadcrumbJSON, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';

// Формируем полный URL страницы товара
$productPageUrl = $baseUrl . '/' . ltrim($page_url, '/');

// Формируем длинное описание для JSON-LD (очищенное и обрезанное)
$descriptionTextJSON = cot_string_truncate(seomarketpro_descriptionText_cleaning($item['fieldmrkt_text'] ?? ''), 2500, true, false);


// $wordCount Подсчёт слов (универсальный для русского и английского)
$full_text = $item['fieldmrkt_text'] ?? '';
$clean_text = strip_tags($full_text);
$clean_text = preg_replace('/\s+/', ' ', $clean_text);
$clean_text = trim($clean_text);
$wordCount = !empty($clean_text) ? str_word_count($clean_text, 0, 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя0123456789-') : 0;	


// Формируем массив изображений для продукта
$productImage = [];
$productImage[] = $page_image;

// ниже пока не трогаем. на потом
/* if (!empty($item['fieldmrkt_file'])) {
    $productImage[] = $item['fieldmrkt_file'];
} elseif (!empty($page_image)) {
    $productImage[] = htmlspecialchars($page_image, ENT_QUOTES, 'UTF-8');
} else {
    $productImage[] = Cot::$cfg['plugin']['seomarketpro']['nonimage'];
} */

// Получаем валюту цены в верхнем регистре
$productPriceCurrency = strtoupper(Cot::$cfg['market']['market_currency'] ?? 'USD');



// ---------- PRODUCT JSON-LD ----------
// Формируем JSON для Product
$productJSON = [
    "@context" => "http://schema.org",
    "@type" => "Product",
    "category" => $page_category_name,
    "name" => $item['fieldmrkt_title'],
    "description" => $descriptionTextJSON,
    "image" => $productImage,
	"brand" => [
		"@type" => "Brand",
		"name" => Cot::$cfg['maintitle']
	],
    "sku" => $page_id,
    "mpn" => 'E-' . $page_id,
    "url" => $productPageUrl,
    "offers" => [
        "@type" => "Offer",
        "url" => $productPageUrl,
        "priceCurrency" => $productPriceCurrency,
        "price" => (string)($item['fieldmrkt_costdflt'] ?? 0),
        "priceValidUntil" => date('c', strtotime('+1 year')),
        "itemCondition" => "http://schema.org/NewCondition",
        "availability" => "http://schema.org/InStock"
    ],
    "review" => [
        [
            "@type" => "Review",
            "author" => [
                "@type" => "Person",
                "name" => "Anonymous"
            ],
            //"reviewBody" => "Отзывов пока нет",
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
	"inLanguage" => $page_locale,
    "wordCount" => (int)$wordCount
];

// Если товар цифровой, добавляем политику возврата в offers
if ($isDigital) {
    $productJSON['offers']['hasMerchantReturnPolicy'] = [
        "@type" => "MerchantReturnPolicy",
        "returnPolicyCategory" => "https://schema.org/MerchantReturnNotPermitted"
    ];
}

// Добавляем JSON-LD Product в head страницы
Cot::$out['head'] .= '<script type="application/ld+json">' . json_encode($productJSON, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';


// ---------- SoftwareApplication JSON-LD ----------

// Формируем JSON для SoftwareApplication (для цифровых продуктов)
$softwareJSON = [
    "@context" => "http://schema.org",
    "@type" => "SoftwareApplication",
    "applicationCategory" => $page_category_name,
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "itemReviewed" => $item['fieldmrkt_title'],
        "ratingCount" => (int)($total_count ?: 1),
        "ratingValue" => (float)($avg_stars ?: 5)
    ],
    "datePublished" => date('c', $page_date),
    "name" => $item['fieldmrkt_title'],
    "description" => $descriptionTextJSON,
    "url" => $productPageUrl,
    "thumbnailUrl" => $productImage[0],
    "author" => ["@type" => "Person", "name" => $page_author_name, "url" => $page_author_url],
	"inLanguage" => $page_locale,
    "wordCount" => (int)$wordCount
];

// Добавляем JSON-LD SoftwareApplication в head страницы
Cot::$out['head'] .= '<script type="application/ld+json">' . json_encode($softwareJSON, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';

// Секция 6: Добавление мета-тегов в вывод — добавляем сформированные теги в общий мета-вывод страницы
global $out;
$out['meta'] = (isset($out['meta']) ? $out['meta'] : '') . $meta_tags;