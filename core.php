<?php

class DFDebug {
	// If is_admin(), this will contain object for admin-related functionality
	var $admin = null;
	var $modules = array();

	var $settings = array();
	var $enabled = true;

	var $default_settings = array();

	function __construct() {
		$this->load_settings();
		$this->load_modules();

		// Backend functionality
		if ( is_admin() ) {
			require( DFDEBUG_DIR . '/admin.php' );
			$this->admin = new DFDebug_Admin( $this );
		}

		add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), apply_filters( 'dfdebug_admin_bar_priority', 999999 ) );
	}

	function load_settings() {
		$settings = get_option( 'dfdebug_settings', array() );
		$user_settings = $this->get_user_settings();

		$this->settings = array_merge( $this->default_settings, $settings, $user_settings );
	}

	function get_user_settings() {
		$uid = get_current_user_id();
		$settings = $uid ? (array) get_user_meta( $uid, 'dfdebug_settings', true ) : array();

		return $settings;
	}

	function admin_bar_menu( &$menu_bar ) {
		// Setup parent menu item
		$parent_id = 'dfdebug_main';
		$menu_bar->add_node( array(
			'id' => $parent_id,
			'title' => 'Debug Tools',
			'parent' => 'top-secondary'
		) );

		if ( current_user_can( 'manage_options' ) ) {
			$menu_bar->add_node( array(
				'id'     => 'dfdebug_admin',
				'title'  => 'Settings',
				'href'   => admin_url( 'admin.php?page=dfdebug-admin' ),
				'parent' => $parent_id
			) );
		}

		do_action( 'dfdebug_menu_bar_items', array( &$menu_bar ), $parent_id );
	}

	function get_available_modules() {
		$path = dirname( __FILE__ );
		$default_modules = array(
			'basic' => array(
				'require'     => $path . '/modules/basic.php',
				'class'       => 'DFDebug_Module_Basic'
			),
			'hook' => array(
				'require'     => $path . '/modules/hook.php',
				'class'       => 'DFDebug_Module_Hook'
			),
			'query' => array(
				'require'     => $path . '/modules/query.php',
				'class'       => 'DFDebug_Module_Query'
			),
			'cron' => array(
				'require'     => $path . '/modules/cron.php',
				'class'       => 'DFDebug_Module_Cron'
			)
		);

		return apply_filters( 'dfdebug_available_modules', $default_modules );
	}

	function get_active_modules() {
		$global_modules = get_option( 'dfdebug_modules_global_active' );
		$user_modules = get_user_meta( get_current_user_id(), 'dfdebug_active_modules', true );

		$modules = array_merge( (array) $global_modules, (array) $user_modules );
		$modules = array_filter( $modules );

		return apply_filters( 'dfdebug_active_modules', $modules );
	}

	//@todo: Introduce better handling for when a file exists, but the class is missing.
	//       There are many other loading situations to consider, as well.
	function load_module( $slug, $config, $activate = false ) {
		if ( !empty( $config['require'] ) && file_exists( $config['require'] ) ) {
			require_once( $config['require'] );
		}

		$this->modules[$slug] = new $config['class']( $slug );
		$this->modules[$slug]->id = $slug;

		if ( $activate ) {
			$this->modules[$slug]->activate();
		}
	}

	function load_modules() {
		$available_modules = $this->get_available_modules();
		$active_modules = $this->get_active_modules();

		$load_modules = array_intersect_key($available_modules, array_flip( $active_modules ) );

		foreach( $load_modules as $slug => $config ) {
			$this->load_module( $slug, $config, true );
		}
	}

	// Load all (for administration purposes, etc.)
	// Selectively initialize ones not already enabled during load_modules()
	function load_all_modules() {
		$available_modules = $this->get_available_modules();
		$active_slugs = array_keys( $this->modules );

		foreach ( $available_modules as $slug => $config ) {
			if ( !in_array( $slug, $active_slugs ) )
				$this->load_module( $slug, $config );
		}
	}

	// Based on wp_debug_mode()
	function set_debug_mode( $enable, $display = false, $log_file = false ) {
		if ( $enable ) {
			// E_DEPRECATED is a core PHP constant in PHP 5.3. Don't define this yourself.
			// The two statements are equivalent, just one is for 5.3+ and for less than 5.3.
			if ( defined( 'E_DEPRECATED' ) )
				error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
			else
				error_reporting( E_ALL );

			if ( $display )
				ini_set( 'display_errors', 1 );
			elseif ( null !== $display )
				ini_set( 'display_errors', 0 );

			if ( $log_file ) {
				$file = is_bool( $log_file ) ? WP_CONTENT_DIR . '/debug.log' : $log_file;
				ini_set( 'log_errors', 1 );
				ini_set( 'error_log', $file );
			}
		} else {
			error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
		}
	}
}

/**
 * Module class definition
 *
 */

class DFDebug_Module {
	var $id;
	var $name;
	var $description;
	var $active;

	function __construct( $args ) {
		$this->name = $args['name'];
		$this->description = $args['description'];
		$this->active = false;
	}

	function activate() {
		$this->active = true;
	}

}
