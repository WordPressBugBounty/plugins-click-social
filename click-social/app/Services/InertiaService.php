<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\InertiaHeaders;

class InertiaService
{
	public function register()
	{
		\add_action('template_redirect', [$this, 'handleInertiaRequests']);
	}

	public function handleInertiaRequests()
	{
		if (InertiaHeaders::inRequest()) {
		}
	}
}
