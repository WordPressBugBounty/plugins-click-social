<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class MigrationManager
{
	use SingleTon;

	protected $pluginLatestVersion;

	/**
	 * All the migrations that need to be executed before the app is booted,
	 * will be executed here.
	 */
	public function run()
	{
		$this->executeMigrations();
	}

	/**
	 * Run all migrations.
	 */
	private function executeMigrations()
	{
		$this->setPluginLatestVersion();

		$migrations = sbcs_get_config('migrations');
		if (empty($migrations)) {
			return;
		}

		foreach ($migrations as $version => $class) {
			if (!$this->shouldRunMigrations($version)) {
				return;
			}

			(new $class())->up();
		}

		$this->markMigrationAsFinished();
	}

	private function setPluginLatestVersion()
	{
		$this->pluginLatestVersion = sbcs_get_env('RELEASE_VERSION', sbcs_get_config('plugin_version'));
	}

	private function shouldRunMigrations($ref_version)
	{
		$run_migrations_up_to = SettingsManager::getInstance()->get('plugin_version');

		$current_version = $this->pluginLatestVersion;

		if (!$run_migrations_up_to) {
			return true;
		}

		return version_compare($current_version, $ref_version, '>=')
			&& version_compare($current_version, $run_migrations_up_to, '>');
	}

	private function markMigrationAsFinished()
	{
		SettingsManager::getInstance()->update('plugin_version', $this->pluginLatestVersion);
	}
}
