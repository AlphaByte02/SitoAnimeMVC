<?php
namespace Mvc\Helpers;

class Request
{
	protected $controller;

	protected $action;

	protected $args = [];
	protected $argsGet = [];

	protected $isPost;

	public function __construct(string $controller, string $action, array $args = [], bool $isPost = false)
	{
		$this->controller = Strings::convertToStudlyCaps($controller);
		$this->action = Strings::convertToCamelCase($action);
		if (!$isPost) {
			foreach ($args as $key => $value) {
				if (is_numeric($key)) {
					$this->args[$key] = $value;
				} else {
					$this->argsGet[$key] = $value;
				}
			}
		} else {
			$this->args = $args;
		}

		$this->isPost = $isPost;
	}

	public function getController(): string
	{
		return $this->controller;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function getArgs(): array
	{
		return $this->args;
	}

	public function getAllArgs(): array
	{
		return $this->args + $this->argsGet;
	}

	public function get(string $key, ?string $default = null)
	{
		$args = $this->args + $this->argsGet;

		if (empty($args) || !array_key_exists($key, $args)) {
			return $default;
		}

		return $args[$key];
	}

	public function has(...$keys): bool
	{
		$args = $this->args + $this->argsGet;

		$existAll = true;
		foreach ($keys as $key) {
			$existAll &= !empty($args) && array_key_exists($key, $args);

			if (!$existAll) {
				return false;
			}
		}

		return $existAll;
	}

	public function argsEmpty(): bool
	{
		return empty($this->args + $this->argsGet);
	}

	public function isPost(): bool
	{
		return $this->isPost;
	}
}
