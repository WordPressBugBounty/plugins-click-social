<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) exit;

class MenuPermission
{
	public static function handle($request)
	{
		$connected = AccountManager::getInstance()->isConnected();
		if (! $connected) {
			return;
		}

		$role = MemberTransaction::getUserRole();
		if (! $role) {
			return $request->merge(['capability' => false]);
		} elseif (WpUserRoles::SuperAdmin === $role) {
			return;
		}

		if (
			WpUserRoles::Standard === $role &&
			empty(MemberTransaction::getMemberData()['social_accounts'])
		) {
			$request->merge([
				'redirectRoute' => '-' . sbcs_get_config('redirect_routes')['support'] ?? '',
			]);
		}

		$request->merge(['capability' => static::getUserCap()]);
	}

	protected static function getUserCap()
	{
		$user_id = get_current_user_id();
		$user_roles = get_userdata($user_id);
		$cap = array_keys($user_roles->allcaps);

		return $cap[0] ?? false;
	}
}
