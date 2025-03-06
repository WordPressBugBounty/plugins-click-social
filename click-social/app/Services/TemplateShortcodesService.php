<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Core\SettingsManager;

class TemplateShortcodesService
{
	use SingleTon;

	public function register()
	{
		add_shortcode('clicksocial_title', [$this, 'renderPostTitle']);
		add_shortcode('clicksocial_excerpt', [$this, 'renderPostExcerpt']);
		add_shortcode('clicksocial_short_link', [$this, 'renderShortlink']);
		add_shortcode('clicksocial_categories_as_hashtags', [$this, 'renderCategoriesAsHashtags']);
		add_shortcode('clicksocial_post_link', [$this, 'renderPostlink']);
	}

	public function renderCategoriesAsHashtags()
	{
		global $clicksocial_post;

		if (!$clicksocial_post) {
			return false;
		}

		// Get the categories names from WP_Post as a string.
		$categories = get_the_category($clicksocial_post->ID);

		if (empty($categories)) {
			return '';
		}

		$categories = array_map(
			function ($category) {
				return $category->name ? '#' . $category->name : null;
			},
			$categories
		);

		return implode(', ', array_filter($categories));
	}

	public function renderShortlink()
	{
		global $clicksocial_post, $wp_rewrite;

		// Early return if post doesn't exist
		if (!$clicksocial_post) {
			return false;
		}

		// If shortlinks are disabled, return the permalink
		if (SettingsManager::getInstance()->get('disable_shortlink', false)) {
			return get_permalink($clicksocial_post->ID);
		}

		// Generate the shortlink (final return - this is the 3rd return)
		$clicksocialId = sbcs_get_config('shortlink_id_key');
		$encodedId = urlencode(base64_encode($clicksocial_post->ID));

		return ($wp_rewrite->permalink_structure !== '')
			? get_site_url() . '/' . $clicksocialId . '/' . $encodedId
			: get_site_url() . '/?' . $clicksocialId . '=' . $encodedId;
	}



	public function renderPostTitle()
	{
		global $clicksocial_post;

		if (!$clicksocial_post) {
			return false;
		}

		return $clicksocial_post->post_title;
	}

	public function renderPostExcerpt()
	{
		global $clicksocial_post;

		if (!$clicksocial_post) {
			return false;
		}

		if ($clicksocial_post->excerpt) {
			return $clicksocial_post->excerpt;
		}

		$excerpt = get_the_excerpt($clicksocial_post);

		$addSuffix = '';

		if (strlen($excerpt) > sbcs_get_config('post.max_length')) {
			$addSuffix = '...';
		}

		return wp_kses_post(
			substr(
				substr($excerpt, 0, sbcs_get_config('post.max_length')),
				0,
				strrpos($excerpt, ' ')
			) . $addSuffix
		);
	}

	public function renderPostlink()
	{
		global $clicksocial_post;

		if (!$clicksocial_post) {
			return false;
		}

		return get_permalink($clicksocial_post->ID);
	}

	public function doShortcodes($templateContent)
	{
		// Transform user-friendly shortcodes to the actual WordPress supported shortcodes.
		$templateContent = str_replace(
			[
				'[title]',
				'[excerpt]',
				'[short_link]',
				'[categories_hashtags]',
				'[post_link]'
			],
			[
				'[clicksocial_title]',
				'[clicksocial_excerpt]',
				'[clicksocial_short_link]',
				'[clicksocial_categories_as_hashtags]',
				'[clicksocial_post_link]'
			],
			$templateContent
		);

		// Process the shortcodes.
		return do_shortcode($templateContent);
	}
}
