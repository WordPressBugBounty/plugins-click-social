<?php

namespace Smashballoon\ClickSocial\App\Services;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;

class ShortlinkHandlerService
{
	use SingleTon;

	private $shortlinkIdKey;

	public function register()
	{
		$this->shortlinkIdKey = sbcs_get_config('shortlink_id_key');

		add_action('init', [$this, 'addRewriteRule']);
		add_filter('query_vars', [$this, 'addQueryVars']);
		add_action('template_redirect', [$this, 'redirectShortlink']);
	}

	public function addQueryVars($query_vars)
	{
		$query_vars[] = $this->shortlinkIdKey;
		return $query_vars;
	}

	public function addRewriteRule()
	{
		add_rewrite_rule(
			'^' . $this->shortlinkIdKey . '/([^/]*)/?',
			'index.php?' . $this->shortlinkIdKey . '=$matches[1]',
			'top'
		);
	}

	public function redirectShortlink()
	{
		global $wp_rewrite;

		$shortLinkId = get_query_var($this->shortlinkIdKey);

		// Don't redirect if shortlink id is not found.
		if (!$shortLinkId) {
			return;
		}

		// Decode shortlink id encoded as base64 and cast it to int.
		$postId = (int)base64_decode($shortLinkId, true);

		// If post id is not found, return.
		if (!$postId) {
			return;
		}

		// Redirect to post url.
		$url = get_permalink($postId);
		wp_redirect(esc_url_raw($url), 302);
		exit;
	}
}
