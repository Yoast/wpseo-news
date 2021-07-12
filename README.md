Yoast News SEO for Yoast SEO
==========================
Requires at least: 5.6
Tested up to: 5.8
Stable tag: 12.8
Requires PHP: 5.6.20
Depends: Yoast SEO

Yoast News SEO module for the Yoast SEO plugin.

[![Code Climate](https://codeclimate.com/repos/54523c37e30ba0670f0016b8/badges/373c97133cba47d9822b/gpa.svg)](https://codeclimate.com/repos/54523c37e30ba0670f0016b8/feed)

This repository uses [the Yoast grunt tasks plugin](https://github.com/Yoast/plugin-grunt-tasks).

Installation
============

1. Go to Plugins -> Add New.
2. Click "Upload" right underneath "Install Plugins".
3. Upload the zip file that this readme was contained in.
4. Activate the plugin.
5. Go to SEO -> Extensions -> Licenses, enter your license key and Save.
6. Your license key will be validated.
7. You can now use Yoast News SEO. See also https://kb.yoast.com/kb/configuration-guide-for-news-seo/

Frequently Asked Questions
--------------------------

You can find the [Yoast News SEO FAQ](https://kb.yoast.com/kb/category/news-seo/) in our knowledge base.

Changelog
=========
### 12.8: July 13th, 2021
Enhancements:
* Adds key/value pairs of all News SEO meta tags to our REST API.
* Improves the performance of the news sitemap. Props to [archon810](https://github.com/archon810).

Bugfixes:
* Fixes a bug where a database query including duplicate column names would fail on certain database systems.

Other:
* Adds a filter to override the decision to omit an item from the news sitemap. Props to [joneslloyd](https://github.com/joneslloyd).
* Sets the minimum WordPress version to 5.6.

### 12.7: April 6th, 2021
Enhancements:

* Adds News to the Yoast SEO sidebar in the Elementor editor and the block editor.
* Merges the `Exclude from News sitemap` and the `Googlebot-News index` settings from the News meta box into one setting: `Exclude this post from Google News`. Because both settings served the same purpose, to exclude your content from the News sitemap.
* Removes the `Default genre` setting from the News sitemap and settings because Google no longer supports `Genres` for articles in Google News.

Other:

* Sets the WordPress tested up to version to 5.7 and minimum supported WordPress version to 5.6.

Bugfixes:

* Fixes a bug where certain conditions (e.g. using a different admin language) would result in an endless loop.
* Fixes "mixed content" warnings on the News SEO options page.

### Earlier versions
For the changelog of earlier versions, please refer to [the changelog on yoast.com](https://yoa.st/news-seo-changelog).
