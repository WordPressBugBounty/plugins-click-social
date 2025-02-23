<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\HelpSupportController;
use Smashballoon\ClickSocial\App\Core\AdminRoute;
use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

$isConnected = (new AccountManager())->isConnected();
$wp_user = wp_get_current_user();
$memberData = MemberTransaction::getMemberData($wp_user->ID);

if (! $isConnected) {
	/** Onboarding screens */
	require_once SBCS_DIR_PATH . '/routes/admin/public.php';
}

if ($isConnected) {
	/** Calendar page */
	require_once SBCS_DIR_PATH . '/routes/admin/calendar.php';
	require_once SBCS_DIR_PATH . '/routes/admin/post.php';

	/** Settings page */
	if (
		isset($memberData['role']) &&
		(WpUserRoles::Admin === $memberData['role'] || WpUserRoles::SuperAdmin === $memberData['role'])
	) {
		// TODO: /routes/settings.php should be added here after the onboarding pages are done.
		require_once SBCS_DIR_PATH . '/routes/admin/settings.php';
	}
}

require_once SBCS_DIR_PATH . '/routes/admin/onboarding.php';

/** Help page. */
AdminRoute::get('HelpSupport', [HelpSupportController::class, 'index'])
	->setCapability('manage_options')
	->middleware(['permission:subMenu'])
	->addSubmenu(
		__('Support', 'click-social'),
		__('Support', 'click-social')
	);

// TODO: add ability to add multiple subpages on the same page!
