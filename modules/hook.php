<?php

class DFDebug_Module_Hook extends DFDebug_Module {

	var $hook_times = array();

	function __construct() {
		parent::__construct( array(
			'name' => 'Hooks',
			'description' => 'Hook diagnostics'
		) );
	}

	function activate() {
		//This should be governed by a setting, not active globally

		// Track start time. Just about the earliest time we can get from a plugin.
		global $timestart;
		$filter = current_filter();
		$key = $filter ? sprintf( 'Instance Created (%s)', $filter ) : 'Instance Created';
		$this->hook_times[$key][] = number_format( microtime( true ) - $timestart, 3 );
		$this->setup_hook_times();

		// Add this late in the init process to give plugins a chance to add to the filter.
		add_action( 'init',                   array( &$this, 'setup_more_hook_times' ), 50    );
		add_action( 'dfdebug_menu_bar_items', array( &$this, 'menu_items' ),            10, 2 );

	}

	// Runs during plugin load, can track some hooks before "init"
	function setup_hook_times() {

		// Unused.
		$default_hooks = array(
			'plugins_loaded'    => false,  // Some environment setup
			'after_setup_theme' => false,  // Theme is setup
			'init'              => false,  // Most plugins do their setup work
			'wp_head'           => false,  // Page output happens here
			'wp_footer'         => false
		);

		$this->track_hook_times( $default_hooks );

		// Allow selection of hooks to track
		if ( $hooks = get_user_meta( get_current_user_id(), 'dfdebug_track_hook_times', true ) )
			$this->track_hook_times( $hooks );

	}

	// Runs late in "init" action and therefore can only track later actions.
	function setup_more_hook_times() {
		if ( ! has_filter( 'dfdebug_track_hook_times' ) )
			return;

		$hooks = apply_filters( 'dfdebug_track_hook_times', array() );
		if ( $hooks )
			$this->track_hook_times( $hooks );
	}

	function track_hook_times( $hooks ) {
		$callback = array( $this, 'get_hook_times' );
		$default_args = array( 'priority' => 10, 'accepted_args' => 1, 'callback' => $callback );

		foreach( $hooks as $hook => $args ) {
			$args = wp_parse_args( $args, $default_args );
			add_action( $hook, $args['callback'], $args['priority'], $args['accepted_args'] );
		}
	}

	function get_hook_times() {
		$this->hook_times[ current_filter() ][] = $this->safe_timer_stop();
	}

	function menu_items( $menu_bar, $parent_id ) {
		if ( empty( $this->hook_times ) )
			return;

		$menu_bar->add_node( array(
			'id'     => 'dfdebug_hook_times',
			'title'  => 'Hook times',
			'parent' => $parent_id
		) );

		foreach ( $this->hook_times as $hook => $times ) {
			$menu_bar->add_node( array(
				'id'     => 'dfdebug_hook_times_' . sanitize_title( $hook ),
				'title'  => sprintf( '%s: %d runs, last at %s', esc_html( $hook ), count( $times ), end( $times ) ),
				'parent' => 'dfdebug_hook_times'
			) );
		}

	}

	// For use in 'all' actions or other situations where a profiling function is hooked to multiple actions.
	function current_filter() {
		return $GLOBALS['wp_current_filter'][0];
	}

	function safe_timer_stop( $display = 0, $precision = 3 ) {
		if ( function_exists( 'timer_stop' ) ) {
			return timer_stop( $display, $precision );
		}

		global $timestart;
		$timer = number_format( microtime( true ) - $timestart, $precision );

		if ( $display )
			echo $timer;

		return $timer;
	}
}
