<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

final class Core
{
	use SingleTon;

	public function __construct()
	{
		$this->load();
	}

	private function load()
	{
		MigrationManager::getInstance()->run();
		BootManager::getInstance()->run();
	}
}
