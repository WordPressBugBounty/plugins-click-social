<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) {
	exit;
}

use pq\Connection;
use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Core\CredentialCloak;
use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\Request;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Enums\Connectors;
use Smashballoon\ClickSocial\App\Enums\WpUserCapabilities;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\InertiaHeaders;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

class SettingsController extends BaseController
{
	/**
	 * Index page.
	 *
	 * @return string
	 */

	public $connectors;

	public function __construct()
	{
		$returnURL = sbcs_admin_route('Settings', '/Workspace/ConnectedAccounts');
		$accountManager = new AccountManager();
		$identifier = base64_encode(wp_json_encode([
			'id' => $accountManager->getExternalWPUserId(),
			'wordpress_userid' => get_current_user_id(),
			'accessToken' => md5(SettingsManager::getInstance()->get('access_token') ?? ''),
		]));

		$queryParams = 'return_url=' . urlencode($returnURL) . '&hash=' . urlencode($identifier);

		$this->connectors = Connectors::list($queryParams);
	}

	public function index()
	{
		return Inertia::redirect('click-social-Settings', '/Workspace/Members');
	}

	/**
	 * Renders the account profile page for the current user.
	 *
	 * This method fetches the current user's data and prepares it for display
	 * in the profile view. It handles user data retrieval and formatting
	 * before passing it to the profile template.
	 *
	 * @return string Rendered HTML content of the profile page
	 * @throws \WP_Error If user retrieval fails
	 * @since 1.0.0
	 *
	 */
	public function accountProfile()
	{
		// Get current user data from WordPress
		$current_user = get_user_by('id', get_current_user_id());

		// Initialize empty user data array
		$user_data = [];

		// Process user data if user exists
		if ($current_user instanceof \WP_User) {
			$user_data = [
				'display_name' => $current_user->display_name,
				'user_email' => $current_user->user_email,
				'wp_user_id' => $current_user->ID,
				'avatar' => get_avatar_url(
					$current_user->user_email,
					['size' => 80]
				)
			];
		}

		// Render the profile template with user data
		return $this->render('Settings/Account/Profile', [
			'selectedUser' => $user_data,
		]);
	}

	/**
	 * Account billing page.
	 *
	 * @return string
	 */
	public function accountBilling()
	{
		$billingResponse = AuthHttp::get('user/billing')->getBody(true) ?? [];

		return $this->render('Settings/Account/Billing', $billingResponse['data'] ?? []);
	}

	/**
	 * Account connection page.
	 *
	 * @param Request $request Request.
	 *
	 * @return string
	 */
	public function accountConnection($request)
	{
		$additionalPayload = [];
		if ($request->method() === 'POST') {
			$accountManager = new AccountManager();

			switch ($request->input('action')) {
				case 'connect':
					$apiKey = $request->input('apiKey');
					if (CredentialCloak::isCloaked($apiKey)) {
						$apiKey = SettingsManager::getInstance()->get('api_key', '');
					}

					$accountManager->connect($apiKey);
					$accountManager->storeExternalWPUsersIds();

					if (!$accountManager->isConnected()) {
						$additionalPayload = [
							'submission' => [
								'responses' => [
									'apiKey' => [
										'status_code' => 400,
										'message' => 'Invalid API Key',
									],
								],
							],
						];
					} else {
						$additionalPayload = [
							'submission' => [
								'responses' => [
									'apiKey' => [
										'status_code' => 200,
										'message' => 'Site connected successfully!',
									],
								],
							],
						];
					}
					break;
				case 'disconnect':
					$accountManager->disconnect();

					Inertia::redirect('click-social');
					exit;

					break;
				default:
					break;
			}
		}

		return $this->render(
			'Settings/Account/Connection',
			array_merge(
				[
					'apiKey' => CredentialCloak::cloak(SettingsManager::getInstance()->get('api_key', '')),
				],
				$additionalPayload
			)
		);
	}

	/**
	 * Workspace Connected Accounts page.
	 *
	 * @param Request $request Request.
	 *
	 * @return string
	 */
	public function workspaceConnectedAccounts($request)
	{
		$additionalPayload = [];

		$additionalPayload['connectors'] = $this->connectors;

		$social_accounts_res = AuthHttp::get('social-accounts');

		$additionalPayload['social_accounts'] = $social_accounts_res->getBody(true)['data'] ?? [];

		return $this->render('Settings/Workspace/ConnectedAccounts', $additionalPayload);
	}

	public function deleteSocialAccount($request)
	{
		$deleteRequest = AuthHttp::post(
			'social-accounts/remove',
			['id' => $request->input('id')]
		);

		$additionalPayload['submission']['responses']['posts'] = $deleteRequest->getBody(true);
		$additionalPayload['submission']['responses']['posts']['status_code'] = $deleteRequest->getStatusCode();

		return $this->workspaceConnectedAccounts($request);
	}

	/**
	 * Workspace Connected Accounts page.
	 *
	 * @param Request $request Request.
	 *
	 * @return string
	 */
	public function workspaceConnectedAccountsDelete($request)
	{
		$additionalPayload = [];

		$deleteRequest = AuthHttp::post(
			'social-accounts/remove',
			['uuid' => $request->input('uuid')]
		);

		$additionalPayload['submission']['responses']['posts'] = $deleteRequest->getBody(true);
		$additionalPayload['submission']['responses']['posts']['status_code'] = $deleteRequest->getStatusCode();
		$additionalPayload['connectors'] = $this->connectors;
		$social_accounts_res = AuthHttp::get('social-accounts');
		$additionalPayload['social_accounts'] = $social_accounts_res->getBody(true)['data'] ?? [];

		return $this->render('Settings/Workspace/ConnectedAccounts', $additionalPayload);
	}

	/**
	 * Workspace Timezone page.
	 *
	 * @param Request $request Request.
	 *
	 * @return string
	 */
	public function workspaceTimezone($request)
	{
		$additionalPayload = [];

		if ($request->method() === 'POST') {
			$message = 'We encountered an error and we cannot save the timezone. Please try again later.';
			$status = 500;
			if (SettingsManager::getInstance()->update('timezone_source', $request->input('timezone_source'))) {
				$message = 'Timezone was updated!';
				$status = 200;
			}

			$additionalPayload['submission']['responses']['posts']['message'] = $message;
			$additionalPayload['submission']['responses']['posts']['status_code'] = $status;
			$additionalPayload['submission']['responses']['posts']['v'] = wp_rand(1, 9999);
		}

		$additionalPayload['account_timezone'] = AuthHttp::get('user')->getBody(true)['data']['timezone'] ?? '';

		$additionalPayload['timezone_source'] = SettingsManager::getInstance()->get('timezone_source', 'account');

		return $this->render('Settings/Workspace/Timezone', $additionalPayload);
	}

	/**
	 * Workspace Quick Share page.
	 *
	 * @param Request $request Request.
	 *
	 * @return string
	 */
	public function workspaceQuickShare($request)
	{
		$additionalPayload = [];

		if ($request->method() === 'DELETE') {
			$deleteRequest = AuthHttp::post('social-accounts/remove', ['id' => $request->input('id')]);

			$additionalPayload['submission']['responses']['posts'] = $deleteRequest->getBody(true);
			$additionalPayload['submission']['responses']['posts']['status_code'] = $deleteRequest->getStatusCode();
		}

		$social_accounts_demo = AuthHttp::get('social-accounts')->getBody(true) ?: [];

		$additionalPayload['social_accounts'] = $social_accounts_demo;

		return $this->render('Settings/Workspace/QuickShare', $additionalPayload);
	}

	/**
	 * Workspace Connected Accounts Add page.
	 *
	 * @return string
	 */
	public function workspaceConnectedAccountsAdd()
	{
		return $this->render('Settings/Workspace/ConnectedAccounts/Add', [
			'connectors' => $this->connectors
		]);
	}

	/**
	 * Workspace Members page.
	 *
	 * @return string
	 */
	public function workspaceMembers()
	{
		$wpUsers = (UserManagement::getInstance())->getUsers([
			'exclude_wp_superadmin' => 0
		]);

		return $this->render('Settings/Workspace/Members', [
			'wpUsers' => $wpUsers,
			'wpUserCaps' => WpUserCapabilities::list(),
		]);
	}

	/**
	 * Handle Advanced Settings page.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function workspaceAdvanced($request)
	{
		if ($request->isMethod('POST')) {
			$disable_shortlink = (bool)$request->input('disable_shortlink', false);
			SettingsManager::getInstance()->update('disable_shortlink', $disable_shortlink);
		}

		return Inertia::render('Settings/Workspace/Advanced', [
			'disable_shortlink' => SettingsManager::getInstance()->get('disable_shortlink', false),
		]);
	}
}
