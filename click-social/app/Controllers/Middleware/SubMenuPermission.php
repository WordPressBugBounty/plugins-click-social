<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) exit;

class SubMenuPermission
{
	public static function handle($request)
	{
		$route = $request->get('route');

		// Prevent showing public submenus to users who don't have the capability.
		// This condition is meant to exclude `subscriber` role users.
		if (static::isPublicSubMenus($route) && current_user_can('edit_posts')) {
			return $request->merge(['capability' => static::getUserCap()]);
		}

		$connected = AccountManager::getInstance()->isConnected();
		if (! $connected) {
			return $request->merge(['capability' => false]);
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
			return $request->merge(['capability' => false]);
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

	protected static function isPublicSubMenus($route)
	{
		$routes = [
			'click-social-HelpSupport',
		];

		return in_array($route, $routes);
	}
}
