<?php

namespace Smashballoon\ClickSocial\App\Services;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class TranslationService
 * Handles loading and managing translations for the plugin.
 */
class TranslationService
{
	use SingleTon;

	public function register()
	{
		// Only load translations on Click Social admin pages
		if (sbcs_is_click_social_page()) {
			// Load and share translations
			$this->loadAndShareTranslations();
		}
	}

	/**
	 * Load translations and share them with Inertia
	 */
	public function loadAndShareTranslations()
	{
		$translations = $this->loadTranslations();

		// Share translations with Inertia
		InertiaAdapter\Inertia::share(['translations' => $translations]);
	}

	/**
	 * Load translations from JSON files
	 *
	 * @return array The loaded translations
	 */
	public function loadTranslations()
	{
		// Use WordPress transients for caching
		$locale = get_locale();
		$cache_key = 'sbcs_translations_' . $locale;
		$translations = get_transient($cache_key);

		if (false !== $translations) {
			return $translations;
		}

		$translations = [];
		$json_files = $this->getTranslationFiles($locale);

		foreach ($json_files as $file) {
			$file_translations = $this->readTranslationFile($file);
			if (!empty($file_translations)) {
				$translations = array_merge(
					$translations,
					$file_translations['locale_data']['click-social'] ?? []
				);
			}
		}

		// Cache for 12 hours
		set_transient($cache_key, $translations, 12 * HOUR_IN_SECONDS);

		return $translations;
	}

	/**
	 * Get translation files for the given locale
	 *
	 * @param string $locale The locale to get translations for
	 * @return array Array of file paths
	 */
	private function getTranslationFiles($locale)
	{
		$files = [];
		$dir = SBCS_DIR_PATH . 'languages';
		$prefix = 'click-social-' . $locale . '-';

		if (is_dir($dir)) {
			$directory = new \DirectoryIterator($dir);
			foreach ($directory as $file) {
				if (!$file->isFile()) {
					continue;
				}

				$filename = $file->getFilename();
				if (strpos($filename, $prefix) === 0 && substr($filename, -5) === '.json') {
					$files[] = $file->getPathname();
				}
			}
		}

		return $files;
	}

	/**
	 * Read and parse a translation file using WordPress filesystem API
	 *
	 * @param string $file The path to the translation file
	 * @return array|null The parsed translations or null on error
	 */
	private function readTranslationFile(string $file): ?array
	{
		$result = null;

		// Only proceed if the file exists and is readable
		if (file_exists($file) && is_readable($file)) {
			// Initialize WordPress filesystem
			global $wp_filesystem;

			if (!function_exists('WP_Filesystem')) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$filesystem_initialized = WP_Filesystem();

			// Use WordPress filesystem API to read the file
			if ($filesystem_initialized) {
				$file_content = $wp_filesystem->get_contents($file);

				if (false !== $file_content) {
					$decoded = json_decode($file_content, true);

					// Only set result if JSON was valid
					if (null !== $decoded || JSON_ERROR_NONE === json_last_error()) {
						$result = $decoded;
					}
				}
			}
		}

		return $result;
	}
}
