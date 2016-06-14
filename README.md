Yoast News SEO for Yoast SEO
==========================
Requires at least: 4.3
Tested up to: 4.5
Stable tag: 3.3
Depends: wordpress-seo


Yoast News SEO module for the Yoast SEO plugin.

[![Code Climate](https://codeclimate.com/repos/54523c37e30ba0670f0016b8/badges/373c97133cba47d9822b/gpa.svg)](https://codeclimate.com/repos/54523c37e30ba0670f0016b8/feed)

Installation
============

1. Go to Plugins -> Add New.
2. Click "Upload" right underneath "Install Plugins".
3. Upload the zip file that this readme was contained in.
4. Activate the plugin.
5. Go to SEO -> Extensions -> Licenses, enter your license key and Save.
6. Your license key will be validated.
7. You can now use Yoast News SEO. See also https://yoast.com/wordpress/plugins/news-seo/news-seo-configuration-guide/

Changelog
=========

### 3.3: June 14th, 2016

* Enhancements
	* Adds the Yoast i18n module to the Yoast SEO News settings page, which informs users the plugin isn't available in their language and what they can do about it.

* Bugfixes
    * Fixes a bug where the support beacon for Yoast SEO News was added to all Yoast SEO settings pages.
    * Fixes a bug where updates were not working reliably when multiple paid Yoast plugins were active.

### 3.2: April 20th, 2016

* Bugfixes
    * Fixes a bug where the news sitemap would be shown in the index even if the sitemap itself was empty.
    * Fixes a bug where clicking the 'Update now' button on the plugin page didn't update correctly.

### 3.1: March 2nd, 2016

* Bugfixes
	* Fixes a bug where our license manager could sometimes not reach our licensing system due to problems with ssl.

* Enhancements
	* Makes sure users don't have to reactivate their license after updating or disabling/enabling the plugin.
	* Adds a support beacon on the News SEO settings page enabling users to ask for support from the WordPress backend.
	* Contains a few performance optimizations.

### 3.0: November 18th, 2015

* Synchronized plugin version with all other Yoast SEO plugins for WordPress.

* Bugfixes
	* Fixes a bug where the links in the sitemap would 'randomly' change from https to http or the other way around (in very rare circumstances).
	* Fixes the news metabox that was broken in combination with Yoast SEO 3.0.
	* Fixes deprecation warnings for filters that have been removed in Yoast SEO 3.0

### 2.2.5: June 10th, 2015

* Bugfixes
	* Fixes a bug where the news sitemap cache was not cleared when the News SEO settings were updated.
	* Added 1 new translation: en_AU.

### 2.2.4: April 29th, 2015

* Bugfixes
	* Fixes a bug where the news sitemap cache was not cleared when a news item was edited or added.
	* Fixes a bug where the stylesheets for the news sitemap were not included when the sitemap was served from cache.
	* Fixes a bug where specialchars were escaped in the news sitemap.

### 2.2.3: March 24th, 2015

* Bugfixes
	* Fixes a bug where an invalid argument error could be raised when visiting the sitemap.
	* Fixes a bug where the sitemap could contain wrongly formatted publication dates.
	* Fixes a bug where the `wpseo_news_sitemap_url` filter wasn't working properly.
	* Fixes a bug where the News sitemap genre wasn't saved on posts.
	* Fixes a bug where the sitemap didn't always contain the correct image url, props [Marcus Jaschen](https://github.com/mjaschen).
	* Fixes a bug with duplicate keywords in the news sitemap.
	* Fixes an issue where the Editor's pick RSS took very long to load, by only fetching the selected post types from the database.
	* Fixes a bug where the default keywords and the meta news keywords weren't added to the sitemap.
* Enhancements
	* Introduces a few string improvements.
	* Added 9 new languages: en_GB, es_ES, es_MX, fr_FR, he_IL, it_IT, nl_NL, ro_RO and tr_TR.

### 2.2.2: December 17th, 2014

* Bugfixes
  * The stocktickers didn't work properly, these are fixed
  * Setting correct HTTP header to be sure output is executed as a RSS-feed
* Enhancements
  * Hide the standout meta-tag automatically after seven days (you can still see standout was used in admin but it won't be displayed)
  * Show the total number of used standout meta-tags (for the last seven days) in the post-admin

### 2.2.1: November 11th, 2014

* Bugfixes
  * Fixed a bug where button to Editors' Pick RSS didn't work.
  * Fixed a bug where the wrong image url ended up in the <image:loc> in the Editor's Pick RSS
  * Fixed a bug where the wrong http header was set for the Editor's Pick RSS
* Enhancements
  *	Added translations for Polish

### 2.2: October 7th, 2014
* Bugfixes
  * Fixed a bug where button to Editors' Pick RSS didn't work.
	* Fixed bug where plugin would give a white screen of death in certain installations.
	* Improve using the right image for the news sitemap.

* Enhancements
	* Added `pubDate` to editors pick RSS feed.

### 2.1: July 9th, 2014
* Several performance optimizations for sitemap generation.
* Added button that links to news sitemap on admin page.
* Added an option to include only the featured image in the XML News sitemap.
* Introduced filter `wpseo_locale` for locale/language of the XML News sitemap.
* Introduced filter `wpseo_news_sitemap_url` to allow changing the XML News sitemap URL.

### 2.0.6: June 10th, 2014
* Removed the wptexturize filter from the_title and the_content in the Editors' Pick feed because corrupts our output.
* Added guid elements to item elements in the Editors' Pick feed.
* Added an atom:link element as recommended by the RSS Advisory Board to identifying a feed's URL within the feed.
* Added the WPSEO News fields to the WPSEO meta fields class to fix a bug where the post meta genre field isn't saved.

### 2.0.5: June 5th, 2014
* Fixed a publication_date timezone bug.

### 2.0.4: May 15th, 2014
* Bugfixes
  * Add CDATA tags to RSS feed text output.
  * Now using the same title for the Editors' Pick title as the channel title.

### 2.0.3: April 23rd, 2014
* Enhancement
  * Sitemaps now use creation dates instead of modified dates.

### 2.0.2: April 22nd, 2014
* Bugfixes
  * Fixed a bug with version_compare.

* Enhancement
  * Adds sanitize callback to register_settings.

### 2.0.1: April 22nd, 2014
* Bugfix
  * Changed EDD product name to 'News SEO'.

### 2.0: April 22nd, 2014
* Initial release
