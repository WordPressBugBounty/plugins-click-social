<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\Request;
use Smashballoon\ClickSocial\App\Enums\PostStatus;
use Smashballoon\ClickSocial\App\Services\DateTimeService;
use Smashballoon\ClickSocial\App\Services\PostService;
use Smashballoon\ClickSocial\App\Services\RevivalCampaignService;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\SocialAccountService;

class RevivePostsController extends BaseController
{
	public const CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT = 'Calendar/RevivalPosts';

	/**
	 * Get revival campaign data.
	 *
	 * @return array
	 */
	private function getRevivalCampaign()
	{
		$revivalCampaignData = AuthHttp::get('revival-campaign')->getBody(true);
		$revivalCampaign = $revivalCampaignData['data'] ?? [];
		if (!empty($revivalCampaign['settings'])) {
			$revivalCampaign['settings'] = json_decode($revivalCampaign['settings'], true);
		}
		return $revivalCampaign;
	}

	/**
	 * Get revival posts.
	 *
	 * @param Request $request
	 * @param array $revivalCampaign
	 * @return array
	 */
	private function getRevivalPosts($request, $revivalCampaign)
	{
		if (empty($revivalCampaign['uuid'])) {
			return [];
		}

		return PostService::getBatchPost($request, [
			'revival_campaign_uuid' => $revivalCampaign['uuid'],
		]);
	}

	/**
	 * Handle post update.
	 *
	 * @param Request $request
	 * @param string $status
	 * @return mixed
	 */
	private function handlePostUpdate($request, $status)
	{
		$payload = [
			'uuid' => sanitize_text_field($request->input('postId')),
			'status' => $status,
			'requires_approval' => 0,
		];

		$res = AuthHttp::post('posts/update', $payload);
		$frontendResponse = $this->prepareFrontendData($request);
		$frontendResponse['postUpdateResponse'] = $res->getBody(true);

		return $this->render(self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT, $frontendResponse);
	}

	/**
	 * Display the index page.
	 *
	 * @param Request $request
	 * @param array $additionalPayload
	 * @return mixed
	 */
	public function index($request, $additionalPayload = [])
	{
		$revivalCampaign = $this->getRevivalCampaign();
		$links = sbcs_get_environment_links(sbcs_get_config('links'));

		$revivalPosts = function () use ($request, $revivalCampaign) {
			return $this->getRevivalPosts($request, $revivalCampaign);
		};

		$timezone = function () {
			return DateTimeService::getTimezone();
		};

		$social_accounts = function () {
			return SocialAccountService::filterSocialAccountsForCurrentUser();
		};

		return $this->render(
			self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT,
			[
				'posts' => $revivalPosts,
				'social_accounts' => $social_accounts,
				'timezone' => $timezone,
				'revivalCampaign' => $revivalCampaign ?: null,
				'submission' => ['responses' => $additionalPayload],
				'links' => $links,
			]
		);
	}

	/**
	 * Store the revival campaign.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function store(Request $request)
	{
		if ($request->input('post')) {
			$this->storePost($request);
		}

		if ($request->input('campaign_id') || $request->input('settings')) {
			$this->storeRevivalCampaign($request);
		}
	}

	/**
	 * Store a post.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function storePost($request)
	{
		$additionalPayload = PostService::storePost($request->input('post'));

		return $this->index($request, $additionalPayload);
	}

	/**
	 * Store the revival campaign.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function storeRevivalCampaign($request)
	{
		if (!$request->isMethod('POST')) {
			return $this->index($request, [
				'submission' => [
					'responses' => [
						'posts' => ['status_code' => 400, 'message' => 'No valid action to do.'],
					]
				]
			]);
		}

		if ($request->input('action') === 'delete') {
			return $this->delete($request);
		}

		$campaignSettings = $request->input('settings');
		if (!$campaignSettings) {
			return $this->index($request, [
				'submission' => [
					'responses' => [
						'posts' => [
							'status_code' => 400,
							'message' => 'No valid settings found. Please try again.'
						],
					]
				]
			]);
		}

		$revivalCampaignResponse = AuthHttp::get('revival-campaign')->getBody(true);

		if (isset($revivalCampaignResponse['data']['uuid'])) {
			AuthHttp::post('revival-campaign/remove', [
				'website_id' => SettingsManager::getInstance()->get('website_id'),
			])->getBody(true);
		}

		$revivalCampaign = new RevivalCampaignService($campaignSettings);

		$selectedWpPostsIds = $campaignSettings['filter_data']['selected_posts'];

		if (count($selectedWpPostsIds) === 0) {
			return Inertia::render(self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT, [
				'submission' => [
					'responses' => [
						'posts' => [
							'status_code' => 400,
							'message' => 'No posts found for the selected date range. Please try again.'
						],
					]
				]
			]);
		}

		if (count($campaignSettings['filter_data']['selected_accounts']) === 0) {
			return Inertia::render(self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT, [
				'submission' => [
					'responses' => [
						'posts' => [
							'status_code' => 400,
							'message' => 'Select at least one social account to schedule the posts. Please try again.'
						],
					]
				]
			]);
		}

		$RevivalCampaignPostResponse = AuthHttp::post(
			'revival-campaign',
			['settings' => $campaignSettings]
		)->getBody(true);

		SettingsManager::getInstance()->update(
			'website_id',
			sanitize_text_field($RevivalCampaignPostResponse['data']['website_id'])
		);

		$revival_campaign_id = $RevivalCampaignPostResponse['data']['uuid'];

		$revivalCampaign->setPostIds($selectedWpPostsIds);

		$generatedScheduledPosts = $revivalCampaign->generateAllScheduledPosts($revival_campaign_id);

		if (count($generatedScheduledPosts) === 0) {
			return Inertia::render(self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT, [
				'submission' => [
					'responses' => [
						'posts' => [
							'status_code' => 400,
							'message' => 'No posts were scheduled! Please try again.'
						],
					]
				]
			]);
		}

		if (!$revival_campaign_id) {
			return Inertia::render(self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT, [
				'submission' => [
					'responses' => [
						'posts' => [
							'status_code' => 400,
							'message' => 'An error occurred while creating the campaign. Please try again.'
						],
					]
				]
			]);
		}

		$payload = [
			'data' => $generatedScheduledPosts,
		];

		$createPostsForCampaign = AuthHttp::post('posts/batch', $payload);

		if ($createPostsForCampaign->getStatusCode() !== 200) {
			return Inertia::render(self::CALENDAR_REVIVAL_POSTS_PAGE_COMPONENT, [
				'submission' => [
					'responses' => [
						'posts' => [
							'status_code' => 400,
							'message' => 'An error occurred while creating the posts. Please try again.'
						],
					]
				]
			]);
		}

		return $this->index($request, [
			'payload' => $payload,
			'submission' => [
				'responses' => [
					'posts' => [
						'status_code' => 200,
						'message' => 'Successfully scheduled posts.'
					],
				]
			]
		]);
	}

	/**
	 * Prepare frontend data.
	 *
	 * @param Request $request
	 * @param array $additionalPayload
	 * @return array
	 */
	public function prepareFrontendData($request, $additionalPayload = [])
	{
		$revivalCampaign = $this->getRevivalCampaign();

		$revivalPosts = function () use ($request, $revivalCampaign) {
			return $this->getRevivalPosts($request, $revivalCampaign);
		};

		$timezone = function () {
			return DateTimeService::getTimezone();
		};

		$social_accounts = function () {
			return SocialAccountService::filterSocialAccountsForCurrentUser();
		};

		return [
			'posts' => $revivalPosts,
			'social_accounts' => $social_accounts,
			'social_account_uuid' => sanitize_text_field($request->input('social_account_uuid')),
			'submission' => ['responses' => $additionalPayload],
			'timezone' => $timezone(),
			'revivalCampaign' => $revivalCampaign ?: null,
		];
	}

	/**
	 * Delete a post.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function deletePost(Request $request)
	{
		$payload = [
			'uuid' => sanitize_text_field($request->input('postId')),
		];

		$res = AuthHttp::post('posts/remove', $payload);
		$responseBody = $res->getBody(true);

		return $this->index($request, [
			'postDeleteResponse' => [
				'status_code' => $res->getStatusCode(),
				'message' => $responseBody ?? '',
			],
		]);
	}

	/**
	 * Reject a post.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function rejectPost($request)
	{
		return $this->handlePostUpdate($request, PostStatus::REJECTED);
	}

	/**
	 * Approve a post.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function approvePost($request)
	{
		return $this->handlePostUpdate($request, PostStatus::SCHEDULED);
	}

	/**
	 * Update a post.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function updatePost($request)
	{
		$additionalPayload = PostService::updatePost($request);

		return $this->index($request, $additionalPayload);
	}

	/**
	 * Delete revival campaign.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function delete(Request $request)
	{
		AuthHttp::post('revival-campaign/remove', [])->getBody(true);

		return $this->index($request);
	}

	/**
	 * Get user subscription limit.
	 *
	 * @param Request $request
	 * @return void
	 */
	public function userSubscriptionLimit($request)
	{
		$user_limits = AuthHttp::get('user/billing')->getBody(true)['data'];
		$post_limits = $user_limits['limits']['schedule_posts'] ?? false;

		if (empty($user_limits) || empty($post_limits)) {
			return wp_send_json(0);
		}

		if (!isset($post_limits['max']) || !isset($post_limits['value'])) {
			return wp_send_json(0);
		}

		$available_post_limit = $post_limits['max'] - $post_limits['value'];
		wp_send_json($available_post_limit);
	}
}
