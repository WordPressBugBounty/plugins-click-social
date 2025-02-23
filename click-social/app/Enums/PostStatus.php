<?php

namespace Smashballoon\ClickSocial\App\Enums;

if (! defined('ABSPATH')) {
	exit;
}

class PostStatus
{
	public const DRAFT = 'draft';
	public const SCHEDULED = 'scheduled';
	public const PUBLISHED = 'published';
	public const PENDING = 'pending';
	public const REJECTED = 'rejected';
	public const FAILED = 'failed';
}
