<?php

class DFDebug_Module_Cron extends DFDebug_Module {
	function __construct() {
		parent::__construct( array(
			'name' => 'Cron',
			'description' => 'Cron debugging'
		) );
	}

	function activate() {
		add_action( 'dfdebug_admin_menu', array( $this, 'admin_menu' ) );
	}

	function admin_menu( $parent_id ) {
		add_submenu_page( $parent_id, 'Cron', 'Cron', 'manage_options', 'dfdebug_cron', array( $this, 'cron_view' ) );
	}

	function cron_view() {
		$schedules = wp_get_schedules();
		$crons = _get_cron_array();

		$schedule_template =  '<p><strong>%s</strong><br />%d seconds<br /><span class="description">%s</span></p>';
		$local_tz = get_option( 'timezone_string', '' );
		$gmt_offset = get_option( 'gmt_offset' ) * 3600;
		$cron_jobs = array();
		foreach ( $crons as $ts => $tasks ) {

			foreach ( $tasks as $hook => $cron ) {
				foreach ( $cron as $key => $data ) {
					$interval = !empty( $data['interval'] ) ? $data['interval'] : '';
					$schedule = !empty( $data['schedule'] ) ? $data['schedule'] : '';
					$args     = !empty( $data['args'] )     ? $data['args']     : array();

					$arg_string = $args ? implode( ', ', $args ) : '';
					if ( !empty( $interval ) ) {
						$sched_string = sprintf( '<strong>Schedule:</strong> %s<br /><strong>Interval:</strong> %d', $schedule, $interval );
					} else {
						$sched_string = '';
					}

					$cron_jobs[$ts][] = compact( 'hook', 'key', 'interval', 'schedule', 'args', 'arg_string', 'sched_string' );
				}

			}
		}

		global $title;
		echo '<div class="wrap">';
		echo '<h2>' . $title . '</h2>';
		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th>Schedules</th>';
		echo '<td>';
		foreach ( $schedules as $slug => $sched ) {
			echo sprintf( $schedule_template, esc_html( $sched['display'] ), $sched['interval'], esc_html( $slug ) );
		}
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th>Crons</th>';
		echo '<td>';

		echo '<table>';
		?>
		<tr>
			<th>Next Run</th>
			<th>Hooks/Function</th>
			<th>Sched</th>
		</tr>
		<?
		$last_ts = '';
		
		foreach ( $cron_jobs as $ts => $tasks ) {
			$last_hook = '';
			foreach ( $tasks as $task ) {
				?>
				<tr>
					<td valign="top">
					<?php if ( $ts != $last_ts ) { ?>
						<?php echo date( 'Y-m-d H:i:s', $ts ) ?> GMT<br />
						<?php echo date( 'Y-m-d H:i:s', $ts + $gmt_offset ) ?> <?php echo $local_tz ?><br />
						<?php echo $ts ?> secs<br />
						<?php echo human_time_diff( $ts ) . ( time() > $ts ? ' ago' : '' ) ?>
					<?php } ?>
					</td>

					<td valign="top">
						<strong> <?php echo $task['hook'] ?></strong><br />
						<strong>Functions:</strong><br />
						<?php echo $this->functions_for_hook( $task['hook'] ) ?>(<?php echo $task['arg_string'] ?>)
					</td>
					<td valign="top"><?php echo $task['sched_string'] ?></td>
				</tr>
				<?php
				$last_hook = $hook;
				$last_ts = $ts;
			}
		}
		echo '</table>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
		echo '</div>';

	}

	function functions_for_hook( $hook ) {
		global $wp_filter;
		if ( empty( $wp_filter[$hook] ) ) {
			return '';
		}

		$functions = array();
		foreach ( $wp_filter[$hook] as $priority => $hooks ) {
			foreach( $hooks as $hook ) {
				if ( isset( $hook['function'] ) ) {
					$func = $hook['function'];
					if ( is_array( $func ) ) {
						if ( is_string( $func[0] ) ) {
							$functions[] = sprintf( '%s::%s', $func[0], $func[1] );
						} else {
							$functions[] = sprintf( '%s->%s', get_class( $func[0] ), $func[1] );
						}
					} else {
						$functions[] = $func;
					}
				}
			}
		}

		$html = implode( '<br />', $functions );
		return $html;
	}
}
