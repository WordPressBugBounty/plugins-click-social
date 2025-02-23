<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\PostController;
use Smashballoon\ClickSocial\App\Core\AdminRoute;

AdminRoute::post(
	'',
	[PostController::class, 'approvePost'],
	'/ApprovePost'
)
->middleware(['permission:fullAccess']);

AdminRoute::post(
	'',
	[PostController::class, 'rejectPost'],
	'/RejectPost'
)
->middleware(['permission:fullAccess']);

AdminRoute::post(
	'',
	[PostController::class, 'deletePost'],
	'/DeletePost'
)
->middleware(['permission:fullAccess']);


// Post Comments Routs

if (sbcs_get_config('features.post_comments')) {
	AdminRoute::post(
		'',
		[PostController::class, 'singlePostWithComments'],
		'/PostComments'
	)
	->middleware(['permission:subMenu']);

	AdminRoute::post(
		'',
		[PostController::class, 'storePostComment'],
		'/PostCommentStore'
	)
	->middleware(['permission:subMenu']);
}
