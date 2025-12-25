<?php
/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0 
 * Purpose: plugin functions
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.functions.php
 * @package SeoMarketPro
 * @version 2.2.7
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.com/users/webitproff
 * @license BSD
 */



defined('COT_CODE') or die('Wrong URL');
// Импорт пространства имен: используем класс UsersHelper из Cotonti для работы с пользователями
use cot\users\UsersHelper;
/**
 * cleaning text !!! BETA !!! встряхиваем холст с описанием как коврик от пыли.
 * @param string $text Input text
 * @return string 
 */

function seomarketpro_descriptionText_cleaning(string $text): string
{
    if (empty($text)) {
        return '';
    }
		// меняем входящее на пробелы, что бы в исходный код, текст не выходил слитно при strip_tags описания товара или статьи 
		$tags_replace = [
			'<br>' => ' ',
			'</h1>' => ' ',
			'</h2>' => ' ',
			'</h3>' => ' ',
			'</h4>' => ' ',	
			'</li>' => ' ',			
			'<li>' => ' '
		];

		$text = strtr($text, $tags_replace);
		
    // Clean text: remove HTML, decode entities
    $text = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));

    // Replace punctuation with spaces and normalize spaces
	$text = preg_replace('/[!?;:"\'\(\)\[\]{}<>\n\r\t]/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', trim($text));
	
	return $text;
}


/**
 * Extracts keywords from text
 * @param string $text Input text
 * @param int $limit Maximum number of keywords to return
 * @return string Comma-separated keywords
 */

function cot_extract_keywords_marketpro(string $text, int $limit = 10): string
{
    if (empty($text)) {
        return '';
    }

    // Load stop words from language file
    $stop_words = !empty(Cot::$L['seomarketpro_stop_words']) ? explode(',', Cot::$L['seomarketpro_stop_words']) : [];
    $stop_words = array_map('trim', $stop_words);
    $stop_words = array_map('mb_strtolower', $stop_words);

    // Clean text: remove HTML, decode entities, convert to lowercase
    $text = strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
    $text = mb_strtolower($text, 'UTF-8');

    // Replace punctuation with spaces and normalize spaces
    $text = preg_replace('/[.,!?;:"\'\(\)\[\]{}<>\n\r\t]/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', trim($text));

    // Split into words
    $words = explode(' ', $text);
    $word_count = [];

    // Count word frequencies
    foreach ($words as $word) {
        $word = trim($word);
        if (mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stop_words)) {
            $word_count[$word] = ($word_count[$word] ?? 0) + 1;
        }
    }

    // Sort by frequency and limit
    arsort($word_count);
    $keywords = array_keys(array_slice($word_count, 0, $limit, true));

    // Return comma-separated keywords
    return implode(', ', $keywords);
}


/**
 * Estimates reading time for text
 * @param string $text Input text
 * @return int Estimated reading time in minutes
 */
function cot_estimate_read_time_marketpro(string $text): int
{
    if (empty($text)) {
        return 1;
    }

    // Удаляем HTML-теги и BB-коды
    $text = strip_tags($text);
    $text = preg_replace('/\[.*?\]/', '', $text);
    $text = trim($text);

    if (empty($text)) {
        return 1;
    }

    // Подсчитываем слова (для UTF-8, включая русский текст)
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $word_count = count($words);

    // Средняя скорость чтения: 200 слов в минуту
    $minutes = (int)ceil($word_count / 200);

    return max(1, $minutes);
}

/**
 * Получает первое изображение карточки товара.
 *
 * Логика:
 * 1. Сначала проверяется экстраполе 'link_main_image'.
 * 2. Если оно пустое, ищем изображение в модуле 'files'.
 * 3. Если нет и там — ищем в плагине 'attacher'.
 * 4. Если ни один источник не дал результат — возвращаем заглушку.
 *
 * @param int $page_id ID товара
 * @return string URL изображения или заглушки
 */
function get_seomarketpro_main_first_image(int $page_id): string
{
    // Объявляем, что будем использовать глобальные переменные $db, $db_market и $cfg
    // Эти переменные определены где-то выше в системе (обычно в основном файле CMS Cotonti)
    global $db, $db_market, $cfg;

    // Создаём статическую переменную $page_cache. 
    // "static" значит, что она сохраняется между вызовами функции и не очищается.
    // Это локальный кэш: если функция вызывается несколько раз с одним и тем же $page_id,
    // мы не будем каждый раз делать запрос в базу данных, а возьмём данные из этого массива.
    static $page_cache = [];

    // Формируем полный URL к изображению-заглушке (которое показывается, если фото нет).
    // rtrim($cfg['mainurl'], '/') — убирает слеш в конце основного адреса сайта (например, https://site.com/)
    // ltrim(..., '/') — убирает слеш в начале пути к заглушке.
    // Если в настройках плагина указан свой путь к заглушке — берём его, иначе по умолчанию 'images/default-placeholder.jpg'
    $default_image = rtrim($cfg['mainurl'], '/') . '/' .
        ltrim($cfg['plugin']['seomarketpro']['nonimage'] ?? 'images/default-placeholder.jpg', '/');

    // Если передан некорректный ID товара (меньше или равно нулю) — сразу возвращаем заглушку.
    // Это защита от ошибок.
    if ($page_id <= 0) {
        // trim() убирает лишние пробелы и переносы строк, на всякий случай
        return trim($default_image);
    }

    // Проверяем, есть ли уже данные о товаре в нашем локальном кэше.
    // Если нет — будем запрашивать из базы данных.
    if (!isset($page_cache[$page_id])) {
        // Делаем запрос к базе данных: выбираем все поля из таблицы товаров по ID.
        // $db_market — имя таблицы с товарами (определена глобально).
        // ? — это плейсхолдер, в который подставится $page_id (защита от SQL-инъекций).
        // fetch(PDO::FETCH_ASSOC) — получаем результат как ассоциативный массив (ключ — имя поля).
        // Если ничего не найдено — возвращаем пустой массив [].
        $page_cache[$page_id] = $db->query(
            "SELECT * FROM $db_market WHERE fieldmrkt_id = ?",
            [$page_id]
        )->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    // Присваиваем полученные данные товару в переменную $item для удобства.
    $item = $page_cache[$page_id];

    // Если товар по каким-то причинам не найден в базе — возвращаем заглушку.
    if (!$item) {
        return trim($default_image);
    }

    // -------------------------
    // 1. Проверка экстраполя "link_main_image"
    // -------------------------
    // Пытаемся взять значение поля с прямой ссылкой на главное изображение.
    // Если такого поля нет — будет пустая строка.
    $image_field = $item['fieldmrkt_link_main_image'] ?? '';
    
    // Если поле не пустое — значит, там есть ссылка на изображение.
    if (!empty($image_field)) {
        // Проверяем, начинается ли ссылка с http:// или https://
        // Если да — это уже полный URL, используем как есть.
        // Если нет — это относительный путь, и нужно добавить основной адрес сайта.
        $url = preg_match('#^https?://#', $image_field)
            ? $image_field
            : rtrim($cfg['mainurl'], '/') . '/' . ltrim($image_field, '/');
        
        // Возвращаем готовый URL (с trim на всякий случай).
        return trim($url);
    }

    // -------------------------
    // 2. Проверка модуля "files" от Alex300
    // -------------------------
    // Проверяем, активен ли модуль "files" в системе.
    // cot_module_active() — функция CMS, которая это проверяет.
    if (cot_module_active('files')) {
        // Если модуль активен — подключаем глобальную переменную с именем таблицы файлов.
        global $db_files;
        
        // Подключаем основной файл модуля "files", чтобы все его функции стали доступны.
        require_once cot_incfile('files', 'module');

        // Делаем запрос: ищем любой файл, прикреплённый к товару (source = 'market', source_id = ID товара).
        // LIMIT 1 — нам нужно только первое изображение.
        $file = $db->query(
            "SELECT * FROM $db_files WHERE source = 'market' AND source_id = ? LIMIT 1",
            [$page_id]
        )->fetch(PDO::FETCH_ASSOC);

        // Если файл найден — формируем полный URL к нему.
        if ($file) {
            // Путь обычно выглядит как: https://site.com/att_files/папка/имя_файла.jpg
            $url = rtrim($cfg['mainurl'], '/') . '/att_files/' . ltrim($file['path'], '/') . '/' . ltrim($file['file_name'], '/');
            return trim($url);
        }
    }

    // -------------------------
    // 3. Проверка плагина "attacher"
    // -------------------------
    // Проверяем, активен ли плагин "attacher" И при этом модуль "files" НЕ активен.
    // Это важно: обычно один из них используется, но не оба одновременно.
    if (cot_plugin_active('attacher') && !cot_module_active('files')) {
        // Подключаем глобальную таблицу attacher.
        global $db_attacher;
        
        // Подключаем основной файл плагина attacher.
        require_once cot_incfile('attacher', 'plug');

        // Ищем прикреплённый файл для области "market" и конкретного товара.
        $file = $db->query(
            "SELECT * FROM $db_attacher WHERE att_area = 'market' AND att_item = ? LIMIT 1",
            [$page_id]
        )->fetch(PDO::FETCH_ASSOC);

        // Если файл найден — формируем URL.
        if ($file) {
            // В attacher путь хранится в поле att_path и уже включает имя файла.
            $url = rtrim($cfg['mainurl'], '/') . '/' . ltrim($file['att_path'], '/');
            return trim($url);
        }
    }

    // -------------------------
    // 4. Если ничего не найдено — возвращаем заглушку
    // -------------------------
    // Если ни один из способов не дал изображение — показываем стандартную заглушку.
    return trim($default_image);
}