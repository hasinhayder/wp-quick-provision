<?php
/*
Plugin Name: WP Quick Configurator
Plugin URI:
Description:
Version: 1.0
Author: Hasin Hayder
Author URI: https://hasin.me
License: GPLv2 or later
Text Domain: wp-quick-configurator
Domain Path: /languages/
*/

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wp-quick-configurator' );
} );

add_action( 'admin_menu', function () {
	add_menu_page( 'WP Quick Configurator', 'WP Quick Config', 'manage_options', 'wpqc', function () {

		include_once( ABSPATH . 'wp-admin/includes/theme.php' );
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		include_once( ABSPATH . 'wp-admin/includes/file.php' );
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		include_once( ABSPATH . 'wp-admin/includes/misc.php' );

		/*$wpqc_data = [
			'themes'  => [ 'astra', 'amity', 'solid-construction-classic' ],
			'plugins' => [ 'contact-form-7', 'woocommerce', 'wordfence', 'autoptimize', ]
		];
		echo "<pre>";
		print_r( wp_json_encode( $wpqc_data, JSON_PRETTY_PRINT ) );

		echo "</pre>"*/
		?>
        <h2><?php _e( 'WordPress Quick Configurator', 'wp-quick-configurator' ); ?></h2>
        <form method="POST">

            <label for="gist"><strong><?php _e( 'Your GIST Configuration URL', 'wp-quick-configurator' ); ?></strong></label><br/>
            <textarea style="font-size: 15px;margin-top:20px;" name="gist" id="gist" cols="70"
                      rows="5">https://gist.githubusercontent.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306/</textarea><br/>
            <!--            <label for="themes">Install Themes</label><br/>-->
            <!--            <textarea name="themes" id="themes" cols="70"-->
            <!--                      rows="5">solid-construction-classic, astra, amity</textarea><br/>-->
            <!---->
            <!--            <label for="plugins">Install Plugins</label><br/>-->
            <!--            <textarea name="plugins" id="plugins" cols="70"-->
            <!--                      rows="5">contact-form-7, woocommerce, wordfence, autoptimize</textarea><br/>-->

			<?php echo submit_button( __( 'Show Me The Magic', 'wp-quick-configurator' ) ); ?>
        </form>
		<?php

		if ( isset( $_POST['submit'] ) ) {
			$wpqc_theme_installer  = new Theme_Upgrader();
			$wpqc_plugin_installer = new Plugin_Upgrader();

			/*$wpqc__themes  = sanitize_text_field( $wpqc__POST['themes'] );
			$wpqc__plugins = sanitize_text_field( $wpqc__POST['plugins'] );
			$wpqc_themes   = explode( ',', $wpqc__themes );
			$wpqc_plugins  = explode( ',', $wpqc__plugins );*/

			$wpqc_gist_url        = trailingslashit( sanitize_text_field( $_POST['gist'] ) ) . "raw";
			$wpqc_gist_mixed_data = wp_remote_get( $wpqc_gist_url );
			$wpqc_gist_body       = json_decode( strtolower( $wpqc_gist_mixed_data['body'] ), true );
			$wpqc_themes          = $wpqc_gist_body['themes'];
			$wpqc_plugins         = $wpqc_gist_body['plugins'];

			$wpqc_installed_themes  = wp_get_themes();
			$wpqc_installed_plugins = wpqc_process_keys( array_keys( get_plugins() ) );

			/*echo "<pre>";
			//print_r($wpqc_installed_themes);
			echo "</pre>";
			echo "<pre>";
			//print_r($wpqc_installed_plugins);
			echo "</pre>";*/

			echo '<h2>' . __( 'Installing Themes', 'wp-quick-configurator' ) . '</h2>';
			foreach ( $wpqc_themes as $wpqc_theme ) {
				$wpqc__theme = strtolower( trim( $wpqc_theme ) );
				if ( ! array_key_exists( $wpqc__theme, $wpqc_installed_themes ) ) {
					?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php printf( __( "<strong>Installing Theme %s</strong>", 'wp-quick-configurator' ), $wpqc__theme ); ?></p>
                        <p>
							<?php
							$wpqc_theme_installer->install( 'https://downloads.wordpress.org/theme/' . $wpqc__theme . '.latest-stable.zip' );
							?>
                        </p>
                    </div>
					<?php

				} else {
					?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php printf( __( "Theme <strong>%s</strong> is already installed", 'wp-quick-configurator' ), $wpqc__theme ); ?></p>
                    </div>
					<?php
				}
			}

			do_action( "wpqc_themes_installed" );

			switch_theme( 'astra' );

			echo '<h2>' . __( 'Installing Plugins', 'wp-quick-configurator' ) . '</h2>';
			foreach ( $wpqc_plugins as $wpqc_plugin ) {
				$wpqc__plugin = strtolower( trim( $wpqc_plugin ) );
				if ( ! array_key_exists( $wpqc__plugin, $wpqc_installed_plugins ) ) {
					?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php printf( __( "<strong>Installing Plugin %s</strong>", 'wp-quick-configurator' ), $wpqc__plugin ); ?></p>
                        <p>
							<?php
							$wpqc_plugin_installer->install( 'https://downloads.wordpress.org/plugin/' . $wpqc__plugin . '.latest-stable.zip' );
							?>
                        </p>
                    </div>
					<?php

				} else {
					?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php printf( __( 'Plugin <strong>%s</strong> is already installed', 'wp-quick-configurator' ), $wpqc__plugin ); ?></p>
                    </div>
					<?php
				}
			}

			do_action( "wpqc_plugins_installed" );

			$wpqc_installed_plugins = process_keys( array_keys( get_plugins() ) );
			echo '<h2>' . __( 'Activating Plugins', 'wp-quick-configurator' ) . '</h2>';
			foreach ( $wpqc_plugins as $wpqc_plugin ) {
				$wpqc__plugin = strtolower( trim( $wpqc_plugin ) );
				activate_plugin( $wpqc_installed_plugins[ $wpqc__plugin ] );
				?>
                <div class="notice notice-success is-dismissible">
                    <p><?php printf( __( "</strong>%s</strong> is Activated <br/>", 'wp-quick-configurator' ), $wpqc__plugin ); ?></p>
                </div>
				<?php
			}

			do_action( "wpqc_plugins_activated" );


			//do_action( "wpqc_options_updated" );
		}

	}, '', 5 );


} );

function wpqc_process_keys( $wpqc_keys ) {
	$wpqc__keys = [];
	foreach ( $wpqc_keys as $wpqc_key ) {
		$wpqc__key                  = explode( DIRECTORY_SEPARATOR, $wpqc_key );
		$wpqc_keys[ $wpqc__key[0] ] = $wpqc_key;
	}

	return $wpqc__keys;
}

