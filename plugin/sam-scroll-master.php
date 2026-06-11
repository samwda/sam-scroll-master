<?php
/*
 * Plugin Name: Sam Scroll Master
 * Description: Enhance your website's user experience with Sam Scroll Master.
 * Version:     1.1
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
    define( 'SAMSM_PREFIX', 'samsm' ); // unique prefix
}
if ( ! defined( 'SAMSM_VERSION' ) ) {
    define( 'SAMSM_VERSION', '1.1' );
}
if ( ! defined( 'SAMSM_PLUGIN_FILE' ) ) {
    define( 'SAMSM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SAMSM_ASSETS_URL' ) ) {
    define( 'SAMSM_ASSETS_URL', plugin_dir_url( SAMSM_PLUGIN_FILE ) . 'assets/' );
}
if ( ! defined( 'SAMSM_ASSETS_DIR' ) ) {
    define( 'SAMSM_ASSETS_DIR', plugin_dir_path( SAMSM_PLUGIN_FILE ) . 'assets/' );
}

/**
 * Migrate old ssp_* options to new samsm_* options (minimal, safe)
 */
function samsm_maybe_migrate_options() {
    $mappings = [
        'ssp_load_admin_js'    => SAMSM_PREFIX . '_load_admin_js',
        'ssp_user_roles'       => SAMSM_PREFIX . '_user_roles',
        'ssp_excluded_pages'   => SAMSM_PREFIX . '_excluded_pages',
        'ssp_excluded_types'   => SAMSM_PREFIX . '_excluded_types',
        'ssp_excluded_terms'   => SAMSM_PREFIX . '_excluded_terms',
        'ssp_device_types'     => SAMSM_PREFIX . '_device_types',
    ];

    foreach ( $mappings as $old => $new ) {
        if ( get_option( $new ) === false && get_option( $old ) !== false ) {
            update_option( $new, get_option( $old ) );
        }
    }
}
add_action( 'admin_init', 'samsm_maybe_migrate_options', 5 );

/**
 * Detect device type (sanitized)
 */
function samsm_get_device_type() {
    $ua = '';
    if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
    }

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
 * FRONTEND: enqueue SmoothScroll + init script (registered & enqueued properly)
 */
function samsm_enqueue_scripts() {
    // device checks
    $device_types   = (array) get_option( SAMSM_PREFIX . '_device_types', [] );
    $current_device = samsm_get_device_type();
    if ( $device_types && ! in_array( $current_device, $device_types, true ) ) {
        return;
    }

    $current_id        = get_queried_object_id();
    $current_post_type = $current_id ? get_post_type( $current_id ) : '';
    if ( $current_post_type === 'attachment' ) {
        return;
    }

    $excluded_pages = (array) get_option( SAMSM_PREFIX . '_excluded_pages', [] );
    $excluded_types = (array) get_option( SAMSM_PREFIX . '_excluded_types', [] );
    $excluded_terms = (array) get_option( SAMSM_PREFIX . '_excluded_terms', [] );
    $roles          = (array) get_option( SAMSM_PREFIX . '_user_roles', [] );

    $current_user = wp_get_current_user();
    $has_role     = empty( $roles ) || ( ! empty( $current_user->ID ) && array_intersect( $roles, $current_user->roles ) );
    if ( ! is_user_logged_in() && ! in_array( 'guest', $roles, true ) && ! empty( $roles ) ) {
        return;
    }
    if ( ! $has_role ) {
        return;
    }

    if ( in_array( $current_id, $excluded_pages, true ) || in_array( $current_post_type, $excluded_types, true ) ) {
        return;
    }

    if ( is_single() && $current_post_type ) {
        $taxes = get_object_taxonomies( $current_post_type );
        foreach ( $taxes as $tax ) {
            $terms = wp_get_post_terms( $current_id, $tax, [ 'fields' => 'ids' ] );
            if ( array_intersect( $terms, $excluded_terms ) ) {
                return;
            }
        }
    }

    // Register and enqueue SmoothScroll library (must exist at assets/SmoothScroll.js)
    wp_register_script( SAMSM_PREFIX . '-smooth-scroll', SAMSM_ASSETS_URL . 'SmoothScroll.js', [], SAMSM_VERSION, true );
    wp_enqueue_script( SAMSM_PREFIX . '-smooth-scroll' );

    // Register and enqueue our frontend init file
    wp_register_script( SAMSM_PREFIX . '-smooth-init', SAMSM_ASSETS_URL . 'smooth-init.js', [ SAMSM_PREFIX . '-smooth-scroll' ], SAMSM_VERSION, true );
    wp_enqueue_script( SAMSM_PREFIX . '-smooth-init' );
}
add_action( 'wp', 'samsm_enqueue_scripts' );

/**
 * ADMIN: enqueue select2, admin css & js on settings page
 */
function samsm_admin_enqueue( $hook ) {
    // load only on our settings page
    if ( $hook !== 'settings_page_samsm-settings' ) {
        return;
    }

    $ver = SAMSM_VERSION;

    // Select2 (assumed bundled in assets/)
    wp_register_style( SAMSM_PREFIX . '-select2', SAMSM_ASSETS_URL . 'select2.min.css', [], $ver );
    wp_register_script( SAMSM_PREFIX . '-select2', SAMSM_ASSETS_URL . 'select2.min.js', [ 'jquery' ], $ver, true );

    wp_enqueue_style( SAMSM_PREFIX . '-select2' );
    wp_enqueue_script( SAMSM_PREFIX . '-select2' );

    // Admin CSS
    wp_register_style( SAMSM_PREFIX . '-admin-css', SAMSM_ASSETS_URL . 'admin.css', [], $ver );
    wp_enqueue_style( SAMSM_PREFIX . '-admin-css' );

    // Admin JS (depends on select2)
    wp_register_script( SAMSM_PREFIX . '-admin-js', SAMSM_ASSETS_URL . 'admin.js', [ 'jquery', SAMSM_PREFIX . '-select2' ], $ver, true );
    wp_enqueue_script( SAMSM_PREFIX . '-admin-js' );

    // Localize AJAX data for admin.js
    wp_localize_script(
        SAMSM_PREFIX . '-admin-js',
        'samsmAdmin',
        [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( SAMSM_PREFIX . '_ajax_nonce' ),
        ]
    );

    // Optionally load SmoothScroll inside admin if enabled
    if ( get_option( SAMSM_PREFIX . '_load_admin_js', 0 ) ) {
        wp_register_script( SAMSM_PREFIX . '-smooth-scroll-admin', SAMSM_ASSETS_URL . 'SmoothScroll.js', [], $ver, true );
        wp_enqueue_script( SAMSM_PREFIX . '-smooth-scroll-admin' );
        wp_register_script( SAMSM_PREFIX . '-smooth-init-admin', SAMSM_ASSETS_URL . 'smooth-init.js', [ SAMSM_PREFIX . '-smooth-scroll-admin' ], $ver, true );
        wp_enqueue_script( SAMSM_PREFIX . '-smooth-init-admin' );
    }
}
add_action( 'admin_enqueue_scripts', 'samsm_admin_enqueue' );

/**
 * SETTINGS PAGE
 */
function samsm_admin_menu() {
    add_options_page( 'Sam Scroll Master', 'Sam Scroll Master', 'manage_options', 'samsm-settings', 'samsm_settings_page' );
}
add_action( 'admin_menu', 'samsm_admin_menu' );

/**
 * AJAX: search posts & pages
 */
add_action( 'wp_ajax_samsm_search_posts', 'samsm_ajax_search_posts' );
add_action( 'wp_ajax_nopriv_samsm_search_posts', 'samsm_ajax_search_posts' );
function samsm_ajax_search_posts() {
    check_ajax_referer( SAMSM_PREFIX . '_ajax_nonce', 'nonce' );

    $q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

    $posts = get_posts( [
        'post_type'   => [ 'post', 'page' ],
        's'           => $q,
        'numberposts' => 15,
        'post_status' => 'publish',
    ] );

    $results = [];
    foreach ( $posts as $p ) {
        $results[] = [
            'id'   => $p->ID,
            'text' => esc_html( $p->post_title ) . ' (' . esc_html( ucfirst( $p->post_type ) ) . ')',
        ];
    }

    wp_send_json( $results );
}

/**
 * Render settings page (no inline <script>/<style>)
 */
function samsm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST[ SAMSM_PREFIX . '_save' ] ) ) {
        check_admin_referer( SAMSM_PREFIX . '_save_nonce', SAMSM_PREFIX . '_nonce' );

        update_option( SAMSM_PREFIX . '_load_admin_js', isset( $_POST[ SAMSM_PREFIX . '_load_admin_js' ] ) ? 1 : 0 );
        update_option( SAMSM_PREFIX . '_user_roles', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_user_roles' ] ?? [] ) ) );
        update_option( SAMSM_PREFIX . '_excluded_pages', array_map( 'intval', (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_excluded_pages' ] ?? [] ) ) );
        update_option( SAMSM_PREFIX . '_excluded_types', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_excluded_types' ] ?? [] ) ) );
        update_option( SAMSM_PREFIX . '_excluded_terms', array_map( 'intval', (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_excluded_terms' ] ?? [] ) ) );
        update_option( SAMSM_PREFIX . '_device_types', array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ SAMSM_PREFIX . '_device_types' ] ?? [] ) ) );

        echo '<div class="updated"><p>' . esc_html__( 'Settings saved successfully! All selections are secure and validated.', 'sam-scroll-master' ) . '</p></div>';
    }

    $load_admin_js  = get_option( SAMSM_PREFIX . '_load_admin_js', 0 );
    $roles          = (array) get_option( SAMSM_PREFIX . '_user_roles', [] );
    $excluded_pages = (array) get_option( SAMSM_PREFIX . '_excluded_pages', [] );
    $excluded_types = (array) get_option( SAMSM_PREFIX . '_excluded_types', [] );
    $excluded_terms = (array) get_option( SAMSM_PREFIX . '_excluded_terms', [] );
    $device_types   = (array) get_option( SAMSM_PREFIX . '_device_types', [] );

    global $wp_roles;
    $all_roles = $wp_roles->roles;
    $post_types = get_post_types( [ 'public' => true ], 'objects' );
    $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
    $excluded_taxonomies = [ 'nav_menu', 'link_category', 'post_format' ];
    $all_posts = get_posts( [ 'post_type' => [ 'post', 'page' ], 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ] );
    ?>
    <div class="ssp-settings-wrap">
        <h1><img src="<?php echo esc_url( SAMSM_ASSETS_URL . 'logo.png' ); ?>" alt="SSM Logo" /> Sam Scroll Master</h1>
        <p style="color: rgba(255,255,255,0.85); font-style: italic; margin-bottom: 2.5em; line-height:1.8;">
            Configure Sam Scroll Master. Use the options below to control scroll behavior on specific users, devices, post types, or taxonomies.
        </p>
        <form method="post">
            <?php wp_nonce_field( SAMSM_PREFIX . '_save_nonce', SAMSM_PREFIX . '_nonce' ); ?>

            <fieldset class="ssp-fieldset">
                <legend>SSM Core Options</legend>
                <p style="margin-bottom:1.5em;">
                    <em style="color:#ddd"><?php echo esc_html__( 'Smooth scroll is applied site-wide by default. Use exclusions and role filters below to control where it runs.', 'sam-scroll-master' ); ?></em><br/><br/>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_load_admin_js' ); ?>" value="1" <?php checked( 1, $load_admin_js ); ?> /> Enable Smooth Scroll in WordPress Admin Panel</label>
                </p>
            </fieldset>

            <fieldset class="ssp-fieldset">
                <legend>User Roles & Devices</legend>
                <p>
                    <strong>User Roles:</strong><br/>
                    <?php foreach ( $all_roles as $rk => $rd ): ?>
                        <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_user_roles[]' ); ?>" value="<?php echo esc_attr( $rk ); ?>" <?php checked( in_array( $rk, $roles, true ) ); ?> /> <?php echo esc_html( $rd['name'] ); ?></label>
                    <?php endforeach; ?>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_user_roles[]' ); ?>" value="guest" <?php checked( in_array( 'guest', $roles, true ) ); ?> /> Guest</label>
                </p>
                <p>
                    <strong>Devices:</strong><br/>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_device_types[]' ); ?>" value="desktop" <?php checked( in_array( 'desktop', $device_types, true ) ); ?> /> Desktop</label>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_device_types[]' ); ?>" value="tablet" <?php checked( in_array( 'tablet', $device_types, true ) ); ?> /> Tablet</label>
                    <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_device_types[]' ); ?>" value="mobile" <?php checked( in_array( 'mobile', $device_types, true ) ); ?> /> Mobile</label>
                </p>
            </fieldset>

            <fieldset class="ssp-fieldset">
                <legend>Exclusions</legend>
                <p>
                    <strong>Posts & Pages:</strong><br/>
                    <select id="samsm-excluded-pages" name="<?php echo esc_attr( SAMSM_PREFIX . '_excluded_pages[]' ); ?>" multiple style="width:100%;">
                        <?php foreach ( $all_posts as $p ): ?>
                            <option value="<?php echo esc_attr( $p->ID ); ?>" <?php echo in_array( $p->ID, $excluded_pages, true ) ? 'selected' : ''; ?>>
                                <?php echo esc_html( $p->post_title ) . ' (' . esc_html( ucfirst( $p->post_type ) ) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <strong>Post Types:</strong><br/>
                    <?php foreach ( $post_types as $pt ): if ( $pt->name === 'attachment' ) continue; ?>
                        <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_excluded_types[]' ); ?>" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $excluded_types, true ) ); ?> /> <?php echo esc_html( $pt->labels->singular_name ); ?></label>
                    <?php endforeach; ?>
                </p>

                <p>
                    <strong>Taxonomies:</strong><br/>
                    <?php foreach ( $taxonomies as $tk => $tx ):
                        if ( in_array( $tk, $excluded_taxonomies, true ) ) { continue; }
                        $terms = get_terms( [ 'taxonomy' => $tk, 'hide_empty' => false ] );
                        if ( empty( $terms ) ) { continue; }
                        ?>
                        <div>
                            <strong><?php echo esc_html( $tx->labels->name ); ?></strong><br/>
                            <?php foreach ( $terms as $t ): ?>
                                <label><input type="checkbox" name="<?php echo esc_attr( SAMSM_PREFIX . '_excluded_terms[]' ); ?>" value="<?php echo esc_attr( $t->term_id ); ?>" <?php checked( in_array( $t->term_id, $excluded_terms, true ) ); ?> /> <?php echo esc_html( $t->name ); ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </p>
            </fieldset>

            <p><input type="submit" name="<?php echo esc_attr( SAMSM_PREFIX . '_save' ); ?>" class="button button-primary" value="Save Settings" /></p>
        </form>
    </div>
    <?php
}
