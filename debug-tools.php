<?php
/*
Plugin Name: Debug tools
Description: Lightweight debug/tuning tools intended for use on production servers.
Version: 1.1
Author: Jeff Brand
*/

if ( !function_exists( 'get_option' ) )
	die( 'No direct access!' );

// Conditional loader. Don't load code unless we're active.
DFDebug_Loader::setup();

class DFDebug_Loader {

	static function setup() {
		define( 'DFDEBUG_DIR',    dirname( __FILE__ ) );
		define( 'DFDEBUG_URL',    plugins_url( '', __FILE__ ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'init' ) );

	}

	static function init() {
		if ( self::is_enabled() )
			self::load();
	}

	static function is_enabled() {
		// Enable for admins
		$enabled = @current_user_can( 'manage_options' ) || self::is_force_enabled();

		//Enable if opted in
		$uid = get_current_user_id();
		if ( !$enabled && $uid != 0 ) {
			$enabled = get_user_meta( $uid, 'dfdebug_enabled', true );
		}

		return apply_filters( 'dfdebug_enabled', $enabled );
	}

	static function is_force_enabled() {
		return !empty( $_REQUEST['dfdebug_force'] );
	}

	static function load() {
		global $dfdebug;
		require( DFDEBUG_DIR . '/core.php' );

		do_action( 'dfdebug_preinit' );

		$dfdebug = new DFDebug();

		do_action( 'dfdebug_init' );
	}
}