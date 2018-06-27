<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.1.3
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Registers all neccessary ACF fields.  This is currently disabled in funbotic.php.
 */

if ( function_exists( "register_field_group" ) ) {
    
	register_field_group(array (
		'id' => 'acf_do-not-alter-camper-current-session-status',
		'title' => 'Do Not Alter: Camper Current Session Status',
		'fields' => array (
			array (
				'key' => 'field_5b33be3bf06b7',
				'label' => 'Funbotic Camper Current Session Status',
				'name' => 'funbotic_camper_current_session_status',
				'type' => 'true_false',
				'instructions' => 'Check this box if the camper is attending the current session of camp.	This will determine if the camper\'s name appears as a choice when tagging photos.',
				'message' => '',
				'default_value' => 0,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'user_type',
					'operator' => '==',
					'value' => 'administrator',
					'order_no' => 0,
					'group_no' => 0,
				),
				array (
					'param' => 'ef_user',
					'operator' => '==',
					'value' => 'subscriber',
					'order_no' => 1,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
    ));
    
	register_field_group(array (
		'id' => 'acf_do-not-alter-campers-in-media',
		'title' => 'Do Not Alter: Campers in Media',
		'fields' => array (
			array (
				'key' => 'field_5a9d9940540fe',
				'label' => 'Campers in Media',
				'name' => 'campers_in_media',
				'type' => 'checkbox',
				'instructions' => 'Select all campers present in this piece of media.	This media will show up in the camper\'s private profile, so please ensure they are clearly visible.',
				'choices' => array (),
				'default_value' => '',
				'layout' => 'vertical',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'ef_media',
					'operator' => '==',
					'value' => 'image',
					'order_no' => 0,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
    ));
    
	register_field_group(array (
		'id' => 'acf_do-not-alter-child-relationship',
		'title' => 'Do Not Alter: Child Relationship',
		'fields' => array (
			array (
				'key' => 'field_5ad9158054d4d',
				'label' => 'Funbotic Children',
				'name' => 'funbotic_children',
				'type' => 'checkbox',
				'instructions' => 'Select the children to be associated with this user.',
				'choices' => array (
					'child1' => 'Child 1',
					'child2' => 'Child 2',
				),
				'default_value' => '',
				'layout' => 'vertical',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'user_type',
					'operator' => '==',
					'value' => 'administrator',
					'order_no' => 0,
					'group_no' => 0,
				),
				array (
					'param' => 'ef_user',
					'operator' => '==',
					'value' => 'customer',
					'order_no' => 1,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
    ));
    
	register_field_group(array (
		'id' => 'acf_do-not-alter-parent-relationship',
		'title' => 'Do Not Alter: Parent Relationship',
		'fields' => array (
			array (
				'key' => 'field_5ad910fd54ee6',
				'label' => 'Funbotic Parents',
				'name' => 'funbotic_parents',
				'type' => 'textarea',
				'instructions' => 'These are the parent profiles associated with this camper.	To alter parent/child associations, you will need to edit the parent profile.',
				'default_value' => '',
				'formatting' => 'br',
				'maxlength' => '',
				'placeholder' => '',
				'rows' => '',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'user_type',
					'operator' => '==',
					'value' => 'administrator',
					'order_no' => 0,
					'group_no' => 0,
				),
				array (
					'param' => 'ef_user',
					'operator' => '==',
					'value' => 'subscriber',
					'order_no' => 1,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array (
			),
		),
		'menu_order' => 0,
    ));
    
	register_field_group(array (
		'id' => 'acf_do-not-alter-profile-user-id',
		'title' => 'Do Not Alter: Profile User ID',
		'fields' => array (
			array (
				'key' => 'field_5b19686e83a0c',
				'label' => 'Profile User ID',
				'name' => 'profile_user_id',
				'type' => 'number',
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'min' => '',
				'max' => '',
				'step' => '',
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'user_type',
					'operator' => '==',
					'value' => 'administrator',
					'order_no' => 0,
					'group_no' => 0,
				),
				array (
					'param' => 'ef_user',
					'operator' => '==',
					'value' => 'administrator',
					'order_no' => 1,
					'group_no' => 0,
				),
			),
		),
		'options' => array (
			'position' => 'normal',
			'layout' => 'no_box',
			'hide_on_screen' => array (
				0 => 'permalink',
				1 => 'the_content',
				2 => 'excerpt',
				3 => 'custom_fields',
				4 => 'discussion',
				5 => 'comments',
				6 => 'revisions',
				7 => 'slug',
				8 => 'author',
				9 => 'format',
				10 => 'featured_image',
				11 => 'categories',
				12 => 'tags',
				13 => 'send-trackbacks',
			),
		),
		'menu_order' => 0,
	));
}