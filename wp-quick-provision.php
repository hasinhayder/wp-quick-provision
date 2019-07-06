<?php
/*
Plugin Name: WP Quick Configurator
Plugin URI:
Description:
Version: 1.0
Author: Hasin Hayder
Author URI: https://hasin.me
License: GPLv2 or later
Text Domain: wp-quick-provision
Domain Path: /languages/
*/

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wp-quick-provision' );
} );

add_action( 'admin_menu', function () {
	add_menu_page( 'WP Quick Provision', 'Quick Provision', 'manage_options', 'wpqp', function () {

		include_once( ABSPATH . 'wp-admin/includes/theme.php' );
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		include_once( ABSPATH . 'wp-admin/includes/file.php' );
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		include_once( ABSPATH . 'wp-admin/includes/misc.php' );

		/*$wpqp_data = [
			'themes'  => [ 'astra', 'amity', 'solid-construction-classic' ],
			'plugins' => [ 'contact-form-7', 'woocommerce', 'wordfence', 'autoptimize', ]
		];
		echo "<pre>";
		print_r( wp_json_encode( $wpqp_data, JSON_PRETTY_PRINT ) );

		echo "</pre>"*/
		?>
        <h2><?php _e( 'WordPress Quick Configurator', 'wp-quick-provision' ); ?></h2>
        <form method="POST">

            <label for="gist"><strong><?php _e( 'Your GIST Configuration URL', 'wp-quick-provision' ); ?></strong></label><br/>
            <textarea style="font-size: 15px;margin-top:20px;" name="gist" id="gist" cols="70"
                      rows="5">https://gist.githubusercontent.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306/</textarea><br/>
            <!--            <label for="themes">Install Themes</label><br/>-->
            <!--            <textarea name="themes" id="themes" cols="70"-->
            <!--                      rows="5">solid-construction-classic, astra, amity</textarea><br/>-->
            <!---->
            <!--            <label for="plugins">Install Plugins</label><br/>-->
            <!--            <textarea name="plugins" id="plugins" cols="70"-->
            <!--                      rows="5">contact-form-7, woocommerce, wordfence, autoptimize</textarea><br/>-->

			<?php echo submit_button( __( 'Show Me The Magic', 'wp-quick-provision' ) ); ?>
        </form>
		<?php

		if ( isset( $_POST['submit'] ) ) {
			$wpqp_theme_installer  = new Theme_Upgrader();
			$wpqp_plugin_installer = new Plugin_Upgrader();

			/*$wpqp__themes  = sanitize_text_field( $wpqp__POST['themes'] );
			$wpqp__plugins = sanitize_text_field( $wpqp__POST['plugins'] );
			$wpqp_themes   = explode( ',', $wpqp__themes );
			$wpqp_plugins  = explode( ',', $wpqp__plugins );*/

			$wpqp_gist_url        = trailingslashit( sanitize_text_field( $_POST['gist'] ) ) . "raw";
			$wpqp_gist_mixed_data = wp_remote_get( $wpqp_gist_url );
			$wpqp_gist_body       = json_decode( strtolower( $wpqp_gist_mixed_data['body'] ), true );
			$wpqp_themes          = apply_filters( 'wpqp_themes', $wpqp_gist_body['themes'] );
			$wpqp_plugins         = apply_filters( 'wpqp_plugins', $wpqp_gist_body['plugins'] );
			$wpqp_options         = apply_filters( 'wpqp_options', $wpqp_gist_body['options'] );

			$wpqp_installed_themes  = wp_get_themes();
			$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );

			/*echo "<pre>";
			//print_r($wpqp_installed_themes);
			echo "</pre>";
			echo "<pre>";
			//print_r($wpqp_installed_plugins);
			echo "</pre>";*/

			echo '<h2>' . __( 'Installing Themes', 'wp-quick-provision' ) . '</h2>';
			foreach ( $wpqp_themes as $wpqp_theme ) {
				$wpqp__theme = strtolower( trim( $wpqp_theme ) );
				if ( ! array_key_exists( $wpqp__theme, $wpqp_installed_themes ) ) {
					?>
                    <div class="notice notice-success">
                        <p><?php printf( __( "<strong>Installing Theme %s</strong>", 'wp-quick-provision' ), $wpqp__theme ); ?></p>
                        <p>
							<?php
							$wpqp_theme_installer->install( 'https://downloads.wordpress.org/theme/' . $wpqp__theme . '.latest-stable.zip' );
							?>
                        </p>
                    </div>
					<?php

				} else {
					?>
                    <div class="notice notice-error">
                        <p><?php printf( __( "Theme <strong>%s</strong> is already installed", 'wp-quick-provision' ), $wpqp__theme ); ?></p>
                    </div>
					<?php
				}
			}

			do_action( "wpqp_themes_installed" );

			echo '<h2>' . __( 'Installing Plugins', 'wp-quick-provision' ) . '</h2>';
			foreach ( $wpqp_plugins as $wpqp_plugin ) {
				$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );
				if ( ! array_key_exists( $wpqp__plugin, $wpqp_installed_plugins ) ) {
					?>
                    <div class="notice notice-success">
                        <p><?php printf( __( "<strong>Installing Plugin %s</strong>", 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                        <p>
							<?php
							$wpqp_plugin_installer->install( 'https://downloads.wordpress.org/plugin/' . $wpqp__plugin . '.latest-stable.zip' );
							?>
                        </p>
                    </div>
					<?php

				} else {
					?>
                    <div class="notice notice-error">
                        <p><?php printf( __( 'Plugin <strong>%s</strong> is already installed', 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                    </div>
					<?php
				}
			}

			do_action( "wpqp_plugins_installed" );

			$wpqp_installed_plugins = process_keys( array_keys( get_plugins() ) );
			echo '<h2>' . __( 'Activating Plugins', 'wp-quick-provision' ) . '</h2>';
			foreach ( $wpqp_plugins as $wpqp_plugin ) {
				$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );
				activate_plugin( $wpqp_installed_plugins[ $wpqp__plugin ] );
				?>
                <div class="notice notice-success">
                    <p><?php printf( __( "</strong>%s</strong> is Activated <br/>", 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                </div>
				<?php
			}

			do_action( "wpqp_plugins_activated" );

			foreach ( $wpqp_options as $_okey => $_ovalue ) {
				update_option( $_okey, $_ovalue );
			}

			do_action( "wpqp_options_updated" );
		}

	}, 'dashicons-list-view', 5 );


} );

function wpqp_process_keys( $wpqp_keys ) {
	$wpqp__keys = [];
	foreach ( $wpqp_keys as $wpqp_key ) {
		$wpqp__key                  = explode( DIRECTORY_SEPARATOR, $wpqp_key );
		$wpqp_keys[ $wpqp__key[0] ] = $wpqp_key;
	}

	return $wpqp__keys;
}

