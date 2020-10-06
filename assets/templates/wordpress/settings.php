<!-- assets/templates/wordpress/settings.php -->
<div class="wrap" id="civiwpmailsync_admin_wrapper">

	<h1><?php esc_html_e( 'CiviCRM WordPress Mail Sync', 'civicrm-wp-mail-sync' ); ?></a></h1>

	<?php if ( ! empty( $messages ) ) : ?>
		<?php echo $messages; ?>
	<?php endif; ?>

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

		<h3><?php esc_html_e( 'Developer Utilities', 'civicrm-wp-mail-sync' ); ?></h3>

		<p><?php esc_html_e( 'Please note that this plugin is in the early stages of development and not intended for use in production.', 'civicrm-wp-mail-sync' ); ?></p>

		<table class="form-table">

			<tr>
				<th scope="row"><?php esc_html_e( 'Sync Mailings to WordPress', 'civicrm-wp-mail-sync' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civiwpmailsync_sync" id="civiwpmailsync_sync" value="1" />
					<label class="civiwpmailsync_settings_label" for="civiwpmailsync_sync"><?php esc_html_e( 'Check this to sync existing CiviCRM Mailings to WordPress.', 'civicrm-wp-mail-sync' ); ?></label>
					<p class="description"><?php esc_html_e( 'WARNING: this will probably only work when there are a small number of mailings.', 'civicrm-wp-mail-sync' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Delete Mailings from WordPress', 'civicrm-wp-mail-sync' ); ?></th>
				<td>
					<input type="checkbox" class="settings-checkbox" name="civiwpmailsync_clear" id="civiwpmailsync_clear" value="1" />
					<label class="civiwpmailsync_settings_label" for="civiwpmailsync_clear"><?php esc_html_e( 'Check this to clear synced Mailings from WordPress.', 'civicrm-wp-mail-sync' ); ?></label>
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
			<input class="button-primary" type="submit" id="civiwpmailsync_settings_submit" name="civiwpmailsync_settings_submit" value="<?php esc_attr_e( 'Save Changes', 'civicrm-wp-mail-sync' ); ?>" />
		</p>

	</form>

</div><!-- /.wrap -->



