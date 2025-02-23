<?php

namespace Smashballoon\ClickSocial\App\Core;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (!defined('ABSPATH')) {
	exit;
}

class AccountManager
{
	use SingleTon;

	public function __construct()
	{
	}

	/**
	 * Connect to the ClickSocial account and retrieve connection keys.
	 *
	 * @param string $apiKey API Key.
	 *
	 * @return mixed
	 */
	public function connect($apiKey)
	{
		$currentUser = wp_get_current_user();

		$payload = [
			'site_url' => get_site_url(),
			'user_id' => $currentUser->get('ID'),
			'username' => $currentUser->get('user_login'),
			'email' => $currentUser->get('user_email'),
			// Add site title.
			'title' => get_bloginfo('name'),
		];

		$headers = [
			'Authorization' => 'Bearer ' . $apiKey,
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'X-Requested-With' => 'XMLHttpRequest',
		];

		$response =
		Http::post('connect', [
			'headers' => $headers,
			'body' => trim(wp_json_encode($payload)),
		]);

		$responseBody = $response->getBody();

		$statusCode = $response->getStatusCode();
		if ($statusCode < 200 || $statusCode > 299) {
			// We have an error.
			return $responseBody['message'];
		}

		MemberTransaction::store([
			'access_token' => $responseBody['data']['access_token'] ?? '',
			'role' => WpUserRoles::SuperAdmin,
		]);
		SettingsManager::getInstance()->update('api_key', sanitize_text_field($apiKey));
		SettingsManager::getInstance()->update('site_key', sanitize_text_field($responseBody['data']['site_key']));

		return $responseBody['message'];
	}

	/**
	 * Disconnect from ClickSocial account.
	 *
	 * @return mixed
	 */
	public function disconnect()
	{
		$payload = [
			'site_key' => SettingsManager::getInstance()->get('site_key'),
		];

		$headers = [
			'Authorization' => 'Bearer ' . SettingsManager::getInstance()->get('api_key'),
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
		];

		Http::post('disconnect', [
			'headers' => $headers,
			'body' => trim(wp_json_encode($payload)),
		]);

		// Delete the settings regardless if the API is accessible or not.
		SettingsManager::getInstance()->delete('api_key');
		SettingsManager::getInstance()->delete('site_key');
		SettingsManager::getInstance()->delete('access_token');

		return true;
	}

	/**
	 * Is connected to ClickSocial account?
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return !empty(SettingsManager::getInstance()->get('api_key', ''));
	}

	// TODO remove?
	/**
	 * Store external WP Users ids in User Meta table.
	 *
	 * @return void
	 */
	public function storeExternalWPUsersIds()
	{
		$wpUsers = AuthHttp::get('wp-users')->getBody(true);
		foreach ($wpUsers['data'] as $wpUser) {
			update_user_meta($wpUser['wordpress_userid'], 'sbcs_wp_user_id', $wpUser['id']);
		}
	}

	/**
	 * Get external WP user id from user meta table.
	 *
	 * @return mixed
	 */
	public function getExternalWPUserId()
	{
		return get_user_meta(get_current_user_id(), 'sbcs_wp_user_id', true);
	}
}
