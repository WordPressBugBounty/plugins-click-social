<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class CredentialCloak
{
	public static function cloak($credential)
	{
		return str_pad(substr($credential, -4), strlen($credential), '*', STR_PAD_LEFT);
	}

	public static function isCloaked($credential)
	{
		return static::cloak($credential) === $credential;
	}
}
