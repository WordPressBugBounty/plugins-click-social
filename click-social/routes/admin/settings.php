<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\QuickShareController;
use Smashballoon\ClickSocial\App\Controllers\SettingsController;
use Smashballoon\ClickSocial\App\Controllers\SocialAccountController;
use Smashballoon\ClickSocial\App\Controllers\UserManagement;
use Smashballoon\ClickSocial\App\Core\AdminRoute;

AdminRoute::get('Settings', [SettingsController::class, 'index'])
	->setCapability('manage_options')
	->middleware(['permission:subMenu'])
	->addSubmenu(
		__('Settings', 'click-social'),
		__('Settings', 'click-social')
	);

AdminRoute::get(
	'Settings',
	[SettingsController::class, 'accountConnection'],
	'/Account/Connection'
)
	->middleware(['permission:super_admin']);

AdminRoute::post(
	'Settings',
	[SettingsController::class, 'accountConnection'],
	'/Account/Connection'
)
	->middleware(['permission:super_admin']);

AdminRoute::get(
	'Settings',
	[SettingsController::class, 'accountProfile'],
	'/Account/Profile'
)
	->middleware(['permission:user']);

AdminRoute::get(
	'Settings',
	[ SettingsController::class, 'accountBilling' ],
	'/Account/Billing'
)
->middleware(['permission:admin']);

AdminRoute::get(
	'Settings',
	[QuickShareController::class, 'show'],
	'/Workspace/QuickShare'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[QuickShareController::class, 'store'],
	'/Workspace/QuickShare/Store'
)
->middleware(['permission:admin']);

AdminRoute::get(
	'Settings',
	[SettingsController::class, 'workspaceTimezone'],
	'/Workspace/Timezone'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[SettingsController::class, 'workspaceTimezone'],
	'/Workspace/Timezone'
)
->middleware(['permission:admin']);

AdminRoute::get(
	'Settings',
	[SettingsController::class, 'workspaceConnectedAccounts'],
	'/Workspace/ConnectedAccounts'
)
	->middleware(['permission:super_admin']);

AdminRoute::get(
	'Settings',
	[SettingsController::class, 'workspaceConnectedAccountsAdd'],
	'/Workspace/ConnectedAccounts/Add'
)
	->middleware(['permission:super_admin']);

AdminRoute::post(
	'Settings',
	[SettingsController::class, 'workspaceConnectedAccountsDelete'],
	'/Workspace/ConnectedAccounts/DeleteAccount'
)
	->middleware(['permission:super_admin']);

AdminRoute::post(
	'Settings',
	[SettingsController::class, 'workspaceConnectedAccounts'],
	'/Workspace/ConnectedAccounts'
)
	->middleware(['permission:super_admin']);

AdminRoute::get(
	'Settings',
	[UserManagement::class, 'memberShow'],
	'/Workspace/Members'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'addMember'],
	'/Workspace/Members/AddMember'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'deleteUser'],
	'/Workspace/Members/DeleteMember'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'getSingleUser'],
	'/Workspace/Members/SingleMember'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[SocialAccountController::class, 'showAccountList'],
	'/Workspace/Members/SocialAccountList'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'addUserPermission'],
	'/Workspace/Members/Addpermission'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'updateUserPermission'],
	'/Workspace/Members/Updatepermission'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'removeUserPermission'],
	'/Workspace/Members/RemovePermission'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[UserManagement::class, 'updateUserRole'],
	'/Workspace/Members/RoleChange'
)
->middleware(['permission:admin']);

AdminRoute::get(
	'Settings',
	[SettingsController::class, 'workspaceAdvanced'],
	'/Workspace/Advanced'
)
->middleware(['permission:admin']);

AdminRoute::post(
	'Settings',
	[SettingsController::class, 'workspaceAdvanced'],
	'/Workspace/Advanced'
)
->middleware(['permission:admin']);

if (sbcs_get_config('features.ai_prompt') === true) {
	require_once SBCS_DIR_PATH . '/routes/admin/ai-prompt.php';
}
