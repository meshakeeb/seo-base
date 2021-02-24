<?php
/**
 * The Frontend.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
class Frontend {

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_action( 'wp', [ $this, 'integrations' ] );
		add_action( 'wp', [ $this, 'archive_redirect' ] );
		add_action( 'wp', [ $this, 'attachment_redirect_urls' ] );

		// Head.
		add_action( 'wp_head', [ $this, 'head' ], 1 );
		add_action( 'wp_head', [ $this, 'remove_cores' ], 0 );
		remove_action( 'get_the_generator_html', 'wc_generator_tag', 10 );
		remove_action( 'get_the_generator_xhtml', 'wc_generator_tag', 10 );
	}

	/**
	 * Head placeholder.
	 */
	public function head() {
		global $wp_query;

		$old_wp_query = null;
		if ( ! $wp_query->is_main_query() ) {
			$old_wp_query = $wp_query;
			wp_reset_query();
		}

		echo "\n<!-- " . esc_html__( 'NHG SEO plugin', 'nhg-seo' ) . " -->\n";

		/**
		 * Add extra output in the head tag.
		 */
		do_action( 'nhg_seo_head' );

		echo '<!-- /' . esc_html__( 'NHG SEO plugin', 'nhg-seo' ) . " -->\n\n";

		if ( ! empty( $old_wp_query ) ) {
			$wp_query = $old_wp_query;
			unset( $old_wp_query );
		}
	}

	/**
	 * Remove core actions, now handled by us.
	 */
	public function remove_cores() {
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'noindex', 1 );
	}

	/**
	 * Initialize integrations.
	 */
	public function integrations() {
		$type = get_query_var( 'sitemap' );
		if ( ! empty( $type ) || is_customize_preview() ) {
			return;
		}

		// Load document.
		Document\Document::get();

		// OpenGraph.
		OpenGraph\Image::get();
		( new OpenGraph\Facebook() )->hooks();
		( new OpenGraph\Twitter() )->hooks();

		// LD-JSON.
		( new Breadcrumbs() )->hooks();
		( new Product() )->hooks();
		( new Rich_Snippet() )->hooks();

		Plugin::get()->head = new Head();
		Plugin::get()->head->hooks();
	}

	/**
	 * Redirects attachment to its parent post if it has one.
	 */
	public function attachment_redirect_urls() {
		global $post;

		// Early bail.
		if ( ! is_attachment() ) {
			return;
		}

		$redirect = ! empty( $post->post_parent )
			? get_permalink( $post->post_parent )
			: home_url( '/' );

		/**
		 * Redirect atachment to its parent post.
		 *
		 * @param string  $redirect URL as calculated for redirection.
		 * @param WP_Post $post     Current post instance.
		 */
		$this->redirect( $redirect, 301 );
	}

	/**
	 * When certain archives are disabled, this redirects those to the homepage.
	 */
	public function archive_redirect() {
		global $wp_query;

		if ( $wp_query->is_date || $wp_query->is_author ) {
			$this->redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/**
	 * Wraps wp_safe_redirect to add header.
	 *
	 * @param string $location The path to redirect to.
	 * @param int    $status   Status code to use.
	 */
	private function redirect( $location, $status = 302 ) {
		header( 'X-Redirect-By: Tights' );
		wp_safe_redirect( $location, $status );
		exit;
	}
}
