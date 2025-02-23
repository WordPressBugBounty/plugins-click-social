<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Core\CredentialCloak;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Enums\Connectors;
use Smashballoon\ClickSocial\App\Services\DateTimeService;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\SocialAccountService;

class OnboardingController extends BaseController
{
	/**
	 * Onboarding route.
	 *
	 * @return mixed
	 */
	public function index()
	{
		$links = sbcs_get_environment_links(sbcs_get_config('links'));

		$wpOnboardingUserData = sbcs_base64url_encode(wp_json_encode(sbcs_get_onboarding_data()));

		return Inertia::render(
			'Onboarding',
			[
				'links' => $links,
				'wpOnboardingUserData' => $wpOnboardingUserData,
			]
		);
	}

	/**
	 * Onboarding Account Setup page.
	 *
	 * @return string
	 */
	public function onboardingAccountSetup($request, $additionalPayload = [])
	{
		$accountManager = new AccountManager();
		$isConnected = $accountManager->isConnected();

		$step = $request->input('step') ?: ($isConnected ? 2 : 1);

		$completedSteps = [];
		if ($isConnected) {
			$completedSteps[] = '1';
		}

		if ($isConnected && SocialAccountService::filterSocialAccountsForCurrentUser()) {
			$completedSteps[] = '2';
		}

		if ($isConnected && SettingsManager::getInstance()->get('timezone_source', '')) {
			$completedSteps[] = '3';
		}

		$returnURL = 'return_url=' . urlencode(sbcs_admin_route('', '/AccountSetup')) . '&step=2';

		return $this->render(
			'Onboarding/AccountSetup',
			array_merge(
				[
					'step' => $step,
					'completedSteps' => $completedSteps,
					'connectors' => Connectors::list($returnURL, 'Connect to '),
					'timezone_source' => SettingsManager::getInstance()->get('timezone_source', ''),
					'account_timezone' => $isConnected ? DateTimeService::getAccountTimezone() : '',
					'social_accounts' => $isConnected ? SocialAccountService::filterSocialAccountsForCurrentUser() : [],
				],
				$additionalPayload
			)
		);
	}

	/**
	 * Store.
	 *
	 * @param $request Request.
	 *
	 * @return string
	 */
	public function store($request)
	{
		$additionalPayload = [];

		$accountManager = new AccountManager();

		if ($request->input('apiKey') !== null) {
			$apiKey = $request->input('apiKey');
			if (CredentialCloak::isCloaked($apiKey)) {
				$apiKey = SettingsManager::getInstance()->get('api_key', '');
			}

			$accountManager->connect($apiKey);

			// Default to WP timezone if not set to match the WordPress posts timezone.
			if (!SettingsManager::getInstance()->get('timezone_source', '')) {
				SettingsManager::getInstance()->update('timezone_source', 'wp');
			}

			// TODO move logic into a service.
			if (!$accountManager->isConnected()) {
				$additionalPayload = [
					'submission' => [
						'responses' => [
							'apiKey' => [
								'status_code' => 400,
								'message' => 'Invalid API Key',
							],
						],
					],
				];
			} else {
				$additionalPayload = [
					'submission' => [
						'responses' => [
							'apiKey' => [
								'status_code' => 200,
								'message' => 'Site connected successfully!',
							],
						],
					],
				];
			}
		}

		if ($request->input('timezone_source')) {
			SettingsManager::getInstance()->update('timezone_source', $request->input('timezone_source'));
		}

		return $this->onboardingAccountSetup($request, $additionalPayload);
	}

	/**
	 * disableOnboardingTour.
	 *
	 * @param $request Request.
	 *
	 * @return void
	 */
	public function disableOnboardingTour($request)
	{
		// Check if the request is a POST request and if the disable_onboarding_tour input is set.
		// Get the current user id and add it to the disable_onboarding_tour array.
		// Redirect back to the ClickSocial page after we save the onboarding status.
		if ($request->method() === 'POST' && $request->input('disable_onboarding_tour')) {
			$userId = get_current_user_id();
			$disable_onboarding_tour = SettingsManager::getInstance()->get('disable_onboarding_tour', '');
			$disable_onboarding_tour = !empty($disable_onboarding_tour) ? json_decode($disable_onboarding_tour) : [];

			if (!in_array($userId, $disable_onboarding_tour)) {
				$disable_onboarding_tour[] = $userId;
			}

			SettingsManager::getInstance()->update('disable_onboarding_tour', wp_json_encode($disable_onboarding_tour));
			Inertia::redirect('click-social');
		}
	}
}
