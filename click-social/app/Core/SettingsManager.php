<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class SettingsManager
{
	use SingleTon;

	protected const OPTION_KEY = 'clicksocial_settings';

	public function get($setting_name, $default_value = null)
	{
		$option = get_option(self::OPTION_KEY, []);

		return $option[$setting_name] ?? $default_value;
	}

	public function update($setting_name, $new_value)
	{
		$option = get_option(self::OPTION_KEY, []);

		if (!is_array($option)) {
			$option = [];
		}

		if (isset($option[$setting_name]) && $option[$setting_name] === $new_value) {
			return true;
		}

		$option[$setting_name] = $new_value;

		return update_option(self::OPTION_KEY, $option);
	}

	public function delete($setting_name)
	{
		$option = get_option(self::OPTION_KEY, []);

		unset($option[$setting_name]);

		return update_option(self::OPTION_KEY, $option);
	}
}
