<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Controllers\QuickShareController;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;

class AssetsManager
{
	use SingleTon;

	private $configs = [];

	public function register()
	{
		$this->configs = sbcs_get_config();

		if (false !== sbcs_get_config('features.quick_share')) {    // FeatureFlag: quick_share
			if (QuickShareController::getInstance()->isQuickShareEnabled()) {
				add_action('enqueue_block_editor_assets', [$this, 'enqueueGutenbergSidebar']);
				add_action('admin_enqueue_scripts', [$this, 'enqueueGutenbergStyles']);
			}
		}

		if (!sbcs_is_click_social_page()) {
			return;
		}

		add_action('admin_enqueue_scripts', [$this, 'adminScripts']);
	}

	public function adminScripts()
	{
		$script_handle = sbcs_prefix('admin-js');

		wp_enqueue_style(
			sbcs_prefix('admin-css'),
			$this->setAssetsUrl("/build/css/app.css"),
			[],
			$this->setAssetsVersion()
		);

		wp_enqueue_script(
			$script_handle,
			$this->setAssetsUrl("/build/js/app.js"),
			[
				'jquery',
				'wp-element',
				'wp-i18n',
				'wp-media-utils',
				'wp-components',
			],
			$this->setAssetsVersion(),
			true
		);

		wp_enqueue_media();

		$this->adminAjax($script_handle);

		wp_set_script_translations(
			$script_handle,
			'click-social',
			SBCS_DIR_PATH . '/languages'
		);

		wp_enqueue_script($script_handle);
	}

	private function setAssetsUrl($path)
	{
		if ($this->configs['dev_mode'] && file_exists(SBCS_DIR_PATH . 'hot')) {
			return '//localhost:9046/public' . $path;
		}
		return sbcs_asset_url($path);
	}

	private function setAssetsVersion()
	{
		if ($this->configs['dev_mode']) {
			return $this->configs['plugin_version'] . time();
		}
		return $this->configs['plugin_version'];
	}

	public function adminAjax($handle)
	{
		wp_localize_script(
			$handle,
			'sbcsAdmin',
			[
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('sbcsAdminNonce'),
				'pluginUrl' => SBCS_PLUGIN_URL,
				'pluginDir' => SBCS_DIR_PATH,
				// FeatureFlags
				'features' => sbcs_get_config('features'),
				'restUrl' => rest_url('clicksocial/v1/'),
				'defaultRestUrl' => rest_url('wp/v2/'),
				'calendarUrl' => Inertia::getUrl('click-social'),
				'devMode' => sbcs_get_config('dev_mode') === 'true',
			]
		);
	}

	/**
	 * Enqueue Gutenberg sidebar assets and translations.
	 *
	 * @since 1.0.0
	 */
	public function enqueueGutenbergSidebar()
	{
		if (!QuickShareController::getInstance()->memberPermissions()) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen || 'post' !== $screen->base || 'post' !== $screen->id) {
			return;
		}

		$script_handle = sbcs_prefix('gutenberg-sidebar');

		wp_enqueue_script(
			$script_handle,
			$this->setAssetsUrl('/build/js/sidebar-plugin.js'),
			['wp-plugins', 'wp-edit-post', 'wp-i18n', 'wp-element', 'media-upload'],
			$this->setAssetsVersion(),
			['in_footer' => true]
		);

		wp_set_script_translations(
			$script_handle,
			'click-social',
			SBCS_DIR_PATH . '/languages'
		);

		$this->adminAjax($script_handle);
	}


	/**
	 * Enqueue Gutenberg sidebar styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueueGutenbergStyles()
	{
		if (!QuickShareController::getInstance()->memberPermissions()) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen || 'post' !== $screen->base || 'post' !== $screen->id) {
			return;
		}

		wp_enqueue_style(
			sbcs_prefix('gutenberg-sidebar-css'),
			$this->setAssetsUrl('/build/css/custom-sidebar.css'),
			[],
			$this->setAssetsVersion()
		);
	}

}
