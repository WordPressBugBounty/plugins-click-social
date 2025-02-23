<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\MiddlewareResolver;
use Smashballoon\ClickSocial\App\Core\Lib\Request;
use Smashballoon\ClickSocial\App\Core\Lib\SingleTon;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\InertiaHeaders;
use Smashballoon\ClickSocial\App\Services\MemberTransaction;
use Smashballoon\ClickSocial\App\Services\UrlNotificationService;

class AdminRouteManager
{
	use SingleTon;

	public $routes = [];

	private $middlewares = [];

	/**
	 * Define routes with GET, POST, PUT and DELETE request methods and link them to their dedicated
	 * Controller classes and their c.
	 *
	 * @return void
	 */
	public function defineRoutes()
	{
		require_once SBCS_DIR_PATH . 'routes/admin.php';
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register()
	{
		add_action('admin_menu', [ $this, 'defineRoutes' ]);
		add_action('admin_init', [ $this, 'addInertiaSharedData' ], 10);
		add_action('admin_init', [$this, 'processPage'], 11);
		add_action('init', [ $this, 'requestLoginIfLoggedOutInInertia' ]);
		add_action('admin_head', [$this, 'hideUpdateNagOnPages'], 1);
	}

	/**
	 * Hide update nag on admin pages which were created by this plugin.
	 *
	 * @return void
	 */
	public function hideUpdateNagOnPages()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : false;
		if (strpos($page, AdminRoute::PREFIX) === false) {
			return;
		}

		remove_action('admin_notices', 'update_nag', 3);
	}

	/**
	 * Request to log in if the user has been logged out of WordPress Admin.
	 *
	 * @return void
	 */
	public function requestLoginIfLoggedOutInInertia()
	{
		if (! InertiaHeaders::inRequest()) {
			return;
		}

		if (is_user_logged_in()) {
			return;
		}

		status_header(409);
		header('X-Inertia-Location: ' . get_admin_url());
		exit;
	}

	/**
	 * Add default Inertia shared data.
	 */
	public function addInertiaSharedData()
	{
		$accountManager = new AccountManager();

		$user_id = get_current_user_id();
		$disable_onboarding_tour = SettingsManager::getInstance()->get('disable_onboarding_tour', '');
		$disable_onboarding_tour = !empty($disable_onboarding_tour) ? json_decode($disable_onboarding_tour) : [];

		Inertia::share(
			[
				'account' => [
					'is_connected' => $accountManager->isConnected(),
					'external_wp_user_id' => $accountManager->getExternalWPUserId(),
				],
				'site' => [
					'admin_url' => admin_url('/admin.php'),
					'nonce' => wp_create_nonce('wp_rest'),
					'general_settings_admin_url' => admin_url('/options-general.php'),
				],
				'assets' => SBCS_PLUGIN_URL . 'public/', // Assets path.
				'wptimezone' => sbcs_getTimezone(),
				'permissions' => MemberTransaction::getMemberData(null, false),
				'features' => sbcs_get_config('features'),
				'current_user_id' => $user_id,
				'source' => [
					'url' => SBCS_SITE_APP_URL,
				],
				'website_id' => SettingsManager::getInstance()->get('website_id'),
				'urlNotification' => UrlNotificationService::getNotification(),
				'disable_onboarding_tour' => in_array($user_id, $disable_onboarding_tour),
			]
		);
	}

	/**
	 * Retrieve callback key.
	 *
	 * @return string|void
	 */
	public function retrieveCallbackKey()
	{
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
		$page = isset($_GET['page'])
			? sanitize_text_field(wp_unslash($_GET['page']))
			: '';

		$subpage = isset($_GET['subpage'])
			? sanitize_text_field(wp_unslash($_GET['subpage']))
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$key = trim(implode('-', [$page, $subpage]), '-');

		if (!isset($_SERVER['REQUEST_METHOD'])) {
			return;
		}

		if (!isset($this->routes[$key]['methods'][$_SERVER['REQUEST_METHOD']])) {
			return;
		}

		return $key;
	}

	/**
	 * Retrieve callback by get params.
	 *
	 * @return array|string
	 */
	public function retrieveCallbackByGetParams()
	{
		$key = $this->retrieveCallbackKey();

		if (! $key) {
			return false;
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Array List validation.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array List validation.
		$allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
		$method = isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], $allowed_methods, true)
			? sanitize_text_field($_SERVER['REQUEST_METHOD'])
			: 'GET';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		[$controllerClass, $controllerMethod] = $this->routes[$key]['methods'][$method];

		$controllerInstance = new $controllerClass();

		$this->middlewares = sbcs_get_config('globalMiddlewares');

		if (
			isset($this->routes[$key]['methods'][$method]['middleware'])
			&& is_array($this->routes[$key]['methods'][$method]['middleware'])
		) {
			$middlewares = $this->routes[$key]['methods'][$method]['middleware'];
			$this->middlewares = array_merge($this->middlewares, $middlewares);
		}

		return [$controllerInstance, $controllerMethod];
	}

	public function processPage()
	{
		$callback = $this->retrieveCallbackByGetParams();

		if (! $callback) {
			return;
		}

		$request = new Request();

		if (count($this->middlewares) > 0) {
			MiddlewareResolver::execute($this->middlewares, $request);
		}

		if (InertiaHeaders::inRequest()) {
			wp_send_json(call_user_func_array($callback, [$request]));
			return;
		}
		call_user_func_array($callback, [$request]);
	}

	/**
	 * Add route to list.
	 *
	 * @param string $key Key.
	 * @param array $callback Callback.
	 * @param string $method Method.
	 *
	 * @return void
	 */
	public function addRouteToList($key, $callback, $method)
	{
		$predefinedMethods = isset($this->routes[ $key ]['methods']) ? $this->routes[$key]['methods'] : [];

		$this->routes[ $key ] = [
			'methods' => array_merge($predefinedMethods, [$method => $callback]),
		];
	}

	public function setMiddleware($middlewares, $routeName, $methodName)
	{
		$route = $this->routes[$routeName]['methods'][$methodName] ?? null;
		if (! $route) {
			return;
		}

		$this->routes[$routeName]['methods'][$methodName]['middleware'] = $middlewares;
	}
}
