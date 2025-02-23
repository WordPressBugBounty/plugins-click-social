<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) exit;

class SuperAdminPermission
{
	public function handle($request)
	{
		$user_role = MemberTransaction::getUserRole();

		if (WpUserRoles::SuperAdmin !== $user_role) {
			// If user don't have above roles, capability
			// then show error message.
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
