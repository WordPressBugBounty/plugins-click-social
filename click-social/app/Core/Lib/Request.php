<?php

namespace Smashballoon\ClickSocial\App\Core\Lib;

if (! defined('ABSPATH')) {
	exit;
}

class Request
{
	/**
	 * Request body.
	 *
	 * @var array
	 */
	protected $input;

	protected $internalData = [];

	public function __construct()
	{
		if (
			$this->isMethod('POST')
			|| $this->isMethod('PUT')
			|| $this->isMethod('DELETE')
		) {
			$this->input = $this->processBodyInput();
		}
	}

	/**
	 * Process body input by sanitizing it.
	 *
	 * @return array
	 */
	public function processBodyInput()
	{
		$rawInput = file_get_contents('php://input');

		if (!$rawInput) {
			return [];
		}

		$decodedInput = json_decode($rawInput, true);

		if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedInput)) {
			// Handle the error, return an empty array or a specific response.
			return [];
		}

		// Sanitize each field based on the expected type.
		return array_map(
			function ($value) {
				return sbcs_sanitize_data($value);
			},
			$decodedInput
		);
	}

	/**
	 * Get HTTP method.
	 *
	 * @return mixed
	 */
	public function method()
	{
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Array List validation.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array List validation.
		$allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
		return isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], $allowed_methods, true)
			? sanitize_text_field($_SERVER['REQUEST_METHOD'])
			: 'GET';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	}

	/**
	 * Is method.
	 *
	 * @param $methodName
	 *
	 * @return bool
	 */
	public function isMethod($methodName)
	{
		return $this->method() === $methodName;
	}

	/**
	 * Get all inputs (decoded JSON body, GET params, POST attributes).
	 *
	 * @return mixed
	 */
	public function all()
	{
		// phpcs:disable WordPress.Security.NonceVerification
		return sbcs_sanitize_data(
			array_merge(
				$this->input,
				$_GET,
				(array)$_POST
			)
		);
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/**
	 * Retrieve input field by name.
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function input($key)
	{
		// phpcs:disable WordPress.Security.NonceVerification
		if (isset($this->input[$key])) {
			return $this->input[$key];
		}

		if (isset($_GET[$key])) {
			return sanitize_text_field(wp_unslash($_GET[$key]));
		}

		if (isset($_POST[$key])) {
			return sanitize_text_field(wp_unslash($_POST[$key]));
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return null;
	}

	public function merge(array $data)
	{
		$this->internalData = array_merge($this->internalData, $data);
	}

	public function get($key, $single = false)
	{
		if ($single === true) {
			return $this->internalData[$key] ?? false;
		}
		return $this->internalData[$key] ?? $this->internalData;
	}

	public function exception()
	{
		if (
			! empty($this->internalData['exception'])
			&& $this->internalData['exception'] === true
		) {
			return $this->internalData['exception'];
		}

		return false;
	}
}
