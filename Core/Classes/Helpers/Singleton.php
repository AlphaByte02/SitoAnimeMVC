<?php
namespace Mvc\Helpers;

class Singleton
{
	private static $instances = [];

	private function __construct()
	{
	}

	private function __clone()
	{
		throw new \Exception("You can't call this function", 1);
	}

	public function __sleep()
	{
		throw new \Exception("You can't call this function", 1);
	}

	public function __wakeup()
	{
		throw new \Exception("You can't call this function", 1);
	}

	/**
	 * Return the instance of the object
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		$cls = get_called_class();
		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls] = new static;
		}
		return self::$instances[$cls];
	}

	/**
	 * Check if the instace exist
	 *
	 * @return bool
	 */
	public static function exist(): bool
	{
		return array_key_exists(get_called_class(), self::$instances);
	}
}
