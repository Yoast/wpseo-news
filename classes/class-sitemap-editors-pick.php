<?php

class WPSEO_News_Sitemap_Editors_Pick {

	/**
	 * Store the editors picks.
	 *
	 * @var array
	 */
	private $items;

	/**
	 * Construct the Class-Sitemap-Editors-Pick rss feed generator. We set the WP Seo options and we
	 * find the editors picks items and store them in the $items var
	 */
	public function __construct() {
		$this->prepare_items();
	}

	/**
	 * Generate the Editors' Picks URL.
	 *
	 * @param boolean $show_headers
	 */
	public function generate_rss( $show_headers = true ) {

		$options = WPSEO_News::get_options();

		// Show output as XML
		if ( $show_headers ) {
			header( 'Content-Type: application/rss+xml; charset=' . get_bloginfo( 'charset' ) );
		}

		echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '" ?>' . PHP_EOL;
		echo '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">' . PHP_EOL;
		echo '<channel>' . PHP_EOL;

		// Atom channel elements
		echo '<atom:link href="' . get_site_url() . '/editors-pick.rss" rel="self" type="application/rss+xml" />' . PHP_EOL;

		// Display the main channel tags
		echo '<link>' . get_site_url() . '</link>' . PHP_EOL;
		echo '<description>' . get_bloginfo( 'description' ) . '</description>' . PHP_EOL;
		echo '<title>' . get_bloginfo( 'name' ) . '</title>' . PHP_EOL;

		// Display the image tag if an image is set
		if ( isset( $options['ep_image_src'] ) && $options['ep_image_src'] != '' ) {
			$this->show_image( $options['ep_image_src'] );
		}

		// Showing the items
		$this->show_items();

		echo '</channel>' . PHP_EOL;
		echo '</rss>' . PHP_EOL;

	}

	/**
	 * Prepare RSS feed data
	 */
	private function prepare_items() {
		$this->items = array();

		// Remove the wptexturize filter
		remove_filter( 'the_title', 'wptexturize' );
		remove_filter( 'the_content', 'wptexturize' );

		// EP Query
		$ep_query = $this->get_ep_query();

		// The Loop
		if ( $ep_query->have_posts() ) {
			$this->set_items( $ep_query );
		}

		/* Restore original Post Data */
		wp_reset_postdata();
	}

	/**
	 * Create a wp_query object and return this
	 *
	 * @return WP_Query
	 */
	private function get_ep_query() {
		return new WP_Query(
			array(
				'post_type'   => WPSEO_News::get_included_post_types(),
				'post_status' => 'publish',
				'meta_query'  => array(
					array(
						'key'   => '_yoast_wpseo_newssitemap-editors-pick',
						'value' => 'on',
					),
				),
				'order'       => 'DESC',
				'orderby'     => 'date',
			)
		);
	}

	/**
	 * Setting the items for the editors picks.
	 *
	 * @param $ep_query
	 */
	private function set_items( $ep_query ) {
		while ( $ep_query->have_posts() ) {
			$ep_query->the_post();

			$this->set_item();
		}
	}

	/**
	 * Add a single item to $this->items
	 */
	private function set_item() {
		$wpseo_news_item = apply_filters( 'wpseo_news_set_item', array(
			'title'        => get_the_title(),
			'link'         => get_permalink(),
			'description'  => get_the_excerpt(),
			'creator'      => get_the_author_meta( 'display_name' ),
			'published_on' => get_the_date( DATE_RFC822 ),
		) );

		$this->items[] = $wpseo_news_item;
	}

	/**
	 * Loop through the item to show each item
	 */
	private function show_items() {
		// Display the items
		if ( ! empty( $this->items ) ) {
			foreach ( $this->items as $item ) {
				$this->show_item( $item );
			}
		}
	}

	/**
	 * Showing item as XML
	 *
	 * @param array $item
	 */
	private function show_item( $item ) {
		echo '<item>' . PHP_EOL;
		echo '<title><![CDATA[' . $item['title'] . ']]></title>' . PHP_EOL;
		echo '<guid isPermaLink="true">' . $item['link'] . '</guid>' . PHP_EOL;
		echo '<link>' . $item['link'] . '</link>' . PHP_EOL;
		echo '<description><![CDATA[' . $item['description'] . ']]></description>' . PHP_EOL;
		echo '<dc:creator><![CDATA[' . $item['creator'] . ']]></dc:creator>' . PHP_EOL;
		echo '<pubDate>' . $item['published_on'] . '</pubDate>' . PHP_EOL;
		echo '</item>' . PHP_EOL;
	}

	/**
	 * Showing image as XML
	 *
	 * @param string $image_src
	 */
	private function show_image( $image_src ) {
		echo '<image>' . PHP_EOL;
		echo '<url>' . $image_src . '</url>' . PHP_EOL;
		echo '<title>' . get_bloginfo( 'name' ) . '</title>' . PHP_EOL;
		echo '<link>' . get_site_url() . '</link>' . PHP_EOL;
		echo '</image>' . PHP_EOL;
	}
}
