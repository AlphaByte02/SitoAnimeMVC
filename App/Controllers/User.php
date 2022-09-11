<?php

namespace Mvc\Controllers;

use Mvc\App;
use Mvc\Controller;
use Mvc\Helpers\Debugger;
use Mvc\Helpers\Request;
use Mvc\Helpers\Router;
use Mvc\Helpers\Session;
use Mvc\Models\AnimeModel;
use Mvc\Models\UserModel;

class User extends Controller
{
	public function index(): void
	{
		$user = UserModel::getCurrentUser();

		if (empty($user)) {
			Router::Redirect("/user/login");
		}

		// Debugger::getInstance()->addDebug("user", $user);

		$userSeries = $user->series;
		uasort($userSeries, function ($a, $b) {
			return $b->last_update <=> $a->last_update;
		});

		// echo "<pre>" . print_r($userSeries, true). "</pre>";

		$series = ["inprogress" => [], "concluded" => [], "added" => [], "hidden" => []];
		$tags = [];
		foreach ($userSeries as $serie) {
			$anime = AnimeModel::read($serie->anime_id);
			foreach ($anime->tags as $tag) {
				if (empty($tags) || !array_key_exists($tag->id, $tags)) {
					$tags[$tag->id] = ["c" => 1, "name" => $tag->name];
				} else {
					$tags[$tag->id]["c"] += 1;
				}
			}
			switch ($serie->status->code) {
				case 301:
					$series["inprogress"][] = $anime;
					break;
				case 302:
					$series["concluded"][] = $anime;
					break;
				case 303:
					$series["hidden"][] = $anime;
					break;
				default:
					$series["added"][] = $anime;
			}
		}


		uasort($tags, function ($a, $b) {
			return $b["c"] - $a["c"];
		});
		// var_dump($tags);
		$tags = array_slice($tags, 0, 3);
		// Debugger::getInstance()->addDebug("series", $series);

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/user/user",
				config("template") . "/template/footer"
			],
			[
				"title"				=> "User",
				"user"				=> $user,
				"series"			=> $series,
				"mostViewedTags"	=> $tags,
			]
		);
	}

	public function login(): void
	{
		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/user/login",
				config("template") . "/template/footer"
			],
			[
				"title"	=> "Login",
				"lastUri" => Session::get("lastUri")
			]
		);
	}

	public function register(): void
	{
		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/user/register",
				config("template") . "/template/footer"
			],
			[
				"title"	=> "Register"
			]
		);
	}

	// ! Dev Only
	public function refresh(): void
	{
		$user = UserModel::getCurrentUser();

		if (empty($user)) {
			Router::Redirect("/user/login");
		}

		$user->refresh();
	}

	// ! Dev Only
	public function refreshSeries(): void
	{
		$user = UserModel::getCurrentUser();

		if (empty($user)) {
			Router::Redirect("/user/login");
		}

		$user->refreshAllSeries();
	}

	public function loginPost(Request $request): void
	{
		if (!$request->isPost()) {
			echo "Error! No Post";
			return;
		}

		if (!$request->has("username", "password")) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Username o Password mancanti!"
				]
			);
			return;
		}

		$username = $request->get("username");
		$password = $request->get("password");
		$rememberme = $request->get("remember_me", false);

		$user = UserModel::login($username, $password);

		if ($user) {
			echo json_encode(["success" => true]);
		} else {
			echo json_encode(
				[
					"success" => false,
					"error" => "Username o Password errati!"
				]
			);
		}
	}

	public function registerPost(Request $request): void
	{
		if (!$request->isPost()) {
			echo "Error! No Post";
			return;
		}

		if (!$request->has("username", "password", "repeat_password")) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Username o Password mancanti!"
				]
			);
			return;
		}

		$username = $request->get("username");
		$password = $request->get("password");
		$repeat_password = $request->get("repeat_password");

		if ($password != $repeat_password) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Password MUST coincide"
				]
			);
			return;
		}

		$user = UserModel::register($username, $password);

		if ($user) {
			echo json_encode(["success" => true]);
		} else {
			echo json_encode(
				[
					"success" => false,
					"error" => "Si sono verificati degli errori!"
				]
			);
		}
	}

	public function logoutPost(): void
	{
		if (!App::getInstance()->isPostRequest) {
			echo "Error! No Post";
			return;
		}

		/** @var UserModel $user */
		$user = UserModel::getCurrentUser();

		if (!empty($user)) {
			echo json_encode(["success" => $user->logout()]);
			return;
		}

		echo json_encode(["success" => true]);
	}
}
