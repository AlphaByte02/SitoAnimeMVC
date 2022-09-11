<?php
namespace Mvc;

use Mvc\Helpers\Router;
use Mvc\Helpers\Dispatcher;
use Mvc\Helpers\Session;
use Mvc\Helpers\Singleton;

class App extends Singleton
{
	public $mainController;
	public $singleController;

	public $isStarted = false;
	public $isPostRequest = false;

	/**
	 * Class inizialize.
	 */
	public function init(?string $mainController = "Main", bool $singleController = false): self
	{
		$this->mainController = $mainController;
		$this->singleController = $singleController;

		$this->inizializeComponent();

		return $this;
	}

	public function inizializeComponent(): void
	{
		// Inizialization Component
		Session::start();
		$this->isPostRequest = ($_SERVER["REQUEST_METHOD"] == "POST");
	}

	public function setSingleController(bool $value): void
	{
		if(!$this->isStarted)
			$this->singleController = $value;
		else
			throw new \Exception("You cannot change settings when the application is started", 1);
	}

	public function setMainController(string $controller): void
	{
		if(!$this->isStarted)
			$this->mainController = $controller;
		else
			throw new \Exception("You cannot change settings when the application is started", 1);
	}

	public function start(): void
	{
		Router::Route(new Dispatcher($_SERVER["REQUEST_URI"], $this->isPostRequest, $this->mainController, $this->singleController));

		$this->isStarted = true;
	}
}
?>
