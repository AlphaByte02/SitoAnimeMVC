<?php
namespace Mvc\Helpers;

abstract class Router
{
	public static function Status($code = '404', $msg = 'Not Found', bool $force = false)
	{
		if (!headers_sent() || $force) {
			header("HTTP/1.1 {$code} {$msg}", $force);
		}
	}

	public static function HeaderRaw(string $value, bool $force = false)
	{
		if (!headers_sent() || $force) {
			header($value, $force);
		}
	}

	public static function Header(string $header, string $value, bool $force = false)
	{
		if (!headers_sent() || $force) {
			header("$header: $value", $force);
		}
	}

	public static function RemoveHeaders(?string $header = null)
	{
		header_remove($header);
	}

	public static function Mine(string $mime, bool $force = false): void
	{
		if (!headers_sent() || $force) {
			header("Content-Type: {$mime}", $force);
		}
	}

	public static function Redirect(string $url, int $timeRefresh = 0, bool $timeForDebug = false, bool $force = false): void
	{
		if (!headers_sent() || $force) {
			if ($timeRefresh == 0 || ($timeRefresh != 0 && !$timeForDebug))
				header("Location: " . config("subdir") . $url, $force);
			else if ($timeRefresh > 0)
				header("Refresh: $timeRefresh; url = " . config("subdir") . $url, $force);
		}
	}

	public static function Route(Dispatcher $dispatcher)
	{
		// ! Debug
		/*
		Debugger::GetInstance()->addDebug("getController", $dispatcher->getController());
		Debugger::GetInstance()->addDebug("getAction", $dispatcher->getAction());
		Debugger::GetInstance()->addDebug("getArgs", $dispatcher->getArgs());
		*/

		$controller = $dispatcher->getController("Mvc\\Controllers\\");
		if (empty($controller) || !file_exists(__ABSPATH__ . "/App/Controllers/{$dispatcher->getController()}.php")) {
			self::exit("This Controller does not exist!", '/');
			return;
		}

		$controller = new $controller();

		$action = $dispatcher->getAction();

		if (!method_exists($controller, $action)) {
			self::exit("This Function does not exist!", $controller->getFailSafePageUrl());
			return;
		}


		$reflection = new \ReflectionMethod($controller, $action);
		$actionParams = $reflection->getParameters();

		if (!$reflection->isPublic()) {
			self::exit("You cannot access this function!", '/');
			return;
		}

		$args = $dispatcher->getArgs();
		$request = $dispatcher->getRequest();

		if (empty($args) && ($reflection->getNumberOfRequiredParameters() == 0 || $reflection->isVariadic())) {
			$controller->$action();
		} else if (
			!empty($args) &&
			($request->isPost() ||
				(count($actionParams) == 1 &&
					!is_null(reset($actionParams)->getType()) &&
					reset($actionParams)->getType()->getName() == Request::class)
			)
		) {
			$controller->$action($request);
		} else if (!empty($args) && !$request->isPost() && (count($args) >= $reflection->getNumberOfRequiredParameters() || $reflection->isVariadic())) {
			$controller->$action(...$args);
		} else {
			self::exit("This function does not exist with the given parameters!", $controller->getFailSafePageUrl());
			return;
		}

		Session::set("lastUri", $dispatcher->getUri());

		// clearstatcache();
		if (config("debug", false)) {
			Debugger::GetInstance()->echoDebug();
		}
	}

	private static function exit(string $msg, string $redirectUrl): void
	{
		$debug = config("debug", false);

		Debugger::GetInstance()->addDebug("error", $msg);
		Router::Redirect($redirectUrl, 2, $debug);

		if ($debug) {
			Debugger::GetInstance()->echoDebug();
		}
	}
}
