<?php
namespace Ultimate_Fields\Post_Types;

use Ultimate_Fields\Post_Types\UI_Page;
use Ultimate_Fields\Post_Types\Type\Post_Type;
use Ultimate_Fields\Post_Types\Type\Taxonomy;
use Ultimate_Fields\Post_Types\Migration;
use Ultimate_Fields\Template;
use Ultimate_Fields\Autoloader;

/**
 * Adds the functionality for creating custom content types with Ultimate Fields.
 *
 * @since 3.0
 */
class Post_Types {
	/**
	 * Initializes the class as a plugin.
	 *
	 * @since 3.0
	 *
	 * @param string $path The path of the main plugin file. (Only required once)
	 * @return Post_Types The instance of the class.
	 */
	public static function instance( $path = '' ) {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new self( $path );
		}

		return $instance;
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 3.0
	 *
	 * @param string $path The path to the main plugin file.
	 */
	protected function __construct( $path ) {
		define( 'ULTIMATE_FIELDS_PT_DIR', dirname( $path ) . '/' );
		define( 'ULTIMATE_FIELDS_PT_URL', plugins_url( '/', $path ) );
		define( 'ULTIMATE_FIELDS_PT_VER', '3.0' );

		if ( ! defined( 'ULTIMATE_FIELDS_DIR' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_parent_notice' ) );
			return;
		}

		# Add an autoloader for the UI
		new Autoloader( 'Ultimate_Fields\\Post_Types', __DIR__ );

		# Initialize everything else when UF is available.
		add_filter( 'uf.register_ui', array( $this, 'register' ), 30 );

		# If needed, this will flush rewrites after saving.
		add_action( 'init', array( $this, 'init' ) );



		// Load translations
		if( defined( 'UPT_LANGUAGES_DIR' ) ) {
			load_plugin_textdomain( 'upt', false, UPT_LANGUAGES_DIR );
		}

		# Exclude the post types for managing UPT from being listed as options
		add_filter( 'uf.excluded_post_types', array( $this, 'exclude_own_post_types' ) );
	}

	/**
	 * Autoloads a class from the post types plugin.
	 *
	 * @since 3.0
	 *
	 * @param string $class_name The name of the class.
	 */
	 public function autoload( $class_name ) {
 		if ( strrpos( $class_name, 'Ultimate_Fields\\PT\\' ) !== 0 )
 			return;

 		$file = str_replace( 'Ultimate_Fields\\PT\\', '', $class_name );
 		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $file );
 		$path = ULTIMATE_FIELDS_PT_DIR . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $file . '.php';

 		if ( file_exists( $path ) )  {
 			require_once $path;
 		}
 	}

	/**
	 * Registers the functionality of the plugin.
	 *
	 * @since 3.0
	 */
	public function register() {
		if ( defined( 'ULTIMATE_FIELDS_DISABLE_UI' ) ) {
			return;
		}

		# Add paths
		Template::instance()->add_path( ULTIMATE_FIELDS_PT_DIR . 'templates/' );

		# Create a page in the admin for managing content types.
		Post_Type::init();
		Taxonomy::init();

		# Listen for migrations
		$migration = new Migration();
	}

	/**
	 * Exclude the new post types from Ultimate Fields.
	 *
	 * @since 3.0
	 *
	 * @param string[] $post_types The post types that are already excluded.
	 * @return string[]
	 */
	public function exclude_own_post_types( $post_types ) {
		$post_types[] = Post_Type::get_slug();
		$post_types[] = Taxonomy::get_slug();

		return $post_types;
	}

	/**
	 * Flush rewrite rules when necessary.
	 *
	 * @since 3.0
	 */
	public function init() {
		# Flush rewrites if needed
		if ( get_option( 'upt_flush_rewrites' ) ) {
			flush_rewrite_rules();
			delete_option( 'upt_flush_rewrites' );
		}
	}

	/**
	 * Displays a notice that the parent plugin is missing.
	 *
	 * @since 3.0
	 */
	public function missing_parent_notice() {
		$message = __( 'The Ultimate Post Types plugin is active, but it will not work until it&apos;s dependency &quot;Ultimate Fields&quot; is active too. Please <a href="https://www.ultimate-fields.com/docs/quick-start/installation/" target="_blank">install Ultimate Fields</a>.', 'upt' );

		echo '<div id="message" class="notice fatal error">' . wpautop( $message ) . '</div>';
	}
}
