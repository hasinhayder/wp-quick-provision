<?php
/**
 * @package wp_quick_provision
 */

function wpqp_process_keys( $wpqp_keys ) {
	//this function converts an indexed array to an associative array, for especially the installed plugins data which is return by wp_get_plugins()
	$wpqp__keys = [];

	foreach ( $wpqp_keys as $wpqp_key ) {
		$wpqp__key                   = explode( DIRECTORY_SEPARATOR, $wpqp_key );
		$wpqp__keys[ $wpqp__key[0] ] = $wpqp_key;
	}

	return $wpqp__keys;
}


function wpqp_is_okay_to_install( $wpqp_item, $wpqp_type = 'theme' ) {
	//this function checks if the theme or plugin is from wp.org and then if it is in closed state in the wp.org repository
	//otherwise it checks if the item's external link is 404
	if ( strpos( $wpqp_item['source'], "http" ) === false ) {
		if ( 'theme' == $wpqp_type ) {
			$wpqp_api_url = "https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=" . sanitize_text_field( $wpqp_item['source'] );
		} else if ( 'plugin' == $wpqp_type ) {
			$wpqp_api_url = "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=" . sanitize_text_field( $wpqp_item['source'] );
		}
		$wpqp_request = wp_remote_get( $wpqp_api_url );
		$wpqp_body    = json_decode( $wpqp_request['body'], true );

		if ( isset( $wpqp_body['error'] ) ) {
			return false;
		}
	} else {
		//check if the item is 404
		$wpqp_request = wp_remote_head( $wpqp_item['source'] );
		if ( $wpqp_request['response']['code'] == 200 ) {
			return true;
		}
	}

	return true;
}

function wpqp_validate_provision_source( $url ) {
	//this function checks if the provisioning url contains valid data format

	if ( trim( $url ) == '' ) {
		return false;
	}

	if ( wpqp_has_http( $url ) ) {
		$wpqp_remote_data = wp_remote_get( $url );
		if ( is_array( $wpqp_remote_data ) ) {
			$wpqp_remote_body = json_decode( strtolower( $wpqp_remote_data['body'] ), true );
			if ( isset( $wpqp_remote_body['themes'] ) || isset( $wpqp_remote_body['plugins'] ) ) {
				return true;
			}
		} else {
			//other types of data
			return false;
		}
	}

	//org username
	return true;
}

function wpqp_process_provision_source_url( $url ) {
	//this function checks if the provision source is from gist, then adds /raw at the end of it
	//otherwise returns the url as is
	$url = trim( strtolower( sanitize_text_field( $url ) ) );
	if ( strpos( $url, "gist.github.com" ) !== false ) {
		$wpqp_url = trailingslashit( esc_url( $url ) ) . "raw";
	} else if ( wpqp_has_http( $url ) ) {
		$wpqp_url = esc_url( $url );
	} else {
		//org username, return it
		return $url;
	}

	return apply_filters( "wpqp_data_source", $wpqp_url );
}

function wpqp_process_data( $items, $items_type ) {
	//this is kind of an adapter that transforms previous provisioning data format to new format
	//old format = https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306
	//new format = https://gist.github.com/hasinhayder/5cf59b883005e043454f5fe0d2d9546b
	$wpqp_data = [];

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			//it's just a key
			$wpqp_data[ $item ]                = [ 'source' => $item, 'slug' => $item, 'origin' => 'internal' ];
			$wpqp_data[ $item ]['installable'] = wpqp_get_item_url( $wpqp_data[ $item ], $items_type );
		} else {
			$wpqp_data[ $item['slug'] ] = [
				'slug'   => $item['slug'],
				'origin' => 'internal'
			];
			if ( isset( $item['source'] ) ) {
				if ( !wpqp_has_http( $item['source'] ) ) {
					$wpqp_data[ $item['slug'] ]['origin']      = 'internal';
					$wpqp_data[ $item['slug'] ]['installable'] = wpqp_get_item_url( $wpqp_data[ $item['slug'] ], $items_type );
				} else {
					$wpqp_data[ $item['slug'] ]['origin']      = 'external';
					$wpqp_data[ $item['slug'] ]['installable'] = $item['source'];
					$wpqp_data[ $item['slug'] ]['source'] = $item['source'];
				}
			} else {
				$item['source']                            = $item['slug'];
				$wpqp_data[ $item['slug'] ]                = $item;
				$wpqp_data[ $item['slug'] ]['origin']      = 'internal';
				$wpqp_data[ $item['slug'] ]['installable'] = wpqp_get_item_url( $wpqp_data[ $item['slug'] ], $items_type );
			}
		}

	}

	return $wpqp_data;
}

function wpqp_get_item_url( $wpqp_item, $wpqp_item_type = 'theme' ) {
	//this function returns latest stable urls for wp.org plugin or themes if the item has no external link set as source
	//otherwise it returns the external source
	if ( 'theme' == $wpqp_item_type ) {
		if ( strpos( $wpqp_item['source'], "http" ) === false ) {
			return esc_url( 'https://downloads.wordpress.org/theme/' . $wpqp_item['source'] . '.latest-stable.zip' );
		} else {
			return esc_url( $wpqp_item['source'] );
		}
	} else if ( 'plugin' == $wpqp_item_type ) {
		if ( strpos( $wpqp_item['source'], "http" ) === false ) {
			return esc_url( 'https://downloads.wordpress.org/plugin/' . $wpqp_item['source'] . '.latest-stable.zip' );
		} else {
			return esc_url( $wpqp_item['source'] );
		}
	}
}

function wpqp_has_http( $data ) {
	if ( strpos( $data, 'http' ) !== false ) {
		return true;
	}

	return false;
}

function wpqp_remote_get( $url ) {
	//a wrapper of wp_remote_get with support for wp.org username for fetching user's favorite themes and plugins
	if ( wpqp_has_http( $url ) ) {
		$wpqp_remote_result = wp_remote_get( $url );
	} else {
		//org user name
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		$wpqp_remote_result = [ 'body' => [ 'themes' => [], 'plugins' => [] ] ];

		$org_favorite_themes = themes_api( "query_themes", [
			'user'     => $url,
			'browse'   => 'favorites',
			'per_page' => 250,
			'fields'   => [
				'description'       => false,
				'short_description' => false,
				'download_link'     => false,
				'tags'              => false,
				'icons'             => false,
				'ratings'           => false,
			]
		] );

		$org_favorite_plugins = plugins_api( "query_plugins", [
			'user'     => $url,
			'per_page' => 250,
			'fields'   => [
				'description'       => false,
				'short_description' => false,
				'download_link'     => false,
				'tags'              => false,
				'icons'             => false,
				'ratings'           => false,
			]
		] );

		if ( isset( $org_favorite_themes->themes ) ) {
			foreach ( $org_favorite_themes->themes as $theme ) {
				array_push( $wpqp_remote_result['body']['themes'], $theme->slug );
			}
		}

		if ( isset( $org_favorite_plugins->plugins ) ) {
			foreach ( $org_favorite_plugins->plugins as $plugin ) {
				array_push( $wpqp_remote_result['body']['plugins'], $plugin['slug'] );
			}
		}

		$wpqp_remote_result['body'] = json_encode( $wpqp_remote_result['body'] );

	}

	return $wpqp_remote_result;
}