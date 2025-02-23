<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;

class SocialAccountService
{
	use SingleTon;

	public static function getSocialAccounts()
	{
		if (!MemberTransaction::hasMemberAccessToken()) {
			return null;
		}

		return AuthHttp::get('social-accounts');
	}

	public static function filterSocialAccountsForCurrentUser()
	{
		$res = self::getSocialAccounts();

		if (!$res) {
			return false;
		}

		if (200 !== $res->getStatusCode()) {
			return false;
		}

		$socialAccounts = $res->getBody(true)['data'] ?? false;
		if (empty($socialAccounts)) {
			return false;
		}

		$user_role = MemberTransaction::getUserRole();
		if (WpUserRoles::Standard !== $user_role) {
			return $socialAccounts;
		}

		$permissions = MemberTransaction::getMemberData();
		if (empty($permissions['social_accounts'])) {
			return false;
		}

		// standard user
		$filteredSocialAccounts = [];
		foreach ($socialAccounts as $socialAccount) {
			foreach ($permissions['social_accounts'] as $permission) {
				if ($socialAccount['uuid'] === $permission['social_account_uuid']) {
					$filteredSocialAccounts[] = $socialAccount;
				}
			}
		}

		return $filteredSocialAccounts;
	}
}
