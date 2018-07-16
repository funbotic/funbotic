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
 * "If" Shortcode Plugin
 * 
 * Provides an "if" shortcode to conditionally render content.
 * 
 * Developed by geomagas, minor edits to include all modern conditionals by Alexander LaBrie.
 */
$if_shortcode_filter_prefix = 'evaluate_condition_';
$if_shortcode_block 		= NULL;

add_shortcode( 'if', 'process_if_shortcode' );

function process_if_shortcode( $atts, $content ) {
	global $if_shortcode_filter_prefix;
	$false_strings 	= array( '0', '', 'false', 'null', 'no' );
	$atts 			= normalize_empty_atts( $atts );
	$result 		= false;

	foreach ( $atts as $condition => $val ) {
		$mustbe = !in_array( $val, $false_strings, true ); // strict, or else empty atts don't work as expected
		$evaluate = apply_filters( "{$if_shortcode_filter_prefix}{$condition}", false );
		$result |= $evaluate == $mustbe;
	}

	global $if_shortcode_block;
	$save_block 		= $if_shortcode_block;
	$if_shortcode_block = array( 'result' => $result, 'else' => '',);
	$then 				= do_shortcode( $content );
	$else				= $if_shortcode_block['else'];
	$if_shortcode_block = $save_block;

	return $result ? $then : $else;
}
	
add_shortcode( 'else', 'process_else_shortcode' );

function process_else_shortcode( $atts, $content ) {
	global $if_shortcode_block;

	if( $if_shortcode_block && !$if_shortcode_block['result'] ) {
		$if_shortcode_block['else'] .= do_shortcode( $content );
	}

	return '';
}
	
add_shortcode( 'eitherway', 'process_eitherway_shortcode' );

function process_eitherway_shortcode( $atts, $content ) {
	$content = do_shortcode( $content );
	global $if_shortcode_block;

	if( $if_shortcode_block ) {
		$if_shortcode_block['else'] .= $content;
	}

	return $content;
}
	
// Add supported conditional tags.
add_action( 'init', 'if_shortcode_conditional_tags' );

function if_shortcode_conditional_tags() {
	$supported = array(
		'comments_open',
		'has_tag',
		'has_term',
		'in_category',
		'is_404',
		'is_admin',
		'is_archive',
		'is_attachment',
		'is_author',
		'is_category',
		'is_child_theme',
		'is_comments_popup',
		'is_customize_preview',
		'is_date',
		'is_day',
		'is_feed',
		'is_front_page',
		'is_home',
		'is_month',
		'is_multi_author',
		'is_multisite',
		'is_main_site',
		'is_page',
		'is_page_template',
		'is_paged',
		'is_preview',
		'is_rtl',
		'is_search',
		'is_single',
		'is_singular',
		'is_sticky',
		'is_super_admin',
		'is_tag',
		'is_tax',
		'is_time',
		'is_trackback',
		'is_year',
		'pings_open',
		);

	global $if_shortcode_filter_prefix;

	foreach ($supported as $tag) {
		add_filter( "{$if_shortcode_filter_prefix}{$tag}", $tag );
	}
}

// normalize_empty_atts found here: http://wordpress.stackexchange.com/a/123073/39275
function normalize_empty_atts( $atts ) {
	foreach ( $atts as $attribute => $value ) {
		if ( is_int( $attribute ) ) {
			$atts[strtolower( $value )] = true;
			unset( $atts[$attribute] );
		}
	}
	return $atts;
}


// Conditional evaluator to determine if the user is logged in.
// Parameter to use in shortcode is: is_logged_in
function is_logged_in() {
	return is_user_logged_in();
}

// Conditional evaluator to determine if the user has the role of Subscriber from LearnDash.
// Parameter to use in shortcode is: is_user_role_subscriber
function is_user_role_subscriber() {
	$currentUserID = get_current_user_id();
	if ( user_can( $currentUserID, 'subscriber' ) ) {
		return true;
	} else {
		return false;
	}
}

// Conditional evaluator to determine if the user has the role of Group Leader from LearnDash.
// Parameter to use in shortcode is: is_user_role_group_leader
function is_user_role_group_leader() {
	$currentUserID = get_current_user_id();
	if ( user_can( $currentUserID, 'group_leader' ) ) {
		return true;
	} else {
		return false;
	}
}

// Conditional evaluator to determine if the user has the role of Customer from WooCommerce - used for Parents.
// Parameter to use in shortcode is: is_user_role_customer
function is_user_role_customer() {
	$currentUserID = get_current_user_id();
	if ( user_can( $currentUserID, 'customer' ) ) {
		return true;
	} else {
		return false;
	}
}

// Conditional evalulator to determine if the user has the role of Administrator.
// Parameter to use in shortcode is: is_user_role_administrator
function is_user_role_administrator() {
	$currentUserID = get_current_user_id();
	if ( user_can( $currentUserID, 'administrator' ) ) {
		return true;
	} else {
		return false;
	}
}

// Add all filters for conditional evalulators.
add_filter( $if_shortcode_filter_prefix . 'is_logged_in', 'is_logged_in' );
add_filter( $if_shortcode_filter_prefix . 'is_user_role_subscriber', 'is_user_role_subscriber' );
add_filter( $if_shortcode_filter_prefix . 'is_user_role_group_leader', 'is_user_role_group_leader' );
add_filter( $if_shortcode_filter_prefix . 'is_user_role_customer', 'is_user_role_customer' );
add_filter( $if_shortcode_filter_prefix . 'is_user_role_administrator', 'is_user_role_administrator' );