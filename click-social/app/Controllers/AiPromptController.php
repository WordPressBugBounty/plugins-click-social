<?php

namespace Smashballoon\ClickSocial\App\Controllers;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Request;
use Smashballoon\ClickSocial\App\Services\SocialAccountService;
use Smashballoon\ClickSocial\App\Services\WPPosts;

if (!defined('ABSPATH')) {
	exit;
}

class AiPromptController extends BaseController
{
	/**
	 * @var array
	 */
	private $socialAccounts;

	public function __construct()
	{
		$this->socialAccounts = SocialAccountService::filterSocialAccountsForCurrentUser() ?: [];
	}

	/**
	 * Retrieve and display AI prompts with pagination, search and sorting capabilities.
	 * Makes an authenticated request to the AI prompts API endpoint and renders the results.
	 *
	 * @param $request The HTTP request object containing:
	 * @type int $page Page number for pagination
	 * @type string $search Search term for filtering prompts
	 * @type string $sort Sort direction ('asc' or 'desc')
	 *
	 * @return View Returns rendered view with prompt data
	 */
	public function allPrompt($request)
	{
		// Extract and set default values for request parameters
		$page = $request->input('promptPage') ?? 1;
		$search = $request->input('search') ?? '';
		$sort = $request->input('sort') ?? 'desc';

		// Prepare parameters for API request
		$params = [
			'page' => $page,
			'sort' => $sort,
		];
		if ($search) {
			$params['search'] = $search;
		}

		// Make authenticated API request
		$res = AuthHttp::get('ai/prompts', $params);
		$prompts = $res->getBody(true);

		// Render view with processed data and pagination information
		return $this->render('Settings/Workspace/AiPromptLib', [
			'prompts' => $prompts['data'] ?? [],
			'total' => $prompts['total'] ?? 0,
			'current_page' => $prompts['current_page'] ?? 1,
			'per_page' => $prompts['per_page'] ?? 10,
			'social_accounts' => $this->socialAccounts
		]);
	}

	public function singlePrompt($request)
	{
		$data = $this->singlePromptResponse($request, []);
		return $this->render('Settings/Workspace/PromptLib/SinglePrompt', $data);
	}

	private function singlePromptResponse($request, $data)
	{
		$promptUuid = sanitize_text_field($request->input('promptUuid'));

		$prompt = [];
		$pageTitle = __('New Prompt', 'click-social');
		if ($promptUuid) {
			$pageTitle = __('Edit Prompt', 'click-social');

			$res = AuthHttp::get('ai/prompts/id/' . $promptUuid);
			$prompt = $res->getBody(true);
		}

		$default = [
			'promptUuid'			=> $promptUuid,
			'prompt'				=> $prompt['data'] ?? false,
			'singlePromptPageTitle'	=> $pageTitle,
			'social_accounts'       => $this->socialAccounts
		];

		return \wp_parse_args($data, $default);
	}

	public function store($request)
	{
		$title = sanitize_textarea_field($request->input('title'));
		$prompt = sanitize_textarea_field($request->input('prompt'));

		AuthHttp::post('ai/prompts', [
			'title' => $title,
			'prompt' => $prompt,
		]);

		return $this->allPrompt(new Request());
	}

	public function remove($request)
	{
		AuthHttp::post('ai/prompts/batch', [
			'delete' => $request->input('promptUuid'),
		]);

		return $this->allPrompt(new Request());
	}

	public function update($request)
	{
		$promptUuid = sanitize_text_field($request->input('promptUuid'));
		$title = sanitize_textarea_field($request->input('title'));
		$prompt = sanitize_textarea_field($request->input('prompt'));

		AuthHttp::post('ai/prompts/update', [
			'uuid'		=> $promptUuid,
			'title'		=> $title,
			'prompt'	=> $prompt,
		]);

		return $this->allPrompt(new Request());
	}

	public function aiGenerate($request)
	{
		$prompt = sanitize_text_field($request->input('prompt'));
		$wpPostId = sanitize_text_field($request->input('wpPostId'));

		$prompt = $prompt . "\n" . WPPosts::getPostContent($wpPostId);

		$res = AuthHttp::post('ai/generate', [
			'prompt'	=> $prompt,
		]);

		$data = $this->singlePromptResponse($request, [
			'aiContent' => $res->getBody(true)['data'] ?? false,
		]);

		return $this->render('Settings/Workspace/PromptLib/SinglePrompt', $data);
	}
}
