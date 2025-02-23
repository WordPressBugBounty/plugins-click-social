<?php

namespace Smashballoon\ClickSocial\App\Core;

if (!defined('ABSPATH')) exit;

use Smashballoon\ClickSocial\App\Core\Lib\MiddlewareResolver;
use Smashballoon\ClickSocial\App\Core\Lib\Request;
use Smashballoon\ClickSocial\App\Services\InertiaAdapter\Inertia;

class AdminRoute
{
	/**
	 * Routes prefix. E.g. /wp-admin/wp-admin.php?click-social....
	 */
	public const PREFIX = 'click-social';

	private $callback;

	private $methodName;

	/**
	 * Page.
	 * @var mixed
	 */
	private $page;

	private $route;

	/**
	 * Capability.
	 * @var mixed
	 */
	private $capability;

	protected $middlewares = [];

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		return $this;
	}

	/**
	 * Define GET request listener.
	 *
	 * @param string $page Page.
	 * @param array $callbackArgs Callback callable. E.g. [class => method]
	 * @param string $subpage Subpage.
	 *
	 * @return AdminRoute
	 */
	public static function get($page, $callbackArgs, $subpage = '')
	{
		$newAdminRoute = new self();
		return $newAdminRoute->request('GET', $page, $callbackArgs, $subpage);
	}

	/**
	 * Define POST request listener.
	 *
	 * @param string $page Page.
	 * @param array $callbackArgs Callback callable. E.g. [class => method]
	 * @param string $subpage Subpage.
	 *
	 * @return AdminRoute
	 */
	public static function post($page, $callbackArgs, $subpage = '')
	{
		$newAdminRoute = new self();
		return $newAdminRoute->request('POST', $page, $callbackArgs, $subpage);
	}

	/**
	 * Define PUT request listener.
	 *
	 * @param string $page Page.
	 * @param array $callbackArgs Callback callable. E.g. [class => method]
	 * @param string $subpage Subpage.
	 *
	 * @return AdminRoute
	 */
	public static function put($page, $callbackArgs, $subpage = '')
	{
		$newAdminRoute = new self();
		return $newAdminRoute->request('PUT', $page, $callbackArgs, $subpage);
	}

	/**
	 * Define DELETE request listener.
	 *
	 * @param string $page Page.
	 * @param array $callbackArgs Callback callable. E.g. [class => method]
	 * @param string $subpage Subpage.
	 *
	 * @return AdminRoute
	 */
	public static function delete($page, $callbackArgs, $subpage = '')
	{
		$newAdminRoute = new self();
		return $newAdminRoute->request('DELETE', $page, $callbackArgs, $subpage);
	}

	/**
	 * Define generic request listener.
	 *
	 * @param string $methodName Method name. E.g. POST, PUT, DELELE.
	 * @param string $page Page.
	 * @param array $callbackArgs Callback callable. E.g. [class => method]
	 * @param string $subpage Subpage.
	 *
	 * @return AdminRoute
	 */
	public function request($methodName, $page, $callbackArgs, $subpage = '')
	{
		$this->page = $page;
		$this->methodName = $methodName;
		$this->callback = $callbackArgs;

		$routes = array_filter([
			static::PREFIX,
			$page,
			$subpage ? $this->convertSubpage($subpage) : '',
		]);

		$this->route = implode(
			'-',
			$routes
		);

		AdminRouteManager::getInstance()->addRouteToList($this->route, $callbackArgs, $methodName);

		return $this;
	}

	/**
	 * Set capability for admin route.
	 *
	 * @param string $capability WordPress User Capability.
	 *
	 * @return $this
	 */
	public function setCapability($capability)
	{
		$this->capability = $capability;
		return $this;
	}

	/**
	 * Get prefixed page.
	 *
	 * @param string $page Page.
	 *
	 * @return string
	 */
	public function getPrefixedPage($page)
	{
		if ($page) {
			return static::PREFIX . '-' . $page;
		}

		return static::PREFIX;
	}

	/**
	 * Convert subpage from slash (React folder hierarchy) to hyphen.
	 *
	 * @param string $subpage Subpage.
	 *
	 * @return string
	 */
	public function convertSubpage($subpage)
	{
		return trim(str_replace('/', '-', $subpage), '-');
	}

	/**
	 * Add menu page to WordPress Admin.
	 *
	 * @param string $pageTitle Page title.
	 * @param string $menuTitle Menu title.
	 *
	 * @return AdminRoute
	 */
	public function addMenu($pageTitle, $menuTitle)
	{
		$request = new Request();
		if (count($this->middlewares) > 0) {
			$request->merge([
				'capability' => $this->capability,
				'menuTitle' => $menuTitle,
			]);

			MiddlewareResolver::execute($this->middlewares, $request);
			$this->capability = $request->get('capability');
		}

		if (! $this->capability) {
			return $this;
		}

		\add_menu_page(
			$pageTitle,
			$menuTitle,
			$this->capability,
			$this->getRoute($request),
			[$this, 'printMarkup'],
			sbcs_get_config('menu_icon'),
			40
		);

		return $this;
	}

	/**
	 * Add submenu page to WordPress Admin.
	 *
	 * @param string $pageTitle Page title.
	 * @param string $menuTitle Menu title.
	 *
	 * @return AdminRoute
	 */
	public function addSubmenu($pageTitle, $menuTitle)
	{
		if (count($this->middlewares) > 0) {
			$request = new Request();
			$request->merge([
				'capability' => $this->capability,
				'menuTitle' => $menuTitle,
				'route' => $this->getPrefixedPage($this->page),
			]);

			MiddlewareResolver::execute($this->middlewares, $request);
			$this->capability = $request->get('capability');
		}

		if (! $this->capability) {
			return $this;
		}

		\add_submenu_page(
			self::PREFIX,
			$pageTitle,
			$menuTitle,
			$this->capability,
			$this->getPrefixedPage($this->page),
			[$this, 'printMarkup']
		);

		return $this;
	}

	public function printMarkup()
	{
		MiddlewareResolver::execute(
			sbcs_get_config('globalMiddlewares'),
			new Request()
		);

		Inertia::printMarkup();
	}

	public function middleware(array $middlewares)
	{
		// Use the route middlewares for the menu and submenu pages.
		$this->middlewares = $middlewares;

		// Use the global middlewares and route middleware.
		AdminRouteManager::getInstance()->setMiddleware(
			$this->getAllMiddlewares(),
			$this->route,
			$this->methodName
		);

		return $this;
	}

	/**
	 * Retrieve all middlewares currently defined.
	 *
	 * @return array List of middlewares, including global middlewares and route-level middlewares.
	 */
	public function getAllMiddlewares()
	{
		return $this->middlewares;
	}

	private function getRoute($request)
	{
		$route = self::PREFIX;

		if ($request->get('redirectRoute', true) === '/') {
			return $route;
		} elseif ($request->get('redirectRoute', true)) {
			$route .= $request->get('redirectRoute');
		}

		return $route;
	}
}
