<?php
/**
 * The Twitter metadata.
 *
 * @package    NHG
 * @subpackage NHG\SEO\OpenGraph
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\OpenGraph;

use NHG\SEO\Document\Strategy;

defined( 'ABSPATH' ) || exit;

/**
 * Twitter.
 */
class Twitter extends Base {

	/**
	 * Network slug.
	 *
	 * @var string
	 */
	public $network = 'twitter';

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_action( 'nhg_seo_head', [ $this, 'type' ], 40 );
		add_action( 'nhg_seo_head', [ $this, 'title' ], 40 );
		add_action( 'nhg_seo_head', [ $this, 'description' ], 40 );
		add_action( 'nhg_seo_head', [ $this, 'website' ], 40 );
		add_action( 'nhg_seo_head', [ $this, 'image' ], 40 );
	}

	/**
	 * Display the Twitter card type.
	 */
	public function type() {
		$this->type = 'summary_large_image';
		$this->tag( 'twitter:card', $this->type );
	}

	/**
	 * Output the title.
	 */
	public function title() {
		$this->tag( 'twitter:title', $this->get_title() );
	}

	/**
	 * Output the description.
	 */
	public function description() {
		$this->tag( 'twitter:description', $this->get_description() );
	}

	/**
	 * Output the Twitter account for the site.
	 */
	public function website() {
		$site = Strategy::get_option( 'twitter_username' );
		if ( '' !== $site ) {
			$this->tag( 'twitter:site', '@' . $site );
		}
	}

	/**
	 * Output the image for Twitter.
	 *
	 * Only used when OpenGraph is inactive or Summary Large Image card is chosen.
	 */
	public function image() {
		if ( ! Image::get()->has_images() ) {
			return;
		}

		$image = current( Image::get()->get_images() );
		$this->tag( 'twitter:image', esc_url( $image['url'] ) );
	}
}
