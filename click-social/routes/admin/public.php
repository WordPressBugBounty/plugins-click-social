<?php

if (! defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\OnboardingController;
use Smashballoon\ClickSocial\App\Core\AdminRoute;

AdminRoute::get('', [OnboardingController::class, 'index'])
	->setCapability('manage_options')
	->addMenu(
		__('ClickSocial', 'click-social'),
		__('ClickSocial', 'click-social')
	)
	->addSubmenu(
		__('Onboarding', 'click-social'),
		__('Onboarding', 'click-social')
	);
