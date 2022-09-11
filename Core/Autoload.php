<?php

spl_autoload_register(function($className) {
	$class = array_map(function($el) {
		return ucwords($el);
	}, explode("\\" , $className));
	if(reset($class) == config("mainnamespace", "Mvc")) {
		$class = array_slice($class, 1);
	}

	$class = implode("/" , $class);

	$paths = array(
		"/Core/Classes/$class.php",
		"/App/$class.php",
		"/vendor/$class.php",
		"/$class.php"
	);
	foreach ($paths as $path)
	{
		if (file_exists(__ABSPATH__ . $path))
		{
			require_once __ABSPATH__ . $path;

			return;
		}
	}

	throw new \Exception("Class Not Found: \\$className", 1);
});
