<?php
/**
 * Ukrainian Language File for SeoMarketPro plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: seomarketpro.ua.lang.php
 * Purpose: Ukrainian localization. Defines strings.
 * Date: Dec 18th, 2025
 * @package SeoMarketPro
 * @version 2.1.2
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.com/users/webitproff
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

/**
 * Plugin Config
 */

$L['cfg_seomarketpro_currency'] = 'Валюта в розмітці';
$L['cfg_seomarketpro_currency_hint'] = 'допускаються лише трибуквені коди ISO 4217 у верхньому регістрі. використовується лише для відображення в JSON-LD розмітці';

$L['cfg_nonlogo'] = 'Шлях до логотипу за замовчуванням';
$L['cfg_nonlogo_hint'] = 'plugins/seomarketpro/img/logo.webp (без домену)';

$L['cfg_nonimage'] = 'Шлях до зображення за замовчуванням';
$L['cfg_nonimage_hint'] = 'plugins/seomarketpro/img/image.webp (no domain) (без домену)';

$L['cfg_maxrelated'] = 'Максимальна кількість повʼязаних товарів на сторінці';
$L['cfg_maxrelated_hint'] = 'у картці товару будуть показані інші товари з цієї категорії';


/**
 * Plugin Info
 */
$L['info_name'] = 'Seo Market Pro';
$L['info_desc'] = 'Розширює SEO-можливості модуля Market PRO у Cotonti: додає мета-теги, Open Graph, Twitter Card, структуровані дані JSON-LD Schema.org, витяг ключових слів, оцінку часу читання та функціонал повʼязаних товарів.';
$L['info_notes'] = 'Потрібно: модуль <a href="https://github.com/webitproff/marketpro-cotonti" target="_blank"><strong>Market PRO v.5+ by webitproff</strong></a>, PHP 8.4+, MySQL 8.0+, Cotonti Siena v.0.9.26 +';


$L['seomarketpro_related'] = 'Повʼязані та схожі товари';
$L['seomarketpro_read_time'] = 'хвилин читання';
$L['seomarketpro_unknown_author'] = 'Невідомий продавець';

/**
 * Stop words for keyword extraction (Ukrainian)
 */
$L['seomarketpro_stop_words'] = 'і,в,у,на,з,із,за,до,від,для,це,як,що,а,але,без,був,була,були,було,бути,вам,вас,весь,вся,все,всі,ви,він,вона,вони,воно,де,да,для,до,його,її,їх,якщо,є,ще,ж,же,за,здесь,знову,і,із,або,коли,кому,лише,мене,мені,може,ми,мій,моя,моє,мої,навіть,наш,не,ні,ніхто,нічого,ну,о,об,однак,один,перед,по,під,після,потім,тому,майже,при,про,раз,сам,сама,саме,своє,свої,себе,собі,скільки,так,там,тепер,те,того,також,тільки,ти,уже,хоча,хто,чого,чим,щоб,ця,ці,це,я,усе,мене,потім,вони,якщо,тут,відразу,щось';

