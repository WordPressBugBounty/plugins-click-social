<?php

namespace Smashballoon\ClickSocial\App\Controllers;

use Smashballoon\ClickSocial\App\Core\Lib\AuthHttp;
use Smashballoon\ClickSocial\App\Core\Lib\Http;
use Smashballoon\ClickSocial\App\Enums\PostStatus;
use Smashballoon\ClickSocial\App\Services\DateTimeService;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\PostService;
use Smashballoon\ClickSocial\App\Services\SocialAccountService;

if (! defined('ABSPATH')) {
	exit;
}

class PostController extends BaseController
{
	public function approvePost($request)
	{
		$payload = [
			'uuid'				=> sanitize_text_field($request->input('postId')),
			'status'			=> PostStatus::SCHEDULED,
			'requires_approval' => 0,
		];

		$res = AuthHttp::post('posts/update', $payload);

		$frontendResponse = $this->prepareFrontendData($request);
		$frontendResponse['postUpdateResponse'] = $res->getBody(true);

		return $this->render('Calendar', $frontendResponse);
	}

	public function rejectPost($request)
	{
		$payload = [
			'uuid'				=> sanitize_text_field($request->input('postId')),
			'status'			=> PostStatus::REJECTED,
			'requires_approval' => 0,
		];

		$res = AuthHttp::post('posts/update', $payload);

		$frontendResponse = $this->prepareFrontendData($request);
		$frontendResponse['postUpdateResponse'] = $res->getBody(true);

		return $this->render('Calendar', $frontendResponse);
	}

	public function deletePost($request)
	{
		$payload = [
			'uuid'	=> sanitize_text_field($request->input('postId')),
		];

		$res = AuthHttp::post('posts/remove', $payload);

		$frontendResponse = $this->prepareFrontendData($request);
		$frontendResponse['postDeleteResponse'] = $res->getBody(true);

		return $this->render('Calendar', $frontendResponse);
	}

	public function prepareFrontendData($request, $additionalPayload = [])
	{
		$timezone = function () {
			return DateTimeService::getTimezone();
		};

		$social_accounts = function () {
			return SocialAccountService::filterSocialAccountsForCurrentUser();
		};

		$timeslots = Inertia::lazy(function () use ($request) {
			return AuthHttp::get(
				'timeslots',
				['social_account_uuid' => sanitize_text_field($request->input('social_account_uuid'))]
			)->getBody(true);
		});

		$posts = function () use ($request) {
			return PostService::getBatchPost($request);
		};

		return [
			'posts'				=> $posts,
			'social_accounts'	=> $social_accounts,
			'social_account_uuid'	=> sanitize_text_field($request->input('social_account_uuid')),
			'timeslots'			=> $timeslots,
			'submission'		=> ['responses'	=> $additionalPayload],
			'timezone'			=> $timezone(),
		];
	}

	public function singlePostWithComments($request = null, $additionalPayload = [])
	{
		$comments = function () use ($request) {
			return AuthHttp::get(
				'post/comments',
				[
					'post_uuid' => sanitize_text_field($request->input('postId')),
					'status' => 'published'
				]
			)->getBody(true)['data'] ?? false;
		};


		$payload = $this->prepareFrontendData($request, $additionalPayload);
		$payload['postComments'] = $comments;
		if (function_exists('get_avatar_url') && function_exists('get_current_user_id')) {
			$payload['gravatar'] = get_avatar_url(get_current_user_id());
		}

		if ($request->input('isRevivePage')) {
			return $this->render('Calendar/RevivalPosts', $payload);
		}

		return $this->render('Calendar', $payload);
	}

	public function getPostComments($post_id)
	{
		return AuthHttp::get(
			'post/comments',
			[
				'post_uuid' => sanitize_text_field($post_id),
				'status' => 'published'
			]
		)->getBody(true)['data'] ?? false;
	}

	public function storePostComment($request = null, $additionalPayload = [])
	{
		AuthHttp::post(
			'post/comments',
			[
				'post_uuid'	=> sanitize_text_field($request->input('postId')),
				'content'	=> sanitize_textarea_field($request->input('content')),
				'status'	=> 'published',
			]
		)->getBody(true)['data'] ?? false;

		$posts = function () use ($request) {
			return PostService::getBatchPost($request);
		};

		$comments = function () use ($request) {
			return $this->getPostComments($request->input('postId'));
		};

		$payload = [
			'postComments' => $comments,
			'posts' => $posts,
		];

		return $this->render('Calendar', $payload);
	}

	public function updatePostComment($request = null, $additionalPayload = [])
	{
		AuthHttp::post(
			'post/comments/update',
			[
				'uuid'		=> sanitize_text_field($request->input('commentId')),
				'content'	=> sanitize_textarea_field($request->input('content')),
			]
		)->getBody(true)['data'] ?? false;

		$comments = function () use ($request) {
			$res = $this->getPostComments($request->input('postId'))->getBody(true);
			return $res['data'] ?? false;
		};

		$payload = $this->prepareFrontendData($request, $additionalPayload);
		$payload['postComments'] = $comments;

		return $this->render('Calendar', $payload);
	}

	public function removePostComment($request = null, $additionalPayload = [])
	{
		AuthHttp::post(
			'post/comments/remove',
			[
				'uuid' => sanitize_text_field($request->input('commentId'))
			]
		)->getBody(true)['data'] ?? false;

		$comments = function () use ($request) {
			$res = $this->getPostComments($request->input('postId'))->getBody(true);
			return $res['data'] ?? false;
		};

		$payload = $this->prepareFrontendData($request, $additionalPayload);
		$payload['postComments'] = $comments;

		return $this->render('Calendar', $payload);
	}
}
