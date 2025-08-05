<?php

use Timber\Site;
use App\Support\WPVite;

/**
 * Class StarterSite
 */
class StarterSite extends Site {
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );

		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_filter( 'timber/twig/environment/options', [ $this, 'update_twig_environment_options' ] );
		
		//Load theme.json
		add_filter('theme_file_path', array( $this, 'override_theme_json_path'), 10, 2 );
		
		//ACF
		add_filter( 'acf/settings/save_json', array( $this, 'acf_json_save_point' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'acf_json_load_point' ) );
		//add_filter('acf/fields/google_map/api', array( $this, 'my_acf_google_map_api' ) ); //google map field
		
		//exclude node modules from export
		//add_filter('ai1wm_exclude_themes_from_export', array( $this, 'ignore_node') );

		parent::__construct();
	}

	/**
	 * This is where you can register custom post types.
	 */
	public function register_post_types() {

	}

	/**
	 * This is where you can register custom taxonomies.
	 */
	public function register_taxonomies() {

	}

	/**
	 * This is where you add some context
	 *
	 * @param string $context context['this'] Being the Twig's {{ this }}.
	 */
	public function add_to_context( $context ) {
		$context['foo']   = 'bar';
		$context['stuff'] = 'I am a value set in your functions.php file';
		$context['notes'] = 'These values are available everytime you call Timber::context();';
		$context['menu']  = Timber::get_menu();
		$context['site']  = $this;

		return $context;
	}

	public function theme_supports() {
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support(
			'post-formats',
			array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			)
		);

		add_theme_support( 'menus' );
		
		
		/*
		 * Register Nav Menus
		 *
		 * See: https://developer.wordpress.org/reference/functions/register_nav_menus/
		 */
		 
		register_nav_menus([
			'primary' => 'Primary Menu',
			'secondary' => 'Secondary Menu',
		]);
		
		/*
		 * Image Sizes
		 *
		 * See: https://developer.wordpress.org/reference/functions/add_image_size/
		 */
		
		//add_image_size( 'special-thumb', 1510, 1220, true );
	}

	/**
	 * Create custom Functions to add to Twig
	 *
	 * This would return 'foo bar!'.
	 *
	 * @param string $text being 'foo', then returned 'foo bar!'.
	 */
	/*public function myfoo( $text ) {
		$text .= ' bar!';
		return $text;
	}*/

	/**
	 * This is where you can add your own functions to twig.
	 *
	 * @param Twig\Environment $twig get extension.
	 */
	public function add_to_twig( $twig ) {
		/**
		 * Required when you want to use Twig’s template_from_string.
		 * @link https://twig.symfony.com/doc/3.x/functions/template_from_string.html
		 */
		// $twig->addExtension( new Twig\Extension\StringLoaderExtension() );
		
		//	Add custom functions to Twig
		//$twig->addFilter( new Twig\TwigFilter( 'myfoo', [ $this, 'myfoo' ] ) );
		
		$twig->addFunction(new \Twig\TwigFunction(
			'vite',
			function ($entries = [], $buildDir = 'public/build') {
				return new \Twig\Markup($this->wp_vite($entries, $buildDir), 'UTF-8');
			},
			['is_safe' => ['html']]
		));
		
		$twig->addFunction(new \Twig\TwigFunction(
			'vite_asset',
			function ($entry, $buildDir = 'public/build') {
				return $this->wp_vite_asset($entry, $buildDir);
			}
		));
		
		$twig->addFunction(new \Twig\TwigFunction(
			'vite_svg',
			function (string $asset, array $attrs = []) {
				$vite = new \App\Support\WPVite(['buildDirectory' => 'public/build']);
				$markup = $vite->inlineSvg($asset, $attrs);
				return new \Twig\Markup($markup, 'UTF-8');
			},
			['is_safe' => ['html']]
		));

		return $twig;
	}

	/**
	 * Updates Twig environment options.
	 *
	 * @link https://twig.symfony.com/doc/2.x/api.html#environment-options
	 *
	 * \@param array $options An array of environment options.
	 *
	 * @return array
	 */
	function update_twig_environment_options( $options ) {
	    // $options['autoescape'] = true;

	    return $options;
	}
	
	/**
	 *
	 * Vite Asset generation.
	 *
	 */
	protected function wp_vite($entries = [], $buildDir = 'public/build'): string {
		$vite = new \App\Support\WPVite(['buildDirectory' => $buildDir]);
		return $vite->tags($entries);
	}
	
	protected function wp_vite_asset($entry, $buildDir = 'public/build'): string {
		$vite = new \App\Support\WPVite(['buildDirectory' => $buildDir]);
		return $vite->assetUrl($entry);
	}
	
	/**
	 * Use the generated theme.json file.
	 *
	 * @return string
	 */
	public function override_theme_json_path($path, $file) {
		if ($file === 'theme.json') {
			return get_stylesheet_directory() . '/public/build/assets/theme.json';
		}
		return $path;
	}
	
	/**
	 *
	 * Move ACF json to /src folder
	 *
	 */
	 
	public function acf_json_save_point( $path ) {
		return get_stylesheet_directory() . '/src/acf-json';
	}
	
	public function acf_json_load_point( $paths ) {
		// Remove the original path (optional).
		unset($paths[0]);
	
		// Append the new path and return it.
		$paths[] = get_stylesheet_directory() . '/src/acf-json';
	
		return $paths;    
	}
	
	public function my_acf_google_map_api( $api ){
		$api['key'] = 'YOUR API KEY';
		return $api;
	}
	
	/**
	 *
	 * Ignore node_modules in All-in-One WP Migration and Backup
 	 *
	 */
	 
	public function ignore_node( $exclude_filters ) {
	  $exclude_filters[] = get_stylesheet_directory() . '/node_modules';
	  return $exclude_filters;
	}
}
