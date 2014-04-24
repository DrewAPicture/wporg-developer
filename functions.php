<?php

namespace DevHub;

/**
 * Custom template tags for this theme.
 */
require __DIR__ . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require __DIR__ . '/inc/extras.php';

/**
 * Customizer additions.
 */
require __DIR__ . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require __DIR__ . '/inc/jetpack.php';

if ( ! function_exists( 'loop_pagination' ) ) {
	require __DIR__ . '/inc/loop-pagination.php';
}

if ( ! function_exists( 'breadcrumb_trail' ) ) {
	require __DIR__ . '/inc/breadcrumb-trail.php';
}

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 640; /* pixels */
}


add_action( 'init', __NAMESPACE__ . '\\init' );


function init() {
	add_filter( 'wp_parser_post_type_args', __NAMESPACE__ . '\\parser_rewrite_args_filter', 10, 2 );
	add_filter( 'wp_parser_taxonomy_args',  __NAMESPACE__ . '\\parser_rewrite_args_filter', 10, 2 );

	add_action( 'widgets_init',             __NAMESPACE__ . '\\widgets_init' );
	add_action( 'pre_get_posts',            __NAMESPACE__ . '\\pre_get_posts' );
	add_action( 'wp_enqueue_scripts',       __NAMESPACE__ . '\\theme_scripts_styles' );
	add_filter( 'post_type_link',           __NAMESPACE__ . '\\method_permalink', 10, 2 );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
}

/**
 * Filter rewrite arguments for WP-Parser post types and taxonomies.
 *
 * @param array  $args Rewrite arguments.
 * @param string $slug Post type or taxonomy slug.
 * @return array Filtered arguments.
 */
function parser_rewrite_args_filter( $args, $slug ) {
	switch( $slug ) {
		// Function post type.
		case 'wp-parser-function':
			$rewrites = array(
				'has_archive' => 'reference/functions',
				'rewrite'     => array(
					'feeds'      => false,
					'slug'       => 'reference/function',
					'with_front' => false,
				),
			);
			break;

		// Class post type.
		case 'wp-parser-class':
			$rewrites = array(
				'has_archive' => 'reference/classes',
				'rewrite'     => array(
					'feeds'      => false,
					'slug'       => 'reference/class',
					'with_front' => false,
				),
			);
			break;

		// Hook post type.
		case 'wp-parser-hook':
			$rewrites = array(
				'has_archive' => 'reference/hooks',
				'rewrite'     => array(
					'feeds'      => false,
					'slug'       => 'reference/hook',
					'with_front' => false,
				),
			);

		// Source File taxonomy.
		case 'wp-parser-source-file':
			$rewrites = array( 'rewrite' => array( 'slug' => 'reference/files' ) );
			break;

		// @package taxonomy.
		case 'wp-parser-package':
			$rewrites = array( 'rewrite' => array( 'slug' => 'reference/package' ) );
			break;

		// @since taxonomy.
		case 'wp-parser-since':
			$rewrites = array( 'rewrite' => array( 'slug' => 'reference/since' ) );
			break;

		default :
			$rewrites = array();
			break;
	}

	return array_merge( $args, $rewrites );
}


/**
 * widgets_init function.
 *
 * @access public
 * @return void
 */
function widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'wporg' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="box gray widget %2$s">',
		'after_widget'  => '</div></aside>',
		'before_title'  => '<h1 class="widget-title">',
		'after_title'   => '</h1><div class="widget-content">',
	) );
}

/**
 * @param \WP_Query $query
 */
function pre_get_posts( $query ) {

	if ( $query->is_main_query() && $query->is_post_type_archive() ) {
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}
}

function method_permalink( $link, $post ) {
	if ( $post->post_type !== 'wp-parser-function' || $post->post_parent == 0 )
		return $link;

	list( $class, $method ) = explode( '-', $post->post_name );
	$link = home_url( user_trailingslashit( "method/$class/$method" ) );
	return $link;
}

function theme_scripts_styles() {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'open-sans', '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,400,300,600' );
	wp_enqueue_style( 'wporg-developer-style', get_stylesheet_uri() );
	wp_enqueue_style( 'wp-dev-sass-compiled', get_template_directory_uri() . '/main.css', array( 'wporg-developer-style' ) );
	wp_enqueue_script( 'wporg-developer-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20120206', true );
	wp_enqueue_script( 'wporg-developer-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20130115', true );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}