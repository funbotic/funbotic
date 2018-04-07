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
 * Credit to https://www.cssigniter.com/how-to-add-custom-fields-to-the-wordpress-registration-form/
 */

/**
 * Front end registration
 */

add_action( 'register_form', 'funbotic_registration_form' );

function funbotic_registration_form() {
	$year = ! empty( $_POST['year_of_birth'] ) ? intval( $_POST['year_of_birth'] ) : '';
	(int) $currentYear = date( 'Y' );

	?>
	<p>
		<label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'funbotic' ) ?><br/>
			<input type="number"
			       min="1900"
			       max="<?php $currentYear; ?>"
			       step="1"
			       id="year_of_birth"
			       name="year_of_birth"
			       value="<?php echo esc_attr( $year ); ?>"
			       class="input"
			/>
		</label>
	</p>
	<?php
}

add_filter( 'registration_errors', 'funbotic_registration_errors', 10, 3 );

function funbotic_registration_errors( $errors, $sanitized_user_login, $user_email ) {
	if ( empty( $_POST['year_of_birth'] ) ) {
		$errors->add( 'year_of_birth_error', __( '<strong>ERROR</strong>: Please enter your year of birth.', 'funbotic' ) );
	}

	if ( ! empty( $_POST['year_of_birth'] ) && intval( $_POST['year_of_birth'] ) < 1900 ) {
		$errors->add( 'year_of_birth_error', __( '<strong>ERROR</strong>: You must be born after 1900.', 'funbotic' ) );
	}

	return $errors;
}

add_action( 'user_register', 'funbotic_user_register' );

function funbotic_user_register( $user_id ) {
	if ( ! empty( $_POST['year_of_birth'] ) ) {
		update_user_meta( $user_id, 'year_of_birth', intval( $_POST['year_of_birth'] ) );
	}
}

/**
 * Back end registration
 */

add_action( 'user_new_form', 'funbotic_admin_registration_form' );

function funbotic_admin_registration_form( $operation ) {
	if ( 'add-new-user' !== $operation ) {
		// $operation may also be 'add-existing-user'
		return;
	}

	$year = ! empty( $_POST['year_of_birth'] ) ? intval( $_POST['year_of_birth'] ) : '';
	(int) $currentYear = date( 'Y' );

	?>
	<h3><?php esc_html_e( 'Personal Information', 'funbotic' ); ?></h3>

	<table class="form-table">
		<tr>
			<th><label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'funbotic' ); ?></label> <span class="description"><?php esc_html_e( '(required)', 'funbotic' ); ?></span></th>
			<td>
				<input type="number"
			       min="1900"
			       max="<?php $currentYear; ?>"
			       step="1"
			       id="year_of_birth"
			       name="year_of_birth"
			       value="<?php echo esc_attr( $year ); ?>"
			       class="regular-text"
				/>
			</td>
		</tr>
	</table>
	<?php
}

add_action( 'user_profile_update_errors', 'funbotic_user_profile_update_errors', 10, 3 );

function funbotic_user_profile_update_errors( $errors, $update, $user ) {
	if ( $update ) {
		return;
	}

	if ( empty( $_POST['year_of_birth'] ) ) {
		$errors->add( 'year_of_birth_error', __( '<strong>ERROR</strong>: Please enter your year of birth.', 'funbotic' ) );
	}

	if ( ! empty( $_POST['year_of_birth'] ) && intval( $_POST['year_of_birth'] ) < 1900 ) {
		$errors->add( 'year_of_birth_error', __( '<strong>ERROR</strong>: You must be born after 1900.', 'funbotic' ) );
	}
}

add_action( 'edit_user_created_user', 'funbotic_user_register' );

add_action( 'show_user_profile', 'funbotic_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'funbotic_show_extra_profile_fields' );

function funbotic_show_extra_profile_fields( $user ) {
	$year = get_the_author_meta( 'year_of_birth', $user->ID );
	(int) $currentYear = date( 'Y' );
	?>
	<h3><?php esc_html_e( 'Personal Information', 'funbotic' ); ?></h3>

	<table class="form-table">
		<tr>
			<th><label for="year_of_birth"><?php esc_html_e( 'Year of birth', 'funbotic' ); ?></label></th>
			<td>
				<input type="number"
			       min="1900"
			       max="<?php $currentYear; ?>"
			       step="1"
			       id="year_of_birth"
			       name="year_of_birth"
			       value="<?php echo esc_attr( $year ); ?>"
			       class="regular-text"
				/>
			</td>
		</tr>
	</table>
	<?php
}