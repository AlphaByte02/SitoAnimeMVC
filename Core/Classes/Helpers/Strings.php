<?php
namespace Mvc\Helpers;

class Strings
{
	public static function contains(string $haystack, $needle, string $logic = "or")
	{
		if (empty($haystack) || empty($needle)) {
			return false;
		}

		if (is_array($needle)) {
			$logic = strtolower($logic);
			$res = $logic == "and";
			foreach ($needle as $piece) {
				if (strlen($piece) > strlen($haystack)) {
					if ($logic == "and") {
						return false;
					}
					
					continue;
				}

				$c = !(strpos($haystack, $piece) === false && strpos($haystack, $piece) !== 0);

				$res = ($logic == "and" ? ($res && $c) : ($res || $c));

				if ($res == ($logic != "and")) {
					return $piece ?: true;
				}
			}
			return false;
		}

		if (strlen($needle) > strlen($haystack)) {
			return false;
		}

		return !(strpos($haystack, $needle) === false && strpos($haystack, $needle) !== 0);
	}

	public static function endsWith(string $haystack, $needle)
	{
		if (empty($haystack) || empty($needle)) {
			return false;
		}

		if (is_array($needle)) {
			foreach ($needle as $piece) {
				if (strlen($piece) > strlen($haystack)) {
					continue;
				}

				if (strlen($piece) === 0 || (substr($haystack, -(strlen($piece))) === $piece)) {
					return $piece ?: true;
				}
			}
			return false;
		}

		if (strlen($needle) > strlen($haystack)) {
			return false;
		}

		return strlen($needle) === 0 || (substr($haystack, -(strlen($needle))) === $needle);
	}

	public static function startsWith(string $haystack, $needle)
	{
		if (empty($haystack) || empty($needle)) {
			return false;
		}

		if (is_array($needle)) {
			foreach ($needle as $piece) {
				if (strlen($piece) > strlen($haystack)) {
					continue;
				}

				if (substr($haystack, 0, strlen($piece)) === $piece) {
					return $piece ?: true;
				}
			}
			return false;
		}

		if (strlen($needle) > strlen($haystack)) {
			return false;
		}

		return substr($haystack, 0, strlen($needle)) === $needle;
	}

	public static function convertToStudlyCaps(string $string): string
	{
		return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
	}

	public static function convertToCamelCase(string $string): string
	{
		return lcfirst(self::convertToStudlyCaps($string));
	}

	public static function sha256(string $string, string $salt = ""): string
	{
		return hash("sha256", $string . $salt);
	}
}

?>
