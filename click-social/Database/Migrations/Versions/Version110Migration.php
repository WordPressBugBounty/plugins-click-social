<?php

namespace Smashballoon\ClickSocial\Database\Migrations\Versions;

use Smashballoon\ClickSocial\App\Core\SettingsManager;

if (!defined('ABSPATH')) {
	exit;
}

class Version110Migration
{
	/**
	 * Migrate the template structure to new format.
	 *
	 * @param array $template
	 *
	 * @return array
	 */
	public function up()
	{
		add_action('admin_init', [$this, 'migrateInWPAdmin']);
	}

	/**
	 * Run the migration logic in WordPress admin. For heavy migrations,
	 * it is recommended to run them in the admin area.
	 *
	 * @return void
	 */
	public function migrateInWPAdmin()
	{
		$this->migrateQuickShareTemplatesFromContentToTemplate();
		$this->migrateUsersPostsQuickShareTemplatesFromContentToTemplate();
	}

	/**
	 * Migrates template structure from 'content' to 'template' if needed
	 *
	 * @param array &$templates The templates array to potentially modify
	 * @return array The migrated templates array
	 */
	private function migrateTemplateStructure($templates)
	{
		// If we have the old 'content' key but not the new 'template' key
		if (isset($templates['content']) && !isset($templates['template'])) {
			// Add the new key with the same value
			$templates['template'] = $templates['content'];
			unset($templates['content']);
		}

		return $templates;
	}

	/**
	 * Migration for QuickShare templates from 'content' to 'template'.
	 *
	 * @return void
	 */
	public function migrateQuickShareTemplatesFromContentToTemplate()
	{
		// Update all QuickShare templates to new format.
		$quickShare = SettingsManager::getInstance()->get('quick_share', false);

		if (empty($quickShare['accounts'])) {
			return;
		}

		foreach ($quickShare['accounts'] as $id => $account) {
			$quickShare['accounts'][$id]['templates']['template'] = $account['templates']['template']
																	?? $account['templates']['content'];
			unset($quickShare['accounts'][$id]['templates']['content']);
		}
		SettingsManager::getInstance()->update('quick_share', $quickShare);
	}

	/**
	 * Migration for Users Posts QuickShare templates from 'content' to 'template'.
	 *
	 * @return void
	 */
	public function migrateUsersPostsQuickShareTemplatesFromContentToTemplate()
	{
		// Update all WordPress posts with new quickshare templates.
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Runs only once on plugin upgrade.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Runs only once on plugin upgrade.
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_key
				FROM `{$wpdb->prefix}postmeta`
				WHERE `meta_key` LIKE %s",
				'clicksocial_quick_share_templates_%'
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		if (!$posts) {
			return;
		}

		foreach ($posts as $post) {
			$postTemplates = get_post_meta(
				$post['post_id'],
				$post['meta_key'],
				true
			);

			if (empty($postTemplates)) {
				continue;
			}

			$postTemplates = json_decode($postTemplates, true);

			if (empty($postTemplates['accounts'])) {
				continue;
			}

			foreach ($postTemplates['accounts'] as $id => $account) {
				$postTemplates['accounts'][$id] = $account['templates'] = array_map(function ($template) {
					return $this->migrateTemplateStructure($template);
				}, $postTemplates['accounts'][$id]);
			}

			update_post_meta(
				$post['post_id'],
				$post['meta_key'],
				wp_json_encode($postTemplates)
			);
		}
	}
}
