<?php
namespace Mvc\Helpers;

class HelpFunction
{
	public static function flatArray(array $array): array
	{
		$flat = []; // initialize return array
		$stack = array_values($array); // initialize stack
		while ($stack) // process stack until done
		{
			$value = array_shift($stack);
			if (empty($value)) {
				continue;
			}

			if (is_array($value)) // a value to further process
			{
				$stack = array_merge(array_values($value), $stack);
			} else // a value to take
			{
				$flat[] = $value;
			}
		}

		return $flat;
	}

	public static function recursiveObjectDiff($obj1, $obj2)
	{
		$diff = array();
		foreach ($obj1 as $k => $v) {
			if ((is_array($obj2) && array_key_exists($k, $obj2)) || (is_object($obj2) && isset($obj2->$k))) {
				if (is_array($v)) {
					if (is_array($obj2)) {
						$rad = self::recursiveObjectDiff($v, $obj2[$k]);
					} else if (is_object($obj2)) {
						$rad = self::recursiveObjectDiff($v, $obj2->$k);
					}
					if (!empty($rad)) {
						$diff[$k] = $rad;
					}
				} else {
					if (is_array($obj2) && $v != $obj2[$k]) {
						$diff[$k] = ["obj1" => $v, "obj2" => $obj2[$k]];
					} else if (is_object($obj2) && $v != $obj2->$k) {
						$diff[$k] = ["obj1" => $v, "obj2" => $obj2->$k];
					}
				}
			} else {
				$diff[$k] = ["obj1" => $v, "obj2" => null];
			}
		}
		return $diff;
	}

	public static function removeQueryStringVariables(string $url): string
	{
		$div = "?";// "/?";
		if (Strings::contains($url, $div))
			return substr($url, 0, strrpos($url, $div));

		return $url;
	}

	public static function alert($msg)
	{
		echo "<script type='text/javascript'>alert(\"{$msg}\");</script>";
	}

	public static function getImgSrc(string $imgName, ?string $path = null): ?string
	{
		$imgName = str_replace(["?", "_"], ["$", " "], $imgName);
		$ests = ["jpg", "png", "jpeg", "gif"];

		foreach ($ests as $est) {
			$src = path("animeimgsdir", true, path("imagesdir", true, __ABSPATH__)) . "/" . (!empty($path) ? (rtrim($path, "/") . "/") : "") . $imgName . "." . $est;
			if (file_exists($src)) {
				return $src;
			}
		}

		return null;
	}
}
