<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.funbotic.com
 * @since      1.0.1
 *
 * @package    Funbotic
 * @subpackage Funbotic/includes
 * @author     Alexander LaBrie <alabrie@funbotlab.com>
 */

/**
 * Generate shortcode to allow display of a dynamic gallery populated only with images
 * expressly associated with the current user.  Added as part of an init action due to
 * plugins being loaded very early.
 * 
 * Generates shortcode to display LearnDash content as well as dynamic galleries for each
 * child of a parent user.
 */

add_action('init', 'funbotic_user_gallery_init');
add_action('init', 'funbotic_parent_display_init');


function funbotic_user_gallery_init() {
    function funbotic_generate_dynamic_user_gallery( $content = null ) {
        $id = get_current_user_id();
        $current_meta = get_user_meta( $id, 'funbotic_associated_images', false );
        // Just to be safe, we will clean the array to ensure it is in a single dimension.
        $current_associated_images = funbotic_clean_array( $current_meta );

        if ( empty( $current_associated_images ) || is_null( $current_associated_images ) ) {

            return 'No pictures yet!';

        } else {

            $content = '[gallery ids="';

            foreach ( $current_associated_images as $image_id ) {
                $content .= $image_id . ',';
            }

            $content = rtrim($content, ',');
            $content .= '"]';

            return '<span class="dynamic_user_gallery">' . $content . '</span>';
        } // End if/else.
    } // End function funbotic_generate_dynamic_user_gallery.
    add_shortcode( 'funbotic_user_gallery', 'funbotic_generate_dynamic_user_gallery' );
} // End function funbotic_user_gallery_init.


/**
 * Code copied from LearnDash's ld-course-info-widget.php, utilized to generate content that would normally show in the
 * [ld_profile] shortcode, but for whichever user's id is in the $user_to_get parameter.
 * 
 * @since 1.1.5
 * 
 * @param  array 	$atts 	shortcode attributes
 * @return string 	output profile for user
 */
function funbotic_learndash_profile( $atts, $user_to_get ) {
	global $learndash_shortcode_used;
	
	// Add check to ensure LearnDash is active.
	if ( ! is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
		return '';
	}

	// Add check to ensure user is logged in.
	if ( ! is_user_logged_in() ) {
		return '';
	}
	
	$defaults = array(
		'user_id'				=>	$user_to_get,
		'per_page'				=>	false,
		'order' 				=> 'DESC', 
		'orderby' 				=> 'ID', 
		'course_points_user' 	=> 'yes',
		'expand_all'			=> false
	);
	$atts = wp_parse_args( $atts, $defaults );

	if ( ( strtolower($atts['expand_all'] ) == 'yes' ) || ( $atts['expand_all'] == 'true' ) || ( $atts['expand_all'] == '1' )) {
		$atts['expand_all'] = true;
	} else {
		$atts['expand_all'] = false;
	}

	if ( $atts['per_page'] === false ) {
		$atts['per_page'] = $atts['quiz_num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	} else {
		$atts['per_page'] = intval( $atts['per_page'] );
	}

	if ( $atts['per_page'] > 0 ) {
		$atts['paged'] = 1;
	} else {
		unset( $atts['paged'] );
		$atts['nopaging'] = true;
	}

	$atts = apply_filters('learndash_profile_shortcode_atts', $atts);

	if ( empty( $atts['user_id'] ) ) return;

	$current_user = get_user_by( 'id', $atts['user_id'] );
	$user_courses = ld_get_mycourses( $atts['user_id'], $atts );

	$usermeta = get_user_meta( $atts['user_id'], '_sfwd-quizzes', true );
	$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;
	$quiz_attempts = array();

	if ( ! empty( $quiz_attempts_meta ) ) {

		foreach ( $quiz_attempts_meta as $quiz_attempt ) {
			$c = learndash_certificate_details( $quiz_attempt['quiz'], $atts['user_id'] );
			$quiz_attempt['post'] = get_post( $quiz_attempt['quiz'] );
			$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );
			
			if ( $atts['user_id'] == get_current_user_id() && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
				$quiz_attempt['certificate'] = $c;
			}

			if ( !isset( $quiz_attempt['course'] ) )
				$quiz_attempt['course'] = learndash_get_course_id( $quiz_attempt['quiz'] );
			$course_id = intval( $quiz_attempt['course'] );

			$quiz_attempts[$course_id][] = $quiz_attempt;
		}
	}
	
	$profile_pager = array();
	
	if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
		$atts['per_page'] = intval( $atts['per_page'] );
			
		//$paged = get_query_var( 'page', 1 );
		//error_log('paged['. $paged .']');
		
		if ( ( isset( $_GET['ld-profile-page'] ) ) && ( !empty( $_GET['ld-profile-page'] ) ) ) {
			$profile_pager['paged'] = intval( $_GET['ld-profile-page'] );
		} else {
			$profile_pager['paged'] = 1;
		}
		
		$profile_pager['total_items'] = count( $user_courses );
		$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );
		
		$user_courses = array_slice ( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
	}
	
	$learndash_shortcode_used = true;

	return SFWD_LMS::get_template( 
		'profile', 
		array(
			'user_id' 			=> 	$atts['user_id'], 
			'quiz_attempts' 	=> 	$quiz_attempts, 
			'current_user' 		=> 	$current_user, 
			'user_courses' 		=> 	$user_courses,
			'shortcode_atts'	=>	$atts,
			'profile_pager'		=>	$profile_pager
		) 
	);
}


/*
 * This function generates the collated data for all children.  This includes: 
 * - Name
 * - LearnDash profile
 * - Gallery of associated images
 */ 
function funbotic_parent_display_init() {
    function funbotic_generate_parent_display( $content = null ) {
        $id = get_current_user_id();
        
        $current_children = get_the_author_meta( 'funbotic_children', $id );

        if ( empty( $current_children ) || is_null( $current_children ) ) {

            $content = 'According to our records, you do not have any children registered for Fun Bot Lab camp at this time.  If this is an error, please contact us at 703-831-7747.';

        } else {

            $content = '';

            foreach ( $current_children as $child ) {

				$child_name = get_the_author_meta( 'display_name', $child );
				
				if ( $child_name == '' && ( get_the_author_meta( 'nickname', $child ) != '' ) ) {
					$child_name = get_the_author_meta( 'nickname', $child );
				} else {
					$child_name = get_the_author_meta( 'user_login', $child );
				}

				$content .= '<div><h3>' . $child_name . '</h3></div></br>';

				// Only generate data for [ld_profile] if LearnDash is active on the current installation of Wordpress.
				if ( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
					$content .= funbotic_learndash_profile( $atts, $child );
				}

                $current_meta = get_the_author_meta( 'funbotic_associated_images', $child );
                // Just to be safe, we will clean the array to ensure it is in a single dimension.
                if ( empty( $current_meta ) || is_null( $current_meta ) ) {
                    $current_associated_images = array();
                } else {
                    $current_associated_images = funbotic_clean_array( $current_meta );
                }

                if ( empty( $current_associated_images ) || is_null( $current_associated_images ) ) {
                    
                    $content .= '<div>No pictures yet!</div></br>';

                } else {

                    $content .= '<div><span class="dynamic_user_gallery">[gallery ids="';

                    foreach ( $current_associated_images as $image_id ) {
                        $content .= $image_id . ',';
                    }

                    $content = rtrim($content, ',');
                    $content .= '"]</span></div></br>';
                } // End if/else check for associated images.

            } // End foreach ( $current_children as $child ).

        } // End if/else check for children.

        return '<span class="parent_display">' . $content . '</span>';
    } // End function funbotic_generate_parent_display.
    add_shortcode( 'funbotic_parent_display', 'funbotic_generate_parent_display' );
} // End function funbotic_parent_display_init.