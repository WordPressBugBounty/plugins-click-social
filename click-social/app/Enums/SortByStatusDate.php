<?php

namespace Smashballoon\ClickSocial\App\Enums;

if (! defined('ABSPATH')) {
	exit;
}

class SortByStatusDate
{
	public const SCHEDULED_AT = 'scheduled_at';
	public const PUBLISHED_AT = 'published_at';
	public const CREATED_AT = 'created_at';

	public static function batchPostSortQuery()
	{
		return [
			PostStatus::PUBLISHED 	=> self::PUBLISHED_AT,
			PostStatus::SCHEDULED 	=> self::SCHEDULED_AT,
			PostStatus::DRAFT		=> self::CREATED_AT,
			PostStatus::PENDING   	=> self::SCHEDULED_AT,
			PostStatus::FAILED    	=> self::SCHEDULED_AT,
			PostStatus::REJECTED    => self::SCHEDULED_AT,
		];
	}
}
