<?php

namespace PluboUpdater;

use PluboUpdater\Updater;
use PluboUpdater\LicenseMenu;

/**
 * The UpdaterProcessor is in charge of the interaction between the updating system and
 * the rest of WordPress.
 *
 * @author Joan Rodas <joan@sirvelia.com>
 *
 */
class UpdaterProcessor
{

    /**
     * The updater.
     *
     * @var Updater
     */
    private $updater = NULL;

    /**
     * The LicenseMenu.
     *
     * @var LicenseMenu
     */
    private $licensemenu = NULL;

    /**
     * The updater instance.
     *
     * @var UpdaterProcessor|null
     */
    private static $instance = NULL;

    /**
     * Constructor.
     *
     * @param Updater  $updater
     */
    public function __construct($plugin_name, $plugin_slug, $plugin_id, $plugin_version, $api_url)
    {
        $this->updater = new Updater($plugin_slug, $plugin_id, $plugin_version, $api_url);
        $this->licensemenu = null;
        
        if ( is_admin() ) {
            $this->licensemenu = new LicenseMenu($plugin_name, $plugin_slug, $api_url);
        }

        $this->initHooks();
    }

    /**
     * Initialize hooks with WordPress.
     */
    private function initHooks()
    {

    }

    /**
     * Clone not allowed.
     *
     */
    private function __clone()
    {

    }

    /**
     * Initialize processor with WordPress.
     *
     */
    public static function init($plugin_name, $plugin_slug, $plugin_id, $plugin_version, $api_url)
    {
        if (self::$instance === null) {
            self::$instance = new self($plugin_name, $plugin_slug, $plugin_id, $plugin_version, $api_url);
        }

        // Custom action for updater initialization
        do_action('plubo/updater_init');

        return self::$instance;
    }

}
