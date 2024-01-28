<?php

/**
 * @wordpress-plugin
 * Plugin Name:       PLUBO Updater
 * Plugin URI:        https://sirvelia.com/
 * Description:       Manage premium plugin updates.
 * Version:           1.0.0
 * Author:            Joan Rodas - Sirvelia
 * Author URI:        https://sirvelia.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       plubo-updater
 * Domain Path:       /languages
 */

define('PLUBO_UPDATER_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once PLUBO_UPDATER_PLUGIN_DIR . 'vendor/autoload.php';

$plugin_name = '';
$plugin_id = '';
$plugin_slug = '';
$version = '';
$api_url = '';

PluboUpdater\UpdaterProcessor::init(
    $plugin_name,
    $plugin_id,
    $plugin_slug,
    $version,
    $api_url
);