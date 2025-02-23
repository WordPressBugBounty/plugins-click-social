<?php

namespace Smashballoon\ClickSocial\App\Enums;

if (! defined('ABSPATH')) {
	exit;
}

class WpUserRoles
{
	public const SuperAdmin = 'super_admin';
	public const Admin = 'admin';
	public const Standard = 'standard';

	public static function list()
	{
		return [
			[
				'id'	=> self::Admin,
				'label' => __('Admin', 'click-social')
			],
			[
				'id'	=> self::Standard,
				'label' => __('Standard', 'click-social')
			],
		];
	}
}
