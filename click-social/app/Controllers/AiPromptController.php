<?php

namespace Smashballoon\ClickSocial\App\Controllers;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Services\WPPosts;

if (!defined('ABSPATH')) {
	exit;
}

class AiPromptController extends BaseController
{
	public function allPrompt()
	{
		$res = AuthHttp::get('ai/prompts');
		$prompts = $res->getBody(true);

		return $this->render('Settings/Workspace/AiPromptLib', [
			'prompts' => $prompts['data'] ?? [],
		]);
	}

	public function singlePrompt($request)
	{
		$data = $this->singlePromptResponse($request, []);
		return $this->render('Settings/Workspace/SinglePrompt', $data);
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
			'wpPosts'				=> WPPosts::getPosts(),
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

		return $this->allPrompt();
	}

	public function remove($request)
	{
		AuthHttp::post('ai/prompts/batch', [
			'delete' => $request->input('promptUuid'),
		]);

		return $this->allPrompt();
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

		return $this->allPrompt();
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

		return $this->render('Settings/Workspace/SinglePrompt', $data);
	}
}
