<?php
/*
Plugin Name: WP Quick Provision
Plugin URI: https://provisionwp.com
Description: This is a powerful provisioning plugin to install multiple themes and plugins automatically by providing them as a list from <a href='https://gist.github.com'>https://gist.github.com</a>. You can also update multiple options in your options table at once. This plugin can save your time from installing same set of themes and plugins again and again in your WordPress setup. Extremely handy to quickly setup your development platform.
Version: 2.0.1
Author: Hasin Hayder
Author URI: https://provisionwp.com
License: GPLv2 or later
Text Domain: wp-quick-provision
Domain Path: /languages/

@package wp_quick_provision
*/

require_once "wpqp-functions.php";
require_once "class.wpqp-table.php";

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'wp-quick-provision' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( "tools_page_wpqp" == $hook ) {
		wp_enqueue_style( 'wpqp-style', plugin_dir_url( __FILE__ ) . "assets/css/wpqp.css");
	}
} );

add_action( 'admin_menu', function () {
	add_submenu_page( 'tools.php',
		__( 'WP Quick Provision', 'wp-quick-provision' ),
		__( 'Quick Provision', 'wp-quick-provision' ),
		'manage_options',
		'wpqp',
		function () {

			//check if the URL is valid
			if ( isset( $_POST['submit'] ) && ( trim( $_POST['gist'] ) == '' || ! wpqp_validate_provision_source( wpqp_process_provision_source_url( $_POST['gist'] )  ))) {
				wp_redirect( admin_url( 'tools.php?page=wpqp' ) );
				die();
			}

			$wpqp_proceed = true;
			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			?>
            <div class="wrap wpqp">
                <h1><?php _e( 'Quickly Provision Your WordPress Setup', 'wp-quick-provision' ); ?></h1>
                <form method="POST" class="wpqp_form <?php if ( isset( $_POST['proceed'] ) ) {
					echo 'wpqp_hide';
				} ?>">
                    <div class="wpqp_box">
                        <div class="wpqp_box_header">
							<?php _e( "Provision Data" ) ?>
                        </div>
                        <div class="wpqp_box_content">
							<?php wp_nonce_field( 'wpqp_provision', 'wpqp_nonce' ); ?>
							<?php

							if ( ! isset( $_POST['submit'] ) ) {
								?>
                                <label for="gist">
                                    <strong><?php _e( 'Gist URL', 'wp-quick-provision' ); ?></strong>
                                </label><br/>
                                <input type="url" name="gist" id="gist" class="wpqp_text" required
                                       placeholder="<?php _e( 'Gist URL with Provision Data', 'wp-quick-provision' ); ?>"/>
                                <p class="description">
									<?php _e( 'Sample Data URL', 'wp-quick-provision' ); ?>: <a
                                            href="https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306"
                                            target="_blank">https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306</a>
                                </p>
								<?php
							}

							if ( isset( $_POST['submit'] ) ) {

								if ( wp_verify_nonce( sanitize_key( $_POST['wpqp_nonce'] ), 'wpqp_provision' ) ) {
									?>
                                    <input type="hidden" name="gist"
                                           value="<?php echo esc_url( $_POST['gist'] ); ?>"/>
                                    <input type="hidden" name="proceed" value="hellyeah"/>
									<?php
									$wpqp_provision_source_url = wpqp_process_provision_source_url( $_POST['gist'] );
									$wpqp_gist_mixed_data      = wp_remote_get( $wpqp_provision_source_url );
									$wpqp_gist_body            = json_decode( strtolower( $wpqp_gist_mixed_data['body'] ), true );

									if ( ! wpqp_validate_provision_source( $wpqp_provision_source_url ) ) {
										$wpqp_proceed = false;
										?>
                                        <div class="wpqp_info wpqp_error">
                                            <p>
												<?php _e( "Invalid Data URL", 'wp-quick-provision' ); ?>
                                            </p>
                                        </div>
										<?php
									}

									if ( ! isset( $_POST['proceed'] ) ) {

										if ( isset( $wpqp_gist_body['themes'] ) ) {
											$_wpqp_themes = apply_filters( 'wpqp_themes', $wpqp_gist_body['themes'] );
											$wpqp_themes  = wpqp_process_data( $_wpqp_themes );

											_e( '<h2>Installing the following themes</h2>', 'wp-quick-provision' );
											echo '<p class="info">' . __( 'Following is a list of themes we found from your provision data url. It contains items from WordPress.org theme repository as well as externally hosted items. If you are not sure to install any of these items, simply uncheck them and they will not be installed. Just for your reference, the provision data url was ', 'wp-quick-provision' ) . sprintf( '<a href="%1$s" target="_blank">%1$s</a>', esc_url( $_POST['gist'] ) ) . '</p>';
											$wpqp_themes_table = new WPQP_Table( $wpqp_themes, 'themes' );
											$wpqp_themes_table->prepare_items();
											$wpqp_themes_table->display();
										}

										if ( isset( $wpqp_gist_body['plugins'] ) ) {
											$_wpqp_plugins = apply_filters( 'wpqp_plugins', $wpqp_gist_body['plugins'] );
											$wpqp_plugins  = wpqp_process_data( $_wpqp_plugins );

											_e( '<h2>Installing the following plugins</h2>', 'wp-quick-provision' );
											echo '<p class="info">' . __( 'Following is a list of plugins we found from your provision data url. It contains items from WordPress.org plugin repository as well as externally hosted items. If you are not sure to install any of these items, simply uncheck them and they will not be installed. Just for your reference, the provision data url was ', 'wp-quick-provision' ) . sprintf( '<a href="%1$s" target="_blank">%1$s</a>', esc_url( $_POST['gist'] ) ) . '</p>';
											$wpqp_plugins_table = new WPQP_Table( $wpqp_plugins, 'plugins' );
											$wpqp_plugins_table->prepare_items();
											$wpqp_plugins_table->display();
										}

										$wpqp_proceed = false;
									}
								}
							}

							?>
                            <p>
								<?php
								if ( ! isset( $_POST['submit'] ) ) {
									echo submit_button( __( 'Process Provisioning Data', 'wp-quick-provision' ), 'primary wpqp_large_button', 'submit', false );
								} else {
									if ( ! isset( $_POST['proceed'] ) ) {
										echo submit_button( __( 'Start Provisioning', 'wp-quick-provision' ), 'primary wpqp_large_button', 'submit', false );
										?>
                                        <a href="<?php echo admin_url( 'tools.php?page=wpqp' ); ?>"
                                           class="button button-action wpqp_large_button"><?php _e( 'Cancel Provisioning', 'wpqp_provision' ) ?></a>
										<?php
									}
								}
								?>
                            </p>
                        </div>
                    </div>
                </form>
				<?php
				if ( isset( $_POST['proceed'] ) ) {
					?>
                    <p>
                        <a href="<?php echo admin_url( 'tools.php?page=wpqp' ); ?>"
                           class="button button-primary wpqp_large_button "><?php _e( 'Start Again', 'wpqp_provision' ) ?></a>
                    </p>
					<?php
				}


				if ( isset( $_POST['submit'] ) && $wpqp_proceed ) {

					if ( wp_verify_nonce( sanitize_key( $_POST['wpqp_nonce'] ), 'wpqp_provision' ) ) {
						$wpqp_theme_installer  = new Theme_Upgrader();
						$wpqp_plugin_installer = new Plugin_Upgrader();
						$wpqp_gist_mixed_data  = wp_remote_get( $wpqp_provision_source_url );

						if ( isset( $wpqp_gist_mixed_data['body'] ) && trim( $wpqp_gist_mixed_data['body'] ) != '' ) {
							$wpqp_gist_body         = json_decode( strtolower( $wpqp_gist_mixed_data['body'] ), true );
							$wpqp_installed_themes  = wp_get_themes();
							$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );

							if ( isset( $wpqp_gist_body['themes'] ) && isset( $_POST['wpqp_themes'] ) ) {
								$_wpqp_themes = apply_filters( 'wpqp_themes', $wpqp_gist_body['themes'] );
								$wpqp_themes  = wpqp_process_data( $_wpqp_themes );

								if ( count( $wpqp_themes ) > 0 ) {
									echo '<h2>' . __( 'Installing Themes', 'wp-quick-provision' ) . '</h2>';

									foreach ( $wpqp_themes as $wpqp_theme => $wpqp_theme_data ) {
										$wpqp__theme = strtolower( trim( $wpqp_theme ) );

										if ( in_array( $wpqp__theme, $_POST['wpqp_themes'] ) ) {

											if ( ! array_key_exists( $wpqp__theme, $wpqp_installed_themes ) ) {

												if ( wpqp_is_okay_to_install( $wpqp_theme_data, 'theme' ) ) {
													?>
                                                    <div class="wpqp_info wpqp_success">
                                                        <p>
															<?php printf( __( "<strong>Installing theme %s</strong>", 'wp-quick-provision' ), esc_html( $wpqp__theme ) ); ?>
                                                        </p>
                                                        <p>
															<?php
															$wpqp_theme_installer->install( wpqp_get_item_url( $wpqp_theme_data ) );
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
									}

									do_action( "wpqp_themes_installed" );
								}
							}

							if ( isset( $wpqp_gist_body['plugins'] ) && isset( $_POST['wpqp_plugins'] ) ) {
								$_wpqp_plugins     = apply_filters( 'wpqp_plugins', $wpqp_gist_body['plugins'] );
								$wpqp_plugins      = wpqp_process_data( $_wpqp_plugins, 'plugin' );
								$wpqp_plugin_error = [];

								if ( count( $wpqp_plugins ) > 0 ) {
									echo '<h2>' . __( 'Installing Plugins', 'wp-quick-provision' ) . '</h2>';

									foreach ( $wpqp_plugins as $wpqp_plugin => $wpqp_plugin_data ) {
										$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );

										if ( in_array( $wpqp__plugin, $_POST['wpqp_plugins'] ) ) {

											if ( ! array_key_exists( $wpqp__plugin, $wpqp_installed_plugins ) ) {

												if ( wpqp_is_okay_to_install( $wpqp_plugin_data, 'plugin' ) ) {
													?>
                                                    <div class="wpqp_info wpqp_success">
                                                        <p>
															<?php printf( __( "<strong>Installing plugin %s</strong>", 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                        </p>
                                                        <p>
															<?php
															$wpqp_plugin_installer->install( wpqp_get_item_url( $wpqp_plugin_data, 'plugin' ) );
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
									}

									do_action( "wpqp_plugins_installed" );

									echo '<h2>' . __( 'Activating Plugins', 'wp-quick-provision' ) . '</h2>';
									$wpqp_installed_plugins = wpqp_process_keys( array_keys( get_plugins() ) );

									foreach ( $wpqp_plugins as $wpqp_plugin => $wpqp_plugin_data ) {
										$wpqp__plugin = strtolower( trim( $wpqp_plugin ) );

										if ( ! isset( $wpqp_plugin_error[ $wpqp_plugin ] ) && in_array( $wpqp_plugin, $_POST['wpqp_plugins'] ) ) {

											if ( ! is_plugin_active( $wpqp_installed_plugins[ $wpqp__plugin ] ) ) {
												activate_plugin( $wpqp_installed_plugins[ $wpqp__plugin ] );
												?>
                                                <div class="wpqp_info wpqp_success">
                                                    <p>
														<?php printf( __( "Plugin <strong>%s</strong> is activated", 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
                                                    </p>
                                                </div>
												<?php
											} else {
												?>
                                                <div class="wpqp_info wpqp_warning">
                                                    <p>
														<?php printf( __( "Plugin <strong>%s</strong> is already active", 'wp-quick-provision' ), esc_html( $wpqp__plugin ) ); ?>
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

						if ( isset( $_POST['wpqp_themes'] ) || isset( $_POST['wpqp_plugins'] ) ) {
							?>
                            <p>
                                <a href="<?php echo admin_url( 'tools.php?page=wpqp' ); ?>"
                                   class="button button-primary"><?php _e( 'Start Again', 'wpqp_provision' ) ?></a>
                            </p>
							<?php
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

