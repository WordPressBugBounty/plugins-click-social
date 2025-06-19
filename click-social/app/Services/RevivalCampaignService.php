<?php

namespace Smashballoon\ClickSocial\App\Services;

use WP_Query;

if (! defined('ABSPATH')) {
	exit;
}

class RevivalCampaignService
{
	private $settings;

	/**
	 * @var array
	 */
	private $postIds;

	/**
	 * Constructor.
	 *
	 * @param array $settings Settings of the campaign.
	 */
	public function __construct($settings)
	{
		$this->settings = $settings;
	}

	public function setPostIds($postIds)
	{
		$this->postIds = $postIds;
	}

	/**
	 * Fetch WP posts from database.
	 *
	 * @return array
	 */
	public function fetchWPPosts(): array
	{
		// Get all posts by querying by post id.
		$wpPosts = new WP_Query([
			'posts_per_page' => -1,
			'post_type'      => 'post',
			'post__in' => $this->postIds,
		]);

		return $wpPosts->posts;
	}

	/**
	 * Generate all scheduled posts for all selected social accounts.
	 *
	 * @param string $revivalCampaignId Revival Campaign id.
	 *
	 * @return array
	 */
	public function generateAllScheduledPosts($revivalCampaignId): array
	{
		$scheduledPosts = [];
		$campaignSettings = $this->settings;
		$timezone = DateTimeService::isUsingWpTimezone() ? DateTimeService::getTimezone() : null;

		// Get WP posts using post ids.
		$wpPosts = $this->fetchWPPosts();

		// Generate timeslots for all posts.
		$timeslots = TimeslotsScheduleService::generate(
			array_filter(
				$campaignSettings['filter_data']['timeslots'],
				function ($timeslot) {
					return !empty($timeslot['publish_at']);
				}
			),
			count($wpPosts)
		);

		// Flatten templates array for easy filtering.
		$indexedTemplates = wp_list_pluck($campaignSettings['filter_data']['templates'], 'template', 'uuid');

		// Generate posts for each account separately.
		$accountPosts = [];
		foreach ($campaignSettings['filter_data']['selected_accounts'] as $social_account_uuid) {
			$templateContent = $indexedTemplates[$social_account_uuid];

			$posts = $this->generateScheduledPostsForSocialAccount(
				$wpPosts,
				$revivalCampaignId,
				$timezone,
				$templateContent,
				$timeslots,
				$social_account_uuid
			);

			$accountPosts[$social_account_uuid] = $posts;
		}

		// Round-robin merge: pick one from each account, then repeat.
		// Mixing the posts from all selected social networks allows equal distribution
		// of posts, even if the revival campaign post limit is reached.
		$maxPosts = max(array_map('count', $accountPosts));
		$accountKeys = array_keys($accountPosts);

		for ($i = 0; $i < $maxPosts; $i++) {
			foreach ($accountKeys as $accountUuid) {
				if (isset($accountPosts[$accountUuid][$i])) {
					$scheduledPosts[] = $accountPosts[$accountUuid][$i];
				}
			}
		}

		return $scheduledPosts;
	}

	/**
	 * Generate scheduled posts for social account.
	 *
	 * @param array $wpPosts WordPress posts.
	 * @param $revivalCampaignId
	 * @param $timezone
	 * @param $templateContent
	 * @param $timeslots
	 * @param $social_account_uuid
	 *
	 * @return array|array[]
	 */
	public function generateScheduledPostsForSocialAccount(
		$wpPosts,
		$revivalCampaignId,
		$timezone,
		$templateContent,
		$timeslots,
		$social_account_uuid
	) {
		return array_map(
			function (
				$wpPost,
				$i
			) use (
				$revivalCampaignId,
				$timezone,
				$templateContent,
				$timeslots,
				$social_account_uuid
			) {
				global $clicksocial_post;

				$clicksocial_post = $wpPost;

				$post = [
					'wp_post_id'            => $wpPost->ID,
					'content'               => TemplateShortcodesService::getInstance()->doShortcodes($templateContent),
					'revival_campaign_uuid' => $revivalCampaignId,
					'status'                => 'scheduled',
					'social_account_uuid'   => $social_account_uuid,
					'scheduled_at'          => $timeslots[ $i ],
				];

				if ($timezone) {
					$post['timezone'] = $timezone;
				}

				$thumbnail = get_the_post_thumbnail_url($wpPost->ID, 'full');
				if ($thumbnail) {
					$post['media'] = [
						// TODO: should we include media mandatory.
						[
							'file_url'  => $thumbnail,
							'mime_type' => get_post_mime_type(get_post_thumbnail_id($wpPost->ID)),
						],
					];
				}

				return $post;
			},
			$wpPosts,
			array_keys($wpPosts)
		);
	}
}
