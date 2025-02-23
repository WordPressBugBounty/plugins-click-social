<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class PostAttachmentService
{
	use SingleTon;

	/**
	 * Check if a media file exists and is accessible.
	 *
	 * @param array $media Media item data
	 * @return bool Whether the media file exists and is accessible
	 */
	public function mediaFileExists($media)
	{
		// For WordPress media library items.
		if (!empty($media['id'])) {
			$attachment = get_post($media['id']);
			if (!$attachment) {
				return false;
			}
			return true;
		}

		// For external URLs.
		$url = $media['url'] ?? $media['file_url'] ?? '';
		if (empty($url)) {
			return false;
		}

		// If it's a local WordPress URL.
		if (strpos($url, wp_get_upload_dir()['baseurl']) !== false) {
			$file_path = str_replace(
				wp_get_upload_dir()['baseurl'],
				wp_get_upload_dir()['basedir'],
				$url
			);
			return file_exists($file_path);
		}

		// For external URLs, do a lightweight HEAD request
		$response = wp_safe_remote_head($url);
		return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
	}
}
