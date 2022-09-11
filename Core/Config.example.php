<?php
define("__CONFIG__", array(
	"debug"			=>	false,
	"database"		=>	[
		"hostname"	=>	"",
		"user"		=>	"",
		"passwd"	=>	"",
		"dbname"	=>	"",
	],
	"lang"			=>	"en",
	"subdir"		=>	"",
	"template"		=>	"",
));

// Without trailing '/' for non root path
$paths = [
	"animedir"		=>	"",
	"animeimgsdir"	=>	"",

	"resourcesdir" => "Resources",
	"langdir" => "Resources/lang",

	"imagesdir" => "Resources/images",
	"javascriptdir" => "Resources/js",
	"styledir" => "Resources/styles",
];

define("__ABSPATH__", $_SERVER["DOCUMENT_ROOT"] . config("subdir"));

/**
 * Return the config value by key,
 * if not found return default value
 *
 * @param mixed $key
 * @param mixed $default
 *
 * @return mixed
 */
function config($key, $default = "")
{
	return __CONFIG__[$key] ?? $default;
}

/**
 * Return the path value by key,
 * if not found return default value
 *
 * @param mixed $key
 * @param bool $fromroot start with root path
 * @param mixed $default
 *
 * @return mixed
 */
function path($key, bool $fromroot = false, $default = "")
{
	global $paths;

	$value = $paths[$key] ?? $default;

	if (!empty($value) && (substr($value, 0, 1) == "/" || substr($value, 1, 2) == ":/")) {
		return $value;
	}

	return ($fromroot ? __ABSPATH__ : config("subdir")) . '/' . trim($value, '/');
}
