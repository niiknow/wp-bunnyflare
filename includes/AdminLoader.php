<?php

namespace Bunnyflare;

/**
 * Admin pages loader.
 */
class AdminLoader
{
    /**
     * The application domain.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Initialize this class.
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    /**
     * Register our menu page.
     *
     * @return void
     */
    public function admin_menu()
    {
        global $submenu;

        $capability = 'manage_options';
        $slug = $this->prefix;

        $hook = add_menu_page(
            esc_html(__('Bunnyflare', $this->prefix)),
            esc_html(__('Bunnyflare', $this->prefix)),
            $capability,
            $slug,
            [$this, 'plugin_page'],
            'dashicons-text' // tip: https://developer.wordpress.org/resource/dashicons
        );

        if (current_user_can($capability)) {
            add_submenu_page(
                $slug,
                esc_html(__('Dashboard', $this->prefix)),
                esc_html(__('Dashboard', $this->prefix)),
                $capability,
                $slug,
                [$this, 'plugin_page']
            );
            add_submenu_page(
                $slug,
                esc_html(__('Settings', $this->prefix)),
                esc_html(__('Settings', $this->prefix)),
                $capability,
                "admin.php?page={$slug}#/settings"
            );
        }

        if ($account_key && $pullzone_id) {
            add_action( 'admin_bar_menu', 'edge_caching_and_firewall_with_bunnycdn_admin_bar_item', 500 );
        }

        $this->handleClearCache();
    }

    /**
     * Load scripts and styles for the app.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style($this->prefix.'-bootstrap');
        wp_enqueue_style($this->prefix.'-admin');
        wp_enqueue_script($this->prefix.'-admin');
    }

    public function handleBunnyClearCache() {
        if ( empty( $_GET['_cache'] ) || empty( $_GET['_action'] ) || $_GET['_cache'] !== 'bunnyflare' || ( $_GET['_action'] !== 'clear_site_cache' && $_GET['_action'] !== 'clear_page_cache' ) ) {
            return;
        }

        if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bunnyflare_clear_cache_nonce' ) ) {
            return;
        }

        $settings = get_option('bunnyflare_settings');
        if (is_serialized($settings)) {
            $settings = unserialize($settings);
        }

        $account_key = '';
        $pullzone_id = '';
        $cdn = new bunnycdn_api();

        if ( $account_key && $pullzone_id && $_GET['_action'] === 'clear_page_cache' ) {
            $url = parse_url( home_url(), PHP_URL_SCHEME ) . '://' . parse_url( home_url(), PHP_URL_HOST ) . preg_replace( '/\?.*/', '', $_SERVER['REQUEST_URI'] );

            $this->purgePageCache( $url );
        } elseif ( $_GET['_action'] === 'clear_site_cache' ) {
            $this->purgeSiteCache( $pullzone_id );
        }

        wp_safe_redirect( wp_get_referer() );

        exit();
    }

    public function bunnycdn_clear_cache_menu( WP_Admin_Bar $admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $admin_bar->add_menu( array(
            'id'    => 'bunnyflare_clear_site_cache',
            'parent' => 'top-secondary',
            'title' => 'Clear Site Cache',
            'href'   => wp_nonce_url( add_query_arg( array(
                '_cache'  => 'bunnyflare',
                '_action' => 'clear_site_cache',
            ) ), 'bunnyflare_clear_cache_nonce' ),
            'meta' => ['title' => __( 'Clear Site Cache', 'bunnyflare' )]
        ) );

        if ( ! is_admin() ) {
            $admin_bar->add_menu( array(
                'id'    => 'bunnyflare_clear_page_cache',
                'parent' => 'top-secondary',
                'title' => 'Clear Page Cache',
                'href'   => wp_nonce_url( add_query_arg( array(
                    '_cache'  => 'bunnyflare',
                    '_action' => 'clear_page_cache',
                ) ), 'bunnyflare_clear_cache_nonce' ),
                'meta' => ['title' => __( 'Clear Page Cache', 'bunnyflare' )]
            ) );
        }
    }

    /**
     * Render our admin page.
     *
     * @return void
     */
    public function plugin_page()
    {
        $this->enqueue_scripts();

        $settingController = new Api\SettingController();

        // output data for use on client-side
        // https://wordpress.stackexchange.com/questions/344537/authenticating-with-rest-api
        $appVars = apply_filters('bunnyflare/admin_app_vars', [
            'rest'             => [
                'endpoints' => [
                    'settings' => esc_url_raw(rest_url($settingController->get_endpoint())),
                ],
                'nonce'     => wp_create_nonce('wp_rest'),
            ],
            'nonce'            => wp_create_nonce('wp_rest'),
            'settings'         => $settingController->get_settings_raw(),
            'settingStructure' => $settingController->get_settings_structure(true),
            'prefix'           => $this->prefix,
            'adminUrl'         => admin_url('/'),
            'pluginUrl'        => rtrim(\Bunnyflare\Main::$BASEURL, '/'),
        ]);

        wp_localize_script($this->prefix.'-admin', 'vue_wp_plugin_config_admin', $appVars);

        $content = '<div class="admin-app-wrapper"><div id="vue-admin-app"></div></div>';
        echo $content;
    }

    public function purgePageCache($pageUrl)
    {
        $url = "https://api.bunny.net/purge?url=".urlencode($pageUrl);
        $headers = [
            "Accept" => 'application/json',
            "AccessKey" => $accessKey
        ];

        $this->apiPost($url, $headers);
    }

    public function purgeSiteCache($zoneId, $accessKey)
    {
        $url = "https://api.bunny.net/pullzone/$zoneId/purgeCache";
        $headers = [
            "Accept" => 'application/json',
            "AccessKey" => $accessKey
        ];

        $this->apiPost($url, $headers);
    }

    public function apiPost($url, $headers, $body = null)
    {
        $timeout = (int) ini_get( 'max_execution_time' );

        $params = [
            'headers' => $headers,
            'timeout' => $timeout ? $timeout : 30,
        ];

        if (! empty($body)) {
            $params['body'] = $body;
        }

        $response = wp_safe_remote_post( $url, $params );
    }
}
