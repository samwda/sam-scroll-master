<?php
/**
 * Remove all plugin options on uninstall.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

 $samsm_options = [
    // Current options.
    'samsm_load_admin_js',
    'samsm_user_roles',
    'samsm_excluded_pages',
    'samsm_excluded_types',
    'samsm_excluded_terms',
    'samsm_device_types',
    // Legacy (pre-migration) options.
    'ssp_load_admin_js',
    'ssp_user_roles',
    'ssp_excluded_pages',
    'ssp_excluded_types',
    'ssp_excluded_terms',
    'ssp_device_types',
];

foreach ( $samsm_options as $option ) {
    delete_option( $option );
}

delete_transient( 'samsm_purged_caches' );
