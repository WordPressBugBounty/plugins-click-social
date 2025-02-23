<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) exit;

class AdminPermission
{
	public function handle($request)
	{
		$user_role = MemberTransaction::getUserRole();

		if (WpUserRoles::SuperAdmin === $user_role) {
			return;
		}
		if (WpUserRoles::Admin !== $user_role) {
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
}
