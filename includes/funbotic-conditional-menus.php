<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.0.0
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Code to allow menus to be shown based upon whether or not the user is logged in.
 * 
 * All credit to: http://www.wpbeginner.com/wp-themes/how-to-show-different-menus-to-logged-in-users-in-wordpress/
 */
function funbotic_wp_nav_menu_args( $args = '' ) {
	if( is_user_logged_in() ) { 
		$args['menu'] = 'logged-in';
	} else { 
		$args['menu'] = 'logged-out';
	} 
		return $args;
}

add_filter( 'wp_nav_menu_args', 'funbotic_wp_nav_menu_args' );