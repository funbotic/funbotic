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

add_action('wp_logout','funbotic_auto_redirect_after_logout');

function funbotic_auto_redirect_after_logout() {
    wp_redirect( home_url() );
    exit();
}