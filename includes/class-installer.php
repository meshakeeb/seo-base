<?php
/**
 * Plugin activation and deactivation functionality.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Installer class.
 */
class Installer {

	/**
	 * Bind all events.
	 */
	public function __construct() {
		register_activation_hook( NHG_SEO_FILE, [ $this, 'activation' ] );
		register_deactivation_hook( NHG_SEO_FILE, [ $this, 'deactivation' ] );

		add_action( 'wpmu_new_blog', [ $this, 'activate_blog' ] );
		add_action( 'activate_blog', [ $this, 'activate_blog' ] );
		add_filter( 'wpmu_drop_tables', [ $this, 'on_delete_blog' ] );
		add_action( 'nhg_fill_lookup_table', [ $this, 'fill_lookup_table' ] );
	}

	/**
	 * Do things when activating Rank Math.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 */
	public function activation( $network_wide = false ) {
		if ( ! is_multisite() || ! $network_wide ) {
			$this->activate();
			return;
		}

		$this->network_activate_deactivate( true );
	}

	/**
	 * Do things when deactivating Rank Math.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 */
	public function deactivation( $network_wide = false ) {
		if ( ! is_multisite() || ! $network_wide ) {
			$this->deactivate();
			return;
		}

		$this->network_activate_deactivate( false );
	}

	/**
	 * Fill lookup table.
	 */
	public function fill_lookup_table() {
		global $wpdb;

		$wpdb->query(
			"INSERT INTO {$wpdb->prefix}nhg_lookup_table ( product_id, trending, sales_7, sales_30, total_7, total_30, profit_7, profit_30 )
			SELECT DISTINCT ID as product_id,
				COALESCE( NULLIF( t.meta_value, '' ), 0 ) as tredning,
				COALESCE( NULLIF( s7.meta_value, '' ), 0 ) as sales_7,
				COALESCE( NULLIF( s30.meta_value, '' ), 0 ) as sales_30,
				COALESCE( NULLIF( t7.meta_value, '' ), 0 ) as total_7,
				COALESCE( NULLIF( t30.meta_value, '' ), 0 ) as total_30,
				COALESCE( NULLIF( p7.meta_value, '' ), 0 ) as profit_7,
				COALESCE( NULLIF( p30.meta_value, '' ), 0 ) as profit_30
			FROM {$wpdb->prefix}posts p
			LEFT JOIN {$wpdb->prefix}postmeta t ON p.ID = t.post_id AND t.meta_key = '_trending'
			LEFT JOIN {$wpdb->prefix}postmeta s7 ON p.ID = s7.post_id AND s7.meta_key = 'sales_7'
			LEFT JOIN {$wpdb->prefix}postmeta s30 ON p.ID = s30.post_id AND s30.meta_key = 'sales_30'
			LEFT JOIN {$wpdb->prefix}postmeta t7 ON p.ID = t7.post_id AND t7.meta_key = 'total_7'
			LEFT JOIN {$wpdb->prefix}postmeta t30 ON p.ID = t30.post_id AND t30.meta_key = 'total_30'
			LEFT JOIN {$wpdb->prefix}postmeta p7 ON p.ID = p7.post_id AND p7.meta_key = 'profit_7'
			LEFT JOIN {$wpdb->prefix}postmeta p30 ON p.ID = p30.post_id AND p30.meta_key = 'profit_30'
			WHERE post_type IN ( 'product', 'product_variation' )"
		);
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @param int $blog_id ID of the new blog.
	 */
	public function activate_blog( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		$this->activate();
		restore_current_blog();
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param  array $tables List of tables that will be deleted by WP.
	 * @return array
	 */
	public function on_delete_blog( $tables ) {
		global $wpdb;

		$tables[] = $wpdb->prefix . 'nhg_lookup_table';

		return $tables;
	}

	/**
	 * Run network-wide activation/deactivation of the plugin.
	 *
	 * @param bool $activate True for plugin activation, false for de-activation.
	 *
	 * @copyright Copyright (C) 2008-2019, Yoast BV
	 * The following code is a derivative work of the code from the Yoast(https://github.com/Yoast/wordpress-seo/), which is licensed under GPL v3.
	 */
	private function network_activate_deactivate( $activate ) {
		global $wpdb;

		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'" );
		if ( empty( $blog_ids ) ) {
			return;
		}

		foreach ( $blog_ids as $blog_id ) {
			$func = true === $activate ? 'activate' : 'deactivate';

			switch_to_blog( $blog_id );
			$this->$func();
			restore_current_blog();
		}
	}

	/**
	 * Runs on activation of the plugin.
	 */
	private function activate() {
		$sitemaps = new \WP_Sitemaps();
		$sitemaps->register_rewrites();
		flush_rewrite_rules( false );
	}

	/**
	 * Runs on deactivation of the plugin.
	 */
	private function deactivate() {
	}
}
