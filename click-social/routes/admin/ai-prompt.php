<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\AiPromptController;
use Smashballoon\ClickSocial\App\Core\AdminRoute;

AdminRoute::get(
	'Settings',
	[AiPromptController::class, 'allPrompt'],
	'/Workspace/AIPromptLib'
)
->middleware(['permission:subMenu']);

AdminRoute::post(
	'Settings',
	[AiPromptController::class, 'allPrompt'],
	'/Workspace/AIPromptLib'
)
->middleware(['permission:subMenu']);

AdminRoute::post(
	'Settings',
	[AiPromptController::class, 'singlePrompt'],
	'/SinglePrompt'
)
->middleware(['permission:subMenu']);

AdminRoute::post(
	'Settings',
	[AiPromptController::class, 'store'],
	'/Prompt/Store'
)
->middleware(['permission:subMenu']);

AdminRoute::post(
	'Settings',
	[AiPromptController::class, 'remove'],
	'/Prompt/Remove'
)
->middleware(['permission:subMenu']);

AdminRoute::post(
	'Settings',
	[AiPromptController::class, 'update'],
	'/Prompt/Update'
)
->middleware(['permission:subMenu']);

AdminRoute::post(
	'Settings',
	[AiPromptController::class, 'aiGenerate'],
	'/AI/Generate'
)
->middleware(['permission:subMenu']);
