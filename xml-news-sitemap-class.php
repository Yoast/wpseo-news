<?php

/**
 * Class that handles the output of the XML News sitemap, the addition to the index sitemap and output in the <head>.
 */
class WPSEO_XML_News_Sitemap {

	/**
	 * Options array
	 */
	private $options = array();

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->options = get_option('wpseo_news');

		add_action( 'init', array( $this, 'init' ), 10 );
		add_action( 'wpseo_head', array( $this, 'head' ) );
		add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_index' ) );
	}

	/**
	 * Register the XML News sitemap with the main sitemap class.
	 */
	public function init() {
		$GLOBALS['wpseo_sitemaps']->register_sitemap( 'news', array( $this, 'build_news_sitemap' ) );
	}

	/**
	 * Add the XML News Sitemap to the Sitemap Index.
	 *
	 * @param string $str String with Index sitemap content.
	 * @return string
	 */
	function add_to_index( $str ) {
		$result = strtotime( get_lastpostmodified( 'gmt' ) );
		$date   = date( 'c', $result );

		$str .= '<sitemap>' . "\n";
		$str .= '<loc>' . home_url( 'news-sitemap.xml' ) . '</loc>' . "\n";
		$str .= '<lastmod>' . $date . '</lastmod>' . "\n";
		$str .= '</sitemap>' . "\n";
		return $str;
	}

	/**
	 * Build the sitemap and push it to the XML Sitemaps Class instance for display.
	 */
	public function build_news_sitemap() {
		global $wpdb;

		if ( isset( $this->options['newssitemap_posttypes'] ) && $this->options['newssitemap_posttypes'] != '' ) {
			$post_types = array_map( 'sanitize_key', $this->options['newssitemap_posttypes'] );
			$post_types = "'" . implode( "','", $post_types ) . "'";
		} else {
			$post_types = "'post'";
		}

		// Get posts for the last two days only, credit to Alex Moss for this code.
		$items = $wpdb->get_results( "SELECT ID, post_content, post_name, post_author, post_parent, post_modified_gmt, post_date, post_date_gmt, post_title, post_type
									FROM $wpdb->posts
									WHERE post_status='publish'
									AND (DATEDIFF(CURDATE(), post_date_gmt)<=2)
									AND post_type IN ($post_types)
									ORDER BY post_date_gmt DESC
									LIMIT 0, 1000" );

		$output = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
		xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" 
		xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

//		echo '<!--'.print_r($items,1).'-->';

		if ( !empty( $items ) ) {
			foreach ( $items as $item ) {
				$item->post_status = 'publish';

				if ( false != wpseo_get_value( 'newssitemap-include', $item->ID ) && wpseo_get_value( 'newssitemap-include', $item->ID ) == 'off' )
					continue;

				if ( false != wpseo_get_value( 'meta-robots', $item->ID ) && strpos( wpseo_get_value( 'meta-robots', $item->ID ), 'noindex' ) !== false )
					continue;

				if ( 'post' == $item->post_type ) {
					$cats    = get_the_terms( $item->ID, 'category' );
					$exclude = 0;
					foreach ( $cats as $cat ) {
						if ( isset( $this->options['newssitemap_excludecats'][$cat->slug] ) ) {
							$exclude++;
						}
					}
					if ( $exclude >= count( $cats ) )
						continue;
				}

				$publication_name = !empty( $this->options['newssitemapname'] ) ? $this->options['newssitemapname'] : get_bloginfo( 'name' );
				$publication_lang = substr( get_locale(), 0, 2 );

				$keywords = explode( ',', trim( wpseo_get_value( 'newssitemap-keywords', $item->ID ) ) );
				$tags     = get_the_terms( $item->ID, 'post_tag' );
				if ( $tags )
					foreach ( $tags as $tag )
						$keywords[] = $tag->name;

				// TODO: add suggested keywords to each post based on category, next to the entire site
				if ( isset( $this->options['newssitemap_default_keywords'] ) && $this->options['newssitemap_default_keywords'] != '' )
					array_merge( $keywords, explode( ',', $this->options['newssitemap_default_keywords'] ) );
				$keywords = strtolower( implode( ', ', $keywords ) );

				$genre = wpseo_get_value( 'newssitemap-genre', $item->ID );
				if ( is_array( $genre ) )
					$genre = implode( ',', $genre );

				if ( $genre == '' && isset( $this->options['newssitemap_default_genre'] ) && $this->options['newssitemap_default_genre'] != '' )
					$genre = $this->options['newssitemap_default_genre'];
				$genre = trim( preg_replace( '/^none,?/', '', $genre ) );

				$stock_tickers = trim( wpseo_get_value( 'newssitemap-stocktickers' ) );
				if ( $stock_tickers != '' )
					$stock_tickers = "\t\t<stock_tickers>" . htmlspecialchars( $stock_tickers ) . '</stock_tickers>' . "\n";

				$output .= '<url>' . "\n";
				$output .= "\t<loc>" . get_permalink( $item ) . '</loc>' . "\n";
				$output .= "\t<news:news>\n";
				$output .= "\t\t<news:publication>" . "\n";
				$output .= "\t\t\t<news:name>" . htmlspecialchars( $publication_name ) . '</news:name>' . "\n";
				$output .= "\t\t\t<news:language>" . htmlspecialchars( $publication_lang ) . '</news:language>' . "\n";
				$output .= "\t\t</news:publication>\n";
				if ( !empty( $genre ) )
					$output .= "\t\t<news:genres>" . htmlspecialchars( $genre ) . '</news:genres>' . "\n";
				$output .= "\t\t<news:publication_date>" . mysql2date( 'c', $item->post_date_gmt ) . '</news:publication_date>' . "\n";
				$output .= "\t\t<news:title>" . htmlspecialchars( $item->post_title ) . '</news:title>' . "\n";
				if ( !empty( $keywords ) )
					$output .= "\t\t<news:keywords>" . htmlspecialchars( $keywords ) . '</news:keywords>' . "\n";
				$output .= $stock_tickers;
				$output .= "\t</news:news>\n";

				$images = array();
				if ( preg_match_all( '/<img [^>]+>/', $item->post_content, $matches ) ) {
					foreach ( $matches[0] as $img ) {
						if ( preg_match( '/src=("|\')([^"|\']+)("|\')/', $img, $match ) ) {
							$src = $match[2];
							if ( strpos( $src, 'http' ) !== 0 ) {
								if ( $src[0] != '/' )
									continue;
								$src = get_bloginfo( 'url' ) . $src;
							}

							if ( $src != esc_url( $src ) )
								continue;

							if ( isset( $url['images'][$src] ) )
								continue;

							$image = array();
							if ( preg_match( '/title=("|\')([^"\']+)("|\')/', $img, $match ) )
								$image['title'] = str_replace( array( '-', '_' ), ' ', $match[2] );

							if ( preg_match( '/alt=("|\')([^"\']+)("|\')/', $img, $match ) )
								$image['alt'] = str_replace( array( '-', '_' ), ' ', $match[2] );

							$images[$src] = $image;
						}
					}
				}

				if ( isset( $images ) && count( $images ) > 0 ) {
					foreach ( $images as $src => $img ) {
						$output .= "\t\t<image:image>\n";
						$output .= "\t\t\t<image:loc>" . htmlspecialchars( $src ) . "</image:loc>\n";
						if ( isset( $img['title'] ) )
							$output .= "\t\t\t<image:title>" . htmlspecialchars( $img['title'] ) . "</image:title>\n";
						if ( isset( $img['alt'] ) )
							$output .= "\t\t\t<image:caption>" . htmlspecialchars( $img['alt'] ) . "</image:caption>\n";
						$output .= "\t\t</image:image>\n";
					}
				}

				$output .= '</url>' . "\n";
			}
		}

		$output .= '</urlset>';
		$GLOBALS['wpseo_sitemaps']->set_sitemap( $output );
		$GLOBALS['wpseo_sitemaps']->set_stylesheet( '<?xml-stylesheet type="text/xsl" href="' . plugin_dir_url( __FILE__ ) . 'xml-news-sitemap.xsl"?>' );
	}

	/**
	 * Display the optional sources link elements in the <code>&lt;head&gt;</code>.
	 */
	public function head() {
		if ( is_singular() ) {
			global $post;

			$meta_news_keywords = trim( wpseo_get_value( 'newssitemap-keywords', $post->ID ) );
			if ( !empty( $meta_news_keywords ) ) {
				echo '<meta name="news_keywords" content="' . $meta_news_keywords . '" />' . "\n";
			}

			$original_source = trim( wpseo_get_value( 'newssitemap-original', $post->ID ) );
			if ( !empty( $original_source ) ) {
				echo '' ;//issue in head, has no output, If left blank it stays blank, that is why removed.
			} else {
				$sources = explode( '|', $original_source );
				foreach ( $sources as $source )
					echo '';//issue in head, it outputs the Post Permalink instead of original source one
			}
		}
	}
}

$wpseo_news_xml = new WPSEO_XML_News_Sitemap();
