<?php

namespace Smashballoon\ClickSocial\App\Controllers\Middleware;

use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\InertiaHeaders;

if (! defined('ABSPATH')) {
	exit;
}

class VerifyCsrfToken
{
	public function handle($request)
	{
		// Skip CSRF verification for GET requests.
		if ($request->isMethod('GET') && empty($_POST)) {
			return;
		}

		$headers = Inertia::getHeaders();
		$csrf_token = isset($headers['X-Sbcs-Csrf']) ? $headers['X-Sbcs-Csrf'] : $request->input('sbcsAdminNonce');

		if (empty($csrf_token)) {
			$this->dieWithError($headers);
		}

		if (1 !== wp_verify_nonce($csrf_token, 'sbcsAdminNonce')) {
			$this->dieWithError($headers);
		}
	}

	public function dieWithError($headers = [])
	{
		if (!empty($headers['X-Inertia'])) {
			Inertia::render('ErrorPage', [
				'errorTitle' => __('Page expired', 'click-social'),
				'errorMessage' => __('Please refresh the page and try again.', 'click-social'),
			]);
			exit;
		}

		if (did_action('admin_head')) {
			echo '<div class="error"><p>';
			echo esc_html__('Page expired. Please refresh the page and try again.', 'click-social');
			echo '</p></div>';
			exit;
		}

		wp_die(
			esc_html__('Page expired. Please refresh the page and try again.', 'click-social'),
			esc_html__('Page expired', 'click-social'),
			403
		);
	}
}
