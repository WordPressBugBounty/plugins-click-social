<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\OnboardingController;
use Smashballoon\ClickSocial\App\Core\AdminRoute;

// Can be accessed by WordPress administrator users.
AdminRoute::get('', [
	OnboardingController::class,
	'onboardingAccountSetup'
], '/AccountSetup')
	->setCapability('manage_options');

// Can be accessed by WordPress administrator users.
AdminRoute::post('', [
	OnboardingController::class,
	'store'
], '/AccountSetup')
	->setCapability('manage_options');

// Can be accessed by all WordPress users invited to ClickSocial.
AdminRoute::post(
	'',
	[OnboardingController::class, 'disableOnboardingTour'],
	'/DisableOnboardingTour'
)
	->middleware(['permission:user']);
