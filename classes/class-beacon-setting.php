<?php
/**
 * @package News
 */

/**
 * Implements the helpscout beacon suggestions for wpseo news
 */
class WPSEO_News_Beacon_Setting implements Yoast_HelpScout_Beacon_Setting {
	/**
	 * {@inheritdoc}
	 */
	public function get_suggestions( $page ) {
		switch ( $page ) {
			case 'wpseo_news':
				return array(
					'538308c0e4b087d9517672fe',
					// See: http://kb.yoast.com/article/110-configuration-guide-for-news-seo.
					'53901dc1e4b044eb1018e206',
					// See: http://kb.yoast.com/article/133-publication-date-time-in-the-news-xml-sitemap.
					'5375e852e4b03c6512282d5a',
					// See: http://kb.yoast.com/article/36-my-sitemap-is-blank-what-s-wrong.
				);
				break;
		}

		return array();
	}

	/**
	 * Returns a product for a a certain admin page.
	 *
	 * @param string $page The current admin page we are on.
	 *
	 * @return Yoast_Product[] A product to use for sending data to helpscout
	 */
	public function get_products( $page ) {
		switch ( $page ) {
			case 'wpseo_news':
				return array( new WPSEO_News_Product() );
				break;
		}

		return array();
	}
}
