<?php
namespace Mvc\Controllers;

use Mvc\Controller;

class Main extends Controller
{
	public function index(): void
	{
		$this->view(["bootstrap/template/head", "bootstrap/main/main", "bootstrap/template/footer"], ["title" => "Main"]);
	}

	public function mainParam(...$i)
	{
		$this->view(
			["bootstrap/template/head", "bootstrap/main/mainParam", "bootstrap/template/footer"],
			[
				"title" => "MainParam",
				"param" => $i
			]
		);
	}
}

?>

