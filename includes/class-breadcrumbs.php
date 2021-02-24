<?php
/**
 * The Breadcrumbs.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

use WC_Breadcrumb;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumbs.
 */
class Breadcrumbs {

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_action( 'nhg_seo_ld_json', [ $this, 'add_structured_data' ] );

		// Remove actions which can lead to duplications.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_breadcrumb', [ WC()->structured_data, 'generate_breadcrumblist_data' ], 10 );
	}

	/**
	 * Add breadcrumn as JSON only.
	 *
	 * @param object $manager LD-JSON manager instance.
	 */
	public function add_structured_data( $manager ) {
		$breadcrumbs = new WC_Breadcrumb();
		$breadcrumbs->add_crumb( _x( 'Home', 'breadcrumb', 'nhg-seo' ), home_url() );
		$crumbs = $breadcrumbs->generate();

		if ( empty( $crumbs ) || ! is_array( $crumbs ) ) {
			return;
		}

		if ( count( $crumbs ) >= 2 ) {
			array_splice( $crumbs, 1, 1 );
		}

		$markup                    = [];
		$markup['@type']           = 'BreadcrumbList';
		$markup['itemListElement'] = [];

		$current_url = set_url_scheme( 'http://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( $crumbs as $key => $crumb ) {
			$markup['itemListElement'][ $key ] = [
				'@type'    => 'ListItem',
				'position' => $key + 1,
				'item'     => [ 'name' => $crumb[0] ],
			];

			if ( ! empty( $crumb[1] ) ) {
				$markup['itemListElement'][ $key ]['item'] += [ '@id' => $crumb[1] ];
			} elseif ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
				$markup['itemListElement'][ $key ]['item'] += [ '@id' => $current_url ];
			}
		}

		if ( $markup ) {
			$manager->set_data( $markup );
		}
	}
}
