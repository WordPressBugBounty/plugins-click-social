<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) {
	exit;
}

class UserPermission
{
	public function handle()
	{
		$user_role = MemberTransaction::getUserRole();

		if (in_array($user_role, [WpUserRoles::SuperAdmin, WpUserRoles::Admin, WpUserRoles::Standard], true)) {
			return;
		}

		Inertia::render(
			'ErrorPage',
			[
				'errorTitle' => __('Permission Denied', 'click-social'),
				'errorMessage' => __('You do not have sufficient permissions to access this page.', 'click-social'),
			]
		);
		exit;
	}
}
