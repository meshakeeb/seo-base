<?php
/**
 * The Sitemap.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Sitemap.
 */
class Sitemap {

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		if ( ! function_exists( 'wp_sitemaps_get_server' ) ) {
			require 'sitemaps/sitemaps.php';
			require 'sitemaps/class-wp-sitemaps.php';
			require 'sitemaps/class-wp-sitemaps-index.php';
			require 'sitemaps/class-wp-sitemaps-provider.php';
			require 'sitemaps/class-wp-sitemaps-registry.php';
			require 'sitemaps/class-wp-sitemaps-renderer.php';
			require 'sitemaps/class-wp-sitemaps-stylesheet.php';
			require 'sitemaps/providers/class-wp-sitemaps-posts.php';
			require 'sitemaps/providers/class-wp-sitemaps-taxonomies.php';
			require 'sitemaps/providers/class-wp-sitemaps-users.php';

			add_action( 'init', 'wp_sitemaps_get_server' );
			add_filter( 'redirect_canonical', [ $this, 'redirect_canonical' ] );
		}

		add_filter( 'wp_sitemaps_add_provider', [ $this, 'remove_user_provider' ], 10, 2 );

		// Posts.
		add_filter( 'wp_sitemaps_post_types', [ $this, 'filter_post_types' ] );
		add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'remove_noindex_posts' ], 10, 2 );
		add_filter( 'wp_sitemaps_posts_entry', [ $this, 'add_attributes_posts_entry' ], 10, 2 );

		// Taxonomies.
		add_filter( 'wp_sitemaps_taxonomies', [ $this, 'filter_taxonomies' ] );
		add_filter( 'wp_sitemaps_taxonomies_query_args', [ $this, 'remove_noindex_taxonomies' ], 10, 2 );
	}

	/**
	 * Redirect canonical url for sitemap.
	 *
	 * @param  string $redirect The redirect URL currently determined.
	 * @return bool|string $redirect The canonical redirect URL.
	 */
	public function redirect_canonical( $redirect ) {
		if ( get_query_var( 'sitemap' ) || get_query_var( 'sitemap-stylesheet' ) ) {
			return false;
		}

		return $redirect;
	}

	/**
	 * Remove user provider.
	 *
	 * @param  WP_Sitemaps_Provider $provider Instance of a WP_Sitemaps_Provider.
	 * @param  string               $name     Name of the sitemap provider.
	 * @return bool|WP_Sitemaps_Provider
	 */
	public function remove_user_provider( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}

	/**
	 * Filters the list of post object sub types available within the sitemap.
	 *
	 * @param  WP_Post_Type[] $post_types Array of registered post type objects keyed by their name.
	 * @return WP_Post_Type[]
	 */
	public function filter_post_types( $post_types ) {
		unset( $post_types['banner'] );

		return $post_types;
	}

	/**
	 * Filter the list of taxonomy object subtypes available within the sitemap.
	 *
	 * @param  WP_Taxonomy[] $taxonomies Array of registered taxonomy objects keyed by their name.
	 * @return WP_Taxonomy[]
	 */
	public function filter_taxonomies( $taxonomies ) {
		unset(
			$taxonomies['category'],
			$taxonomies['product_tag'],
			$taxonomies['pa_lengde'],
			$taxonomies['pa_motstand'],
			$taxonomies['pa_sokkestorrelse'],
			$taxonomies['pa_storrelse'],
			$taxonomies['pa_str']
		);

		return $taxonomies;
	}

	/**
	 * Remove noindex posts.
	 *
	 * @param  array  $args      Array of WP_Query arguments.
	 * @param  string $post_type Post type name.
	 * @return array
	 */
	public function remove_noindex_posts( $args, $post_type ) {
		$exclude_posts = $this->get_posts_to_exclude( $post_type );
		if ( ! empty( $exclude_posts ) ) {
			$args['post__not_in'] = isset( $args['post__not_in'] )
				? array_merge( $args['post__not_in'], $exclude_posts )
				: $exclude_posts;
		}

		return $args;
	}

	/**
	 * Get post ids to exclude.
	 *
	 * @param  string $post_type Post type name.
	 * @return array
	 */
	private function get_posts_to_exclude( $post_type ) {
		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => '_nhg_seo_robots',
				'value'   => 'noindex',
				'compare' => '=',
			],
		];

		if ( 'product' === $post_type ) {
			$meta_query[] = [
				'key'     => '_stock_status',
				'value'   => 'outofstock',
				'compare' => '=',
			];
		}

		$ids = get_posts(
			[
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'post_type'              => $post_type,
				'posts_per_page'         => -1,
				'post_status'            => [ 'publish' ],

				// Meta Query.
				'meta_query'             => $meta_query,

				// Query Optimization.
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			]
		);

		if ( ! is_array( $ids ) ) {
			$ids = [];
		}

		// Exclude WooCommerce.
		$ids[] = wc_get_page_id( 'shop' );
		$ids[] = wc_get_page_id( 'cart' );
		$ids[] = wc_get_page_id( 'checkout' );
		$ids[] = wc_get_page_id( 'myaccount' );

		return array_filter( $ids );
	}

	/**
	 * Add attributes to posts entry.
	 *
	 * @param  array   $entry Sitemap entry for the post.
	 * @param  WP_Post $post  Post object.
	 * @return array
	 */
	public function add_attributes_posts_entry( $entry, $post ) {
		$entry['lastmod'] = $post->post_modified_gmt;

		if ( 'product' === $post->post_type ) {
			$entry = $this->set_featured_image( $entry, $post );
			$entry = $this->set_gallery_images( $entry, $post );
		}

		return $entry;
	}

	/**
	 * Set featured image.
	 *
	 * @param  array   $entry Sitemap entry for the post.
	 * @param  WP_Post $post  Post object.
	 * @return array
	 */
	private function set_featured_image( $entry, $post ) {
		// Early Bail!!
		if ( ! has_post_thumbnail( $post ) ) {
			return $entry;
		}

		$attachment_id = get_post_thumbnail_id( $post );
		$image         = wp_get_attachment_url( $attachment_id );
		if ( $image ) {
			$entry['images'][] = $image;
		}

		return $entry;
	}

	/**
	 * Set gallery images.
	 *
	 * @param  array   $entry Sitemap entry for the post.
	 * @param  WP_Post $post  Post object.
	 * @return array
	 */
	private function set_gallery_images( $entry, $post ) {
		$attachments = wp_parse_id_list( get_post_meta( $post->ID, '_product_image_gallery', true ) );

		// Early Bail!!
		if ( ! is_array( $attachments ) || empty( $attachments ) ) {
			return $entry;
		}

		foreach ( $attachments as $attachment_id ) {
			$image = wp_get_attachment_url( $attachment_id );
			if ( $image ) {
				$entry['images'][] = $image;
			}
		}

		return $entry;
	}

	/**
	 * Remove noindex taxonomies.
	 *
	 * @param  array  $args     Array of WP_Term_Query arguments.
	 * @param  string $taxonomy Taxonomy name.
	 * @return array
	 */
	public function remove_noindex_taxonomies( $args, $taxonomy ) {
		$exclude_terms = $this->get_terms_to_exclude( $taxonomy );
		if ( ! empty( $exclude_terms ) ) {
			$args['exclude'] = isset( $args['exclude'] )
				? array_merge( $args['exclude'], $exclude_terms )
				: $exclude_terms;
		}

		return $args;
	}

	/**
	 * Get term ids to exclude.
	 *
	 * @param  string $taxonomy Taxonomy name.
	 * @return array
	 */
	private function get_terms_to_exclude( $taxonomy ) {
		$ids = get_terms(
			[
				'fields'                 => 'ids',
				'taxonomy'               => $taxonomy,
				'orderby'                => 'term_order',
				'number'                 => -1,

				// Meta Query.
				'meta_query'             => [
					[
						'key'     => '_nhg_seo_robots',
						'value'   => 'noindex',
						'compare' => '=',
					],
				],

				// Query Optimization.
				'update_term_meta_cache' => false,
			]
		);

		return $ids;
	}
}
