<?php
/**
 * Adds several quick-reference shortcodes for use in Explanations and examples.
 *
 * These shortcodes output "quick-reference" links to other functions, hooks, classes,
 * or methods.
 *
 * @package wporg-developer
 */

/**
 * Class to register quick-reference shortcodes.
 */
class WPORG_Shortcodes {

	/**
	 * Associative array of shortcode types with their corresponding post types.
	 *
	 * @access public
	 * @var array
	 */
	public $types = array();

	/**
	 * The original name attribute, stored prior to sanitization.
	 *
	 * @access public
	 * @var string
	 */
	public $original_name;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->types = array(
			'function' => 'wp-parser-function',
			'hook'     => 'wp-parser-hook',
			'class'    => 'wp-parser-class',
			'method'   => 'wp-parser-method'
		);

		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Register the 'function', 'hook', 'class', and 'method' shortcodes.
	 *
	 * @access public
	 */
	public function register_shortcodes() {
		foreach ( array_keys( $this->types ) as $type ) {
			add_shortcode( $type, array( $this, "{$type}_shortcode" ) );
		}
	}

	/**
	 * Callback for the [function] shortcode.
	 *
	 * Example usage:
	 * `[function name="wp_list_pluck" label="wp_list_pluck()"]`
	 *
	 * @access public
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML markup for a valid function reference link, empty string otherwise.
	 */
	public function function_shortcode( $atts ) {
		return $this->handle_shortcode_output( $atts, 'function' );
	}

	/**
	 * Callback for the [hook] shortcode.
	 *
	 * Example usage:
	 * `[hook name="save_post"]`
	 *
	 * @access public
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML markup for a valid hook reference link, empty string otherwise.
	 */
	public function hook_shortcode( $atts ) {
		return $this->handle_shortcode_output( $atts, 'hook' );
	}

	/**
	 * Callback for the [class] shortcode.
	 *
	 * Example usage:
	 * `[class name="WP_Tax_Query"]`
	 *
	 * @access public
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML markup for a valid class reference link, empty string otherwise.
	 */
	public function class_shortcode( $atts ) {
		return $this->handle_shortcode_output( $atts, 'class' );
	}

	/**
	 * Callback for the [method] shortcode.
	 *
	 * Example usage:
	 * `[method name="have_posts" class="WP_Query" label="WP_Query->have_posts()"]`
	 *
	 * @access public
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML markup for a valid method reference link, empty string otherwise.
	 */
	public function method_shortcode( $atts ) {
		return $this->handle_shortcode_output( $atts, 'method' );
	}

	/**
	 * Handle sanitizing attributes and building the output for the given shortcode.
	 *
	 * @access protected
	 *
	 * @param array  $atts      Array of original shortcode attributes.
	 * @param string $shortcode Shortcode name.
	 * @return string HTML markup for a valid reference link based on the shortcode type.
	 *                An empty string otherwise.
	 */
	protected function handle_shortcode_output( $atts, $shortcode ) {
		$default_pairs = array( 'name' => '', 'label' => '' );

		if ( 'method' === $shortcode ) {
			$default_pairs['class'] = '';
		}

		/*
		 * Store the original 'name' attribute for use as the link label fallback. During
		 * sanitization, the 'name' attribute is lowercased via sanitize_key().
		 *
		 * This is especially helpful for use with the `[class]` shortcode. Failing to supply
		 * a label for classes like WP_Query or WP_Tax_Query, for instance, will result
		 * in a fallback link label of 'WP_Query' instead of 'wp_query'.
		 */
		if ( ! empty( $atts['name'] ) ) {
			$this->original_name = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $atts['name'] );
		}

		$atts = shortcode_atts( $default_pairs, $this->_sanitize_attributes( $atts ), $shortcode );

		$output = $this->build_link_html( $atts, $shortcode );

		return $output ? $output : '';
	}

	/**
	 * Utility method for sanitizing incoming shortcode attributes.
	 *
	 * Supports only the 'name', 'class', and 'label' attributes. All others will be unset.
	 *
	 * @access private
	 *
	 * @param array $atts Shortcode attributes to
	 * @return array Sanitizated shortcode attributes.
	 */
	private function _sanitize_attributes( $atts ) {
		foreach ( $atts as $attribute => $value ) {
			if ( 'name' == $attribute || 'class' == $attribute ) {
				$atts[ $attribute ] = sanitize_key( $value );
			} elseif ( 'label' == $attribute ) {
				$atts[ $attribute ] = strip_tags( $value );
			} else {
				unset( $atts[ $attribute ] );
			}
		}
		return $atts;
	}

	/**
	 * Build a quick-reference link for the given shortcode type and slug.
	 *
	 * @access protected
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $type Type of shortcode. Accepts 'function', 'hook', 'class', or 'method'.
	 * @return string|bool HTML markup for the quick-reference link, false otherwise.
	 */
	protected function build_link_html( $atts, $type ) {
		$link = false;

		// Bail if the 'name' attribute is empty.
		if ( empty( $atts['name'] ) ) {
			return $link;
		}

		// Bail if there's no class specified for a method.
		if ( 'method' === $type && empty( $atts['class'] ) ) {
			return $link;
		}

		// Build and verify the URL is good based on the given $type.
		if ( $url = $this->_build_and_verify_url( $atts, $type ) ) {

			/*
			 * If the label's set, use the label and apply 'the_title' filters. Otherwise
			 * use the original 'name' attribute, passed through 'the_title' filters.
			 */
			if ( ! empty( $atts['label'] ) ) {
				$label = apply_filters( 'the_title', $atts['label'] );
			} else {
				$label = apply_filters( 'the_title', strip_tags( $this->original_name ) );
			}

			$link = sprintf( '<a href="%1$s" class="quick-reference %2$s">%3$s</a>',
				esc_url( $url ),
				sanitize_html_class( $type ),
				$label
			);
		}
		return $link;
	}

	/**
	 * Utility method to build and verify a reference URL based on the given shortcode type.
	 *
	 * @access private
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $type Type of shortcode. Accepts 'function', 'hook', 'class', or 'method'.
	 * @return string|bool URL if the URL is real, false otherwise.
	 */
	private function _build_and_verify_url( $atts, $type ) {
		// Grab the post type object based on the given $type.
		if ( ! is_null( $post_type_object = get_post_type_object( $this->types[ $type ] ) ) ) {
			$rewrite_slug = $post_type_object->rewrite['slug'];

			// Build the path based on the given $type.
			if ( ! empty( $rewrite_slug ) ) {

				// Include the class name in the URL for methods.
				$path_to_item = '/%1$s/%2$s/%3$s';

				if ( 'method' == $type ) {
					$path = sprintf( $path_to_item, $rewrite_slug, sanitize_key( $atts['class'] ), $atts['name'] );
				} else {
					$path = sprintf( $path_to_item, $rewrite_slug, $atts['name'], '' );
				}

				$base_url = home_url( $path );

				/*
				 * @todo Probably rethink the most scalable method for verifying validity of these
				 * items. Doing a remote request for every name attribute is almost as bad as running
				 * a query for each of them. For now, just assume the slug exists :/
				 */
				return $base_url;

//				// Grab the URL to see if it's good.
//				$resp = wp_remote_get( esc_url( $base_url ) );
//
//				// If the URL is good, build the link markup.
//				if ( 200 == wp_remote_retrieve_response_code( $resp ) ) {
//					return $base_url;
//				}
			}
		}
		return false;
	}
}

$shortcodes = new WPORG_Shortcodes();
