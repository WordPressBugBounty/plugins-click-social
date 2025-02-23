<?php

namespace Smashballoon\ClickSocial\App\Controllers;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\SocialAccountService;

if (! defined('ABSPATH')) {
	exit;
}

class NotificationController extends BaseController
{
	use SingleTon;

	public function show($request)
	{
		$social_accounts = function () {
			return SocialAccountService::filterSocialAccountsForCurrentUser();
		};

		$notifications = AuthHttp::get('notifications', [
			'type' => 'post'
		]);

		Inertia::render('Calendar/Notifications', [
			'notifications'		=> $notifications->getBody(true)['data'] ?? false,
			'social_accounts'	=> $social_accounts,
		]);
	}

	public function getNotifications($request)
	{
		return AuthHttp::get('notifications', [
			'type' => 'post'
		]);
	}
}
