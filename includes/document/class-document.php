<?php
/**
 * The Document Class
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

use WP_Post;
use NHG\SEO\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Document class.
 */
class Document {

	/**
	 * Hold current document object.
	 *
	 * @var IDocument
	 */
	private $document = null;

	/**
	 * Hold title.
	 *
	 * @var string
	 */
	private $title = null;

	/**
	 * Hold description.
	 *
	 * @var string
	 */
	private $description = null;

	/**
	 * Hold robots.
	 *
	 * @var array
	 */
	private $robots = null;

	/**
	 * Hold canonical.
	 *
	 * @var array
	 */
	private $canonical = null;

	/**
	 * Hold brands.
	 *
	 * @var array
	 */
	private $brands = null;

	/**
	 * Initialize object
	 *
	 * @return Document
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new Document();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Setup document.
	 */
	private function setup() {
		$this->document = $this->get_current_document();

		if ( Helper::is_home_static_page() ) {
			$this->document->set_object( get_queried_object() );
		} elseif ( Helper::is_simple_page() ) {
			$post = WP_Post::get_instance( Helper::get_simple_page_id() );
			$this->document->set_object( $post );
		}
	}

	/**
	 * Get document based on context.
	 *
	 * @return IDocument
	 */
	private function get_current_document() {
		if ( is_search() ) {
			return new Search();
		}

		if ( Helper::is_shop_page() ) {
			return new Shop();
		}

		if ( Helper::is_home_static_page() || Helper::is_simple_page() ) {
			return new Singular();
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$document = new Taxonomy();
			$document->set_object( get_queried_object() );
			return $document;
		}

		return new Error404();
	}

	/**
	 * Get title after sanitization.
	 *
	 * @return string
	 */
	public function get_title() {
		if ( ! is_null( $this->title ) ) {
			return $this->title;
		}

		$this->title = $this->document->title();

		// Early Bail!!
		if ( '' === $this->title ) {
			return $this->title;
		}

		$this->title = preg_replace( '[\s\s+]', ' ', $this->title ); // Remove excess whitespace.
		$this->title = wp_strip_all_tags( stripslashes( $this->title ), true );
		$this->title = convert_smilies( esc_html( $this->title ) );

		return $this->title;
	}

	/**
	 * Get description after sanitization.
	 *
	 * @return string
	 */
	public function get_description() {
		if ( ! is_null( $this->description ) ) {
			return $this->description;
		}

		$this->description = trim( $this->document->description() );

		// Early Bail!!
		if ( '' === $this->description ) {
			return $this->description;
		}

		$this->description = wp_strip_all_tags( stripslashes( $this->description ), true );

		return $this->description;
	}

	/**
	 * Get robots after sanitization.
	 *
	 * @return array
	 */
	public function get_robots() {
		if ( ! is_null( $this->robots ) ) {
			return $this->robots;
		}

		$this->robots = $this->document->robots();
		$this->validate_robots();

		// Respect some robots settings.
		// Force override to respect the WP settings.
		if ( 0 === absint( get_option( 'blog_public' ) ) || filter_has_var( INPUT_GET, 'replytocom' ) ) {
			$this->robots['index'] = 'noindex';
		}
		// For WooCommerce override.
		if ( is_cart() || is_checkout() || is_account_page() ) {
			remove_action( 'wp_head', 'wc_page_noindex' );
			return [
				'index'  => 'noindex',
				'follow' => 'follow',
			];
		}

		$this->robots = array_unique( $this->robots );

		return $this->robots;
	}

	/**
	 * Validate robots.
	 */
	private function validate_robots() {
		if ( empty( $this->robots ) || ! is_array( $this->robots ) ) {
			$this->robots = [
				'index'  => 'index',
				'follow' => 'follow',
			];
			return;
		}

		$this->robots = array_intersect_key(
			$this->robots,
			[
				'index'        => '',
				'follow'       => '',
				'noarchive'    => '',
				'noimageindex' => '',
				'nosnippet'    => '',
			]
		);

		// Add Index and Follow.
		if ( ! isset( $this->robots['index'] ) ) {
			$this->robots = [ 'index' => 'index' ] + $this->robots;
		}

		if ( ! isset( $this->robots['follow'] ) ) {
			$this->robots = [ 'follow' => 'follow' ] + $this->robots;
		}
	}

	/**
	 * Get canonical after sanitization.
	 *
	 * @param  bool $un_paged    Whether or not to return the canonical with or without pagination added to the URL.
	 * @param  bool $no_override Whether or not to return a manually overridden canonical.
	 * @return string
	 */
	public function get_canonical( $un_paged = false, $no_override = false ) {
		if ( is_null( $this->canonical ) ) {
			$this->generate_canonical();
		}

		$canonical = $this->canonical['canonical'];
		if ( $un_paged ) {
			$canonical = $this->canonical['canonical_unpaged'];
		} elseif ( $no_override ) {
			$canonical = $this->canonical['canonical_no_override'];
		}

		return $canonical;
	}

	/**
	 * Generate canonical URL parts.
	 */
	private function generate_canonical() {
		$this->canonical = wp_parse_args(
			$this->document->canonical(),
			[
				'canonical'          => '',
				'canonical_unpaged'  => '',
				'canonical_override' => '',
			]
		);
		extract( $this->canonical ); // phpcs:ignore

		if ( is_front_page() || ( function_exists( 'ampforwp_is_front_page' ) && ampforwp_is_front_page() ) ) {
			$canonical = user_trailingslashit( home_url() );
		}

		// If not singular than we can have pagination.
		if ( ! is_singular() ) {
			$canonical_unpaged = $canonical;
			$canonical         = $this->get_canonical_paged( $canonical, get_query_var( 'paged' ) );
		}

		$this->canonical['canonical_unpaged']     = $canonical_unpaged;
		$this->canonical['canonical_no_override'] = $canonical;

		// Force canonical links to be absolute, relative is NOT an option.
		$canonical = '' !== $canonical && true === Helper::is_relative( $canonical ) ? $this->base_url( $canonical ) : $canonical;
		$canonical = '' !== $canonical_override ? $canonical_override : $canonical;

		$this->canonical['canonical'] = $canonical;
	}

	/**
	 * Get canonical paged
	 *
	 * @param  string $url                   The un-paginated URL of the current archive.
	 * @param  string $page                  The page number to add on to $url for the $link tag.
	 * @param  bool   $add_pagination_base   Optional. Whether to add the pagination base (`page`) to the url.
	 * @param  string $pagination_query_name Optional. The name of the query argument that holds the current page.
	 * @return string
	 */
	public function get_canonical_paged( $url, $page, $add_pagination_base = true, $pagination_query_name = 'page' ) {
		global $wp_rewrite;

		if ( ! $url || $page < 2 ) {
			return $url;
		}

		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $url );
			if ( $add_pagination_base ) {
				$url .= trailingslashit( $wp_rewrite->pagination_base );
			}

			return user_trailingslashit( $url . $page );
		}

		return add_query_arg( $pagination_query_name, $page, \user_trailingslashit( $url ) );
	}

	/**
	 * Parse the home URL setting to find the base URL for relative URLs.
	 *
	 * @param  string $path Optional path string.
	 * @return string
	 */
	private function base_url( $path = null ) {
		$parts    = wp_parse_url( get_option( 'home' ) );
		$base_url = trailingslashit( $parts['scheme'] . '://' . $parts['host'] );

		if ( ! is_null( $path ) ) {
			$base_url .= ltrim( $path, '/' );
		}

		return $base_url;
	}

	/**
	 * Returns the array of brand taxonomy.
	 *
	 * @return bool|array
	 */
	public function get_brands() {
		$product      = wc_get_product();
		$this->brands = false;

		// Early Bail!!
		if ( ! is_object( $product ) ) {
			return false;
		}

		$taxonomy = 'product_brand';
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		$brands       = wp_get_post_terms( $product->get_id(), $taxonomy );
		$this->brands = empty( $brands ) || is_wp_error( $brands ) ? false : $brands;

		return $this->brands;
	}
}
