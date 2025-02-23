<?php

namespace Smashballoon\ClickSocial\App\Controllers;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\Lib\Request;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Enums\PostStatus;
use Smashballoon\ClickSocial\App\Enums\SortByStatusDate;
use Smashballoon\ClickSocial\App\Services\DateTimeService;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;
use Smashballoon\ClickSocial\App\Services\PostService;
use Smashballoon\ClickSocial\App\Services\SocialAccountService;

class CalendarController extends BaseController
{
	/**
	 * Index route.
	 *
	 * @param Request $request Request.
	 * @param array $additionalPayload Additional payload.
	 *
	 * @return mixed
	 */
	public function index($request = null, $additionalPayload = [])
	{
		$timezone = function () {
			return DateTimeService::getTimezone();
		};

		$social_accounts = function () {
			return SocialAccountService::filterSocialAccountsForCurrentUser() ?: [];
		};

		$timeslots = Inertia::lazy(function () use ($request) {
			$args = [ 'social_account_uuid' => $request->input('social_account_uuid') ];

			if (DateTimeService::isUsingWpTimezone()) {
				$args['timezone'] = sbcs_getTimezone();
			}

			return AuthHttp::get(
				'timeslots',
				$args
			)->getBody(true);
		});

		$posts = function () use ($request) {
			return PostService::getBatchPost($request);
		};

		return $this->render('Calendar', [
			'posts'             => $posts,
			'social_accounts'   => $social_accounts,
			'social_account_uuid' => $request->input('social_account_uuid'),
			'timeslots'         => $timeslots,
			'submission'        => [ 'responses' => $additionalPayload ],
			'timezone'          => $timezone(),
		]);
	}

	public function storeTimeslots($request)
	{
		$additionalPayload = [];

		if (! $request->input('timeslots')) {
			return [];
		}
		$payload = [
			'create' => array_filter(
				$request->input('timeslots'),
				function ($timeslot) {
					return isset($timeslot['action']) && 'create' === $timeslot['action'];
				}
			),
			'update' => array_filter($request->input('timeslots'), function ($timeslot) {
				return isset($timeslot['action']) && 'update' === $timeslot['action'];
			}),
			'delete' => array_map(function ($timeslot) {
				return $timeslot['uuid'];
			}, array_filter($request->input('timeslots'), function ($timeslot) {
				return isset($timeslot['action']) && 'delete' === $timeslot['action'];
			})),
		];

		if (DateTimeService::isUsingWpTimezone()) {
			$payload['timezone'] = sbcs_getTimezone();
		}

		$postRequest = AuthHttp::post(
			'timeslots/batch',
			$payload
		);

		$postRequestData = $postRequest->getBody(true);

		$additionalPayload['timeslots'] = $postRequestData;

		$message = __('Schedule was not updated!', 'click-social');
		if ($postRequest->successful()) {
			$message = __('Schedule updated successfully!', 'click-social');
		}

		$additionalPayload['timeslots']['message']     = sanitize_textarea_field($message);
		$additionalPayload['timeslots']['status_code'] = $postRequest->getStatusCode();

		return $this->index($request, $additionalPayload);
	}

	/**
	 * Store method.
	 *
	 * @param Request $request Request.
	 *
	 * @return mixed
	 */
	public function storePost($request)
	{
		$additionalPayload = PostService::storePost($request->input('post'));

		return $this->index($request, $additionalPayload);
	}

	/**
	 * Calendar Delete post.
	 *
	 * @param Request $request Request.
	 *
	 * @return mixed
	 */
	public function delete($request)
	{
		$additionalPayload = [];

		if ($request->method() === 'DELETE') {
			$deleteRequest = AuthHttp::post('posts/remove', ['uuid' => $request->input('id')]);

			$additionalPayload['posts'] = $deleteRequest->getBody(true);
			$additionalPayload['posts']['status_code'] = $deleteRequest->getStatusCode();
		}

		return $this->index($request, $additionalPayload);
	}

	public function loadMore($request)
	{
		$social_account_uuid = sanitize_text_field($request->input('social_account_uuid'));
		$revival_camp_uuid = sanitize_text_field($request->input('revival_campaign_uuid'));
		$post_status = sanitize_text_field($request->input('postStatus'));
		$pageCount = (int)$request->input('pageCount');

		$sort_by = SortByStatusDate::batchPostSortQuery();

		$params = [
			'filter' => [
				'post_status' => [
					'status' => $post_status,
				],
				'date_sort' => [
					'query' => $sort_by[$post_status],
				],
				'include_empty_timeslots' => true,
			],
			'pagination' => [
				'page'	=> $pageCount,
			]
		];

		if ($social_account_uuid) {
			$params['filter']['social_account']['uuid'] = $social_account_uuid;
		}
		if ($revival_camp_uuid) {
			$params['filter']['revival_camp']['uuid'] = $revival_camp_uuid;
		}

		if (SettingsManager::getInstance()->get('timezone_source', 'wp') === 'wp') {
			$params['timezone'] = sbcs_getTimezone();
		}

		$posts = AuthHttp::get('posts', $params);

		wp_send_json([
			'pageCount' => $pageCount,
			'posts' => $posts->getBody(true)
		]);
		die();
	}

	public function calendarPosts($request)
	{
		$social_account_uuid = sanitize_text_field($request->input('social_account_uuid'));
		$month = sanitize_text_field($request->input('month'));

		$params = [
			'filter' => [
				'date_sort' => [
					'query' => SortByStatusDate::batchPostSortQuery(),
					'month' => $month,
				],
			]
		];
		if ($social_account_uuid) {
			$params['filter']['social_account']['uuid'] = $social_account_uuid;
		}

		if (SettingsManager::getInstance()->get('timezone_source', 'wp') === 'wp') {
			$params['timezone'] = sbcs_getTimezone();
		}

		$posts = AuthHttp::get('posts/batch', $params);

		wp_send_json([
			'posts' => $posts->getBody(true)['data'] ?? false,
		]);
		die();
	}

	public function updatePost($request)
	{
		$additionalPayload = PostService::updatePost($request);

		return $this->index($request, $additionalPayload);
	}
}
