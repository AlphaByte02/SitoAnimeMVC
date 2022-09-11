<?php
namespace Mvc\Helpers;

use Mvc\Helpers\Singleton;

class Translator extends Singleton {

	protected $langs = [];

	public function translate(string $lang, string $string, int $count = 0): string
	{
		$tmp = $this->divide($string);
		$file = $tmp["file"];
		$key = $tmp["string"];

		if (empty($file) || empty($key))
			return $string;

		$trans = $this->getTranslate($lang, $file, $key, $count);

		if (!empty($trans)) {
			return $trans;
		}

		if (!isset($this->langs[$lang][$file]) && file_exists(path("langdir", true) . "/$lang/$file.php")) {
			$this->langs[$lang][$file] = include path("langdir", true) . "/$lang/$file.php";

			$trans = $this->getTranslate($lang, $file, $key, $count);

			if (!empty($trans)) {
				return $trans;
			}
		}

		return $string;
	}

	protected function divide($string): array
	{
		$parts = explode(".", trim($string, '.'));

		return ["file" => $parts[0], "string" => $parts[1]];
	}

	protected function getTranslate(string $lang, string $file, string $string, int $count = 0) : ?string
	{
		if (array_key_exists($lang, $this->langs)) {
			if (array_key_exists($file, $this->langs[$lang])) {
				if (!is_array($this->langs[$lang][$file][$string])) {
					return $this->langs[$lang][$file][$string];
				}
				else {
					 // TODO: Manage range field if given
					$countTransalte = count($this->langs[$lang][$file][$string]);
					return $this->langs[$lang][$file][$string][$count >= $countTransalte ? ($countTransalte - 1) : ($count >= 1 ? ($count - 1) : 0)];
				}
			}
		}

		return null;
	}
}

?>
