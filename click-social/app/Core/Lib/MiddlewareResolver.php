<?php

namespace Smashballoon\ClickSocial\App\Core\Lib;

if (! defined('ABSPATH')) {
	exit;
}

class MiddlewareResolver
{
	protected static $middlewares = [];
	protected static $request;

	public static function execute($middlewares, $request)
	{
		static::$middlewares = $middlewares;
		static::$request = $request;
		static::iterateAndResolve();
	}

	protected static function iterateAndResolve()
	{
		foreach (static::$middlewares as $middleware) {
			static::resolve($middleware);
		}
	}

	protected static function resolve($key)
	{
		$middlewares = static::getMiddleres();
		if (empty($middlewares[$key])) {
			throw new \Exception(
				sprintf(
					"No middleware found for key '%s'",
					esc_html($key)
				)
			);
		}

		$middleware = $middlewares[$key];
		if (! class_exists($middleware)) {
			throw new \Exception(
				sprintf(
					"Middleware class '%s' not found",
					esc_html($middleware)
				)
			);
		}

		$middleware = new $middleware();
		return call_user_func_array([$middleware, 'handle'], [static::$request]);
	}

	protected static function getMiddleres()
	{
		$middlewares = sbcs_get_config('middlewares');
		if (empty($middlewares) || count($middlewares) === 0) {
			throw new \Exception("No middlewares found in config file");
		}

		return $middlewares;
	}
}
