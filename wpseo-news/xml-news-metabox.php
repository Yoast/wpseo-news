<?php

/**
 * This code handles the display of the Google news tab in the WP SEO metabox.
 */
class WPSEO_XML_News_Sitemap_Metabox extends WPSEO_Metabox {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter( 'wpseo_save_metaboxes', array( &$this, 'save_meta_boxes' ), 10, 1 );
		add_action( 'wpseo_tab_header', array( &$this, 'tab_header' ) );
		add_action( 'wpseo_tab_content', array( &$this, 'tab_content' ) );
	}

	/**
	 * Ping Google about the update of this sitemap
	 */
	public function ping() {
		// Ping Google. Just do it. Not optional because if you don't want to ping Google you don't need no freaking news sitemap.
		wp_remote_get( 'http://www.google.com/webmasters/tools/ping?sitemap=' . home_url( 'news_sitemap.xml' ) );
	}

	/**
	 * Make sure the parent class knows which meta_boxes content to save.
	 *
	 * @param array $mbs meta boxes that need to be saved.
	 * @return array
	 */
	public function save_meta_boxes( $mbs ) {
		$mbs = array_merge( $mbs, $this->get_meta_boxes() );
		return $mbs;
	}

	/**
	 * Display the tab header in the WP SEO metabox
	 */
	public function tab_header() {
		global $post;

		$options = get_option( "wpseo" );
		if ( isset ( $options['newssitemap_posttypes'] ) && $options['newssitemap_posttypes'] != '' ) {
			foreach ( $options['newssitemap_posttypes'] as $post_type ) {
				if ( $post->post_type == $post_type )
					echo '<li class="news"><a class="wpseo_tablink" href="#wpseo_news">' . __( 'Google News' ) . '</a></li>';
			}
		} else {
			if ( $post->post_type == 'post' )
				echo '<li class="news"><a class="wpseo_tablink" href="#wpseo_news">' . __( 'Google News' ) . '</a></li>';
		}
	}

	/**
	 * Create the content for the tab and return it to the parent class for handling.
	 *
	 * @return string Contents of the tab
	 */
	public function tab_content() {
		global $post;

		$options = get_option( "wpseo" );
		if ( isset( $options['newssitemap_posttypes'] ) && $options['newssitemap_posttypes'] != '' ) {
			if ( !in_array( $post->post_type, $options['newssitemap_posttypes'] ) )
				return;
		} else {
			if ( $post->post_type != 'post' )
				return;
		}

		$content = '';
		foreach ( $this->get_meta_boxes() as $meta_box ) {
			$content .= $this->do_meta_box( $meta_box );
		}
		$this->do_tab( 'news', __( 'Google News' ), $content );
	}

	/**
	 * The metaboxes to display and save for the tab
	 *
	 * @return array $mbs
	 */
	public function get_meta_boxes() {
		$mbs                             = array();
		$options                         = get_option( 'wpseo_xml' );
		$stdgenre                        = ( isset( $options['newssitemap_default_genre'] ) ) ? $options['newssitemap_default_genre'] : 'blog';
		$mbs['newssitemap-include']      = array(
			"name"  => "newssitemap-include",
			"type"  => "checkbox",
			"std"   => 'on',
			"title" => __( "Include in News Sitemap" )
		);
		$mbs['newssitemap-genre']        = array(
			"name"        => "newssitemap-genre",
			"type"        => "multiselect",
			"std"         => $stdgenre,
			"title"       => __( "Google News Genre", 'yoast-wpseo' ),
			"description" => __( "Genre to show in Google News Sitemap.", 'yoast-wpseo' ),
			"options"     => array(
				"none"          => __( "None", 'yoast-wpseo' ),
				"pressrelease"  => __( "Press Release", 'yoast-wpseo' ),
				"satire"        => __( "Satire", 'yoast-wpseo' ),
				"blog"          => __( "Blog", 'yoast-wpseo' ),
				"oped"          => __( "Op-Ed", 'yoast-wpseo' ),
				"opinion"       => __( "Opinion", 'yoast-wpseo' ),
				"usergenerated" => __( "User Generated", 'yoast-wpseo' ),
			),
		);
		$mbs['newssitemap-original']     = array(
			"name"        => "newssitemap-original",
			"std"         => "",
			"type"        => "text",
			"title"       => __( "Original Source", 'yoast-wpseo' ),
			"description" => __( 'Is this article the original source of this news? If not, please enter the URL of the original source here. If there are multiple sources, please separate them by a pipe symbol: | .', 'yoast-wpseo' ),
		);
		$mbs['newssitemap-stocktickers'] = array(
			"name"        => "newssitemap-stocktickers",
			"std"         => "",
			"type"        => "text",
			"title"       => __( "Stock Tickers", 'yoast-wpseo' ),
			"description" => __( 'A comma-separated list of up to 5 stock tickers of the companies, mutual funds, or other financial entities that are the main subject of the article. Each ticker must be prefixed by the name of its stock exchange, and must match its entry in Google Finance. For example, "NASDAQ:AMAT" (but not "NASD:AMAT"), or "BOM:500325" (but not "BOM:RIL").', 'yoast-wpseo' ),
		);
		return $mbs;
	}

}

$wpseo_news_xml_metabox = new WPSEO_XML_News_Sitemap_Metabox();