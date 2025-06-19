<?php

namespace Smashballoon\ClickSocial\App\Core\Lib;

use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (!defined('ABSPATH')) {
	exit;
}

class Http
{
	/**
	 * Base URL.
	 *
	 * @var string
	 */
	private static $base_url  = SBCS_SITE_APP_URL;
	private static $namespace = '';
	private static $version   = 'v1/';

	/**
	 * Create API URL with Base URL, Namespace, Version and Route
	 *
	 * @param string $route
	 * @return string
	 */
	private static function get_url($route, $get_params = [])
	{
		$url = static::$base_url . static::$namespace . static::$version . $route;

		if ($get_params) {
			return $url . '?' . http_build_query($get_params);
		}

		return $url;
	}

	/**
	 * Get the default arguments for wp_remote_... requests.
	 *
	 * @return array
	 */
	private static function getDefaultArguments()
	{
		return [
			'timeout' => 30,
		];
	}

	/**
	 * POST request
	 *
	 * @param (string) $route
	 * @param (array)  $args
	 * @param (array)  $get_params
	 * @return Response
	 */
	public static function post($route, $args = [], $get_params = [])
	{
		$default   = static::getDefaultArguments();
		$arguments = array_merge($default, $args);
		$url       = static::get_url($route, $get_params);

		if (\sbcs_get_config('dev_mode') === 'true') {
			$res = \wp_remote_post($url, $arguments);
		} else {
			$res = \wp_safe_remote_post($url, $arguments);
		}

		return new Response($res);
	}

	/**
	 * GET request
	 *
	 * @param (string) $route
	 * @param (array)  $args
	 * @return Response
	 */
	public static function get($route, $args = [], $get_params = [])
	{
		$default   = static::getDefaultArguments();
		$args      = is_array($args) ? $args : [];
		$arguments = array_merge($default, $args);
		$url       = static::get_url($route, $get_params);

		if (\sbcs_get_config('dev_mode') === 'true') {
			$res = \wp_remote_get($url, $arguments);
		} else {
			$res = \wp_safe_remote_get($url, $arguments);
		}

		return new Response($res);
	}

	/**
	 * PUT request
	 *
	 * @param (string) $route
	 * @param (array)  $args
	 * @param (array)  $get_params
	 * @return Response
	 */
	public static function put($route, $args = [], $get_params = [])
	{
		$default   = static::getDefaultArguments();
		$arguments = array_merge($default, $args);
		$url       = static::get_url($route, $get_params);

		if (\sbcs_get_config('dev_mode') === 'true') {
			$res = \wp_remote_post($url, $arguments);
		} else {
			$res = \wp_safe_remote_post($url, $arguments);
		}

		return new Response($res);
	}

	/**
	 * DELETE request
	 *
	 * @param (string) $route
	 * @param (array)  $args
	 * @return Response
	 */
	public static function delete($route, $args = [], $get_params = [])
	{
		$default   = static::getDefaultArguments();
		$arguments = array_merge($default, $args);
		$url       = static::get_url($route, $get_params);

		if (\sbcs_get_config('dev_mode') === 'true') {
			$res = \wp_remote_post($url, $arguments);
		} else {
			$res = \wp_safe_remote_post($url, $arguments);
		}

		return new Response($res);
	}
}
