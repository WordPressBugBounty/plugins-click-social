<?php

namespace Smashballoon\ClickSocial\App\Enums;

if (! defined('ABSPATH')) {
	exit;
}

class PostScheduledAt
{
	public const NOW = 'now';
	public const NEXT_EMPTY_TIMESLOT = 'next_empty_timeslot';
}
