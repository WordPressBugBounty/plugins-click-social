<?php

namespace Smashballoon\ClickSocial\App\Enums;

if (! defined('ABSPATH')) {
	exit;
}

class Connectors
{
	public const TWITTER = 'twitter';
	public const FACEBOOK = 'facebook';
	public const INSTAGRAM = 'instagram';

	public static function list($queryParams, $labelPrefix = '')
	{
		$connector_base_url = sbcs_get_env('CONNECTOR_BASE_URL');
		if (! $connector_base_url) {
			$connector_base_url = SBCS_SITE_APP_URL;
		}

		return [
			[
				'label'	=> $labelPrefix . 'Twitter',
				'href'	=> $connector_base_url . 'v1/connect/twitter?' . $queryParams,
			],
			[
				'label'	=> $labelPrefix . 'Facebook',
				'href'	=> $connector_base_url . 'v1/connect/facebook?' . $queryParams,
			],
			[
				'label'	=> $labelPrefix . 'Instagram',
				'href'	=> $connector_base_url . 'v1/connect/instagram?' . $queryParams,
			]
		];
	}
}
