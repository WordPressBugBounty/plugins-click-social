<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (! defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;

class HelpSupportController extends BaseController
{
	/**
	 * Support route.
	 *
	 * @return mixed
	 */
	public function index()
	{
		$links = sbcs_get_environment_links(sbcs_get_config('links'));
		return Inertia::render('HelpSupport', ['links' => $links]);
	}
}
