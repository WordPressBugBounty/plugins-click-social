<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Enums\WpUserCapabilities;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

class UserManagement extends BaseController
{
	use SingleTon;

	public function memberShow()
	{
		$wpUsers = $this->getUsers([
			'exclude_wp_superadmin' => 0
		]);

		if (empty($wpUsers)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$wp_users = $wpUsers;

		$wp_users = array_values($wp_users);

		return $this->render('Settings/Workspace/Members', [
			'wpUsers' => $wp_users,
		]);
	}

	public function addMember($request)
	{
		$email = $request->input('wpUser')['email'] ?? false;
		$wpUser = get_user_by('email', $email);

		if (!$wpUser || !$email) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$payload = [
			'username' => $wpUser->user_login,
			'email' => $wpUser->user_email,
			'wpuser_id' => $wpUser->ID,
			'role' => 'standard',
		];

		$response =
			AuthHttp::post('wp-users', $payload);

		if (200 === $response->getStatusCode()) {
			$user = $response->getBody(true)['data'] ?? [];

			MemberTransaction::store(
				[
					'access_token' => $user['access_token'] ?? '',
					'role' => WpUserRoles::Standard,
				],
				$wpUser->ID
			);
		} elseif (429 === $response->getStatusCode()) {
			return $this->render('Settings/Workspace/Members', [
				'error_max_limit' => $response->getBody(true) ?? [],
			]);
		}

		$wpUsers = $this->getUsers(['exclude_wp_superadmin' => 0]);
		return $this->render('Settings/Workspace/Members', [
			'wpUsers' => $wpUsers,
		]);
	}

	public function getSingleUser($request)
	{
		$id = sanitize_text_field($request->input('id'));

		if (empty($id)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		return $this->render('Settings/Workspace/Members', [
			'selectedUser' => $this->getSingleUserById($id),
			'wpUserCaps' => WpUserCapabilities::list(),
		]);
	}

	public function getSingleUserById($id)
	{
		$response = AuthHttp::get('wp-users/' . $id);

		$data = [];
		if (200 === $response->getStatusCode()) {
			$data = $response->getBody(true)['data'] ?? [];
			$data['avatar'] = get_avatar_url($data['email'], ['size' => 32]);

			$new_user = get_user_by('id', $data['wordpress_userid']);
			// Process user data if user exists
			if ($new_user instanceof \WP_User) {
				$data['displayName'] = $new_user->display_name;
			}
		}

		// Get social accounts data
		$socialAccounts = AuthHttp::get('social-accounts')->getBody(true)['data'] ?: [];


		if (!empty($data) && !empty($data['permissions'])) {
			// Loop through each permission (social account) for this user
			foreach ($data['permissions'] as $index => $socialAccount) {
				// Filter social accounts to find match for this permission
				$socialAccountData = array_filter($socialAccounts, function ($account) use ($socialAccount) {
					return $account['uuid'] === $socialAccount['social_account_uuid'];
				});

				// Get first matching social account
				$socialAccountData = array_shift($socialAccountData);

				// Add social account data to user permissions
				$data['permissions'][$index]['social_network_data'] = $socialAccountData ?: '';
			}
		}

		return $data;
	}

	public function deleteUser($request)
	{
		$id = sanitize_text_field($request->input('id'));
		$wordpress_userid = sanitize_text_field($request->input('wordpress_userid'));

		if (empty($id)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$response =
			AuthHttp::post('wp-users/remove', ['uuid' => $id]);

		if (200 === $response->getStatusCode()) {
			MemberTransaction::removeRole($wordpress_userid);
		}

		Inertia::redirect('click-social-Settings', '/Workspace/Members');
	}

	public function updateUserRole($request)
	{
		$id = sanitize_text_field($request->input('id'));
		$role = sanitize_text_field($request->input('role'));
		$wp_user_id = sanitize_text_field($request->input('wordpress_userid'));

		if (empty($id) || empty($role)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$response =
			AuthHttp::post('wp-users/update', [
				'uuid' => $id,
				'role' => $role,
			]);

		if ($response->getStatusCode() === 200) {
			MemberTransaction::addRole($wp_user_id, $role);
		}

		Inertia::redirect('click-social-Settings', '/Workspace/Members');
	}

	public function addUserPermission($request)
	{
		$wpUserId = sanitize_text_field($request->input('uuid'));
		$accountId = sanitize_text_field($request->input('accountId'));
		$cap = sanitize_text_field($request->input('capability'));
		$wordpress_userid = sanitize_text_field($request->input('wordpress_userid'));

		if (empty($wpUserId) || empty($accountId) || empty($cap)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$response =
			AuthHttp::post('wp-users/permission', [
				'wpuser_uuid' => $wpUserId,
				'social_account_uuid' => $accountId,
				'capability' => $cap,
			]);

		if ($response->getStatusCode() === 200) {
			$data = $response->getBody(true)['data'];
			$permission_id = $data['uuid'];
			$social_account_uuid = $data['social_account_uuid'];

			MemberTransaction::addCapability($wordpress_userid, [
				'id' => $permission_id,
				'social_account_uuid' => $social_account_uuid,
				'wp_user_id' => $wpUserId,
				'capability' => $cap,
			]);
		}

		return $this->render('Settings/Workspace/Members', [
			'selectedUser' => $this->getSingleUserById($wpUserId),
			'wp_user_permission' => $response->getBody(true) ?? [],
			'wpUserCaps' => WpUserCapabilities::list(),
		]);
	}

	public function updateUserPermission($request)
	{
		$id = sanitize_text_field($request->input('id'));
		$cap = sanitize_text_field($request->input('capability'));
		$wp_user_id = sanitize_text_field($request->input('wordpress_userid'));
		$social_account_uuid = sanitize_text_field($request->input('social_account_uuid'));

		if (empty($id) || empty($cap)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$response =
			AuthHttp::post('wp-users/permission/update', [
				'uuid' => $id,
				'capability' => $cap,
				'social_account_uuid' => $social_account_uuid,
			]);

		if ($response->getStatusCode() === 200) {
			MemberTransaction::updateCapability($wp_user_id, [
				'id' => $id,
				'capability' => $cap,
			]);
		}

		return $this->render('Settings/Workspace/Members', [
			'userCapUpdated' => $response->getBody(true) ?? [],
			'wpUserCaps' => WpUserCapabilities::list(),
		]);
	}

	public function removeUserPermission($request)
	{
		$id = sanitize_text_field($request->input('id'));
		$wordpress_userid = sanitize_text_field($request->input('wordpress_userid'));
		$wp_user_id = sanitize_text_field($request->input('wp_user_id'));

		if (empty($id)) {
			return $this->render('Settings/Workspace/Members', []);
		}

		$response =
			AuthHttp::post('wp-users/permission/remove', [
				'uuid' => $id,
			]);

		if ($response->getStatusCode() === 200) {
			MemberTransaction::removeCapability($wordpress_userid, [
				'id' => $id,
			]);
		}

		return $this->render('Settings/Workspace/Members', [
			'selectedUser' => $this->getSingleUserById($wp_user_id),
			'wpUserCaps' => WpUserCapabilities::list(),
		]);
	}

	/**
	 * Retrieves and enriches user data with WordPress profile information.
	 *
	 * Fetches users through the authentication HTTP service and enhances the data
	 * with WordPress-specific user information like avatar URLs and display names.
	 *
	 * @param array $args Query arguments for filtering users
	 * @return array Enhanced user data array containing profile information
	 * @since 1.0.0
	 *
	 */
	public function getUsers(array $args = []): array
	{
		// Fetch users through authentication service
		$response = AuthHttp::get(
			'wp-users',
			$args
		);

		// Get response body or default to empty array
		$wp_users = $response->getBody(true) ?? [];

		// Return early if no valid user data is present
		if (!isset($wp_users['data']) || !is_array($wp_users['data'])) {
			return $wp_users;
		}

		// Extract user data from response
		$wp_users = $wp_users['data'];

		// Enhance each user with WordPress profile information
		foreach ($wp_users as $key => $wp_user) {
			// Get WordPress user object
			$user = get_user_by('ID', $wp_user['wordpress_userid']);

			// Add profile image URL
			$wp_users[$key]['profileUrl'] = get_avatar_url(
				$wp_user['email'],
				['size' => 32]
			);


			// Add user display name
			$wp_users[$key]['displayName'] = ! empty($user->display_name) ? $user->display_name : '';
		}

		return $wp_users;
	}
}
