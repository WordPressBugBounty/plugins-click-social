<?php

namespace Smashballoon\ClickSocial\App\Services;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Enums\PostStatus;

if (! defined('ABSPATH')) exit;

class PostService
{
	public static function getSharedPayload()
	{
		$args = [];
		if (SettingsManager::getInstance()->get('timezone_source', 'wp') === 'wp') {
			$args['timezone'] = sbcs_getTimezone();
		}

		return $args;
	}

	public static function getBatchPost($request, $params = [])
	{
		$args = array_merge(
			static::getSharedPayload(),
			static::setBatchPostFilter($request, $params)
		);

		$posts = AuthHttp::get('posts/batch', $args)->getBody(true);

		return [
			PostStatus::PUBLISHED	=> $posts['data'][PostStatus::PUBLISHED] ?? '',
			PostStatus::SCHEDULED	=> $posts['data'][PostStatus::SCHEDULED] ?? '',
			PostStatus::DRAFT		=> $posts['data'][PostStatus::DRAFT] ?? '',
			PostStatus::PENDING		=> $posts['data'][PostStatus::PENDING] ?? '',
			PostStatus::FAILED		=> $posts['data'][PostStatus::FAILED] ?? '',
			PostStatus::REJECTED	=> $posts['data'][PostStatus::REJECTED] ?? '',
			'pagination'			=> $posts['pagination'] ?? '',
		];
	}

	protected static function setBatchPostFilter($request, $params = [])
	{
		$start_date = gmdate('Y-m-d', strtotime('now', time()));
		$end_date = gmdate('Y-m-d', strtotime('+7 day', time()));

		// For Revival Campaign, let's display all posts for all campaign as it clearly displays
		// how many posts were created by the campaign.
		if (! empty($params['revival_campaign_uuid'])) {
			$start_date = gmdate('Y-m-d', strtotime('-10 years', time()));
			$end_date = gmdate('Y-m-d', strtotime('+10 years', time()));
		}

		$query = [
			PostStatus::PUBLISHED 	=> 'published_at',
			PostStatus::SCHEDULED 	=> 'scheduled_at',
			PostStatus::DRAFT		=> 'created_at',
			PostStatus::PENDING   	=> 'scheduled_at',
			PostStatus::FAILED    	=> 'scheduled_at',
			PostStatus::REJECTED    => 'created_at',
		];

		$args = [
			'filter' => [
				'date_sort'      => [
					'query'      => $query,
					'start_date' => $start_date,
					'end_date'   => $end_date,
				],
				'include_empty_timeslots' => true,
			],
		];

		// For revival campaign, include the revival campaign uuid as filter.
		if (! empty($params['revival_campaign_uuid'])) {
			$args['filter']['revival_camp'] = [
				'uuid' => $params['revival_campaign_uuid'],
			];

			// Do not include empty timeslots for revival campaign.
			$args['filter']['include_empty_timeslots'] = false;
		}

		// For revival campaign, don't include the social account uuid when filtering.
		if (empty($params['revival_campaign_uuid'])) {
			if ($request->input('social_account_uuid')) {
				$args['filter']['social_account'] = [
					'uuid' => $request->input('social_account_uuid'),
				];
			}
		}

		return $args;
	}

	public static function storePost($post)
	{
		$postRequest = AuthHttp::post(
			'posts',
			array_merge(
				self::getSharedPayload(),
				!empty($post) ? $post : []
			)
		);

		$postRequestData = $postRequest->getBody(true);

		$postRequestData['status_code'] = $postRequest->getStatusCode();
		$additionalPayload['v'] = wp_rand(1, 9999);

		$additionalPayload['posts'] = $postRequestData;

		return $additionalPayload;
	}

	public static function updatePost($request)
	{
		$responses = [];

		$post_data = $request->input('post');
		$media_data = ! empty($post_data['media']) ? $post_data['media'] : false;

		unset($post_data['media']);

		$postRequest = AuthHttp::post(
			'posts/update',
			array_merge(
				self::getSharedPayload(),
				$post_data
			)
		);

		$responses['posts'] = array_merge(
			[
				'status_code' => $postRequest->getStatusCode(),
			],
			$postRequest->getBody(true)
		);

		$media_payload = [
			'post_uuid' => $post_data['uuid'],
			'media' => [],
		];

		if ($media_data) {
			foreach ($media_data as $media) {
				if (empty($media['uuid'])) {
					$media_payload['media']['create'][] = $media;
				}
			}
		}

		if (! empty($post_data['updateMedia'])) {
			$media_payload['media']['update'] = $post_data['updateMedia'];
		}

		if (! empty($post_data['deleteMedia'])) {
			$media_payload['media']['delete'] = $post_data['deleteMedia'];
		}

		if (! empty($media_payload['media'])) {
			$postAttachmentsRequest = AuthHttp::post('post-attachments/batch', $media_payload);

			if ($postAttachmentsRequest->failed()) {
				$responses['posts'] = [
					'status_code' => $postAttachmentsRequest->getStatusCode(),
					'message' => __(
						'Failed to update media attachments for the edited post. Please retry.',
						'click-social'
					),
				];
			}
		}

		return $responses;
	}
}
