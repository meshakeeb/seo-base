<?php
/**
 * The Facebook metadata.
 *
 * @package    NHG
 * @subpackage NHG\SEO\OpenGraph
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\OpenGraph;

use NHG\SEO\Helper;
use NHG\SEO\Document\Document;
use NHG\SEO\Document\Strategy;

defined( 'ABSPATH' ) || exit;

/**
 * Facebook.
 */
class Facebook extends Base {

	/**
	 * Network slug.
	 *
	 * @var string
	 */
	public $network = 'facebook';

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_filter( 'jetpack_enable_open_graph', '__return_false' );

		if ( isset( $GLOBALS['fb_ver'] ) || class_exists( 'Facebook_Loader', false ) ) {
			add_filter( 'fb_meta_tags', [ $this, 'facebook_filter' ], 10, 1 );
			return;
		}

		add_filter( 'language_attributes', [ $this, 'add_namespace' ], 15 );
		add_action( 'nhg_seo_head', [ $this, 'locale' ], 30, 0 );
		add_action( 'nhg_seo_head', [ $this, 'type' ], 30, 0 );
		add_action( 'nhg_seo_head', [ $this, 'title' ], 30, 0 );
		add_action( 'nhg_seo_head', [ $this, 'description' ], 30, 0 );
		add_action( 'nhg_seo_head', [ $this, 'url' ], 30 );
		add_action( 'nhg_seo_head', [ $this, 'site_name' ], 30 );
		add_action( 'nhg_seo_head', [ $this, 'website' ], 30 );
		add_action( 'nhg_seo_head', [ $this, 'site_owner' ], 30 );
		add_action( 'nhg_seo_head', [ $this, 'images' ], 30, 0 );

		if ( is_singular( 'product' ) ) {
			add_action( 'nhg_seo_head', [ $this, 'product_data' ], 30 );
		}
	}

	/**
	 * Filter the Facebook plugins metadata.
	 *
	 * @param  array $meta_tags The array to fix.
	 * @return array
	 */
	public function facebook_filter( $meta_tags ) {
		$meta_tags['http://ogp.me/ns#type']  = $this->type( false );
		$meta_tags['http://ogp.me/ns#title'] = $this->title( false );

		// Filter the locale too because the Facebook plugin locale code is not as good as ours.
		$meta_tags['http://ogp.me/ns#locale'] = $this->locale( false );

		$desc = $this->description( false );
		if ( ! empty( $desc ) ) {
			$meta_tags['http://ogp.me/ns#description'] = $desc;
		}

		return $meta_tags;
	}

	/**
	 * Adds prefix attributes to the <html> tag.
	 *
	 * @param  string $input The input namespace string.
	 * @return string
	 */
	public function add_namespace( $input ) {
		if ( is_singular( 'product' ) ) {
			return $input . ' prefix="og: http://ogp.me/ns# product: https://ogp.me/ns/product#"';
		}

		return $input . ' prefix="og: http://ogp.me/ns#"';
	}

	/**
	 * Output the locale, doing some conversions to make sure the proper Facebook locale is outputted.
	 *
	 * @see  http://www.facebook.com/translations/FacebookLocales.xml for the list of supported locales
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/article/
	 *
	 * @param  bool $echo Whether to echo or return the locale.
	 * @return string
	 */
	public function locale( $echo = true ) {
		$locale = get_locale();
		$locale = Facebook_Locale::get( $locale );

		if ( $echo ) {
			$this->tag( 'og:locale', $locale );
		}

		return $locale;
	}

	/**
	 * Output the OpenGraph type.
	 *
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/object/
	 *
	 * @param  bool $echo Whether to echo or return the type.
	 * @return string
	 */
	public function type( $echo = true ) {
		$type = $this->get_type();
		if ( '' !== $type && $echo ) {
			$this->tag( 'og:type', $type );
		}

		return $type;
	}

	/**
	 * Outputs the SEO title as OpenGraph title.
	 *
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/article/
	 *
	 * @param  bool $echo Whether or not to echo the output.
	 * @return string
	 */
	public function title( $echo = true ) {
		$title = trim( $this->get_title() );
		if ( $echo ) {
			$this->tag( 'og:title', $title );
		}

		return $title;
	}

	/**
	 * Output the OpenGraph description, specific OG description first, if not, grab the meta description.
	 *
	 * @param  bool $echo Whether to echo or return the description.
	 * @return string
	 */
	public function description( $echo = true ) {
		$desc = trim( $this->get_description() );
		if ( $echo ) {
			$this->tag( 'og:description', $desc );
		}

		return $desc;
	}

	/**
	 * Outputs the canonical URL as OpenGraph URL, which consolidates likes and shares.
	 *
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/article/
	 */
	public function url() {
		$this->tag( 'og:url', esc_url( $this->get_canonical() ) );
	}

	/**
	 * Output the site name straight from the blog info.
	 */
	public function site_name() {
		$this->tag( 'og:site_name', get_bloginfo( 'name' ) );
	}

	/**
	 * Outputs the websites FB page.
	 *
	 * @link https://developers.facebook.com/blog/post/2013/06/19/platform-updates--new-open-graph-tags-for-media-publishers-and-more/
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/article/
	 */
	public function website() {
		$site = Strategy::get_option( 'facebook_url' );
		if ( 'article' === $this->get_type() && '' !== $site ) {
			$this->tag( 'article:publisher', $site );
		}
	}

	/**
	 * Outputs the site owner.
	 *
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/article/
	 */
	public function site_owner() {
		$app_id = Strategy::get_option( 'facebook_app_id' );
		if ( 0 !== absint( $app_id ) ) {
			$this->tag( 'fb:app_id', $app_id );
			return;
		}

		$admins = Strategy::get_option( 'facebook_admin_id' );
		if ( '' !== trim( $admins ) ) {
			$this->tag( 'fb:admins', $admins );
			return;
		}
	}

	/**
	 * Create new Image class and get the images to set the `og:image`.
	 *
	 * @param string|bool $image Optional. Image URL.
	 */
	public function images( $image = false ) {
		$meta = false;
		foreach ( Image::get()->get_images() as $image => $image_meta ) {
			$this->image_tag( $image_meta );
			if ( ! $meta ) {
				$this->image_meta( $image_meta );
			}
			$meta = true;
		}
	}

	/**
	 * Adds the other product images to the OpenGraph output.
	 */
	public function product_data() {
		$product = wc_get_product();
		if ( ! is_object( $product ) ) {
			return;
		}

		$brands = Document::get()->get_brands();
		if ( ! empty( $brands ) ) {
			$this->tag( 'product:brand', $brands[0]->name );
		}

		$this->tag( 'product:price:amount', $product->get_price() );
		$this->tag( 'product:price:currency', get_woocommerce_currency() );

		if ( $product->is_in_stock() ) {
			$this->tag( 'product:availability', 'instock' );
		}
	}

	/**
	 * Outputs an image tag based on whether it's https or not.
	 *
	 * @param array $image_meta Image metadata.
	 */
	private function image_tag( $image_meta ) {
		$og_image = $image_meta['url'];
		$this->tag( 'og:image', esc_url( $og_image ) );

		// Add secure URL if detected. Not all services implement this, so the regular one also needs to be rendered.
		if ( Helper::str_starts_with( 'https://', $og_image ) ) {
			$this->tag( 'og:image:secure_url', esc_url( $og_image ) );
		}
	}

	/**
	 * Output the image metadata.
	 *
	 * @param array $image_meta Image meta data to output.
	 */
	private function image_meta( $image_meta ) {
		$image_tags = [ 'width', 'height', 'alt', 'type' ];
		foreach ( $image_tags as $key ) {
			if ( ! empty( $image_meta[ $key ] ) ) {
				$this->tag( 'og:image:' . $key, $image_meta[ $key ] );
			}
		}
	}

	/**
	 * Get type.
	 *
	 * @return string
	 */
	private function get_type() {
		if ( is_front_page() || is_home() ) {
			return 'website';
		}

		// We use "object" for archives etc. as article doesn't apply there.
		if ( ! is_singular() ) {
			return 'object';
		}

		return Helper::is_product() ? 'product' : 'article';
	}
}
