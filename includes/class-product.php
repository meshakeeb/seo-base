<?php
/**
 * The Product.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

use NHG\SEO\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Product.
 */
class Product {

	/**
	 * Current post.
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Current Product.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Hold json.
	 *
	 * @var array
	 */
	private $markup;

	/**
	 * Hold product attributes.
	 *
	 * @var array
	 */
	private $attributes;

	/**
	 * Registers functionality through WordPress hooks.
	 */
	public function hooks() {
		add_action( 'nhg_seo_ld_json', [ $this, 'add_structured_data' ] );
		remove_action( 'woocommerce_single_product_summary', [ WC()->structured_data, 'generate_product_data' ], 60 );
	}

	/**
	 * Set data.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 */
	private function set_property( $key, $value ) {
		$this->markup[ $key ] = $value;
	}

	/**
	 * Add breadcrumn as JSON only.
	 *
	 * @param object $manager LD-JSON manager instance.
	 */
	public function add_structured_data( $manager ) {
		$this->post    = get_post();
		$this->product = wc_get_product();

		if ( ! is_object( $this->product ) ) {
			global $product;
			$this->product = $product;
		}

		if ( ! is_a( $this->product, 'WC_Product' ) ) {
			return;
		}

		$this->attributes = $this->product->get_attributes();
		$permalink        = get_permalink( $this->product->get_id() );

		$this->set_property( '@type', 'Product' );
		$this->set_property( '@id', $permalink . '#product' ); // Append '#product' to differentiate between this @id and the @id generated for the Breadcrumblist.
		$this->set_property( 'name', $this->product->get_name() );
		$this->set_property( 'url', $permalink );
		$this->set_property( 'description', Document::get()->get_description() );
		$this->set_property( 'category', $this->get_categories() );
		$this->set_property( 'releaseDate', mysql2date( DATE_W3C, $this->post->post_date, false ) );

		// Declare SKU or fallback to ID.
		$this->set_property(
			'sku',
			$this->product->get_sku()
				? $this->product->get_sku()
				: $this->product->get_id()
		);

		$brands = Document::get()->get_brands();
		if ( ! empty( $brands ) ) {
			$this->set_property(
				'brand',
				[
					'@type' => 'Thing',
					'name'  => $brands[0]->name,
				]
			);
		}

		$this->set_identifier();
		$this->set_offers();
		$this->set_ratings();
		$this->set_weight();
		$this->set_images();
		$this->set_dimensions();

		// Check we have required data.
		if ( empty( $this->markup['aggregateRating'] ) && empty( $this->markup['offers'] ) && empty( $this->markup['review'] ) ) {
			return;
		}

		$manager->set_data( $this->markup );
	}

	/**
	 * Set global identifier
	 */
	private function set_identifier() {
		$identifier = $this->product->get_meta( 'ean' );

		// Early Bail!!
		if ( empty( $identifier ) ) {
			return;
		}

		$length = strlen( $identifier );
		$hash   = [
			8  => 'gtin8',
			12 => 'gtin12',
			13 => 'gtin13',
			14 => 'gtin14',
		];

		$key = isset( $hash[ $length ] ) ? $hash[ $length ] : 'gtin';
		$this->set_property( $key, $identifier );
	}

	/**
	 * Set product categories.
	 */
	private function get_categories() {
		$taxonomy   = 'product_cat';
		$categories = get_the_terms( $this->product->get_id(), $taxonomy );
		if ( is_wp_error( $categories ) || empty( $categories ) ) {
			return;
		}

		if ( 0 === $categories[0]->parent ) {
			return $categories[0]->name;
		}

		$ancestors = get_ancestors( $categories[0]->term_id, $taxonomy );
		foreach ( $ancestors as $parent ) {
			$term       = get_term( $parent, $taxonomy );
			$category[] = $term->name;
		}
		$category[] = $categories[0]->name;

		return join( ' > ', $category );
	}

	/**
	 * Set product price.
	 */
	private function set_offers() {
		// Early bail!!
		if ( '' === $this->product->get_price() ) {
			return;
		}

		$shop_url  = home_url();
		$shop_name = get_bloginfo( 'name' );
		$currency  = get_woocommerce_currency();

		// Assume prices will be valid until the end of next year, unless on sale and there is an end date.
		$price_valid_until = gmdate( 'Y-12-31', time() + YEAR_IN_SECONDS );

		if ( $this->product->is_type( 'variable' ) ) {
			$lowest  = $this->product->get_variation_price( 'min', false );
			$highest = $this->product->get_variation_price( 'max', false );

			if ( $lowest === $highest ) {
				$markup_offer = [
					'@type'              => 'Offer',
					'price'              => wc_format_decimal( $lowest, wc_get_price_decimals() ),
					'priceValidUntil'    => $price_valid_until,
					'priceSpecification' => [
						'price'                 => wc_format_decimal( $lowest, wc_get_price_decimals() ),
						'priceCurrency'         => $currency,
						'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
					],
				];
			} else {
				$markup_offer = [
					'@type'      => 'AggregateOffer',
					'lowPrice'   => wc_format_decimal( $lowest, wc_get_price_decimals() ),
					'highPrice'  => wc_format_decimal( $highest, wc_get_price_decimals() ),
					'offerCount' => count( $this->product->get_children() ),
				];
			}
		} else {
			if ( $this->product->is_on_sale() && $this->product->get_date_on_sale_to() ) {
				$price_valid_until = gmdate( 'Y-m-d', $this->product->get_date_on_sale_to()->getTimestamp() );
			}

			$markup_offer = [
				'@type'              => 'Offer',
				'price'              => wc_format_decimal( $this->product->get_price(), wc_get_price_decimals() ),
				'priceValidUntil'    => $price_valid_until,
				'priceSpecification' => [
					'price'                 => wc_format_decimal( $this->product->get_price(), wc_get_price_decimals() ),
					'priceCurrency'         => $currency,
					'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
				],
			];
		}

		$markup_offer += [
			'priceCurrency' => $currency,
			'availability'  => 'http://schema.org/' . ( $this->product->is_in_stock() ? 'InStock' : 'OutOfStock' ),
			'url'           => $this->product->get_permalink(),
			'itemCondition' => 'NewCondition',
			'seller'        => [
				'@type' => 'Organization',
				'name'  => $shop_name,
				'url'   => $shop_url,
			],
		];

		$this->set_property(
			'offers',
			[ apply_filters( 'woocommerce_structured_data_product_offer', $markup_offer, $this->product ) ]
		);
	}

	/**
	 * Set product ratings.
	 */
	private function set_ratings() {
		// Early bail!!
		if ( ! wc_review_ratings_enabled() || $this->product->get_rating_count() < 1 ) {
			return;
		}

		$this->set_property(
			'aggregateRating',
			[
				'@type'       => 'AggregateRating',
				'ratingValue' => $this->product->get_average_rating(),
				'bestRating'  => '5',
				'ratingCount' => $this->product->get_rating_count(),
				'reviewCount' => $this->product->get_review_count(),
			]
		);

		// Markup 5 most recent rating/review.
		$comments = get_comments(
			[
				'number'    => 5,
				'post_id'   => $this->product->get_id(),
				'status'    => 'approve',
				'post_type' => 'product',
				'parent'    => 0,
			]
		);

		if ( ! $comments ) {
			return;
		}

		$reviews = [];
		foreach ( $comments as $comment ) {
			$reviews[] = [
				'@type'         => 'Review',
				'reviewRating'  => [
					'@type'       => 'Rating',
					'bestRating'  => '5',
					'ratingValue' => get_comment_meta( $comment->comment_ID, 'rating', true ),
					'worstRating' => '1',
				],
				'author'        => [
					'@type' => 'Person',
					'name'  => get_comment_author( $comment ),
				],
				'reviewBody'    => get_comment_text( $comment ),
				'datePublished' => get_comment_date( 'c', $comment ),
			];
		}

		$this->set_property( 'review', $reviews );
	}

	/**
	 * Set product weight.
	 */
	private function set_weight() {
		if ( empty( $this->product->get_weight() ) ) {
			return;
		}

		$hash = [
			'lbs' => 'LBR',
			'kg'  => 'KGM',
			'g'   => 'GRM',
			'oz'  => 'ONZ',
		];
		$unit = get_option( 'woocommerce_weight_unit' );

		$this->set_property(
			'weight',
			[
				'@type'    => 'QuantitativeValue',
				'unitCode' => isset( $hash[ $unit ] ) ? $hash[ $unit ] : 'LBR',
				'value'    => $this->product->get_weight(),
			]
		);
	}

	/**
	 * Set product dimension.
	 */
	private function set_dimensions() {
		if ( ! $this->product->has_dimensions() ) {
			return;
		}

		$hash = [
			'in' => 'INH',
			'm'  => 'MTR',
			'cm' => 'CMT',
			'mm' => 'MMT',
			'yd' => 'YRD',
		];
		$unit = get_option( 'woocommerce_dimension_unit' );
		$code = isset( $hash[ $unit ] ) ? $hash[ $unit ] : '';

		$this->set_property(
			'height',
			[
				'@type'    => 'QuantitativeValue',
				'unitCode' => $code,
				'value'    => $this->product->get_height(),
			]
		);

		$this->set_property(
			'width',
			[
				'@type'    => 'QuantitativeValue',
				'unitCode' => $code,
				'value'    => $this->product->get_width(),
			]
		);

		$this->set_property(
			'depth',
			[
				'@type'    => 'QuantitativeValue',
				'unitCode' => $code,
				'value'    => $this->product->get_length(),
			]
		);
	}

	/**
	 * Set product images.
	 */
	private function set_images() {
		if ( ! $this->product->get_image_id() ) {
			return;
		}

		$images   = [];
		$image    = wp_get_attachment_image_src( $this->product->get_image_id(), 'single-post-thumbnail' );
		$images[] = [
			'@type'  => 'ImageObject',
			'url'    => $image[0],
			'height' => $image[2],
			'width'  => $image[1],
		];

		$gallery = $this->product->get_gallery_image_ids();
		foreach ( $gallery as $image_id ) {
			$image    = wp_get_attachment_image_src( $image_id, 'single-post-thumbnail' );
			$images[] = [
				'@type'  => 'ImageObject',
				'url'    => $image[0],
				'height' => $image[2],
				'width'  => $image[1],
			];
		}

		$this->set_property( 'image', $images );
	}

	/**
	 * Find attribute for property.
	 *
	 * @param string $needle Assign this property.
	 */
	public function assign_property( $needle ) {
		foreach ( $this->attributes as $key => $attrib ) {
			if ( stristr( $key, $needle ) ) {
				$this->set_property( $needle, $this->product->get_attribute( $key ) );
				unset( $this->attributes[ $key ] );
				return;
			}
		}
	}

	/**
	 * Map remaining attributes as PropertyValue.
	 */
	public function assign_remaining() {
		$additionals = [];
		foreach ( $this->attributes as $key => $attrib ) {
			if ( $attrib['is_visible'] && ! $attrib['is_variation'] ) {
				$additionals[] = [
					'@type' => 'PropertyValue',
					'name'  => $key,
					'value' => $this->product->get_attribute( $key ),
				];
			}
		}

		$this->set_property( 'additionalProperty', $additionals );
	}
}
