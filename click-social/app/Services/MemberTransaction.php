<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class MemberTransaction
{
	use SingleTon;

	protected static $member_key = 'clicksocial_member';

	public static function hasMemberAccessToken()
	{
		$memberData = static::getMemberData();

		return !empty($memberData['access_token']);
	}

	public static function getMemberData($user_id = null, $withAccessToken = true)
	{
		if (! $user_id) {
			$user_id = get_current_user_id();
		}

		if (empty($user_id)) {
			return [];
		}

		$data = get_user_meta($user_id, static::$member_key, true);
		if (empty($data)) {
			$data = [];
		}

		if (! empty($data) && ! $withAccessToken) {
			unset($data['access_token']);
		}

		return $data;
	}

	public static function getUserRole($user_id = null)
	{
		$memberData = self::getMemberData($user_id);
		if (empty($memberData['role'])) {
			return false;
		}

		return $memberData['role'];
	}

	public static function getUserCapabilityForSocialAccount($social_account_uuid, $user_id = null)
	{
		$memberData = self::getMemberData($user_id);
		if (empty($memberData['social_accounts'])) {
			return false;
		}

		foreach ($memberData['social_accounts'] as $social_account) {
			if ($social_account['social_account_uuid'] === $social_account_uuid) {
				return $social_account['capability'];
			}
		}

		return false;
	}

	public static function store($memberData, $user_id = null)
	{
		if (empty($user_id)) {
			$user_id = get_current_user_id();
		}

		update_user_meta($user_id, static::$member_key, $memberData);
	}

	public static function updatePermissions($user_id, $memberData)
	{
		update_user_meta($user_id, static::$member_key, $memberData);
	}

	public static function addRole($user_id, $role)
	{
		$memberData = self::getMemberData($user_id);

		$memberData['role'] = $role;
		self::updatePermissions($user_id, $memberData);
	}

	public static function removeRole($user_id)
	{
		$memberData = self::getMemberData($user_id);
		if (! isset($memberData['role'])) {
			return false;
		}

		unset($memberData['role']);

		self::updatePermissions($user_id, $memberData);
	}

	public static function addCapability($user_id, $permission)
	{
		$memberData = self::getMemberData($user_id);

		if (empty($memberData['social_accounts']) || ! is_array($memberData['social_accounts'])) {
			$memberData['social_accounts'] = [];
		}

		$memberData['social_accounts'][] = $permission;
		self::updatePermissions($user_id, $memberData);
	}

	public static function updateCapability($user_id, $permission)
	{
		$memberData = self::getMemberData($user_id);

		if (empty($memberData['social_accounts']) || ! is_array($memberData['social_accounts'])) {
			$memberData['social_accounts'] = [];
		}

		if (count($memberData['social_accounts']) > 0) {
			foreach ($memberData['social_accounts'] as $key => $value) {
				if ($permission['id'] === $value['id']) {
					$value['capability'] = $permission['capability'];
					$memberData['social_accounts'][$key] = $value;
					break;
				}
			}
		}

		self::updatePermissions($user_id, $memberData);
	}

	public static function removeCapability($user_id, $permission)
	{
		$memberData = self::getMemberData($user_id);
		if (empty($memberData['social_accounts']) || ! is_array($memberData['social_accounts'])) {
			return false;
		}

		if (count($memberData['social_accounts']) > 0) {
			foreach ($memberData['social_accounts'] as $key => $value) {
				if ($permission['id'] === $value['id']) {
					unset($memberData['social_accounts'][$key]);
					break;
				}
			}
		}

		self::updatePermissions($user_id, $memberData);
	}
}
