<?php

namespace Mvc\Helpers;

class Session
{
	public static $isStarted = false;

	public static function start()
	{
		if (!self::$isStarted) {
			return self::$isStarted = session_start();
		}

		return self::$isStarted;
	}

	public static function regenerateId()
	{
		if (self::$isStarted) {
			return session_regenerate_id(true);
		}

		return false;
	}

	public static function set($key, $value, $serialize = false)
	{
		if (self::$isStarted) {
			$_SESSION[$key] = $serialize ? serialize($value) : $value;
			return true;
		}

		return false;
	}

	public static function get($key, $unserialize = false, $default = null)
	{
		if (self::$isStarted) {
			if (!self::has($key)) {
				return $default;
			}

			if ($unserialize) {
				return unserialize($_SESSION[$key]);
			}

			return $_SESSION[$key];
		}

		return null;
	}

	public static function has($key)
	{
		if (self::$isStarted) {
			return isset($_SESSION[$key]);
		}

		return false;
	}

	public static function close(): bool
	{
		if (self::$isStarted) {
			session_unset();
			return self::$isStarted = session_destroy();
		}

		return true;
	}
}
