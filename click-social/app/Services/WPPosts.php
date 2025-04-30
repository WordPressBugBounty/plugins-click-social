<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

class WPPosts
{
	public static function getPosts($args = [])
	{
		$defaults = [
			'post_type'			=> 'post',
			'posts_per_page'	=> 20,
			'orderby'			=> 'date',
			'order'				=> 'DESC',
			'post_status'		=> 'publish',
		];

		$args = \wp_parse_args($args, $defaults);

		$query = new \WP_Query($args);

		$posts = false;
		if ($query->have_posts()) {
			$posts = $query->posts;
		}

		return $posts;
	}

	public static function getPostContent($postId)
	{
		$postContent = null;

		if ($postId) {
			$getContent = \get_the_content(null, false, $postId);
			$postContent = \wp_strip_all_tags($getContent);
		} else {
			$post = static::getPosts(['posts_per_page' => 1]);
			if (! empty($post[0])) {
				$postContent = \get_the_content(null, false, $post[0]->ID);
			}
		}

		$postContent = \wp_strip_all_tags($postContent);

		$excerptLimit = 300;	// max words from the post content
		if (preg_match('/^(\s*\S+){1,' . $excerptLimit . '}/', $postContent, $content)) {
			return $content[0] ?? '';
		}

		return $postContent;
	}
}
