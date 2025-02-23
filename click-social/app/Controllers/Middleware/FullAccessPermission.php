<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Enums\WpUserCapabilities;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

if (! defined('ABSPATH')) exit;

/**
 * Full access capability check for standard users.
 *
 */
class FullAccessPermission
{
	protected $request;

	public function handle($request)
	{
		$this->request = $request;
		$memberData = MemberTransaction::getMemberData();

		if (
			WpUserRoles::SuperAdmin === $memberData['role'] ||
			WpUserRoles::Admin === $memberData['role']
		) {
			return;
		}

		if (WpUserRoles::Standard === $memberData['role']) {
			$cap = MemberTransaction::getUserCapabilityForSocialAccount($this->request->input('social_account_uuid'));

			if ($cap === WpUserCapabilities::FullAccess) {
				return;
			}
		}

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
