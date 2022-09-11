<?php
namespace Mvc\Helpers;

class Dispatcher
{
	/** @var string $uri */
	private $uri;

	/** @var Request $request */
	private $request;

	private $mainController;
	private $singleController;


	public function __construct(string $uri, bool $isPostRequest, string $mainController = "Main", bool $singleController = false)
	{
		$this->uri = $uri;

		$this->mainController = Strings::convertToStudlyCaps($mainController);
		$this->singleController = $singleController;

		$this->request = $this->parse($uri, $isPostRequest);
	}

	public function parse(string $path, bool $isPost): Request
	{
		$path = HelpFunction::removeQueryStringVariables($path);

		if($path == config("subdir") . '/'){
			return new Request($this->mainController, "index", [], $isPost);
		}
		else {
			$parts = array_map(function($el) {
				return urldecode($el);
			}, array_values(array_filter(explode('/', $path))));

			$offset = 0;
			if (!empty(config("subdir"))) {
				$offset = count(explode("/", trim(config("subdir"), '/')));
			}
			$offset -= $this->singleController ? 1 : 0;

			$controller = $this->singleController ? $this->mainController : Strings::convertToStudlyCaps($parts[0 + $offset]);

			if (empty($parts) || (count($parts) - 1 - $offset) == 0) {
				return new Request($controller, "index", [], $isPost);
			}

			$method = Strings::convertToCamelCase($parts[1 + $offset]);
			$args = !$isPost ? array_slice($parts, 2 + $offset) : $_POST;
			$args = !empty($_GET) ? $args + $_GET : $args;

			return new Request($controller, $method, empty($args) ? [] : $args, $isPost);
		}
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function isSingleController(): bool
	{
		return !empty($this->controller);
	}

	public function getController(string $namespace = ""): ?string
	{
		return $namespace . $this->request->getController();
	}

	public function getAction(): ?string
	{
		return $this->request->getAction();
	}

	public function getArgs(): ?array
	{
		return $this->request->getArgs() ?: [];
	}

	public function getRequest(): Request
	{
		return $this->request;
	}
}

?>
