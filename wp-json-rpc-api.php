<?php
/*
Plugin Name: WordPress JSON RPC API Plugin
Plugin URI: http://austinmatzko.com/wordpress-plugins/wp-json-rpc-api/
Description: This plugin provides a JSON RPC API for WordPress.
Author: Austin Matzko
Author URI: http://austinmatzko.com
Version: 0.9
*/

if ( ! defined('ABSPATH') ) {
	die('Please do not load this file directly.');
}

include_once ABSPATH . WPINC . '/class-IXR.php';

if ( ! function_exists( 'load_wp_json_rpc_api' ) ) {
	function load_wp_json_rpc_api()
	{
		global $wp_json_rpc_api;
		if ( ! class_exists( 'WP_JSON_RPC_API_Control' ) ) {
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'core.php';
		}

		if ( empty( $wp_json_rpc_api ) ) {
			$wp_json_rpc_api = new WP_JSON_RPC_API_Control;
		}
	}

	add_action('plugins_loaded', 'load_wp_json_rpc_api');
}
