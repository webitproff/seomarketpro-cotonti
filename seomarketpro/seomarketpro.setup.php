<?php
/* ====================
[BEGIN_COT_EXT]
Code=seomarketpro
Name=Seo Market Pro
Category=content-seo
Description=SEO enhancements for the MarketPRO module
Version=2.1.1
Date=17 Dec 2025
Author=webitproff
Copyright=Copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.ccom/users/webitproff
Notes=Requires the Market PRO v.5+ by webitproff. PHP 8.4+, MySQL 8.0+, Cotonti Siena v.0.9.26+.
Auth_guests=R
Lock_guests=12345A
Auth_members=RW
Lock_members=
Requires_modules=market
Recommends_modules=
Requires_plugins=
Recommends_plugins=
[END_COT_EXT]
[BEGIN_COT_EXT_CONFIG]
nonlogo=01:string::plugins/seomarketpro/logo.webp:Logo path plugins/seomarketpro/logo.webp (no domain)
nonimage=02:string::plugins/seomarketpro/image.webp:Default image path plugins/seomarketpro/image.webp (no domain)
maxrelated=03:select:0,1,2,3,5,7:3:Maximum related posts per page
seomarketpro_currency=06:select:USD,EUR,RUB,UAH,USDT,BTC:BTC:допускаются только трёхбуквенные коды ISO 4217 в верхнем регистре
[END_COT_EXT_CONFIG]
==================== */

/**
 * Seo Market Pro plugin
 * Filename: seomarketpro.setup.php
 * @package SeoMarketPro
 * @version 2.1.1
 * @copyright (c) webitproff 2025 https://github.com/webitproff or https://abuyfile.ccom/users/webitproff
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');