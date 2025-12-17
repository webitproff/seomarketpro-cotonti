<?php
/**
 * Seo Market Pro plug for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0 
 * Purpose: plugin functions
 * Notes: Requires the Market PRO v.5+ by webitproff.
 * Filename: seomarketpro.functions.php
 * @package SeoMarketPro
 * @version 2.1.1
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.ccom/users/webitproff
 * @license BSD
 */



defined('COT_CODE') or die('Wrong URL');

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
 * Получает первое изображение, прикреплённое к элементу: Карточка товара.
 *
 * @param int $page_id ID элемента (ID товара в $db_market)
 * @return string Полный URL к изображению или к изображению-заглушке
 */
function get_seomarketpro_main_first_image(int $page_id): string
{
    global $db, $db_market, $cfg;

    // Проверяем, что page_id — положительное целое число
    if ($page_id <= 0) {
        return $cfg['mainurl'] . '/images/default-placeholder.jpg';
    }

    // Определяем заглушку по умолчанию: используем конфигурацию или резервный путь
    $default_image = !empty($cfg['plugin']['seomarketpro']['nonimage'])
        ? $cfg['mainurl'] . $cfg['plugin']['seomarketpro']['nonimage']
        : $cfg['mainurl'] . '/images/default-placeholder.jpg';

    // Локальный кэш для данных страницы
    static $page_cache = [];
    if (isset($page_cache[$page_id])) {
        $item = $page_cache[$page_id];
    } else {
        $item = $db->query("SELECT * FROM $db_market WHERE fieldmrkt_id = ?", [$page_id])->fetch(PDO::FETCH_ASSOC);
        $page_cache[$page_id] = $item ?: [];
    }

    // Если страница не найдена, возвращаем заглушку
    if (!$item) {
        return $default_image;
    }

    // Определяем значение дополнительного поля page_link_main_image
	// это если вы создали экстраполе для своего модуля с кодом link_main_image где тип поля file 
	// то есть картинку берем из своего модуля товаров или статей, предварительно используя для этого экстраполе "link_main_image"
    $page_image_extrafield = (!empty($item['fieldmrkt_link_main_image']))
        ? $item['fieldmrkt_link_main_image']
        : '';

    // Если есть изображение в page_link_main_image
    if (!empty($page_image_extrafield)) {
        // Если это не полный URL (нет https:// или http://), добавляем mainurl
        if (!preg_match('#^https?://#', $page_image_extrafield)) {
            return $cfg['mainurl'] . '/' . ltrim($page_image_extrafield, '/');
        }
        // Иначе возвращаем как есть (полный URL)
        return $page_image_extrafield;
    }

    // Проверяем, активен ли модуль файлов
	// если у вас модуль 'files' от Alex300
    if (cot_module_active('files')) {
        global $db_files;
        require_once cot_incfile('files', 'module');

        // Получаем первую запись с изображением
        $files_image = $db->query("SELECT * FROM $db_files WHERE source = 'market' AND source_id = ? LIMIT 1", [$page_id])->fetch(PDO::FETCH_ASSOC);

        if ($files_image) {
            // Формируем URL к изображению
            return $cfg['mainurl'] . '/att_files/' . $files_image['path'] . '/' . $files_image['file_name'];
        }
    }

	// 
    // Проверяем, активен ли плагин attacher
	// если у вас плагин 'attacher' от Roffun, webitproff
    if (cot_plugin_active('attacher') && (!cot_module_active('files'))) {
        global $db_attacher;
        require_once cot_incfile('attacher', 'plug');
        // Получаем первую запись с изображением из таблицы attacher
        $files_image = $db->query("SELECT * FROM $db_attacher WHERE att_area = 'market' AND att_item = ? LIMIT 1", [$page_id])->fetch(PDO::FETCH_ASSOC);
        if ($files_image) {
            // Формируем URL к изображению
            return $cfg['mainurl'] . '/' . $files_image['att_path'];
        }
    }

    // Если изображение не найдено ни в xxxxx_link_main_image, ни в модуле файлов, ни в attacher, возвращаем заглушку
    return $default_image;
}
