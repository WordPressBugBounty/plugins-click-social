<?php

// Todo: to be deleted so we can use Inertia::render directly in the controllers.

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;

class BaseController
{
	protected $brokenConnection = false;

	/**
	 * Render view file and pass data to the file.
	 *
	 * @param  string $file_path File path.
	 * @param  array  $data Data.
	 *
	 * @return mixed
	 */
	public function render($file_path, $data = [])
	{
		if (! (new SettingsManager())->get('api_key')) {
			return Inertia::render($file_path, $data);
		}

		if ($this->brokenConnection === false) {
			$this->brokenConnection = AuthHttp::isBrokenConnection();
		}

		if ($this->brokenConnection === 401) {
			$message = __('Service unavailable. Your connection with ClickSocial server is broken! Please go to Settings page and disconnect it and connect it with a valid API Key.', 'click-social');	// phpcs:ignore

			if (MemberTransaction::getUserRole() === WpUserRoles::Standard) {
				$message = __('Service unavailable. Your connection with ClickSocial server is broken! Please ask the admin to disconnect it and connect it with a valid API Key.', 'click-social');	// phpcs:ignore
			}

			$data['submission'] = [
				'responses' => [
					'apiKey' => [
						'status_code' => 401,
						'message' => $message,
					],
				],
			];
		}

		return Inertia::render($file_path, $data);
	}
}
