<?php
namespace Mvc\Helpers;

class Debugger extends Singleton
{
	private $debugKey;

	public function addDebug(string $key, $ob): void
	{
		if (is_null($this->debugKey))
			$this->debugKey = array();

		$this->debugKey[$key] = $ob;
	}

	public function getDebug(?string $key = null)
	{
		if (is_null($key))
			return $this->debugKey;

		if (key_exists($key, $this->debugKey))
			return $this->debugKey[$key];
		else
			return null;
	}

	public function echoDebug(bool $destroyAfter = true)
	{
		if (!empty($this->debugKey)) {
			$debug = "<pre id='debug'>";
			foreach ($this->debugKey as $dk => $dv) {
				if ($debug != "<pre id='debug'>")
					$debug .= "<br>";

				$debug .= "$dk => " . (!is_null($dv) ? print_r($dv, true) : "NULL");
			}
			echo $debug . "</pre>";
		}

		if ($destroyAfter)
			$this->debugKey = array();
	}
}
