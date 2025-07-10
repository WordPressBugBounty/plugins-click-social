<?php

if (!defined('ABSPATH')) {
	exit;
}

return array (
	'plugin_prefix' => 'sbcs',
	'plugin_slug' => 'sbcs',
	'namaspace_root' => 'Smashballoon\ClickSocial',
	'plugin_version' => '1.3.1',
	'plugin_name' => 'ClickSocial - Social Media Scheduler & Poster',
	'dev_mode' => sbcs_get_env('DEV_MODE'),
	'root_dir' => dirname(__DIR__),
	'middlewares' => [
		'permission:super_admin' => Smashballoon\ClickSocial\App\Controllers\Middleware\SuperAdminPermission::class,
		'permission:admin' => Smashballoon\ClickSocial\App\Controllers\Middleware\AdminPermission::class,
		'permission:user' => Smashballoon\ClickSocial\App\Controllers\Middleware\UserPermission::class,
		'permission:mainMenu' => Smashballoon\ClickSocial\App\Controllers\Middleware\MenuPermission::class,
		'permission:subMenu' => Smashballoon\ClickSocial\App\Controllers\Middleware\SubMenuPermission::class,
		'permission:fullAccess' => Smashballoon\ClickSocial\App\Controllers\Middleware\FullAccessPermission::class,
		'verifyCsrfToken' => Smashballoon\ClickSocial\App\Controllers\Middleware\VerifyCsrfToken::class,
	],
	'globalMiddlewares' => [
		'verifyCsrfToken',
	],
	'features' => [
		// Override the default values from .env file.
		'posts_templates_composer' => false,
		'search' => false,
		'notifications' => true,
		'help_and_support' => true,
		'revival_posts' => true,
		'edit_profile' => false,
		'profile_image' => false,
		'onboarding' => true,
		'quick_share' => "/Workspace/QuickShare",    // false || "/Workspace/QuickShare"
		'quick_share_media' => true,
		'aggregated_calendar_view' => false,
		'post_comments' => true,
		'ai_prompt' => false,
	],
	'post' => [
		'max_length' => 2200, // To maximum limit accepted by X (Twitter), Facebook and Instagram.
	],
	'shortlink_id_key' => 'clicksocial',
	'links' => [
		'onboarding_learn_more_url' => 'https://clicksocial.com/plugin/onboarding',
		'contact_support_url' => 'https://clicksocial.com/plugin/contact-support',
		'documentation_url' => 'https://clicksocial.com/plugin/documentation',
		// phpcs:ignore Generic.Files.LineLength.TooLong -- The link comes with many parameters for tracking.
		'get_started_now_url' => 'https://clicksocial.com/register?utm_source=clicksocial-plugin&utm_medium=welcome-page&utm_campaign=simple-setup&utm_content=GetStartedForFree',
	],
	'menu_icon' =>
	// phpcs:ignore
		'data:image/svg+xml;base64,' . base64_encode('<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path id="logo" fill-rule="evenodd" clip-rule="evenodd" d="M8.50519 0.900391L4.5333 7.7799L6.32796 8.81605L10.2998 1.93654L8.50519 0.900391ZM4.05475 6.66631L4.57283 5.76899L2.77817 4.73284L2.2601 5.63017C0.352607 8.93404 1.4846 13.1587 4.78847 15.0662C8.09234 16.9737 12.317 15.8417 14.2245 12.5378L14.7425 11.6405L12.9479 10.6043L12.4298 11.5017C11.0946 13.8144 8.13732 14.6068 5.82461 13.2715C3.5119 11.9363 2.71951 8.97902 4.05475 6.66631ZM6.95473 9.17809L9.5451 4.69145L11.3398 5.72759L8.74939 10.2142L6.95473 9.17809ZM11.3532 7.33051L9.45357 10.6207L11.2482 11.6569L13.1478 8.36665L11.3532 7.33051Z" fill="white"/></svg>'),
	'redirect_routes' => [
		'support' => 'HelpSupport',
	],
	'migrations' => [
		// Let's remove this migration when users no longer using plugin version 1.1.0 or below.
		'1.1.0' => Smashballoon\ClickSocial\Database\Migrations\Versions\Version110Migration::class,
	]
);
