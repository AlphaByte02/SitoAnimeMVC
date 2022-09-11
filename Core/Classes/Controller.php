<?php
namespace Mvc;

use Mvc\Helpers\Translator;

abstract class Controller
{
	public function getFailSafePageUrl(): string
	{
		return "/";
	}

	protected function view($templateName, array $data = []): void
	{
		// Debugger::GetInstance()->echoDebug();

		if(is_array($data) && !empty($data))
			extract($data);

		$trans = function(string $string, int $count = 0, ?string $lang = "") {
			return Translator::getInstance()->translate($lang ?: config("lang", "en"), $string, $count);
		};

		if(is_array($templateName))
		{
			foreach ($templateName as $name)
				@include_once __ABSPATH__ . "/App/Views/$name.php";
		}
		else
			@include_once __ABSPATH__ . "/App/Views/$templateName.php";
	}
}

?>
