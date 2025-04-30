<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Controllers\WPRestApiController;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Services\ActivationService;
use Smashballoon\ClickSocial\App\Services\DeactivationService;
use Smashballoon\ClickSocial\App\Services\ShortlinkHandlerService;
use Smashballoon\ClickSocial\App\Services\TemplateShortcodesService;
use Smashballoon\ClickSocial\App\Services\TranslationService;

class BootManager
{
	use SingleTon;

	protected $registerList = [];

	public function __construct()
	{
		$this->setRegisterList();
	}

	/**
	 * Load controller or service which need to register hooks initially when the plugin is loaded.
	 *
	 * @return false|void
	 */
	public function run()
	{
		if (empty($this->registerList)) {
			return false;
		}

		foreach ($this->registerList as $class) {
			$class::getInstance()->register();
		}
	}

	/**
	 * List of all those classes which need to register hooks.
	 *
	 * @return array
	 */
	protected function registerList()
	{
		return array(
			ActivationService::class,
			DeactivationService::class,
			AssetsManager::class,
			WPRestApiController::class,
			AdminRouteManager::class,
			TemplateShortcodesService::class,
			ShortlinkHandlerService::class,
			TranslationService::class
		);
	}

	/**
	 * Set the array list of controllers & servicess which will be loaded initially.
	 *
	 * @return void
	 */
	protected function setRegisterList()
	{
		$this->registerList = $this->registerList();
	}
}
