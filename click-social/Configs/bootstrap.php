<?php

use Smashballoon\ClickSocial\App\Core\AdminRoute;

if (!defined('ABSPATH')) {
	exit;
}

if (! function_exists('sbcs_get_current_user_roles')) {
	function sbcs_get_current_user_roles()
	{
		if (is_user_logged_in()) {
			$user  = wp_get_current_user();
			$roles = (array) $user->roles;

			return array_values($roles);
		} else {
			return [];
		}
	}
}

if (! function_exists('sbcs_get_config')) {
	/**
	 * get configs.
	 *
	 * @param string $name - plugin name.
	 *
	 * @return mixed
	 */
	function sbcs_get_config($name = '')
	{
		$configs = require SBCS_DIR_PATH . '/Configs/config.php';

		if ($name === '') {
			return $configs;
		}

		// Checks to see if the config name is in an environment variable
		// and its value is non-null in environment variable and if so,
		// returns that value.
		$env_value = sbcs_get_env($name, null);
		if ($env_value !== null) {
			return $env_value;
		}

		$keys = explode('.', $name);
		return sbcs_parse_config_by_keys($keys, $configs);
	}
}

if (! function_exists('sbcs_parse_config_by_keys')) {
	/**
	 * Parse config by keys. Do not use directly. Use `sbcs_get_config` function instead.
	 *
	 * @param array $keys Keys of the config array.
	 * @param array $configs Configs.
	 *
	 * @return mixed
	 */
	function sbcs_parse_config_by_keys($keys, $configs)
	{
		if (!$keys) {
			return $configs;
		}

		foreach ($keys as $key) {
			if (!isset($configs[$key])) {
				return false;
			}

			$configs = $configs[$key];
		}

		return $configs;
	}
}

if (! function_exists('sbcs_prefix')) {
	/**
	 * Add prefix for the given string.
	 *
	 * @param string $name - plugin name.
	 *
	 * @return string
	 */
	function sbcs_prefix($name)
	{
		return sbcs_get_config('plugin_slug') . "-" . $name;
	}
}

if (! function_exists('sbcs_url')) {
	/**
	 * Add prefix for the given string.
	 *
	 * @param  string $path
	 *
	 * @return string
	 */
	function sbcs_url($path)
	{
		return SBCS_PLUGIN_URL . $path;
	}
}

if (! function_exists('sbcs_asset_url')) {
	/**
	 * Add prefix for the given string.
	 *
	 * @param  string $path
	 *
	 * @return string
	 */
	function sbcs_asset_url($path)
	{
		return sbcs_url("public" . $path);
	}
}

if (! function_exists('sbcs_wp_ajax')) {
	/**
	 * Wrapper function for wp_ajax_* and wp_ajax_nopriv_*
	 *
	 * @param  string $action - action name
	 * @param string $callback - callback method name
	 * @param bool   $public - is this a public ajax action
	 *
	 * @return mixed
	 */
	function sbcs_wp_ajax($action, $callback, $public = false)
	{
		add_action('wp_ajax_' . $action, $callback);
		if ($public) {
			add_action('wp_ajax_nopriv_' . $action, $callback);
		}
	}
}

if (! function_exists('sbcs_render_template')) {
	/**
	 * Require a Template file.
	 *
	 * @param  string $file_path
	 * @param array  $data
	 *
	 * @return mixed
	 *
	 * @throws \Exception - if file not found throw exception
	 * @throws \Exception - if data is not array throw exception
	 */
	function sbcs_render_template($file_path, $data = array())
	{
		$file = SBCS_DIR_PATH . "app" . $file_path;
		if (! file_exists($file)) {
			throw new \Exception("File not found");
		}
		if (! is_array($data)) {
			throw new \Exception("Expected array as data");
		}
		extract( $data, EXTR_PREFIX_SAME, sbcs_get_config('plugin_prefix') );	// @phpcs:ignore

		return require_once $file;
	}
}

if (! function_exists('sbcs_render_view_template')) {
	/**
	 * Require a View template file.
	 *
	 * @param  string $file_path
	 * @param array  $data
	 *
	 * @return mixed
	 */
	function sbcs_render_view_template($file_path, $data = array())
	{
		return sbcs_render_template("/Views" . $file_path, $data);
	}
}

if (! function_exists('sbcs_is_click_social_page')) {
	function sbcs_is_click_social_page()
	{
		return strpos(filter_input(INPUT_GET, 'page') ?? '', 'click-social') !== false;
	}
}

if (! function_exists('sbcs_getTimezone')) {
	/**
	 * Retrieve WordPress timezone.
	 *
	 * @return string
	 */
	function sbcs_getTimezone()
	{
		if (! function_exists('get_option')) {
			return '';
		}

		$timezone_string = get_option('timezone_string');
		if ($timezone_string) {
			return $timezone_string;
		}

		$offset = (float) get_option('gmt_offset');

		$hours     = (int) $offset;
		$minutes   = ($offset - $hours);
		$sign      = ($offset < 0) ? '-' : '+';
		$abs_hour  = abs($hours);
		$abs_mins  = abs($minutes * 60);
		$tz_offset = sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);

		return $tz_offset;
	}
}

if (! function_exists('sbcs_admin_route')) {
	/**
	 * Get admin route.
	 *
	 * @param string $page Page.
	 * @param string $subpage Subpage.
	 *
	 * @return string
	 */
	function sbcs_admin_route($page = '', $subpage = '')
	{
		$adminRoute = new AdminRoute();

		$params = [];

		$params['page'] = $adminRoute->getPrefixedPage($page);

		if (! empty($subpage)) {
			$params['subpage'] = $adminRoute->convertSubpage($subpage);
		}

		return add_query_arg(
			$params,
			get_admin_url() . 'admin.php'
		);
	}
}

if (! function_exists('sbcs_get_env')) {
	/**
	 * Get environment variable.
	 *
	 * @param string $key Key.
	 * @param mixed $default Default.
	 *
	 * @return mixed
	 */
	function sbcs_get_env($key = '', $default = false)
	{
		if (! class_exists('\Dotenv\Dotenv') || !file_exists(SBCS_DIR_PATH . '.env')) {
			return $default;
		}

		$dotenv = \Dotenv\Dotenv::createImmutable(SBCS_DIR_PATH, '.env');
		$dotenv->load();

		if (! $key) {
			return $_ENV;
		}

		return isset($_ENV[$key]) ? sanitize_text_field($_ENV[$key]) : $default;
	}
}

if (! function_exists('sbcs_base64url_decode')) {
	/**
	 * Decode data from Base64URL
	 *
	 * @param string $data
	 * @param bool $strict
	 *
	 * @return bool|string
	 */
	function sbcs_base64url_decode($data, $strict = false)
	{
		// Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
		$b64 = strtr($data, '-_', '+/');

		// Decode Base64 string and return the original data
		return base64_decode($b64, $strict);
	}
}

if (! function_exists('sbcs_base64url_encode')) {
	/**
	 * Encode data to Base64URL
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	function sbcs_base64url_encode($data)
	{
		// Encode the data to standard Base64
		$base64 = base64_encode($data);

		// Convert Base64 to Base64URL by replacing '+' with '-' and '/' with '_'
		$base64url = strtr($base64, '+/', '-_');

		// Remove any trailing '=' padding for a 'cleaner' Base64URL representation
		return rtrim($base64url, '=');
	}
}

if (! function_exists('sbcs_sanitize_data')) {

	/**
	 * Sanitize data, including nested arrays.
	 *
	 * @param $data
	 *
	 * @return array|bool|float|int|mixed|string
	 */
	function sbcs_sanitize_data($data)
	{
		if (is_array($data)) {
			// Recursively sanitize each element of the array.
			return array_map('sbcs_sanitize_data', $data);
		}

		if (is_string($data)) {
			// Sanitize string fields.
			return sanitize_textarea_field($data);
		}

		return $data;
	}

}

if (! function_exists('sbcs_get_onboarding_data')) {
	/**
	 * Get current user data for onboarding.
	 *
	 * @return array|null
	 */
	function sbcs_get_onboarding_data()
	{
		$current_user = wp_get_current_user();

		if ($current_user === null) {
			return null;
		}

		return [
			'email'        => $current_user->user_email,
			'first_name'   => $current_user->first_name,
			'last_name'    => $current_user->last_name,
		];
	}
}

if (! function_exists('sbcs_get_environment_links')) {

	function sbcs_get_environment_links($links)
	{
		if (!sbcs_get_env('SITE_URL')) {
			return $links;
		}

		$site_url = rtrim(sbcs_get_env('SITE_URL'), '/');

		return array_map(function ($link) use ($site_url) {
			return str_replace('https://clicksocial.com', $site_url, $link);
		}, $links);
	}

}

/**
 * Load plugin text domain for translations.
 *
 * @since 1.0.0
 */
if (!function_exists('sbcsLoadPluginTextdomain')) {
	function sbcsLoadPluginTextdomain()
	{
		load_plugin_textdomain(
			'click-social',
			false,
			dirname(plugin_basename(SBCS_FILE)) . '/languages'
		);
	}

	add_action('init', 'sbcsLoadPluginTextdomain');
}
