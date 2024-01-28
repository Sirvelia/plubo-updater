<?php

namespace PluboUpdater;

class LicenseMenu
{

    private $settings_options;
    private $plugin_slug;
    private $plugin_name;
    private $api_url;

    public function __construct($plugin_name, $plugin_slug, $api_url)
    {
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        $this->api_url = $api_url;

        add_action('admin_menu', [$this, 'settings_add_plugin_page']);
        add_action('admin_init', [$this, 'settings_page_init']);
        add_action("update_option_{$this->plugin_slug}_settings", [$this, 'handle_license_activation'], 10, 3);
        add_action("add_option_{$this->plugin_slug}_settings", [$this, 'handle_initial_license_activation'], 10, 2);
    }

    public function settings_add_plugin_page()
    {
        add_options_page(
            $this->plugin_name . ' license', // page_title
            $this->plugin_name . ' license', // menu_title
            'manage_options', // capability
            "{$this->plugin_slug}-settings", // menu_slug
            [$this, 'create_admin_page'] // function
        );
    }

    public function create_admin_page()
    {
        $this->settings_options = get_option("{$this->plugin_slug}_settings"); ?>

        <div class="wrap">
            <h2><?= __('License', 'plubo-updater') ?></h2>

            <form method="post" action="options.php">
                <?php
                settings_fields("{$this->plugin_slug}_settings_group");
                do_settings_sections("{$this->plugin_slug}-settings-admin");
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    public function settings_page_init()
    {
        register_setting(
            "{$this->plugin_slug}_settings_group", // option_group
            "{$this->plugin_slug}_settings", // option_name
            [$this, 'settings_sanitize'] // sanitize_callback
        );

        add_settings_section(
            "{$this->plugin_slug}_settings_section", // id
            __('License', 'plubo-updater'), // title
            [$this, "settings_section_info"], // callback
            "{$this->plugin_slug}-settings-admin" // page
        );

        add_settings_field(
            "{$this->plugin_slug}_license_key", // id
            __('License Key', 'plubo-updater'), // title
            [$this, 'license_key_callback'], // callback
            "{$this->plugin_slug}-settings-admin", // page
            "{$this->plugin_slug}_settings_section" // section
        );
    }

    public function settings_sanitize($input)
    {
        $sanitary_values = [];
        if (isset($input["{$this->plugin_slug}_license_key"])) {
            $sanitary_values["{$this->plugin_slug}_license_key"] = sanitize_text_field($input["{$this->plugin_slug}_license_key"]);
        }

        return $sanitary_values;
    }

    public function settings_section_info()
    {

    }

    public function license_key_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="' . $this->plugin_slug . '_settings[' . $this->plugin_slug . '_license_key]" id="' . $this->plugin_slug . '_license_key" value="%s">',
            isset($this->settings_options["{$this->plugin_slug}_license_key"]) ? esc_attr($this->settings_options["{$this->plugin_slug}_license_key"]) : ''
        );

        $license_message = json_decode(get_option("{$this->plugin_slug}_license_message"));
        $message = false;

        if (isset($license_message->activations)) {
            if ($license_message->activations) {
                $message = "License is active.";
            } else {
                $message = "License for this site is not active. Click the button below to activate.";
            }
        } else {
            $message = "License for this site is not active. Insert your license and click the button below to activate.";
        }

        echo "<p class='description'>{$message}</p>";
    }

    public function handle_initial_license_activation($option, $new_value)
    {
        if (isset($new_value["{$this->plugin_slug}_license_key"]) && !empty($new_value["{$this->plugin_slug}_license_key"])) {
            $this->activate_license($new_value["{$this->plugin_slug}_license_key"]);
        }
    }

    public function handle_license_activation($old_value, $new_value, $option)
    {

        if (isset($new_value["{$this->plugin_slug}_license_key"]) && !empty($new_value["{$this->plugin_slug}_license_key"]) && $old_value["{$this->plugin_slug}_license_key"] !== $new_value["{$this->plugin_slug}_license_key"]) {
            $this->activate_license($new_value["{$this->plugin_slug}_license_key"]);
        } else if (isset($new_value["{$this->plugin_slug}_license_key"]) && empty($new_value["{$this->plugin_slug}_license_key"]) && !empty($old_value["{$this->plugin_slug}_license_key"])) {
            $this->deactivate_license($old_value["{$this->plugin_slug}_license_key"]);
        }
    }

    public function activate_license($license_key)
    {
        $activation_url = $this->api_url . '/license';
        $response = wp_remote_request($activation_url, [
            'sslverify' => false,
            'timeout' => 10,
            'method' => 'PUT',
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
            is_wp_error($response)
            || (200 !== wp_remote_retrieve_response_code($response) && 400 !== wp_remote_retrieve_response_code($response))
            || empty(wp_remote_retrieve_body($response))
        ) {
            return;
        }

        update_option("{$this->plugin_slug}_license_message", wp_remote_retrieve_body($response));
    }

    public function deactivate_license($license_id)
    {
        $deactivation_url = $this->api_url . '/license';
        $response = wp_remote_request($deactivation_url, [
            'sslverify' => false,
            'timeout' => 10,
            'method' => 'DELETE',
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => json_encode(
                [
                    'id' => $license_id,
                    'domain' => home_url()
                ]
            ),
            'data_format' => 'body',
        ]);

        if (200 === wp_remote_retrieve_response_code($response)) {
            delete_option("{$this->plugin_slug}_license_message");
        }
    }
}
