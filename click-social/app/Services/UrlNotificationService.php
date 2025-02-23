<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

class UrlNotificationService
{
	public static function getNotification()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
		$encodedNotification = isset($_GET['sbcs_notification'])
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
			? sanitize_textarea_field(wp_unslash($_GET['sbcs_notification']))
			: null;

		if (empty($encodedNotification)) {
			return [];
		}

		// Decode the base64 encoded notification.
		$decodedNotification = sbcs_base64url_decode($encodedNotification);

		// Ensure the decoded value is valid JSON before decoding it.
		if ($decodedNotification === false || empty($decodedNotification)) {
			return [];
		}

		// Decode JSON and check if it results in a valid array.
		$urlNotificationData = json_decode($decodedNotification, true);

		if (json_last_error() !== JSON_ERROR_NONE || !is_array($urlNotificationData)) {
			return [];
		}

		if (empty($urlNotificationData['message'])) {
			return [];
		}

		if (empty($urlNotificationData['type'])) {
			return [];
		}

		$schema = [
			'type'       => 'object',
			'required'   => [ 'message', 'type', 'expires_at', 'data' ],
			'properties' => [
				'message'    => [ 'type' => 'string' ],
				'type'       => [ 'type' => 'string' ],
				'expires_at' => [ 'type' => 'integer' ],
				'data'       => [
					'type'  => 'object',
					'required' => [
						'social_accounts',
					],
					'properties' => [
						'social_accounts' => [
							'type'  => 'array',
							'items' => [
								'type'     => 'object',
								'required' => [
									'id',
									'label',
									'name'
								],
								'properties' => [
									'id'    => [ 'type' => 'integer' ],
									'label' => [ 'type' => 'string' ],
									'name'  => [ 'type' => 'string' ],
								]
							],
						],
					]
				],
			],
		];

		$data = rest_sanitize_value_from_schema($urlNotificationData, $schema);

		if (is_wp_error($data)) {
			return [];
		}

		return map_deep($data, 'sanitize_text_field');
	}
}
