<?php

namespace Smashballoon\ClickSocial\App\Services;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Core\SettingsManager;

if (! defined('ABSPATH')) exit;

class DateTimeService
{
	public static function isUsingWpTimezone()
	{
		return SettingsManager::getInstance()->get('timezone_source', 'wp') === 'wp';
	}

	public static function getTimezone()
	{
		if (self::isUsingWpTimezone()) {
			return sbcs_getTimezone();
		}

		return self::getAccountTimezone();
	}

	public static function getAccountTimezone()
	{
		$data = AuthHttp::get('user')->getBody(true);

		return $data['data']['timezone'] ?? '';
	}

	// TODO: Timezone, Timeslot related reusable functionalities can be added here.
}
