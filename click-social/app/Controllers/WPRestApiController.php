<?php

namespace Smashballoon\ClickSocial\App\Controllers;

use Smashballoon\ClickSocial\App\Core\AccountManager;
use Smashballoon\ClickSocial\App\Core\ErrorMessage;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Core\SettingsManager;
use Smashballoon\ClickSocial\App\Services\QuickShareService;
use WP_User_Query;

if (!defined('ABSPATH')) {
	exit;
}

class WPRestApiController extends BaseController
{
	use SingleTon;

	protected $base_namespace = 'clicksocial';

	public function register()
	{
		// If the site is not connected to ClickSocial API, then skip registering the routes.
		if (!AccountManager::getInstance()->isConnected()) {
			return;
		}

		add_action('rest_api_init', [$this, 'registerRoutes']);
	}

	public function hasPermissionToEditWordPressPost($request)
	{
		if (!is_user_logged_in()) {
			return false;
		}

		// Allow users with access to post id to use QuickShare.
		if (!current_user_can('edit_post', $request->get_param('id'))) {
			return false;
		}

		return true;
	}

	public function registerRoutes()
	{
		$routesData = [
			[
				'route' => '/v1/quick-share-post-templates',
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'getQuickSharePostTemplates' ],
				'permission_callback' => [$this, 'hasPermissionToEditWordPressPost']
			],
			[
				'route' => '/v1/quick-share-post-templates-update',
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'updateQuickSharePostTemplates' ],
				'permission_callback' => [$this, 'hasPermissionToEditWordPressPost']
			],
			[
				'route' => '/v1/quick-share-posts',
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'createQuickSharePosts' ],
				'permission_callback' => [$this, 'hasPermissionToEditWordPressPost']
			],
			[
				'route' => '/v1/get-users',
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'getUsers' ],
				'permission_callback' => function () {
					return current_user_can('manage_options') || current_user_can('edit_users');
				}
			],
			[
				'route' => '/v1/optimized-posts',
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'getOptimizedPosts' ],
				'permission_callback' => function () {
					return current_user_can('manage_options') || current_user_can('edit_posts');
				}
			],
		];

		foreach ($routesData as $route) {
			register_rest_route($this->base_namespace, $route['route'], [
				'methods' => $route['methods'],
				'callback' => $route['callback'],
				'permission_callback' => $route['permission_callback']
			]);
		}
	}

	public function getUsers($request)
	{
		$search_term = trim($request->get_param('search'));
		$exclude = $request->get_param('exclude');

		$args = array (
			'order' => 'ASC',
			'orderby' => 'display_name',
			'search' => '*' . esc_attr($search_term) . '*',
			'search_columns' => array ( 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' ),
			// Added a protection against querying all the users for large sites.
			'number' => 20,
			'fields' => [ 'user_email', 'display_name', 'ID' ],
		);

		$users = new WP_User_Query($args);
		$usersData = $users->get_results() ?: [];

		$usersData =
			array_values(
				array_map(
					function ($user) {
						return [
							'user_email' => $user->user_email,
							'display_name' => $user->display_name,
						];
					},
					array_filter(
						$usersData,
						function ($item) use ($exclude) {
							if (!isset($item)) {
								return false;
							}

							if (!isset($item->ID)) {
								return false;
							}

							return !in_array($item->ID, $exclude);
						}
					)
				)
			);

		return new \WP_REST_Response($usersData, 200);
	}

	public function getQuickSharePostTemplates($request)
	{
		if (!QuickShareService::getInstance()->fetchCurrentUserRole()) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_PERMISSIONS, [ 'status' => 403 ]);
		}

		if (empty(QuickShareService::getInstance()->fetchUserSocialAccounts())) {
			return new \WP_REST_Response([], 200);
		}

		// Retrieve Quick Share post templates for this post.
		// This API will look for existing post_meta with the key 'quick_share_templates' and return the value.
		// If no value is saved, then all the default accounts templates will be fetched.
		$postId = $request->get_param('id');
		$defaultTemplates = SettingsManager::getInstance()->get('quick_share', false);

		// Get stored templates
		$storedTemplates = QuickShareService::getInstance()->fetchTemplatesForPost($postId, $defaultTemplates);

		// Merge stored data with current templates
		$postTemplates = QuickShareService::getInstance()->mergeStoredTemplates($storedTemplates, $defaultTemplates);

		// Update action from settings
		$postTemplates['action'] = $defaultTemplates['action'];

		$filteredTemplates = $postTemplates;

		if (empty($postId)) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_POST_SELECTED, [ 'status' => 401 ]);
		}

		global $clicksocial_post;
		$clicksocial_post = get_post($postId);

		if (!$clicksocial_post instanceof \WP_Post) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_POST_MISSING, [ 'status' => 401 ]);
		}

		if (
			empty($postTemplates['accounts'])
			|| !is_array($postTemplates['accounts'])
			|| !in_array($clicksocial_post->post_status, ['publish', 'auto-draft', 'draft'])
		) {
			return new \WP_REST_Response([], 200);
		}

		$filteredTemplates['accounts'] = [];

		$filteredTemplates = QuickShareService::getInstance()->processTemplates($postTemplates);

		// Apply this formatting for each account from returned data.
		return new \WP_REST_Response($filteredTemplates, 200);
	}

	public function updateQuickSharePostTemplates($request)
	{
		if (!QuickShareService::getInstance()->fetchCurrentUserRole()) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_PERMISSIONS, [ 'status' => 403 ]);
		}

		if (!QuickShareService::getInstance()->fetchUserSocialAccounts()) {
			return new \WP_REST_Response('false', 200);
		}

		$params = $request->get_params();
		$postId = $request->get_param('id') ?? null;

		if (!$postId) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_POST_SELECTED, [ 'status' => 401 ]);
		}

		$postTemplates = $params['templates'];

		if (empty($postId) || empty($postTemplates)) {
			return new \WP_REST_Response('false', 200);
		}

		global $clicksocial_post;
		$clicksocial_post = get_post($postId);

		if (!$clicksocial_post instanceof \WP_Post) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_POST_MISSING, [ 'status' => 401 ]);
		}

		$postTemplates = QuickShareService::getInstance()->processTemplates($postTemplates);

		if (!$postTemplates['accounts']) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_ACCOUNTS, [ 'status' => 401 ]);
		}

		// Store only essential data
		$storageTemplates = QuickShareService::getInstance()->processTemplatesForStorage($postTemplates);
		QuickShareService::getInstance()->storeTemplatesForPost($postId, $storageTemplates);

		// Return full templates in API response
		return new \WP_REST_Response($postTemplates, 200);
	}

	public function createQuickSharePosts($request)
	{
		if (!QuickShareService::getInstance()->fetchCurrentUserRole()) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_PERMISSIONS, [ 'status' => 403 ]);
		}

		if (!QuickShareService::getInstance()->fetchUserSocialAccounts()) {
			return new \WP_REST_Response('false', 200);
		}

		$params = $request->get_params();
		$postId = $request->get_param('id') ?? null;

		if (!$postId) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_POST_SELECTED, [ 'status' => 401 ]);
		}

		$postTemplates = $params['templates'];

		if (empty($postId) || empty($postTemplates)) {
			return new \WP_REST_Response('false', 200);
		}

		global $clicksocial_post;
		$clicksocial_post = get_post($postId);

		$postTemplates = QuickShareService::getInstance()->processTemplates($postTemplates);

		if (!$postTemplates['accounts']) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_NO_ACCOUNTS, [ 'status' => 401 ]);
		}

		QuickShareService::getInstance()->storeTemplatesForPost($postId, $postTemplates);

		$data = QuickShareService::getInstance()->schedulePosts([
			'accounts' => $postTemplates['accounts'],
			'action' => $postTemplates['action']
		]);

		if ((int)$data['statusCode'] < 200 || (int)$data['statusCode'] > 300) {
			return new ErrorMessage(ErrorMessage::QUICKSHARE_API_ERROR, [ 'status' => 401 ]);
		}

		// Send final templates to Laravel API.
		return new \WP_REST_Response($postTemplates, 200);
	}

	private function buildBaseArgs()
	{
		return [
			'posts_per_page' => 100,
			'post_status' => 'publish',
			'orderby' => 'date',
			'order' => 'DESC',
		];
	}

	private function handleSearchArgs($params, $args)
	{
		if (!empty($params['search'])) {
			$args['s'] = sanitize_text_field($params['search']);
		}
		return $args;
	}

	private function handleDateArgs($params, $args)
	{
		if (!empty($params['exclude_dates'])) {
			$args['date_query'] = [
				'relation' => 'OR',
				[
					'before' => sanitize_text_field($params['exclude_dates']['start']),
				],
				[
					'after' => sanitize_text_field($params['exclude_dates']['end']),
				],
			];
		} elseif (!empty($params['after']) || !empty($params['before'])) {
			$args['date_query'] = [
				'inclusive' => true
			];
			if (!empty($params['after'])) {
				$args['date_query']['after'] = sanitize_text_field($params['after']);
			}
			if (!empty($params['before'])) {
				$args['date_query']['before'] = sanitize_text_field($params['before']);
			}
		}

		return $args;
	}

	private function handleTaxonomyArgs($params)
	{
		$tax_query = [];

		// Categories
		if (!empty($params['categories_exclude'])) {
			$tax_query[] = $this->buildTaxQuery('category', $params['categories_exclude'], 'NOT IN');
		} elseif (!empty($params['categories'])) {
			$tax_query[] = $this->buildTaxQuery('category', $params['categories'], 'IN');
		}

		// Tags
		if (!empty($params['tags_exclude'])) {
			$tax_query[] = $this->buildTaxQuery('post_tag', $params['tags_exclude'], 'NOT IN');
		} elseif (!empty($params['tags'])) {
			$tax_query[] = $this->buildTaxQuery('post_tag', $params['tags'], 'IN');
		}

		return $tax_query;
	}

	private function buildTaxQuery($taxonomy, $terms, $operator)
	{
		return [
			'taxonomy' => $taxonomy,
			'terms' => array_map('absint', explode(',', $terms)),
			'operator' => $operator
		];
	}

	private function handleAuthorArgs($params, $args)
	{
		if (!empty($params['author'])) {
			$args['author__in'] = array_map('absint', explode(',', $params['author']));
		} elseif (!empty($params['author_exclude'])) {
			$args['author__not_in'] = array_map('absint', explode(',', $params['author_exclude']));
		}
		return $args;
	}

	private function formatPosts($posts)
	{
		return array_map(function ($post) {
			return [
				'id' => $post->ID,
				'title' => ['rendered' => get_the_title($post)],
				'date' => get_the_date('c', $post),
				'link' => get_permalink($post),
			];
		}, $posts);
	}

	public function getOptimizedPosts($request)
	{
		$params = $request->get_params();
		$args = $this->buildBaseArgs();

		$args = $this->handleSearchArgs($params, $args);
		$args = $this->handleDateArgs($params, $args);

		$tax_query = $this->handleTaxonomyArgs($params);
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}

		$args = $this->handleAuthorArgs($params, $args);

		$query = new \WP_Query($args);
		$posts = $this->formatPosts($query->posts);

		return new \WP_REST_Response($posts, 200);
	}
}
