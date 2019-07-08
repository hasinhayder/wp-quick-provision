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
                <h1><?php _e( 'Quickly Provision Your WordPress Setup', 'wp-quick-provision' ); ?></h1>

                <form method="POST" class="wpqp_form">
					<?php wp_nonce_field( 'wpqp_provision', 'wpqp_nonce' ); ?>
                    <label for="gist">
                        <strong><?php _e( 'Gist URL', 'wp-quick-provision' ); ?></strong>
                    </label><br/>
                    <input type="text" name="gist" id="gist" class="wpqp_text"
                           placeholder="<?php _e( 'Gist URL with Provision Data', 'wp-quick-provision' ); ?>"/><br/>
                    <p class="description">
						<?php _e( 'Sample Gist URL', 'wp-quick-provision' ); ?>: <a
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
                                    <p>
										<?php _e( "Invalid gist URL", 'wp-quick-provision' ); ?>
                                    </p>
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
						$wpqp_gist_url         = trailingslashit( esc_url( $_POST['gist'] ) ) . "raw";
						$wpqp_gist_mixed_data  = wp_remote_get( $wpqp_gist_url );
						if ( isset( $wpqp_gist_mixed_data['body'] ) && trim( $wpqp_gist_mixed_data['body'] ) != '' ) {
							$wpqp_gist_body         = json_decode( strtolower( $wpqp_gist_mixed_data['body'] ), true );
							$wpqp_installed_themes  = wp_get_themes();
							$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );

							if ( isset( $wpqp_gist_body['themes'] ) ) {
								$wpqp_themes = apply_filters( 'wpqp_themes', $wpqp_gist_body['themes'] );
								array_push( $wpqp_themes, "yumyumyum" );
								if ( count( $wpqp_themes ) > 0 ) {
									echo '<h2>' . __( 'Installing Themes', 'wp-quick-provision' ) . '</h2>';
									foreach ( $wpqp_themes as $wpqp_theme ) {
										$wpqp__theme = strtolower( trim( $wpqp_theme ) );
										if ( ! array_key_exists( $wpqp__theme, $wpqp_installed_themes ) ) {
											if ( wpqp_is_okay_to_install( $wpqp__theme ) ) {
												?>
                                                <div class="wpqp_info wpqp_success">
                                                    <p>
														<?php printf( __( "<strong>Installing theme %s</strong>", 'wp-quick-provision' ), esc_html( $wpqp__theme ) ); ?>
                                                    </p>
                                                    <p>
														<?php
														$wpqp_theme_installer->install( esc_url( 'https://downloads.wordpress.org/theme/' . $wpqp__theme . '.latest-stable.zip' ) );
														?>
                                                    </p>
                                                </div>
												<?php
											} else {
												?>
                                                <div class="wpqp_info wpqp_error">
                                                    <p>
														<?php printf( __( "Theme <strong>%s</strong> is not available to install", 'wp-quick-provision' ), esc_html( $wpqp__theme ) ); ?>
                                                    </p>
                                                </div>
												<?php
											}
										} else {
											?>
                                            <div class="wpqp_info wpqp_warning">
                                                <p>
													<?php printf( __( "Theme <strong>%s</strong> is already installed", 'wp-quick-provision' ), esc_html( $wpqp__theme ) ); ?>
                                                </p>
                                            </div>
											<?php
										}
									}

									do_action( "wpqp_themes_installed" );
								}
							}

							if ( isset( $wpqp_gist_body['plugins'] ) ) {

								$wpqp_plugins      = apply_filters( 'wpqp_plugins', $wpqp_gist_body['plugins'] );
								$wpqp_plugin_error = [];
								array_push( $wpqp_plugins, '24liveblog' );
								array_push( $wpqp_plugins, 'wp-spamshield' );
								array_push( $wpqp_plugins, 'hello-dolly' );
								array_push( $wpqp_plugins, 'litespeed-cache' );
								if ( count( $wpqp_plugins ) > 0 ) {
									echo '<h2>' . __( 'Installing Plugins', 'wp-quick-provision' ) . '</h2>';
									foreach ( $wpqp_plugins as $wpqp_plugin ) {
										$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );
										if ( ! array_key_exists( $wpqp__plugin, $wpqp_installed_plugins ) ) {
											if ( wpqp_is_okay_to_install( $wpqp__plugin, 'plugin' ) ) {
												?>
                                                <div class="wpqp_info wpqp_success">
                                                    <p>
														<?php printf( __( "<strong>Installing plugin %s</strong>", 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                    </p>
                                                    <p>
														<?php
														$wpqp_plugin_installer->install( esc_url( 'https://downloads.wordpress.org/plugin/' . $wpqp__plugin . '.latest-stable.zip' ) );
														?>
                                                    </p>
                                                </div>
												<?php
											} else {
												$wpqp_plugin_error[ $wpqp__plugin ] = true;
												?>
                                                <div class="wpqp_info wpqp_error">
                                                    <p>
														<?php printf( __( 'Plugin <strong>%s</strong> is not available to install', 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                    </p>
                                                </div>
												<?php
											}

										} else {
											?>
                                            <div class="wpqp_info wpqp_warning">
                                                <p>
													<?php printf( __( 'Plugin <strong>%s</strong> is already installed', 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                </p>
                                            </div>
											<?php
										}
									}

									do_action( "wpqp_plugins_installed" );

									$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );

									echo '<h2>' . __( 'Activating Plugins', 'wp-quick-provision' ) . '</h2>';
									foreach ( $wpqp_plugins as $wpqp_plugin ) {
										$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );
										if ( ! isset( $wpqp_plugin_error[ $wpqp_plugin ] ) ) {
											if ( ! is_plugin_active( $wpqp_installed_plugins[ $wpqp__plugin ] ) ) {
												activate_plugin( $wpqp_installed_plugins[ $wpqp__plugin ] );
												?>
                                                <div class="wpqp_info wpqp_success">
                                                    <p>
														<?php printf( __( "Plugin <strong>%s</strong> is activated <br/>", 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                    </p>
                                                </div>
												<?php
											} else {
												?>
                                                <div class="wpqp_info wpqp_warning">
                                                    <p>
														<?php printf( __( "Plugin <strong>%s</strong> is already active<br/>", 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                    </p>
                                                </div>
												<?php
											}
										}

									}

									do_action( "wpqp_plugins_activated" );
								}
							}

							if ( isset( $wpqp_gist_body['options'] ) ) {
								$wpqp_options = apply_filters( 'wpqp_options', $wpqp_gist_body['options'] );
								if ( count( $wpqp_options ) > 0 ) {
									echo '<h2>' . __( 'Updating Options', 'wp-quick-provision' ) . '</h2>';

									foreach ( $wpqp_options as $_okey => $_ovalue ) {
										update_option( sanitize_text_field( $_okey ), sanitize_text_field( $_ovalue ) );
									}

									do_action( "wpqp_options_updated" );
								}
							}
						}
					}
				}
				?>
            </div>
			<?php

		} );


} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	//add a new action link as 'provision now' under the plugin name
	array_unshift( $links, sprintf( "<a href='%s'><strong style='color: #ff631d; display: inline;'>%s</strong></a>", admin_url( 'tools.php?page=wpqp' ), __( 'Provision Now', 'wp-quick-provision' ) ) );

	return $links;
} );

add_action( 'activated_plugin', function ( $plugin ) {
	//redirect the user to the quick provision page after activation
	if ( plugin_basename( __FILE__ ) == $plugin ) {
		exit( wp_redirect( admin_url( 'tools.php?page=wpqp' ) ) );
	}
} );

add_filter( 'plugin_row_meta', function ( $links, $file ) {
	//add two links under the description section
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


function wpqp_is_okay_to_install( $wpqp_slug, $wpqp_type = 'theme' ) {
	//check if the theme or plugin is in closed state in WordPress.org repository
	if ( 'theme' == $wpqp_type ) {
		$wpqp_api_url = "https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=" . sanitize_text_field( $wpqp_slug );
	} else if ( 'plugin' == $wpqp_type ) {
		$wpqp_api_url = "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=" . sanitize_text_field( $wpqp_slug );
	}
	$wpqp_request = wp_remote_get( $wpqp_api_url );
	$wpqp_body    = json_decode( $wpqp_request['body'], true );
	if ( isset( $wpqp_body['error'] ) ) {
		return false;
	}

	return true;
}
