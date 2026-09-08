<?php
/*
 * Plugin Name: Sam Scroll Master
 * Description: Enhance your website's user experience with Sam Scroll Master.
 * Version:     1.2
 * Author:      SAM Web Design Agency
 * Author URI:  https://samwda.ir
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sam-scroll-master
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constants
 */
if ( ! defined( 'SAMSM_PREFIX' ) ) {
    define( 'SAMSM_PREFIX', 'samsm' );
}
if ( ! defined( 'SAMSM_VERSION' ) ) {
    define( 'SAMSM_VERSION', '1.2' );
}
if ( ! defined( 'SAMSM_PLUGIN_FILE' ) ) {
    define( 'SAMSM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SAMSM_ASSETS_URL' ) ) {
    define( 'SAMSM_ASSETS_URL', plugin_dir_url( SAMSM_PLUGIN_FILE ) . 'assets/' );
}

/**
 * Migrate old ssp_* options to new samsm_* options
 */
function samsm_maybe_migrate_options() {
    $mappings = [
        'ssp_load_admin_js'  => SAMSM_PREFIX . '_load_admin_js',
        'ssp_user_roles'     => SAMSM_PREFIX . '_user_roles',
        'ssp_excluded_pages' => SAMSM_PREFIX . '_excluded_pages',
        'ssp_excluded_types' => SAMSM_PREFIX . '_excluded_types',
        'ssp_excluded_terms' => SAMSM_PREFIX . '_excluded_terms',
        'ssp_device_types'   => SAMSM_PREFIX . '_device_types',
    ];

    foreach ( $mappings as $old => $new ) {
        if ( false === get_option( $new ) && false !== get_option( $old ) ) {
            update_option( $new, get_option( $old ) );
        }
    }
}
add_action( 'admin_init', 'samsm_maybe_migrate_options', 5 );

/**
 * Detect device type (sanitized)
 */
function samsm_get_device_type() {
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
    $ua = strtolower( (string) $ua );

    if ( ! $ua ) {
        return 'desktop';
    }

    if ( preg_match( '/mobile|android|touch|silk|kindle|blackberry|opera mini|opera mobi|iphone|ipod|ipad/', $ua ) ) {
        return preg_match( '/ipad|tablet/', $ua ) ? 'tablet' : 'mobile';
    }

    return 'desktop';
}

/**
 * Check if current user role is allowed (fix: Guest logic)
 */
function samsm_should_load_for_user() {
    $roles = (array) get_option( SAMSM_PREFIX . '_user_roles', [] );

    // No restriction = load for everyone.
    if ( empty( $roles ) ) {
        return true;
    }

    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        return (bool) array_intersect( $roles, (array) $user->roles );
    }

    return in_array( 'guest', $roles, true );
}

/**
 * Check if current request is excluded
 */
function samsm_is_excluded_request() {
    $current_id = get_queried_object_id();
    $post_type  = $current_id ? get_post_type( $current_id ) : '';

    if ( 'attachment' === $post_type ) {
        return true;
    }

    $excluded_pages = array_map( 'intval', (array) get_option( SAMSM_PREFIX . '_excluded_pages', [] ) );
    if ( $current_id && in_array( $current_id, $excluded_pages, true ) ) {
        return true;
    }

    $excluded_types = (array) get_option( SAMSM_PREFIX . '_excluded_types', [] );
    if ( $post_type && in_array( $post_type, $excluded_types, true ) ) {
        return true;
    }

    // Fix: is_singular() covers pages & CPTs too, and guards WP_Error.
    $excluded_terms = array_map( 'intval', (array) get_option( SAMSM_PREFIX . '_excluded_terms', [] ) );
    if ( is_singular() && $post_type && $excluded_terms ) {
        foreach ( get_object_taxonomies( $post_type ) as $tax ) {
            $terms = wp_get_post_terms( $current_id, $tax, [ 'fields' => 'ids' ] );
            if ( ! is_wp_error( $terms ) && array_intersect( $terms, $excluded_terms ) ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * FRONTEND: enqueue SmoothScroll + init script
 */
function samsm_enqueue_scripts() {
    $device_types = (array) get_option( SAMSM_PREFIX . '_device_types', [] );
    if ( $device_types && ! in_array( samsm_get_device_type(), $device_types, true ) ) {
        return;
    }

    if ( ! samsm_should_load_for_user() ) {
        return;
    }

    if ( samsm_is_excluded_request() ) {
        return;
    }

    wp_enqueue_script( SAMSM_PREFIX . '-smooth-scroll', SAMSM_ASSETS_URL . 'SmoothScroll.js', [], SAMSM_VERSION, true );
    wp_enqueue_script( SAMSM_PREFIX . '-smooth-init', SAMSM_ASSETS_URL . 'smooth-init.js', [ SAMSM_PREFIX . '-smooth-scroll' ], SAMSM_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'samsm_enqueue_scripts' );

/**
 * Purge all supported cache plugins (called after saving settings)
 *
 * Supported: WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache,
 * WP Fastest Cache, WP Optimize, SG Optimizer, Autoptimize, Hummingbird,
 * Cache Enabler, Breeze, Swift Performance.
 */
function samsm_purge_all_caches() {
    $purged = [];

    // WP Rocket
    if ( function_exists( 'rocket_clean_domain' ) ) {
        rocket_clean_domain();
        if ( function_exists( 'rocket_clean_minify' ) ) {
            rocket_clean_minify();
        }
        $purged[] = 'WP Rocket';
    }

    // LiteSpeed Cache
    if ( class_exists( '\LiteSpeed\Purge' ) && method_exists( '\LiteSpeed\Purge', 'purge_all' ) ) {
        \LiteSpeed\Purge::purge_all();
        $purged[] = 'LiteSpeed Cache';
    } elseif ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
        LiteSpeed_Cache_API::purge_all();
        $purged[] = 'LiteSpeed Cache';
    }

    // W3 Total Cache
    if ( function_exists( 'w3tc_flush_all' ) ) {
        w3tc_flush_all();
        $purged[] = 'W3 Total Cache';
    }

    // WP Super Cache
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache();
        $purged[] = 'WP Super Cache';
    }

    // WP Fastest Cache
    if ( function_exists( 'wpfc_clear_all_cache' ) ) {
        wpfc_clear_all_cache();
        $purged[] = 'WP Fastest Cache';
    }

    // WP Optimize
    if ( function_exists( 'wpo_cache_flush' ) ) {
        wpo_cache_flush();
        $purged[] = 'WP Optimize';
    }

    // SG Optimizer (SiteGround)
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
        sg_cachepress_purge_cache();
        $purged[] = 'SG Optimizer';
    }

    // Autoptimize (minified assets)
    if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
        autoptimizeCache::clearall();
        $purged[] = 'Autoptimize';
    }

    // Hummingbird
    if ( class_exists( '\Hummingbird\WP_Hummingbird' ) && method_exists( '\Hummingbird\WP_Hummingbird', 'flush_cache' ) ) {
        \Hummingbird\WP_Hummingbird::flush_cache();
        $purged[] = 'Hummingbird';
    }

    // Cache Enabler
    if ( class_exists( 'Cache_Enabler' ) && method_exists( 'Cache_Enabler', 'clear_complete_cache' ) ) {
        Cache_Enabler::clear_complete_cache();
        $purged[] = 'Cache Enabler';
    }

    // Swift Performance
    if ( class_exists( 'Swift_Performance_Cache' ) && method_exists( 'Swift_Performance_Cache', 'clear_all_cache' ) ) {
        Swift_Performance_Cache::clear_all_cache();
        $purged[] = 'Swift Performance';
    }

    // Breeze (fires only when active; no-op otherwise)
    if ( did_action( 'breeze_clear_all_cache' ) || class_exists( 'Breeze_PurgeCache' ) ) {
        do_action( 'breeze_clear_all_cache' );
        $purged[] = 'Breeze';
    }

    // Allow other plugins to hook into the purge event.
    do_action( 'samsm_after_purge_caches', $purged );

    return $purged;
}

/**
 * ADMIN: enqueue select2, admin css & js on settings page
 */
function samsm_admin_enqueue( $hook ) {
    if ( 'settings_page_samsm-settings' !== $hook ) {
        return;
    }

    $ver = SAMSM_VERSION;

    wp_enqueue_style( SAMSM_PREFIX . '-select2', SAMSM_ASSETS_URL . 'select2.min.css', [], $ver );
    wp_enqueue_script( SAMSM_PREFIX . '-select2', SAMSM_ASSETS_URL . 'select2.min.js', [ 'jquery' ], $ver, true );

    wp_enqueue_style( SAMSM_PREFIX . '-admin-css', SAMSM_ASSETS_URL . 'admin.css', [], $ver );
    wp_enqueue_script( SAMSM_PREFIX . '-admin-js', SAMSM_ASSETS_URL . 'admin.js', [ 'jquery', SAMSM_PREFIX . '-select2' ], $ver, true );

    wp_localize_script(
        SAMSM_PREFIX . '-admin-js',
        'samsmAdmin',
        [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( SAMSM_PREFIX . '_ajax_nonce' ),
        ]
    );

    if ( get_option( SAMSM_PREFIX . '_load_admin_js', 0 ) ) {
        wp_enqueue_script( SAMSM_PREFIX . '-smooth-scroll-admin', SAMSM_ASSETS_URL . 'SmoothScroll.js', [], $ver, true );
        wp_enqueue_script( SAMSM_PREFIX . '-smooth-init-admin', SAMSM_ASSETS_URL . 'smooth-init.js', [ SAMSM_PREFIX . '-smooth-scroll-admin' ], $ver, true );
    }
}
add_action( 'admin_enqueue_scripts', 'samsm_admin_enqueue' );

/**
 * ADMIN MENU
 */
function samsm_admin_menu() {
    add_options_page(
        __( 'Sam Scroll Master', 'sam-scroll-master' ),
        __( 'Sam Scroll Master', 'sam-scroll-master' ),
        'manage_options',
        'samsm-settings',
        'samsm_settings_page'
    );
}
add_action( 'admin_menu', 'samsm_admin_menu' );

/**
 * Handle settings save (PRG pattern: Post -> Redirect -> Get)
 * Runs on admin_init so redirect happens before any output.
 */
function samsm_handle_save_request() {
    if ( empty( $_POST[ SAMSM_PREFIX . '_save' ] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'sam-scroll-master' ) );
    }

    check_admin_referer( SAMSM_PREFIX . '_save_nonce', SAMSM_PREFIX . '_nonce' );

    // Devices — whitelist.
    $allowed_devices = [ 'desktop', 'tablet', 'mobile' ];
    $devices_raw     = isset( $_POST[ SAMSM_PREFIX . '_device_types' ] ) ? (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_device_types' ] ) : [];
    update_option( SAMSM_PREFIX . '_device_types', array_values( array_intersect( $allowed_devices, array_map( 'sanitize_key', $devices_raw ) ) ) );

    // Roles — whitelist (real roles + guest).
    $allowed_roles   = array_keys( wp_roles()->get_names() );
    $allowed_roles[] = 'guest';
    $roles_raw       = isset( $_POST[ SAMSM_PREFIX . '_user_roles' ] ) ? (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_user_roles' ] ) : [];
    update_option( SAMSM_PREFIX . '_user_roles', array_values( array_intersect( $allowed_roles, array_map( 'sanitize_key', $roles_raw ) ) ) );

    // Excluded pages — int + unique.
    $pages_raw = isset( $_POST[ SAMSM_PREFIX . '_excluded_pages' ] ) ? (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_excluded_pages' ] ) : [];
    update_option( SAMSM_PREFIX . '_excluded_pages', array_values( array_unique( array_filter( array_map( 'intval', $pages_raw ) ) ) ) );

    // Post types — whitelist against real public post types.
    $allowed_types = get_post_types( [ 'public' => true ] );
    unset( $allowed_types['attachment'] );
    $types_raw = isset( $_POST[ SAMSM_PREFIX . '_excluded_types' ] ) ? (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_excluded_types' ] ) : [];
    update_option( SAMSM_PREFIX . '_excluded_types', array_values( array_intersect( $allowed_types, array_map( 'sanitize_key', $types_raw ) ) ) );

    // Terms — int + unique.
    $terms_raw = isset( $_POST[ SAMSM_PREFIX . '_excluded_terms' ] ) ? (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_excluded_terms' ] ) : [];
    update_option( SAMSM_PREFIX . '_excluded_terms', array_values( array_unique( array_filter( array_map( 'intval', $terms_raw ) ) ) ) );

    // Admin JS toggle.
    update_option( SAMSM_PREFIX . '_load_admin_js', empty( $_POST[ SAMSM_PREFIX . '_load_admin_js' ] ) ? 0 : 1 );

    // 🔥 Purge all caches after saving.
    $purged = samsm_purge_all_caches();
    set_transient( 'samsm_purged_caches', $purged, 60 );

    wp_safe_redirect( add_query_arg( 'samsm_saved', '1', admin_url( 'options-general.php?page=samsm-settings' ) ) );
    exit;
}
add_action( 'admin_init', 'samsm_handle_save_request' );

/**
 * AJAX: search posts (admin only — nopriv removed)
 */
function samsm_ajax_search_posts() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
    }

    check_ajax_referer( SAMSM_PREFIX . '_ajax_nonce', 'nonce' );

    $q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

    // All public post types (consistent with exclusions).
    $post_types = get_post_types( [ 'public' => true ], 'names' );
    unset( $post_types['attachment'] );

    $posts = get_posts( [
        'post_type'   => array_values( $post_types ),
        's'           => $q,
        'numberposts' => 15,
        'post_status' => [ 'publish', 'draft', 'pending', 'future', 'private' ],
    ] );

    $results = [];
    foreach ( $posts as $p ) {
        $type_obj  = get_post_type_object( $p->post_type );
        $results[] = [
            'id'   => $p->ID,
            // select2 escapes markup by default; strip tags as extra safety.
            'text' => wp_strip_all_tags( $p->post_title ) . ' (' . ( $type_obj ? $type_obj->labels->singular_name : $p->post_type ) . ')',
        ];
    }

    wp_send_json( $results );
}
add_action( 'wp_ajax_samsm_search_posts', 'samsm_ajax_search_posts' );

/**
 * Render settings page
 */
function samsm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $load_admin_js  = (int) get_option( SAMSM_PREFIX . '_load_admin_js', 0 );
    $roles          = (array) get_option( SAMSM_PREFIX . '_user_roles', [] );
    $excluded_pages = array_map( 'intval', (array) get_option( SAMSM_PREFIX . '_excluded_pages', [] ) );
    $excluded_types = (array) get_option( SAMSM_PREFIX . '_excluded_types', [] );
    $excluded_terms = array_map( 'intval', (array) get_option( SAMSM_PREFIX . '_excluded_terms', [] ) );
    $device_types   = (array) get_option( SAMSM_PREFIX . '_device_types', [] );

    $all_roles           = wp_roles()->roles;
    $post_types          = get_post_types( [ 'public' => true ], 'objects' );
    $taxonomies          = get_taxonomies( [ 'public' => true ], 'objects' );
    $excluded_taxonomies = [ 'nav_menu', 'link_category', 'post_format' ];

    // Performance fix: only pre-selected posts are rendered; the rest load via AJAX.
    $selected_posts = [];
    if ( $excluded_pages ) {
        $selected_posts = get_posts( [
            'post_type'   => 'any',
            'post__in'    => $excluded_pages,
            'numberposts' => -1,
            'post_status' => 'any',
            'orderby'     => 'post__in',
        ] );
    }
    ?>
    <div class="ssp-settings-wrap">
        <?php if ( isset( $_GET['samsm_saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['samsm_saved'] ) ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php esc_html_e( 'Settings saved successfully!', 'sam-scroll-master' ); ?>
                    <?php
                    $purged = get_transient( 'samsm_purged_caches' );
                    delete_transient( 'samsm_purged_caches' );
                    if ( is_array( $purged ) && ! empty( $purged ) ) {
                        echo ' ' . esc_html( sprintf( __( 'Caches purged: %s', 'sam-scroll-master' ), implode( ' | ', $purged ) ) );
                    } else {
                        echo ' ' . esc_html__( 'No supported cache plugin was detected.', 'sam-scroll-master' );
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <h1><img src="<?php echo esc_url( SAMSM_ASSETS_URL . 'logo.png' ); ?>" alt="SSM Logo" /> <?php esc_html_e( 'Sam Scroll Master', 'sam-scroll-master' ); ?></h1>
        <p class="ssp-description">
            <?php esc_html_e( 'Configure Sam Scroll Master. Use the options below to control scroll behavior for specific users, devices, post types, or taxonomies.', 'sam-scroll-master' ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( SAMSM_PREFIX . '_save_nonce', SAMSM_PREFIX . '_nonce' ); ?>

            <fieldset class="ssp-fieldset">
                <legend><?php esc_html_e( 'SSM Core Options', 'sam-scroll-master' ); ?></legend>
                <p>
                    <em><?php esc_html_e( 'Smooth scroll is applied site-wide by default. Use exclusions and role filters below to control where it runs.', 'sam-scroll-master' ); ?></em><br /><br />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_load_admin_js' ); ?>" value="1" <?php checked( 1, $load_admin_js ); ?> />
                        <?php esc_html_e( 'Enable Smooth Scroll in WordPress Admin Panel', 'sam-scroll-master' ); ?>
                    </label>
                </p>
            </fieldset>

            <fieldset class="ssp-fieldset">
                <legend><?php esc_html_e( 'User Roles & Devices', 'sam-scroll-master' ); ?></legend>
                <p>
                    <strong><?php esc_html_e( 'User Roles:', 'sam-scroll-master' ); ?></strong><br />
                    <?php foreach ( $all_roles as $rk => $rd ) : ?>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_user_roles[]' ); ?>" value="<?php echo esc_attr( $rk ); ?>" <?php checked( in_array( $rk, $roles, true ) ); ?> />
                            <?php echo esc_html( $rd['name'] ); ?>
                        </label>
                    <?php endforeach; ?>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_user_roles[]' ); ?>" value="guest" <?php checked( in_array( 'guest', $roles, true ) ); ?> />
                        <?php esc_html_e( 'Guest', 'sam-scroll-master' ); ?>
                    </label>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Devices:', 'sam-scroll-master' ); ?></strong><br />
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_device_types[]' ); ?>" value="desktop" <?php checked( in_array( 'desktop', $device_types, true ) ); ?> /> <?php esc_html_e( 'Desktop', 'sam-scroll-master' ); ?></label>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_device_types[]' ); ?>" value="tablet" <?php checked( in_array( 'tablet', $device_types, true ) ); ?> /> <?php esc_html_e( 'Tablet', 'sam-scroll-master' ); ?></label>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_device_types[]' ); ?>" value="mobile" <?php checked( in_array( 'mobile', $device_types, true ) ); ?> /> <?php esc_html_e( 'Mobile', 'sam-scroll-master' ); ?></label>
                </p>
            </fieldset>

            <fieldset class="ssp-fieldset">
                <legend><?php esc_html_e( 'Exclusions', 'sam-scroll-master' ); ?></legend>

                <!-- Fix: div instead of p (valid HTML) -->
                <div class="ssp-exclusion-block">
                    <strong><?php esc_html_e( 'Posts & Pages:', 'sam-scroll-master' ); ?></strong><br />
                    <select id="samsm-excluded-pages" name="<?php echo esc_attr( SAMSM_PREFIX . '_excluded_pages[]' ); ?>" multiple style="width:100%;">
                        <?php foreach ( $selected_posts as $p ) : ?>
                            <option value="<?php echo esc_attr( $p->ID ); ?>" selected>
                                <?php echo esc_html( $p->post_title ) . ' (' . esc_html( ucfirst( $p->post_type ) ) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ssp-exclusion-block">
                    <strong><?php esc_html_e( 'Post Types:', 'sam-scroll-master' ); ?></strong><br />
                    <?php foreach ( $post_types as $pt ) : ?>
                        <?php if ( 'attachment' === $pt->name ) { continue; } ?>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_excluded_types[]' ); ?>" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $excluded_types, true ) ); ?> />
                            <?php echo esc_html( $pt->labels->singular_name ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Fix: div instead of p -->
                <div class="ssp-exclusion-block">
                    <strong><?php esc_html_e( 'Taxonomies:', 'sam-scroll-master' ); ?></strong><br />
                    <?php foreach ( $taxonomies as $tk => $tx ) : ?>
                        <?php
                        if ( in_array( $tk, $excluded_taxonomies, true ) ) {
                            continue;
                        }
                        // Fix: WP_Error guard + number cap for performance.
                        $terms = get_terms( [ 'taxonomy' => $tk, 'hide_empty' => false, 'number' => 100 ] );
                        if ( is_wp_error( $terms ) || empty( $terms ) ) {
                            continue;
                        }
                        ?>
                        <div class="ssp-tax-block">
                            <strong><?php echo esc_html( $tx->labels->name ); ?></strong><br />
                            <?php foreach ( $terms as $t ) : ?>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_excluded_terms[]' ); ?>" value="<?php echo esc_attr( $t->term_id ); ?>" <?php checked( in_array( (int) $t->term_id, $excluded_terms, true ) ); ?> />
                                    <?php echo esc_html( $t->name ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <p>
                <input type="submit" name="<?php echo esc_attr( SAMSM_PREFIX . '_save' ); ?>" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'sam-scroll-master' ); ?>" />
            </p>
        </form>
    </div>
    <?php
}
