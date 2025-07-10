<?php

// phpcs:disable
/**
 * @package click-social
 *
 * Plugin Name: ClickSocial - Social Media Scheduler & Poster
 * Plugin URI: https://clicksocial.com
 * Description: Effortlessly manage your social media presence directly within WordPress. This plugin allows you to connect with the ClickSocial application available on clicksocial.com. After creating and connecting an account on clicksocial.com, you can create social media posts and schedule them right from the WordPress dashboard.
 * Version: 1.3.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Smash Balloon
 * Author URI: https://smashballoon.com
 * License: GPLv2 or later
 * Text Domain: click-social
 */
// phpcs:enable

if (! defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Core;

define('SBCS_FILE', __FILE__);
define('SBCS_DIR_PATH', plugin_dir_path(SBCS_FILE));
define('SBCS_PLUGIN_URL', plugins_url('/', SBCS_FILE));

if (file_exists(SBCS_DIR_PATH . '/vendor/autoload.php')) {
	require_once SBCS_DIR_PATH . '/vendor/autoload.php';
}
require_once SBCS_DIR_PATH . '/Configs/bootstrap.php';

$base_url = sbcs_get_env('API_BASE_URL', 'https://app.clicksocial.com/');
define('SBCS_SITE_APP_URL', apply_filters('sbcs_site_app_url', $base_url));

Core::getInstance();
