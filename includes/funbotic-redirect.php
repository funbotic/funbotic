<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.1.1
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Redirect users to the Profile page upon login, and back to the Home page upon logging out.
 */

add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );
add_action('wp_logout','funbotic_auto_redirect_after_logout');

function my_login_redirect( $redirect_to, $request, $user ) {
    $redirect_to = home_url() . '/my-account/';
    return $redirect_to;
}

function funbotic_auto_redirect_after_logout() {
    wp_redirect( home_url() );
    exit();
}