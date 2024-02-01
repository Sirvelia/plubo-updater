<?php

namespace PluboUpdater;

class Updater
{
    /**
     * @var string
     */
    public $plugin_id;

    /**
     * @var string
     */
    public $plugin_slug;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $api_url;

    /**
     * @var string
     */
    public $cache_key;

    /**
     * @var boolean
     */
    public $cache_allowed;

    /**
     * @param string $plugin_id   The ID of the plugin.
     * @param string $plugin_slug The slug of the plugin.
     * @param string $version     The current version of the plugin.
     * @param string $api_url     The API URL to the update server.
     */
    public function __construct($plugin_slug, $plugin_id, $version, $api_url)
    {
        $this->plugin_slug   = $plugin_slug;
        $this->plugin_id     = $plugin_id;
        $this->version       = $version;
        $this->api_url       = $api_url;

        $this->cache_key     = str_replace('-', '_', $this->plugin_slug) . '_updater';
        $this->cache_allowed = true; // Only disable this for debugging

        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update']);
        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
    }

    /**
     * Get the license key. Normally, your plugin would have a settings page where
     * you ask for and store a license key. Fetch it here.
     *
     * @return string
     */
    private function get_license_key()
    {
        $settings_options = get_option("{$this->plugin_slug}_settings");
        return isset($settings_options["{$this->plugin_slug}_license_key"]) ? $settings_options["{$this->plugin_slug}_license_key"] : null;
    }

    /**
     * Fetch the update info from the remote server.
     *
     * @return object|bool
     */
    public function request()
    {
        $license_key = $this->get_license_key();
        if (!$license_key) {
            return false;
        }

        $remote = get_transient($this->cache_key);
        if (false !== $remote && $this->cache_allowed) {
            if ('error' === $remote) {
                return false;
            }

            return json_decode($remote);
        }

        $remote = wp_remote_post($this->api_url . "/license/check", [
            'sslverify' => false,
            'timeout' => 30,
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode(
                [
                    'id' => $license_key,
                    'domain' => home_url()
                ]
            ),
            'data_format' => 'body',
        ]);

        if (
            is_wp_error($remote)
            || 200 !== wp_remote_retrieve_response_code($remote)
            || empty(wp_remote_retrieve_body($remote))
        ) {
            set_transient($this->cache_key, 'error', MINUTE_IN_SECONDS * 10);
            return false;
        }

        $payload = wp_remote_retrieve_body($remote);
        set_transient($this->cache_key, $payload, MINUTE_IN_SECONDS * 30);

        return json_decode($payload);
    }

    /**
     * Override the WordPress request to return the correct plugin info.
     *
     * @see https://developer.wordpress.org/reference/hooks/plugins_api/
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return object|bool
     */
    public function info($result, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return false;
        }
        if ($this->plugin_slug !== $args->slug) {
            return false;
        }

        $remote = $this->request();
        if (!$remote || !isset($remote->plugin) || empty($remote->plugin)) {
            return false;
        }

        $plugin_data = $remote->plugin;
        $plugin_data->tested = null;
        $plugin_data->sections = (array) json_decode($remote->plugin->sections);
        $plugin_data->download_url = $remote->download_url ?? '';

        return (object) $plugin_data;
    }

    /**
     * Override the WordPress request to check if an update is available.
     *
     * @see https://make.wordpress.org/core/2020/07/30/recommended-usage-of-the-updates-api-to-support-the-auto-updates-ui-for-plugins-and-themes-in-wordpress-5-5/
     *
     * @param object $transient
     * @return object
     */
    public function update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $res = (object) array(
            'id'            => $this->plugin_id,
            'slug'          => $this->plugin_slug,
            'plugin'        => $this->plugin_id,
            'new_version'   => $this->version,
            'url'           => '',
            'package'       => '',
            'icons'         => [],
            'banners'       => [],
            'banners_rtl'   => [],
            'tested'        => '',
            'requires_php'  => '',
            'compatibility' => new \stdClass(),
        );

        $remote = $this->request();

        if (
            $remote && $remote->plugin && !empty($remote->plugin)
            && version_compare($this->version, $remote->plugin->version, '<')
        ) {
            $res->new_version = $remote->plugin->version;
            $res->package     = $remote->download_url ?? '';

            $transient->response[$res->plugin] = $res;
        } else {
            $transient->no_update[$res->plugin] = $res;
        }

        return $transient;
    }

    /**
     * When the update is complete, purge the cache.
     *
     * @see https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
     *
     * @param WP_Upgrader $upgrader
     * @param array $options
     * @return void
     */
    public function purge($upgrader, $options)
    {
        if (
            $this->cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options['type']
            && !empty($options['plugins'])
        ) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === $this->plugin_id) {
                    delete_transient($this->cache_key);
                }
            }
        }
    }
}
