<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) {
	exit;
}

// phpcs:disable Generic.Files.LineLength.TooLong
/* These are included using __() to make them translatable and detected and added into the translation files. */
class ErrorMessage extends \WP_Error
{
	public const QUICKSHARE_NO_PERMISSIONS = 'quickshare-no-permissions';
	public const QUICKSHARE_NO_POST_SELECTED = 'quickshare-no-post-selected';
	public const QUICKSHARE_POST_MISSING = 'quickshare-post-missing';
	public const QUICKSHARE_API_ERROR = 'quickshare-api-error';
	public const QUICKSHARE_NO_ACCOUNTS = 'quickshare-no-accounts';

	public function constructor($key, $atts)
	{
		return parent::__construct($key, $this->getTranslatedMessage($key), $atts);
	}

	public function getTranslatedMessage($key)
	{
		if ($key === self::QUICKSHARE_NO_PERMISSIONS) {
			return __('No permissions for QuickShare.', 'click-social');
		}

		if ($key === self::QUICKSHARE_NO_POST_SELECTED) {
			return __('No WordPress post provided for QuickShare.', 'click-social');
		}

		if ($key === self::QUICKSHARE_POST_MISSING) {
			return __('WordPress post missing for QuickShare.', 'click-social');
		}

		if ($key === self::QUICKSHARE_API_ERROR) {
			return __('Error while scheduling posts.', 'click-social');
		}

		if ($key === self::QUICKSHARE_NO_ACCOUNTS) {
			return __(
				'Your WordPress account doesn\'t have permissions to create any Social Post using ClickSocial QuickShare. Ask your administrator to grant you permissions.',
				'click-social'
			);
		}
	}
}
// phpcs:enable Generic.Files.LineLength.TooLong
