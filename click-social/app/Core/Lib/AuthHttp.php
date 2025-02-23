<?php

/**
 * Use this class to make authenticated HTTP requests to the API,
 * only after the plugin connected the current site to the API. The payload will be converted to JSON.
 */

namespace Smashballoon\ClickSocial\App\Core\Lib;

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) {
	exit;
}

class AuthHttp extends Http
{
	protected static $response;

	public static function getAuthHeaders()
	{
		$access_token = MemberTransaction::getMemberData()['access_token'] ?? '';

		$default = [
			'headers' => [
				'Content-Type'		=> 'application/json',
				'Accept'			=> 'application/json',
				'Authorization'		=> 'Bearer ' . SettingsManager::getInstance()->get('api_key'),
				'X-Site-Key'		=> SettingsManager::getInstance()->get('site_key'),
				'X-Access-Token'	=> $access_token,
			],
		];

		return $default;
	}

	public static function post($route, $post_body = [], $get_params = [], $args = [])
	{
		return static::$response = parent::post($route, array_merge(
			static::getAuthHeaders(),
			$args,
			[
				'body' => trim(wp_json_encode($post_body))
			]
		), $get_params);
	}

	public static function get($route, $get_params = [], $args = [])
	{
		return static::$response = parent::get($route, array_merge(
			static::getAuthHeaders(),
			$args
		), $get_params);
	}

	public static function put($route, $post_body = [], $get_params = [], $args = [])
	{
		return static::$response = parent::put($route, array_merge(
			static::getAuthHeaders(),
			$args,
			[
				'body' => trim(wp_json_encode($post_body))
			]
		), $get_params);
	}

	public static function delete($route, $post_body = [], $get_params = [], $args = [])
	{
		return static::$response = parent::delete($route, array_merge(
			static::getAuthHeaders(),
			$args
		), $get_params);
	}

	public static function isBrokenConnection()
	{
		if (static::$response && static::$response->getStatusCode() === 401) {
			return static::$response->getStatusCode();
		}
		if (! static::$response) {
			//return AuthHttp::get('user')->getStatusCode();
		}

		return false;
	}
}
