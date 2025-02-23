<?php

namespace Smashballoon\ClickSocial\App\Services\InertiaAdapter;

if (!defined('ABSPATH')) {
	exit;
}

use Closure;

class Inertia
{
	/**
	 * Url.
	 *
	 * @var string
	 */
	protected static $url;

	/**
	 * Properties.
	 *
	 * @var array
	 */
	protected static $props;

	/**
	 * Request.
	 *
	 * @var array
	 */
	protected static $request;

	/**
	 * Version
	 *
	 * @var string
	 */
	protected static $version;

	/**
	 * React component.
	 *
	 * @var string
	 */
	protected static $component;

	/**
	 * HTML Markup.
	 *
	 * @var string
	 */
	protected static $html;

	/**
	 * Shared properties with all routes.
	 *
	 * @var array
	 */
	protected static $shared_props = [];

	/**
	 * Root view.
	 *
	 * @var string
	 */
	protected static $root_view = '/admin/app.php';

	/**
	 * Render component with properties.
	 *
	 * @param string $component Component.
	 * @param array  $props Properties.
	 *
	 * @return mixed
	 */
	public static function render(string $component, array $props = [])
	{
		global $sbcs_inertia_page;

		self::setRequest();

		self::setUrl();
		self::setComponent($component);
		self::setProps($props);

		$sbcs_inertia_page = [
			'url'       => self::$url,
			'props'     => self::$props,
			'version'   => self::$version,
			'component' => self::$component,
		];

		if (InertiaHeaders::inRequest()) {
			InertiaHeaders::addToResponse();

			wp_send_json($sbcs_inertia_page);
		}

		ob_start();
		sbcs_render_view_template(self::$root_view);
		self::setHtml(ob_get_clean());

		return self::$html;
	}

	/**
	 * Print markup already generated by render function. Used for full page reloads.
	 *
	 * @return void
	 */
	public static function printMarkup()
	{
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped.
		echo self::$html;

		// Once the markup was printed, reset it to prevent it from being twice on add_menu_page
		// and add_submenu_page callbacks.
		self::$html = '';
	}

	/**
	 * Set Html markup.
	 *
	 * @param string $html HTML Markup.
	 *
	 * @return void
	 */
	public static function setHtml($html)
	{
		self::$html = $html;
	}

	/**
	 * Set root view.
	 *
	 * @param string $name Name.
	 *
	 * @return void
	 */
	public static function setRootView(string $name)
	{
		self::$root_view = $name;
	}

	/**
	 * Set version.
	 *
	 * @param string $version Version.
	 *
	 * @return void
	 */
	public static function version(string $version = '')
	{
		self::$version = $version;
	}

	/**
	 * Share property with key and value.
	 *
	 * @param array $key Key.
	 * @param string $value Value.
	 *
	 * @return void
	 */
	public static function share($key, $value = null)
	{
		if (is_array($key)) {
			self::$shared_props = array_merge(self::$shared_props, $key);
		} else {
			InertiaHelper::arraySet(self::$shared_props, $key, $value);
		}
	}

	/**
	 * Lazy loading callback.
	 *
	 * @param callable $callback Callback.
	 *
	 * @return LazyProp
	 */
	public static function lazy(callable $callback)
	{
		return new LazyProp($callback);
	}

	/**
	 * Set request with Inertia header.
	 *
	 * @return void
	 */
	protected static function setRequest()
	{
		global $wp;

		self::$request = array_merge([
			'WP-Inertia' => (array) $wp,
		], InertiaHeaders::all());
	}

	/**
	 * Set Url.
	 * Defaults to the previous GET request URL for non-GET requests, so users can get back to the previous
	 * page from where they started the action.
	 * Inertia JS will redirect back to this URL after doing non-GET requests.
	 *
	 * @return void
	 */
	protected static function setUrl()
	{
		// Extract the current relative path of the request.
		$currentUrl = isset($_SERVER['REQUEST_URI'])
			? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))
			: '/';

		// If the current request is a GET request, then we'll store the current URL in a cookie
		// for next non-GET requests.
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
			setcookie(
				'sbcs_inertia_referer_url',
				$currentUrl,
				0,
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
		}

		// Get relative referer URL.
		$referer = self::getRelativeRefererUrl();

		// If the referer URL is a valid URL and the request is a non-GET request,
		// then we'll use it as the Inertia URL.
		if ($referer && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
			self::$url = $referer;
			return;
		}

		self::$url = $currentUrl;
	}

	/**
	 * Get relative referer URL. First try $_SERVER['HTTP_REFERER'] and then the cookie.
	 *
	 * @return string|null String if valid, null otherwise.
	 */
	protected static function getRelativeRefererUrl()
	{
		$referer = null;

		// Use the referer URL as the Inertia URL.
		$http_referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : null;

		if ($http_referer) {
			// Split referer URL into parts.
			$http_referer_url_parts = wp_parse_url($http_referer);
			$referer                =
				(isset($http_referer_url_parts) ? $http_referer_url_parts['path'] : '' )
				. (isset($http_referer_url_parts['query']) ? '?' . $http_referer_url_parts['query'] : '');
		}

		// If the browser doesn't support a referer header, then we'll try to get the referer URL from the cookie.
		if (!$referer) {
			$referer = isset($_COOKIE['sbcs_inertia_referer_url']) ?
				esc_url_raw(wp_unslash($_COOKIE['sbcs_inertia_referer_url'])) :
				null;
		}

		// If the referer is not a relative URL, then it's not a valid referer.
		if (substr($referer, 0, 1) !== '/') {
			$referer = null;
		}

		return $referer;
	}

	/**
	 * Set properties.
	 *
	 * @param array $props Properties.
	 *
	 * @return void
	 */
	protected static function setProps(array $props)
	{
		$props = array_merge($props, self::$shared_props);

		$partial_data = isset(self::$request['x-inertia-partial-data'])
			? self::$request['x-inertia-partial-data']
			: '';

		$only = array_filter(explode(',', $partial_data));

		$partial_component = isset(self::$request['x-inertia-partial-component'])
			? self::$request['x-inertia-partial-component']
			: '';

		$props = ($only && $partial_component === self::$component)
			? InertiaHelper::arrayOnly($props, $only)
			: array_filter($props, function ($prop) {
				// Remove lazy props when not calling for partials.
				return ! ($prop instanceof LazyProp);
			});

		array_walk_recursive($props, function (&$prop) {
			if ($prop instanceof LazyProp) {
				$prop = $prop();
			}

			if ($prop instanceof Closure) {
				$prop = $prop();
			}
		});

		self::$props = $props;
	}

	/**
	 * Set component.
	 *
	 * @param string $component Component.
	 *
	 * @return void
	 */
	protected static function setComponent(string $component)
	{
		self::$component = $component;
	}

	/**
	 * Add HTML with Component properties.
	 *
	 * @param string $id Id.
	 * @param string $classes HTML Classes.
	 *
	 * @return void
	 */
	public static function view(string $id = 'app', string $classes = '')
	{
		global $sbcs_inertia_page;

		if (! isset($sbcs_inertia_page)) {
			return;
		}

		$class = !empty($classes) ? "class=\"" . esc_attr($classes) . "\"" : '';

		$page = htmlspecialchars(
			wp_json_encode($sbcs_inertia_page),
			ENT_QUOTES,
			'UTF-8',
			true
		);

		echo sprintf(
			'<div id="%s" %s data-page="%s"></div>',
			esc_attr($id),
			$class, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped.
			esc_attr($page)
		);
	}

	/**
	 * Get headers.
	 *
	 * @return array
	 */
	public static function getHeaders()
	{
		$headers = [];

		// Sanitize $_SERVER data.
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) === 'HTTP_') {
				$sanitized_name = sanitize_text_field($name);
				$sanitized_value = sanitize_text_field($value);

				$key = str_replace(
					' ',
					'-',
					ucwords(strtolower(str_replace('_', ' ', substr($sanitized_name, 5))))
				);
				$headers[$key] = $sanitized_value;
			}
		}

		return $headers;
	}

	/**
	 * Generate a URL for the given page and optional subpage.
	 *
	 * @param string $page The main page identifier.
	 * @param string $subpage Optional subpage identifier. Defaults to an empty string.
	 *
	 * @return string The constructed URL with the provided page and subpage arguments.
	 */
	public static function getUrl($page, $subpage = '')
	{
		$pageArgs = ['page' => $page];

		if (!empty($subpage)) {
			$pageArgs['subpage'] = trim(str_replace('/', '-', $subpage), '-');
		}

		return add_query_arg(
			$pageArgs,
			get_admin_url() . 'admin.php'
		);
	}

	/**
	 * Redirects to a specified page with an optional subpage.
	 *
	 * @param string $page The main page to redirect to.
	 * @param string $subpage Optional subpage to append to the main page URL.
	 *
	 * @return void
	 */
	public static function redirect($page, $subpage = '')
	{
		$targetPage = self::getUrl($page, $subpage);

		if (InertiaHeaders::inRequest()) {
			status_header(409);
			header('X-Inertia-Location: ' . $targetPage);
			exit;
		}

		wp_redirect(esc_url_raw($targetPage), 302);
		exit;
	}
}
