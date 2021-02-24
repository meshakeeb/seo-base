<?php
/**
 * Plugin loader.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Loader.
 */
class Plugin {

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Plugin
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new Plugin();
		}

		return $instance;
	}

	/**
	 * Set plugin paths.
	 *
	 * @param  string $file File.
	 * @return Plugin
	 */
	public function set_paths( $file ) {
		$this->path     = dirname( $file ) . '/';
		$this->url      = plugins_url( '', $file ) . '/';
		$this->rel_path = dirname( plugin_basename( $file ) );

		// Define constants.
		define( 'NHG_SEO_FILE', $file );

		return $this;
	}

	/**
	 * Instantiate the plugin.
	 *
	 * @return Plugin
	 */
	public function setup() {
		// Initiate classes.
		new Installer();

		if ( is_admin() ) {
			( new Admin\Metabox() )->hooks();
		}

		if ( ! is_admin() ) {
			( new Frontend() )->hooks();
		}

		( new Sitemap() )->hooks();

		// Hooks.
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_filter( 'acf/settings/load_json', [ $this, 'add_acf_path' ] );

		return $this;
	}

	/**
	 * Setup internationlization.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'nhg-seo',
			false,
			$this->rel_path . '/languages'
		);
	}

	/**
	 * Add ACF json path.
	 *
	 * @param  array $paths Array of json paths.
	 * @return array
	 */
	public function add_acf_path( $paths ) {
		$paths[] = $this->path . 'assets/acf-json';

		return $paths;
	}

	/**
	 * Get primary term.
	 *
	 * @param  integer $post_id Post id.
	 * @return bool|string
	 */
	public static function get_primary_term( $post_id = 0 ) {
		$post    = get_post( $post_id );
		$term_id = $post->_nhg_seo_primary_term;

		if ( empty( $term_id ) ) {
			return false;
		}

		$term = get_term( $term_id );

		return empty( $term ) || is_wp_error( $term ) ? false : $term;
	}
}
