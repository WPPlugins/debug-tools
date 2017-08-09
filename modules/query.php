<?php
class DFDebug_Module_Query extends DFDebug_Module {

	function __construct() {
		parent::__construct( array(
			'name' => 'Queries',
			'description' => 'Query diagnostics'
		) );
	}

	function activate() {
		define( 'SAVEQUERIES', true );
//		add_action( 'wp_footer', array( $this, 'show_queries' ) );
	}

	function show_queries() {
		global $wpdb;
		echo '<!--';
		print_r( $wpdb->queries );
		echo '-->';
	}

	function show_write_queries() {
		global $wpdb;
		$updates = array();
		foreach( $wpdb->queries as $q ) {
			if ( strpos( $q[0], 'INSERT' ) !== false || strpos( $q[0], 'UPDATE' ) !== false ) {
				$updates[] = $q;
			}
		}
		echo '<!-- Write Queries';
		print_r( $wpdb->queries );
		echo '-->';
	}

}



