<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Core\SettingsManager;

class ActivationService
{
	use SingleTon;

	public function register()
	{
		// activation event handler
		\register_activation_hook(SBCS_FILE, [$this, 'activate']);
		add_action('admin_init', [$this, 'redirect']);
	}

	public function activate()
	{
		update_option('clicksocial_activation', true);
	}

	public function redirect()
	{
		if (
			SettingsManager::getInstance()->get('api_key') ||
			! get_option('clicksocial_activation')
		) {
			return;
		}

		delete_option('clicksocial_activation');
		\wp_safe_redirect(admin_url('admin.php?page=click-social'));
	}
}
