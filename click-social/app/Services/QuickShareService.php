<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) {
	exit;
}

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Enums\PostScheduledAt;
use Smashballoon\ClickSocial\App\Enums\PostStatus;
use Smashballoon\ClickSocial\App\Enums\QuickShareAction;
use Smashballoon\ClickSocial\App\Enums\WpUserCapabilities;
use Smashballoon\ClickSocial\App\Enums\WpUserRoles;

class QuickShareService
{
	use SingleTon;

	public const POST_META_KEY = 'clicksocial_quick_share_templates_%d';

	public $currentUser;
	public $userSocialAccounts;
	public $userRole;

	public function fetchTemplatesForPost($postId, $default = [])
	{
		$templates = get_post_meta(
			$postId,
			sprintf(self::POST_META_KEY, $this->currentUser->ID),
			true
		);

		if (!$templates) {
			return $default;
		}

		return json_decode($templates, true);
	}

	public function storeTemplatesForPost($postId, $postTemplates)
	{
		// Save post templates in meta field.
		return update_post_meta(
			$postId,
			sprintf(self::POST_META_KEY, $this->currentUser->ID),
			wp_json_encode($postTemplates)
		);
	}

	public function fetchCurrentUserRole()
	{
		$this->currentUser = wp_get_current_user();
		if (!$this->currentUser || !$this->currentUser->exists()) {
			return false;
		}

		$this->userRole = MemberTransaction::getUserRole();
		if (!$this->userRole) {
			return false;
		}

		return true;
	}

	/**
	 * Fetch social accounts for user.
	 *
	 * @return bool
	 */
	public function fetchUserSocialAccounts()
	{
		$this->userSocialAccounts = SocialAccountService::filterSocialAccountsForCurrentUser();
		if (empty($this->userSocialAccounts)) {
			return false;
		}

		return true;
	}

	public function processTemplates($postTemplates)
	{
		global $clicksocial_post;
		$filteredTemplates['accounts'] = [];

		foreach ($postTemplates['accounts'] as $account) {
			// Check if user has view only permission for this account.
			if (
				!$this->hasCurrentUserPermissionToCreateSocialPost($account)
			) {
				continue;
			}

			$accountTemplate = $account['templates']['template'];

			// Only process shortcodes if post is not in draft mode
			// because the post title will default to `Auto Draft`.
			if ($clicksocial_post->post_status !== 'auto-draft') {
				$accountTemplate = TemplateShortcodesService::getInstance()->doShortcodes($accountTemplate);
			}

			$account['templates']['template'] = $accountTemplate;

			$filteredTemplates['accounts'][] = $account;
		}

		$postTemplates['accounts'] = $filteredTemplates['accounts'];

		return $postTemplates;
	}


	/**
	 * Has current user permission to create social post.
	 *
	 * @param array $account Social account.
	 *
	 * @return bool
	 */
	protected function hasCurrentUserPermissionToCreateSocialPost($account)
	{
		$wordpress_user_id = $this->currentUser->ID;

		return !empty(
			array_filter(
				$this->userSocialAccounts,
				function ($social_account) use ($account, $wordpress_user_id) {
					// Check if user has view only permission for this account.
					if ($social_account['uuid'] !== $account['uuid']) {
						return false;
					}

					// Allow users with super admin or admin role to post.
					if (in_array($this->userRole, [ WpUserRoles::SuperAdmin, WpUserRoles::Admin ], true)) {
						return true;
					}

					// Check if user has view only permission for this account.
					if (
						in_array(
							MemberTransaction::getUserCapabilityForSocialAccount($account['uuid'], $wordpress_user_id),
							[
								WpUserCapabilities::FullAccess,
								WpUserCapabilities::FullPostAccess,
								WpUserCapabilities::ApprovalRequired
							],
							true
						)
					) {
						return true;
					}

					return false;
				}
			)
		);
	}

	/**
	 * Generate all scheduled posts based on the provided social accounts and quick share type.
	 *
	 * @param array $socialAccounts List of social accounts with associated templates.
	 * @param string $quickShareType Type of quick share action (e.g., NOW, NEXT_EMPTY_TIMESLOT, DRAFT).
	 *
	 * @return array List of generated posts with updated content, scheduled time, and status.
	 */
	public function generateAllScheduledPosts($socialAccounts, $quickShareType)
	{
		$generatedPosts = [];

		switch ($quickShareType) {
			case PostScheduledAt::NOW:
			case PostScheduledAt::NEXT_EMPTY_TIMESLOT:
				foreach ($socialAccounts as $social_account) {
					$generatedPosts[] = array_merge(
						$social_account['templates'],
						[
						'scheduled_at' => $quickShareType,
						'content' => $social_account['templates']['template'],
						'status' => PostStatus::SCHEDULED
						]
					);
				}

				break;

			case QuickShareAction::DRAFT:
				foreach ($socialAccounts as $social_account) {
					$generatedPosts[] = array_merge(
						$social_account['templates'],
						[
							'scheduled_at' => '',
							'content' => $social_account['templates']['template'],
							'status' => PostStatus::DRAFT
						]
					);
				}

				break;

			default: // No action.
		}

		return $generatedPosts;
	}

	/**
	 * Format accounts data for processing or sending to an API.
	 *
	 * @param array $accounts A list of accounts containing templates, UUIDs, and related data.
	 * @param string $action The action or status to apply to the formatted data.
	 *
	 * @return array The formatted data with templates, media, and additional account details.
	 */
	public function formatAccountsData($accounts, $action)
	{
		// Prepare templates before send to Laravel API.
		$formattedData = [];

		foreach ($accounts as $account) {
			if (
				empty($account['templates']['template']) ||
				empty($account['uuid']) ||
				empty($action)
			) {
				continue;
			}

			$media = [];

			if (!empty($account['templates']['media'])) {
				foreach ($account['templates']['media'] as $mediaItem) {
					$media[] = [
						'file_url' => $mediaItem['url'],
						'mime_type' => $mediaItem['mime'],
					];
				}
			}

			$formattedData[] = [
				'templates' => [
					'template' => $account['templates']['template'],
					'status' => $action,
					'social_account_uuid' => $account['uuid'],
					'media' => $media
				]
			];
		}

		return $formattedData;
	}

	/**
	 * Schedules posts to be published based on provided payload data.
	 *
	 * @param array $payload An associative array containing the necessary data for scheduling posts,
	 *                        including accounts information and the action to be performed.
	 *
	 * @return array An associative array containing the response details with `statusCode` and `body` keys.
	 */
	public function schedulePosts($payload)
	{
		$accounts = $this->formatAccountsData($payload['accounts'], $payload['action']);

		$posts = $this->generateAllScheduledPosts($accounts, $payload['action']);

		if (empty($posts)) {
			return [];
		}

		$res = AuthHttp::post('posts/batch', ['data' => $posts]);

		return [
			'statusCode'	=> $res->getStatusCode(),
			'body'			=> $res->getBody(),
		];
	}

	/**
	 * Processes the provided post templates to prepare them for storage by restructuring
	 * the data and filtering media files that exist.
	 *
	 * @param array $postTemplates The input templates containing action details and accounts data.
	 *     Each account should include a 'uuid' and 'templates' array with 'template' and optionally 'media'.
	 *
	 * @return array The processed templates prepared for storage, including filtered media files for each account.
	 */
	public function processTemplatesForStorage($postTemplates)
	{
		$storageTemplates = [
			'action' => $postTemplates['action'],
			'accounts' => []
		];

		foreach ($postTemplates['accounts'] as $account) {
			$storageTemplate = [
				'uuid' => $account['uuid'],
				'templates' => $account['templates'],
			];

			// Store media data if it exists.
			if (!empty($account['templates']['media'])) {
				$storageTemplate['templates']['media'] = array_filter(
					$account['templates']['media'],
					[PostAttachmentService::getInstance(), 'mediaFileExists']
				);
			}

			$storageTemplates['accounts'][] = $storageTemplate;
		}

		return $storageTemplates;
	}

	/**
	 * Merges stored templates with the current templates by updating the current templates
	 * with content and media from the stored templates where applicable.
	 *
	 * @param array $storedTemplates The templates previously stored, containing account data
	 *     with 'uuid', 'templates' (including 'template' and optionally 'media' as files).
	 * @param array $currentTemplates The templates currently being processed, containing account data
	 *     with 'uuid', 'templates' (including 'template' and optionally 'media').
	 *
	 * @return array The updated current templates with merged content and valid media from the stored templates.
	 */
	public function mergeStoredTemplates($storedTemplates, $currentTemplates)
	{
		if (empty($storedTemplates) || empty($storedTemplates['accounts'])) {
			return $currentTemplates;
		}

		// Create a lookup of stored templates by UUID.
		$storedTemplatesMap = array_column($storedTemplates['accounts'], null, 'uuid');

		foreach ($currentTemplates['accounts'] as $key => $account) {
			if (isset($storedTemplatesMap[$account['uuid']])) {
				$storedTemplate = $storedTemplatesMap[$account['uuid']];

				// Merge stored template content while keeping other current account data
				$currentTemplates['accounts'][$key]['templates']['template'] =
					$storedTemplate['templates']['template'];

				// Merge media if it exists and files still exist
				if (!empty($storedTemplate['templates']['media'])) {
					$validMedia = array_filter(
						$storedTemplate['templates']['media'],
						[PostAttachmentService::getInstance(), 'mediaFileExists']
					);

					if (!empty($validMedia)) {
						$currentTemplates['accounts'][$key]['templates']['media'] = array_values($validMedia);
					}
				}
			}
		}

		return $currentTemplates;
	}
}
