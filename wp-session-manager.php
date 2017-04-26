<?php
/**
 * Plugin Name: WP Session Manager
 * Plugin URI:  http://jumping-duck.com/wordpress/plugins
 * Description: Prototype session management for WordPress.
 * Version:     2.0
 * Author:      Eric Mann
 * Author URI:  http://eamann.com
 * License:     GPLv2+
 */

require __DIR__ . '/vendor/autoload.php';

// Queue up the session stack
EAMann\Sessionz\Manager::initialize()
	->addHandler( new \EAMann\Sessionz\Handlers\OptionsHandler() )
	->addHandler( new \EAMann\Sessionz\Handlers\MemoryHandler() );

// Include WP_CLI routines early
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include 'includes/class-wp-session-utils.php';
	include 'includes/wp-cli.php';
}

// Start up session management, if we're not in the CLI
if ( ! defined( 'WP_CLI' ) || false === WP_CLI ) {
	add_action( 'plugins_loaded', 'session_start' );
}