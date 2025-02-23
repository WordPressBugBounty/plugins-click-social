<?php

namespace Smashballoon\ClickSocial\App\Core\Lib;

if (!defined('ABSPATH')) exit;

trait SingleTon
{
	private static $instance = null;

	public static function getInstance()
	{
		if (! self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
