<?php
/*
Plugin Name: WP Quick Provision
Plugin URI: https://github.com/hasinhayder/wp-quick-provision
Description: This is a powerful provisioning plugin to install multiple themes and plugins automatically by providing them as a list from <a href='https://gist.github.com'>https://gist.github.com</a>. You can also update multiple options in your options table at once. This plugin can save your time from installing same set of themes and plugins again and again in your WordPress setup. Extremely handy to quickly setup your development platform.
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

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( "tools_page_wpqp" == $hook ) {
		wp_enqueue_style( 'wpqp-style', plugin_dir_url( __FILE__ ) . "assets/css/wpqp.css" );
	}
} );

add_action( 'admin_menu', function () {
	add_submenu_page( 'tools.php',
		__( 'WP Quick Provision', 'wp-quick-provision' ),
		__( 'Quick Provision', 'wp-quick-provision' ),
        'manage_options',
		'wpqp',
		function () {
			$wpqp_proceed = true;
			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

			?>
            <div class="wrap">
                <h1><?php _e( 'WordPress Quick Provision', 'wp-quick-provision' ); ?></h1>

                <form method="POST" style="margin-top: 20px;">
					<?php wp_nonce_field( 'wpqp_provision', 'wpqp_nonce' ); ?>
                    <label for="gist"><strong><?php _e( 'Gist URL', 'wp-quick-provision' ); ?></strong></label><br/>
                    <input type="text" style="width:60%;font-size: 15px;margin-top:10px; line-height: 30px;"
                           name="gist"
                           id="gist"
                           placeholder="<?php _e( 'Gist URL with Provision Data', 'wp-quick-provision' ); ?>"/><br/>
                    <p class="description">Sample Gist URL: <a
                                href="https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306"
                                target="_blank">https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306</a>
                    </p>
					<?php
					if ( isset( $_POST['submit'] ) ) {
						if ( wp_verify_nonce( sanitize_key( $_POST['wpqp_nonce'] ), 'wpqp_provision' ) ) {
							$wpqp_gist_url = strtolower( sanitize_text_field( $_POST['gist'] ) );
							if ( strpos( $wpqp_gist_url, "gist" ) === false ) {
								$wpqp_proceed = false;
								?>
                                <div class="wpqp_info wpqp_error" style="margin-left: 1px;">
                                    <p><?php _e( "Invalid gist URL", 'wp-quick-provision' ); ?></p>
                                </div>
								<?php
							}
						}
					}
					?>
					<?php echo submit_button( __( 'Start Provisioning', 'wp-quick-provision' ) ); ?>
                </form>
				<?php

				if ( isset( $_POST['submit'] ) && $wpqp_proceed ) {
					if ( wp_verify_nonce( sanitize_key( $_POST['wpqp_nonce'] ), 'wpqp_provision' ) ) {
						$wpqp_theme_installer  = new Theme_Upgrader();
						$wpqp_plugin_installer = new Plugin_Upgrader();
						$wpqp_gist_url        = trailingslashit( sanitize_text_field( $_POST['gist'] ) ) . "raw";
						$wpqp_gist_mixed_data = wp_remote_get( $wpqp_gist_url );
						$wpqp_gist_body       = json_decode( strtolower( $wpqp_gist_mixed_data['body'] ), true );
						$wpqp_installed_themes  = wp_get_themes();
						$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );
						$wpqp_themes          = apply_filters( 'wpqp_themes', $wpqp_gist_body['themes'] );
						$wpqp_plugins         = apply_filters( 'wpqp_plugins', $wpqp_gist_body['plugins'] );
						$wpqp_options         = apply_filters( 'wpqp_options', $wpqp_gist_body['options'] );

						if ( count( $wpqp_themes ) > 0 ) {
							echo '<h2>' . __( 'Installing Themes', 'wp-quick-provision' ) . '</h2>';
							foreach ( $wpqp_themes as $wpqp_theme ) {
								$wpqp__theme = strtolower( trim( $wpqp_theme ) );
								if ( ! array_key_exists( $wpqp__theme, $wpqp_installed_themes ) ) {
									?>
                                    <div class="wpqp_info wpqp_success" style="margin-left: 1px;">
                                        <p><?php printf( __( "<strong>Installing theme %s</strong>", 'wp-quick-provision' ), $wpqp__theme ); ?></p>
                                        <p>
											<?php
											$wpqp_theme_installer->install( 'https://downloads.wordpress.org/theme/' . $wpqp__theme . '.latest-stable.zip' );
											?>
                                        </p>
                                    </div>
									<?php

								} else {
									?>
                                    <div class="wpqp_info wpqp_error" style="margin-left: 1px;">
                                        <p><?php printf( __( "Theme <strong>%s</strong> is already installed", 'wp-quick-provision' ), $wpqp__theme ); ?></p>
                                    </div>
									<?php
								}
							}

							do_action( "wpqp_themes_installed" );
						}

						if ( count( $wpqp_plugins ) > 0 ) {
							echo '<h2>' . __( 'Installing Plugins', 'wp-quick-provision' ) . '</h2>';
							foreach ( $wpqp_plugins as $wpqp_plugin ) {
								$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );
								if ( ! array_key_exists( $wpqp__plugin, $wpqp_installed_plugins ) ) {
									?>
                                    <div class="wpqp_info wpqp_success" style="margin-left: 1px;">
                                        <p><?php printf( __( "<strong>Installing plugin %s</strong>", 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                                        <p>
											<?php
											$wpqp_plugin_installer->install( 'https://downloads.wordpress.org/plugin/' . $wpqp__plugin . '.latest-stable.zip' );
											?>
                                        </p>
                                    </div>
									<?php

								} else {
									?>
                                    <div class="wpqp_info wpqp_error" style="margin-left: 1px;">
                                        <p><?php printf( __( 'Plugin <strong>%s</strong> is already installed', 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                                    </div>
									<?php
								}
							}

							do_action( "wpqp_plugins_installed" );

							$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );

							echo '<h2>' . __( 'Activating Plugins', 'wp-quick-provision' ) . '</h2>';
							foreach ( $wpqp_plugins as $wpqp_plugin ) {
								$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );
								if ( ! is_plugin_active( $wpqp_installed_plugins[ $wpqp__plugin ] ) ) {
									activate_plugin( $wpqp_installed_plugins[ $wpqp__plugin ] );
									?>
                                    <div class="wpqp_info wpqp_success" style="margin-left: 1px;">
                                        <p><?php printf( __( "Plugin <strong>%s</strong> is activated <br/>", 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                                    </div>
									<?php
								} else {
									?>
                                    <div class="wpqp_info wpqp_error" style="margin-left: 1px;">
                                        <p><?php printf( __( "Plugin <strong>%s</strong> is already active<br/>", 'wp-quick-provision' ), $wpqp__plugin ); ?></p>
                                    </div>
									<?php
								}

							}

							do_action( "wpqp_plugins_activated" );
						}

						if ( count( $wpqp_options ) > 0 ) {
							echo '<h2>' . __( 'Updating Options', 'wp-quick-provision' ) . '</h2>';

							foreach ( $wpqp_options as $_okey => $_ovalue ) {
								update_option( $_okey, $_ovalue );
							}

							do_action( "wpqp_options_updated" );
						}
					}
				}
				?>
            </div>
			<?php

		} );


} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, sprintf( "<a href='%s'><strong style='color: #ff631d; display: inline;'>%s</strong></a>", admin_url( 'tools.php?page=wpqp' ), __( 'Provision Now', 'wp-quick-provision' ) ) );

	return $links;
} );

add_action( 'activated_plugin', function ( $plugin ) {
	if ( plugin_basename( __FILE__ ) == $plugin ) {
		exit( wp_redirect( admin_url( 'tools.php?page=wpqp' ) ) );
	}
} );

add_filter( 'plugin_row_meta', function ( $links, $file ) {
	if ( plugin_basename( __FILE__ ) == $file ) {
		array_push( $links, sprintf( "<a href='%s' target='_blank'>%s</a>", esc_url( 'https://github.com/hasinhayder/wp-quick-provision' ), __( 'Fork on Github', 'wp-quick-provision' ) ) );
		array_push( $links, sprintf( "<a href='%s' target='_blank'>%s</a>", esc_url( 'https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306' ), __( 'Sample data', 'wp-quick-provision' ) ) );
	}

	return $links;
}, 10, 2 );

function wpqp_process_keys( $wpqp_keys ) {
	$wpqp__keys = [];
	foreach ( $wpqp_keys as $wpqp_key ) {
		$wpqp__key                   = explode( DIRECTORY_SEPARATOR, $wpqp_key );
		$wpqp__keys[ $wpqp__key[0] ] = $wpqp_key;
	}

	return $wpqp__keys;
}


