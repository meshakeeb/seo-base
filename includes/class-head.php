<?php
/**
 * The Head.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

use NHG\SEO\Helper;
use NHG\SEO\Document\Strategy;
use NHG\SEO\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Head.
 */
class Head {

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_filter( 'wp_title', [ $this, 'title' ], 15 );
		add_filter( 'thematic_doctitle', [ $this, 'title' ], 15 );
		add_filter( 'pre_get_document_title', [ $this, 'title' ], 15 );

		// Code to move title.
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );

		// Print document parts.
		add_action( 'nhg_seo_head', '_wp_render_title_tag', 1 );
		add_action( 'nhg_seo_head', [ $this, 'metadesc' ], 6 );
		add_action( 'nhg_seo_head', [ $this, 'robots' ], 10 );
		add_action( 'nhg_seo_head', [ $this, 'canonical' ], 15 );
		add_action( 'nhg_seo_head', [ $this, 'adjacent_rel_links' ], 20 );

		// Webmaster tools.
		add_action( 'nhg_seo_head', [ $this, 'webmaster_tools' ], 99 );
	}

	/**
	 * Main title function.
	 *
	 * @param  string $title Already set title or empty string.
	 * @return string
	 */
	public function title( $title ) {
		if ( is_feed() ) {
			return $title;
		}

		$generated = Document::get()->get_title();
		return '' !== $generated ? $generated : $title;
	}

	/**
	 * Output the meta description tag with the generated description.
	 */
	public function metadesc() {
		$generated = Document::get()->get_description();

		if ( '' !== $generated ) {
			echo '<meta name="description" content="' . Helper::truncate( esc_attr( $generated ), 160 ) . '"/>', "\n"; // phpcs:ignore
		}
	}

	/**
	 * Output the meta robots tag.
	 */
	public function robots() {
		$robots    = Document::get()->get_robots();
		$robotsstr = join( ', ', $robots );
		if ( '' !== $robotsstr ) {
			echo '<meta name="robots" content="', esc_attr( $robotsstr ), '"/>', "\n";
		}

		// If a page is noindex, let's remove the canonical URL.
		if ( isset( $robots['index'] ) && 'noindex' === $robots['index'] ) {
			remove_action( 'nhg_seo_head', [ $this, 'canonical' ], 15 );
			remove_action( 'nhg_seo_head', [ $this, 'adjacent_rel_links' ], 20 );
		}
	}

	/**
	 * Output the canonical URL tag.
	 */
	public function canonical() {
		$canonical = Document::get()->get_canonical();
		if ( '' !== $canonical ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical, null, 'other' ) . '" />' . "\n";
		}
	}

	/**
	 * Output authentication codes Webmaster Tools.
	 */
	public function webmaster_tools() {
		$tools = [
			'google_site_verification' => 'google-site-verification',
		];

		$site = ( 'facebook_url' );

		foreach ( $tools as $id => $name ) {
			$content = trim( Strategy::get_option( $id ) );
			if ( empty( $content ) ) {
				continue;
			}

			printf( '<meta name="%1$s" content="%2$s" />' . "\n", esc_attr( $name ), esc_attr( $content ) );
		}
	}

	/**
	 * Add the rel 'prev' and 'next' links to archives or single posts.
	 *
	 * @link http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
	 */
	public function adjacent_rel_links() {
		if ( is_home() ) {
			return;
		}

		if ( is_singular() ) {
			$this->adjacent_rel_links_single();
			return;
		}

		$this->adjacent_rel_links_archive();
	}

	/**
	 * Output the rel next/prev tags on a paginated single post.
	 *
	 * @return void
	 */
	private function adjacent_rel_links_single() {
		$num_pages = 1;

		$queried_object = get_queried_object();
		if ( ! empty( $queried_object ) ) {
			$num_pages = substr_count( $queried_object->post_content, '<!--nextpage-->' ) + 1;
		}

		if ( 1 === $num_pages ) {
			return;
		}

		$page = max( 1, (int) get_query_var( 'page' ) );
		$url  = get_permalink( get_queried_object_id() );

		if ( $page > 1 ) {
			$this->adjacent_rel_link( 'prev', $url, $page - 1, 'page' );
		}

		if ( $page < $num_pages ) {
			$this->adjacent_rel_link( 'next', $url, $page + 1, 'page' );
		}
	}

	/**
	 * Output the rel next/prev tags on archives.
	 */
	private function adjacent_rel_links_archive() {
		$url = Document::get()->get_canonical( true, true );
		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( 2 === $paged ) {
			$this->adjacent_rel_link( 'prev', $url, $paged - 1 );
		}

		if ( is_front_page() ) {
			$url = home_url( '/' );
		}

		if ( $paged > 2 ) {
			$this->adjacent_rel_link( 'prev', $url, $paged - 1 );
		}

		if ( $paged < $GLOBALS['wp_query']->max_num_pages ) {
			$this->adjacent_rel_link( 'next', $url, $paged + 1 );
		}
	}

	/**
	 * Build adjacent page link for archives.
	 *
	 * @param string $rel       Prev or next.
	 * @param string $url       The current archive URL without page parameter.
	 * @param string $page      The page number added to the $url in the link tag.
	 * @param string $query_arg The pagination query argument to use for the $url.
	 */
	private function adjacent_rel_link( $rel, $url, $page, $query_arg = 'paged' ) {
		$url = Document::get()->get_canonical_paged( $url, $page, true, $query_arg );
		echo '<link rel="' . esc_attr( $rel ) . '" href="' . esc_url( $url ) . "\" />\n";
	}
}
