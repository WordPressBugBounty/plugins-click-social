<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\CalendarController;
use Smashballoon\ClickSocial\App\Controllers\NotificationController;
use Smashballoon\ClickSocial\App\Controllers\RevivePostsController;
use Smashballoon\ClickSocial\App\Core\AdminRoute;

AdminRoute::get('', [CalendarController::class, 'index'])
	->setCapability('manage_options')
	->middleware(['permission:mainMenu'])
	->addMenu(
		__('ClickSocial', 'click-social') . apply_filters('sbcs_env_tag', ''),
		__('ClickSocial', 'click-social') . apply_filters('sbcs_env_tag', '')
	);

AdminRoute::get('', [CalendarController::class, 'index'])
	->setCapability('manage_options')
	->middleware(['permission:subMenu'])
	->addSubmenu(
		__('Calendar', 'click-social'),
		__('Calendar', 'click-social')
	);

if (sbcs_get_config('features.revival_posts')) {
	AdminRoute::get('', [ RevivePostsController::class, 'index' ], '/Calendar/RevivePosts')
		->middleware(['permission:subMenu']);
	AdminRoute::post('', [ RevivePostsController::class, 'store' ], '/Calendar/RevivePosts')
		->middleware(['permission:subMenu']);
	AdminRoute::delete('', [ RevivePostsController::class, 'delete' ], '/Calendar/RevivePosts')
		 ->middleware(['permission:subMenu']);
	AdminRoute::get('', [ RevivePostsController::class, 'userSubscriptionLimit' ], '/Calendar/RevivePosts-UserLimits')
		 ->middleware(['permission:subMenu']);

	AdminRoute::post(
		'',
		[RevivePostsController::class, 'updatePost'],
		'/Calendar/RevivePosts/UpdatePost'
	)
	->middleware(['permission:subMenu']);

	AdminRoute::post(
		'',
		[RevivePostsController::class, 'deletePost'],
		'/Calendar/RevivePosts/DeletePost'
	)
		->middleware(['permission:subMenu']);

	AdminRoute::post(
		'',
		[RevivePostsController::class, 'approvePost'],
		'/Calendar/RevivePosts/ApprovePost'
	)
		->middleware(['permission:subMenu']);

	AdminRoute::post(
		'',
		[RevivePostsController::class, 'rejectPost'],
		'/Calendar/RevivePosts/RejectPost'
	)
		->middleware(['permission:subMenu']);
}

if (sbcs_get_config('features.notifications')) {
	AdminRoute::get('', [NotificationController::class, 'show'], '/Calendar/Notifications')
		->middleware(['permission:subMenu']);
}

AdminRoute::post(
	'',
	[CalendarController::class, 'storePost'],
	'/NewPost'
)
	->middleware(['permission:subMenu']);

AdminRoute::post(
	'',
	[CalendarController::class, 'storeTimeslots'],
	'/UpdateTimeslots'
)
	->middleware(['permission:subMenu']);

AdminRoute::delete('', [CalendarController::class, 'delete'])
	->middleware(['permission:subMenu']);

AdminRoute::post(
	'',
	[CalendarController::class, 'loadMore'],
	'/LoadMorePosts'
)
	->middleware(['permission:subMenu']);

AdminRoute::post(
	'',
	[CalendarController::class, 'calendarPosts'],
	'/CalendarPosts'
)
	->middleware(['permission:subMenu']);

AdminRoute::post(
	'',
	[CalendarController::class, 'updatePost'],
	'/UpdatePost'
)
	->middleware(['permission:subMenu']);
