<?php
/**
 * Uninstall cleanup for Boylar magic Elementor.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

$opt_key = 'boylar_magic_elementor_settings';
$settings = get_option( $opt_key, [] );
$keep = is_array( $settings ) && ! empty( $settings['keep_data_on_uninstall'] );

if ( ! $keep ) {
	delete_option( $opt_key );
}

