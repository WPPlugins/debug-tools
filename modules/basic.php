<?php

class DFDebug_Module_Basic extends DFDebug_Module {
	var $is_win;

	function __construct() {
		global $is_IIS;
		$this->is_win = $is_IIS || ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) );

		parent::__construct( array(
			'name' => 'Standard',
			'description' => 'Basic stats'
		) );
	}

	function activate() {
		add_action( 'dfdebug_menu_bar_items', array( &$this, 'menu_items' ), 10, 2 );

		if ( is_admin() ) {

			if ( !$this->is_win ) {
				add_action( 'in_admin_footer', array( &$this, 'admin_footer_times' ) );
			}

			add_action( 'in_admin_footer', array( &$this, 'admin_footer_memory' ) );
		}
	}

	function menu_items( $menu_bar, $parent_id ) {

		$menu_bar->add_node( array(
			'id'     => 'dfdebug_peak_memory',
			'title'  => 'Peak memory: ' . $this->peak_memory_mb() . 'M',
			'parent' => $parent_id
		) );

		$menu_bar->add_node( array(
			'id'     => 'dfdebug_process_time',
			'title'  => 'Processing time: ' . timer_stop() . ' sec',
			'parent' => $parent_id
		) );

		/*
		if ( false !== $load = $this->system_load() ) {
			$menu_bar->add_node( array(
				'id'     => 'dfdebug_system_load',
				'title'  => 'Load: ' . $load,
				'parent' => $parent_id
			) );
		}
		*/

		global $wpdb;
		$menu_bar->add_node( array(
			'id'     => 'dfdebug_query_count',
			'title'  => 'Queries: ' . $wpdb->num_queries,
			'parent' => $parent_id
		) );

	}

	function safer_memory_get_peak_usage() {
		static $mem = 0;

		if ( function_exists( 'memory_get_peak_usage' ) )
			return memory_get_peak_usage();

		$new_mem = memory_get_usage();
		if ( $new_mem > $mem )
			$mem = $new_mem;

		return $mem;
	}

	function peak_memory_mb() {
		return round( (float) $this->safer_memory_get_peak_usage() / pow( 1024, 2 ), 3 );
	}

	function system_load() {
		global $is_IIS;
		$result = '';

		if ( ! $this->is_win && $output = @shell_exec( 'cat /proc/loadavg' ) ) {
			$result = implode( ' ', array_slice( explode( ' ', $output ), 0, 3 ) );
		}

		return $result;
	}

	function admin_footer_times() {
		$times = timer_stop();
		echo "<p>Total time: $times sec</p>";
	}

	function admin_footer_memory() {
		$mem = $this->peak_memory_mb();
		echo "<p>Max memory: $mem MB</p>";
	}

}
