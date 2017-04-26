<?php

/**
 * Utility class for sesion utilities
 *
 * THIS CLASS SHOULD NEVER BE INSTANTIATED
 */
class WP_Session_Utils {
	/**
	 * Sanitize a potential Session ID so we aren't fetching broken data
	 * from the options table.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	private function sanitize( $id ) {
		return preg_replace( "/[^A-Za-z0-9_]/", '', $id );
	}

	/**
	 * Count the total sessions in the database.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int
	 */
	public static function count_sessions() {
		global $wpdb;

		$query = "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_wp_session_expires_%'";

		/**
		 * Filter the query in case tables are non-standard.
		 *
		 * @param string $query Database count query
		 */
		$query = apply_filters( 'wp_session_count_query', $query );

		$sessions = $wpdb->get_var( $query );

		return absint( $sessions );
	}

	/**
	 * Create a new, random session in the database.
	 */
	public static function create_dummy_session() {
		$item = new \EAMann\Sessionz\Objects\Option("" );

		$session_id = self::generate_id();

		// Store the session
		add_option( "_wp_session_{$session_id}", $item->data, '', 'no' );
		add_option( "_wp_session_expires_{$session_id}", $item->time, '', 'no' );
	}

	/**
	 * Delete old sessions from the database.
	 *
	 * @param int $limit Maximum number of sessions to delete.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int Sessions deleted.
	 */
	public static function delete_old_sessions( $limit = 1000 ) {
		global $wpdb;

		$lifetime = intval( ini_get('session.gc_maxlifetime') );
		$limit = intval( $limit );

		// Session is expired if now - item.time > maxlifetime
		// Said another way, if  item.time < now - maxlifetime
		$filter = intval( time() - $lifetime );
		$keys = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_wp_session_expires_%' AND option_value > $filter ORDER BY option_value LIMIT 0, $limit" );

		foreach( $keys as $expiration ) {
			$key = $expiration->option_name;
			$session_id = self::sanitize( substr( $key, 20 ) );

			delete_option( "_wp_session_$session_id" );
			delete_option( "_wp_session_expires_$session_id" );
		}

		return count( $keys );
	}

	/**
	 * Remove all sessions from the database, regardless of expiration.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int Sessions deleted
	 */
	public static function delete_all_sessions() {
		global $wpdb;

		$count = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_wp_session_%'" );

		return (int) ( $count / 2 );
	}

	/**
	 * Generate a new, random session ID.
	 *
	 * @return string
	 */
	public static function generate_id() {
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		$hash = new PasswordHash( 8, false );

		return md5( $hash->get_random_bytes( 32 ) );
	}
} 