<?php

namespace Smashballoon\ClickSocial\App\Enums;

if (! defined('ABSPATH')) {
	exit;
}

class WpUserCapabilities
{
	public const FullAccess = 'full_access';
	public const FullPostAccess = 'full_post_access';
	public const ApprovalRequired = 'approval_required';
	public const ViewOnly = 'view_only';

	public static function list()
	{
		return [
			[
				'id'	=> self::FullAccess,
				'label' => __('Full Access', 'click-social')
			],
			[
				'id'	=> self::FullPostAccess,
				'label' => __('Full Post Access', 'click-social')
			],
			[
				'id'	=> self::ApprovalRequired,
				'label' => __('Approval Required', 'click-social')
			],
			[
				'id'	=> self::ViewOnly,
				'label' => __('View Only', 'click-social')
			],
		];
	}
}
