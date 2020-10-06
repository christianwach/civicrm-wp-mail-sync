<!-- assets/templates/wordpress/settings.php -->
<div class="wrap" id="civiwpmailsync_admin_wrapper">

	<h1><?php _e( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ); ?></a></h1>

	<?php

	// If we've got any messages, show them.
	if ( isset( $messages ) AND ! empty( $messages ) ) echo $messages;

	?>

	<form method="post" id="civiwpmailsync_settings_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'civiwpmailsync_settings_action', 'civiwpmailsync_settings_nonce' ); ?>

		<?php

		/**
		 * Before Settings.
		 *
		 * @since 0.2
		 */
		do_action( 'civiwpmailsync_before_settings' );

		?>

		<h3><?php _e( 'Sync Existing Mailings to WordPress', 'civicrm-wp-mail-sync' ); ?></h3>

		<p><?php _e( 'WARNING: this will probably only work when there are a reasonably small number of mailings. If you have lots of mailings, it would be better to write some kind of chunked update routine yourself.', 'civicrm-wp-mail-sync' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><?php _e( 'Sync to WordPress', 'civicrm-wp-mail-sync' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civiwpmailsync_sync" id="civiwpmailsync_sync" value="1" />
					<label class="civiwpmailsync_settings_label" for="civiwpmailsync_sync"><?php _e( 'Check this to sync existing mailings to WordPress.', 'civicrm-wp-mail-sync' ); ?></label>
				</td>
			</tr>

		</table>

		<hr>

		<?php

		/**
		 * After Settings.
		 *
		 * @since 0.2
		 */
		do_action( 'civiwpmailsync_after_settings' );

		?>

		<p class="submit">
			<input class="button-primary" type="submit" id="civiwpmailsync_settings_submit" name="civiwpmailsync_settings_submit" value="<?php _e( 'Save Changes', 'civicrm-event-organiser' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->



