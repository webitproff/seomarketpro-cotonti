# SeoMarketPro Plugin for CMF Cotonti Siena

**Enhances the SEO capabilities of the MarketPRO module in Cotonti by adding meta tags, Open Graph, Twitter Cards, JSON-LD Schema.org structured data, keyword extraction, reading time estimation, and related products functionality.**

[![Version](https://img.shields.io/badge/version-2.1.1-green.svg)](https://github.com/webitproff/seomarketpro-cotonti/releases)
[![Require Market PRO module](https://img.shields.io/badge/MarketPRO-v.5-gold.svg)](https://github.com/webitproff/marketpro-cotonti)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti_Siena-0.9.26-8A2BE2.svg)](https://github.com/Cotonti/Cotonti)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple.svg)](https://www.php.net/releases/8_4_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-blue.svg)](https://www.mysql.com/)
[![Bootstrap v5.3.8](https://img.shields.io/badge/Bootstrap-v5.3.8-blueviolet.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/webitproff/seomarketpro-cotonti/blob/main/LICENSE)

![SeoMarketPro for Cotonti](https://github.com/user-attachments/assets/e1a6dbcf-216d-41d3-ae2e-25c492c25223)

Seo Market Pro is a plugin for Cotonti CMF that improves search engine optimization for product pages in the MarketPRO module (version 5+).  
It helps products appear better in search engines like Google or Yandex.

With this plugin and MarketPRO v.5+, your products have a better chance of appearing in search results and ranking higher because the necessary meta tags and structured data are added.

### Requirements and Compatibility
- Runs on PHP 8.4+ and MySQL 8.0+/8.4  
- Compatible with image extensions (files module or attacher plugin) and reviews (marketproreviews)  
- Settings allow specifying a logo, image placeholder, maximum number of related products, and currency (e.g., USD, EUR, BTC)

### What Does the Seo Market Pro Plugin Do?

#### Meta Tags: Description and Keywords
Automatically generates description and keywords for the product page. If not filled manually, it pulls from the description text, cleans, and shortens it. This helps search engines understand the page content.

#### Open Graph and Twitter Cards
Creates previews for links in social networks: title, description, image, and logo. When someone shares a product, the post looks neat and attractive.

#### Structured Data (Schema.org)
Adds markup for products (Product) and breadcrumbs (BreadcrumbList). For products, it specifies name, description, price, currency, availability, and reviews. This enables rich snippets in search results — with price, rating, and photo.

#### Navigation Paths (Breadcrumbs)
Breadcrumbs in search results are displayed as names, not links (Home > Category > Product).  
This simplifies navigation and helps search engines better understand the site structure.

#### Tags in Page Templates
Adds reading time for the description, page URL, author name, and a block of related products from the same category (with photo, title, description, and author).  
The plugin automatically selects the first product image and uses it in meta tags and recommendations. If there's no photo, it uses a placeholder.

With Seo Market Pro and MarketPRO v.5+, products get proper markup, which increases the chances of good positions in search.  
Installation is simple, and setup takes just a couple of minutes.  
Suitable for stores on Cotonti where you need to improve visibility without extra effort.
