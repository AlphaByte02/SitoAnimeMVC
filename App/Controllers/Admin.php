<?php

namespace Mvc\Controllers;

use Mvc\Controller;
use Mvc\Helpers\Request;
use Mvc\Helpers\Router;
use Mvc\Models\AnimeModel;
use Mvc\Models\CodesModel;
use Mvc\Models\EpisodeModel;
use Mvc\Models\GroupAnimeModel;
use Mvc\Models\TagsModel;
use Mvc\Models\UserModel;

class Admin extends Controller
{
	private function isAdmin(bool $redirect = true): bool
	{
		$user = UserModel::getCurrentUser();

		if ($redirect) {
			if (empty($user)) {
				Router::Redirect("/user/login");
			}

			if (!$user->isAdmin()) {
				Router::Redirect("/user");
			}
		}


		return !empty($user) && $user->isAdmin();
	}

	public function index(): void
	{
		$this->isAdmin();

		// Debugger::getInstance()->addDebug("user", $user);

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/admin/admin",
				config("template") . "/template/footer"
			],
			[
				"title" => "Admin"
			]
		);
	}

	public function anime(string $view, ?int $id = null): void
	{
		$this->isAdmin();

		if (empty($id)) {
			switch ($view) {
				case "view":
					$this->viewAnime();
					break;
				case "create":
					$this->createAnime();
					break;
				default:
					Router::Redirect("/admin");
			}
		} else {
			switch ($view) {
				case "edit":
					$this->editAnime($id);
					break;
				case "createfrom":
					$this->createAnimeFrom($id);
					break;
				default:
					Router::Redirect("/admin");
			}
		}
	}

	private function createAnime(): void
	{
		$groupsName = GroupAnimeModel::readAllName();
		$tags = TagsModel::readAll();

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/admin/anime/create",
				config("template") . "/template/footer"
			],
			[
				"title" => "Create Anime",
				"groupsname" => $groupsName,
				"tags" => $tags,
			]
		);
	}

	private function createAnimeFrom($animeId): void
	{
		$group = GroupAnimeModel::getGroup($animeId);

		$anime = empty($group) ? AnimeModel::read($animeId) : $group->animes[0]["anime"];

		$groupname = empty($group) ? "" : $group->group_name;
		$groupposition = empty($group) ? "1" : $group->animes[0]["position"];

		if (!$anime) {
			Router::Redirect("/admin/anime/create");
		}

		$groupsName = GroupAnimeModel::readAllName();
		$tags = TagsModel::readAll();

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/admin/anime/createfrom",
				config("template") . "/template/footer"
			],
			[
				"title" => "Create Anime From {$anime->name}",
				"anime" => $anime,
				"groupsname" => $groupsName,
				"tags" => $tags,
				"groupname" => $groupname,
				"groupposition" => $groupposition,
			]
		);
	}

	public function createAnimePost(Request $request): void
	{
		if (!$this->isAdmin(false)) {
			echo json_encode(
				[
					"success" => false,
					"error" => "No Admin!"
				]
			);
			return;
		}

		if (!$request->isPost()) {
			echo json_encode(
				[
					"success" => false,
					"error" => "No Post!"
				]
			);
			return;
		}

		if (!$request->has("anime_name", "anime_release_date", "anime_state", "anime_number_ep", "anime_number_oav", "anime_number_special", "anime_number_movie", "anime_description", "anime_path")) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Fields missing!"
				]
			);
			return;
		}

		$status = CodesModel::read($request->get("anime_state"));

		if (!$status) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Field status wrong!"
				]
			);
			return;
		}

		$anime = new AnimeModel([
			"path" => '/' . str_replace('\\', '/', trim(trim($request->get("anime_path"), '/\\')) . '/'),
			"name" => trim($request->get("anime_name")),
			"name_en" => trim($request->get("anime_name_en")) ?: null,
			"imageurl" => $request->get("anime_image_url") ?: null,
			"status" => $status,
		]);

		if (!empty($request->get("anime_number_ep"))) {
			$anime->episode[51] = new EpisodeModel(["anime_id" => -1, "type" => CodesModel::read("EPISODE_EPISODE"), "number" => $request->get("anime_number_ep")]);
		}
		if (!empty($request->get("anime_number_oav"))) {
			$anime->episode[52] = new EpisodeModel(["anime_id" => -1, "type" => CodesModel::read("EPISODE_OAV"), "number" => $request->get("anime_number_oav")]);
		}
		if (!empty($request->get("anime_number_special"))) {
			$anime->episode[53] = new EpisodeModel(["anime_id" => -1, "type" => CodesModel::read("EPISODE_SPECIAL"), "number" => $request->get("anime_number_special")]);
		}
		if (!empty($request->get("anime_number_movie"))) {
			$anime->episode[54] = new EpisodeModel(["anime_id" => -1, "type" => CodesModel::read("EPISODE_MOVIE"), "number" => $request->get("anime_number_movie")]);
		}

		if ($tags = $request->get("anime_tags")) {
			foreach ($tags as $tag) {
				if ($tagmodel = TagsModel::read($tag)) {
					$anime->tags[$tagmodel->id] = $tagmodel;
				}
			}
			ksort($anime->tags);
		}
		if (!isset($anime->tags)) {
			$anime->tags = [];
		}

		$anime->release_date = $request->get("anime_release_date");
		$anime->description = trim($request->get("anime_description"));
		$anime->note = trim($request->get("anime_note"));

		$res = false;
		$error = "";
		if ($request->has("anime_group_name", "anime_group_position") && !empty($request->get("anime_group_name")) && !empty($request->get("anime_group_position"))) {
			$group = new GroupAnimeModel($request->get("anime_group_name"), ["anime" => $anime, "position" => $request->get("anime_group_position")]);

			$res = (bool) $group->save();
			$error = $group->lastError ?? "";
		} else {
			$res = (bool) $anime->save();
			$error = $anime->lastError ?? "";
		}

		if ($res) {
			echo json_encode(["success" => $res, "animeId" => $anime->id]);
		} else {
			echo json_encode(
				[
					"success" => $res,
					"error" => $error
				]
			);
		}
	}

	private function editAnime(int $animeId): void
	{
		$group = GroupAnimeModel::getGroup($animeId);

		$anime = empty($group) ? AnimeModel::read($animeId) : $group->animes[0]["anime"];

		$groupname = empty($group) ? "" : $group->group_name;
		$groupposition = empty($group) ? "1" : $group->animes[0]["position"];

		// Debugger::getInstance()->addDebug("group", $group);
		// Debugger::getInstance()->addDebug("anime", $anime);

		if (!$anime) {
			Router::Redirect("/admin");
		}

		$groupsName = GroupAnimeModel::readAllName();
		$tags = TagsModel::readAll();

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/admin/anime/edit",
				config("template") . "/template/footer"
			],
			[
				"title" => "Edit Anime | " . $anime->name,
				"groupsname" => $groupsName,
				"tags" => $tags,
				"anime" => $anime,
				"groupname" => $groupname,
				"groupposition" => $groupposition,
			]
		);
	}

	public function editAnimePost(Request $request): void
	{
		if (!$this->isAdmin(false)) {
			echo json_encode(
				[
					"success" => false,
					"error" => "No Admin!"
				]
			);
			return;
		}

		if (!$request->isPost()) {
			echo json_encode(
				[
					"success" => false,
					"error" => "No Post!"
				]
			);
			return;
		}

		if (!$request->has("anime_id", "anime_name", "anime_release_date", "anime_state", "anime_number_ep", "anime_number_oav", "anime_number_special", "anime_number_movie", "anime_description", "anime_path")) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Fields missing!"
				]
			);
			return;
		}

		$anime = null;
		if (empty($request->get("anime_id"))) {
			echo json_encode(
				[
					"success" => false,
					"error" => "ID is missing!"
				]
			);
			return;
		} else {
			$anime = AnimeModel::read($request->get("anime_id"));
		}

		if (empty($anime)) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Anime not Found!"
				]
			);
			return;
		}

		$status = CodesModel::read($request->get("anime_state"));

		if (!$status) {
			echo json_encode(
				[
					"success" => false,
					"error" => "Field status wrong!"
				]
			);
			return;
		}

		$animePost = new AnimeModel([
			"id" => $anime->id,
			"path" => '/' . str_replace('\\', '/', trim(trim($request->get("anime_path"), '/\\')) . '/'),
			"name" => trim($request->get("anime_name")),
			"name_en" => trim($request->get("anime_name_en")) ?: null,
			"imageurl" => $request->get("anime_image_url") ?: null,
			"status" => $status,
		]);

		if (!empty($request->get("anime_number_ep")) || ($request->get("anime_number_ep") == "0" && isset($anime->episode[51]))) {
			$animePost->episode[51] = new EpisodeModel(["anime_id" => $anime->id, "type" => CodesModel::read("EPISODE_EPISODE"), "number" => $request->get("anime_number_ep")]);
		}
		if (!empty($request->get("anime_number_oav")) || ($request->get("anime_number_oav") == "0" && isset($anime->episode[52]))) {
			$animePost->episode[52] = new EpisodeModel(["anime_id" => $anime->id, "type" => CodesModel::read("EPISODE_OAV"), "number" => $request->get("anime_number_oav")]);
		}
		if (!empty($request->get("anime_number_special")) || ($request->get("anime_number_special") == "0" && isset($anime->episode[53]))) {
			$animePost->episode[53] = new EpisodeModel(["anime_id" => $anime->id, "type" => CodesModel::read("EPISODE_SPECIAL"), "number" => $request->get("anime_number_special")]);
		}
		if (!empty($request->get("anime_number_movie")) || ($request->get("anime_number_movie") == "0" && isset($anime->episode[54]))) {
			$animePost->episode[54] = new EpisodeModel(["anime_id" => $anime->id, "type" => CodesModel::read("EPISODE_MOVIE"), "number" => $request->get("anime_number_movie")]);
		}

		if ($tags = $request->get("anime_tags")) {
			foreach ($tags as $tag) {
				if ($tagmodel = TagsModel::read($tag)) {
					$animePost->tags[$tagmodel->id] = $tagmodel;
				}
			}
			ksort($animePost->tags);
		}
		if (!isset($animePost->tags)) {
			$animePost->tags = [];
		}

		$animePost->release_date = $request->get("anime_release_date");
		$animePost->description = trim($request->get("anime_description"));
		$animePost->note = trim($request->get("anime_note")) ?: null;
		$animePost->last_update = $anime->last_update;

		// $diff = HelpFunction::recursiveObjectDiff($anime, $animePost);

		$group = GroupAnimeModel::getGroup($anime->id);
		if ($request->has("anime_group_name", "anime_group_position") && !empty($request->get("anime_group_name")) && !empty($request->get("anime_group_position"))) {
			$groupPost = new GroupAnimeModel($request->get("anime_group_name"), ["anime" => $animePost, "position" => $request->get("anime_group_position")]);
		}

		if ((!empty($group) && $group == $groupPost && $anime == $animePost) || (empty($group) && empty($groupPost) && $anime == $animePost)) {
			echo json_encode(
				[
					"success" => false,
					"error" => "No Changes Made!"
				]
			);
		} else {
			$res = false;
			$error = "";
			if ((!empty($group) && empty($groupPost)) || (!empty($group) && !empty($groupPost) && $group->group_name != $groupPost->group_name)) {
				$group->delete();
			}

			if (!empty($groupPost)) {
				$res = (bool) $groupPost->save();
				$error = $groupPost->lastError ?? "Error";
			} else {
				$res = (bool) $animePost->save();
				$error = $animePost->lastError ?? "Error";
			}

			if ($res) {
				echo json_encode(["success" => $res, "animeId" => $animePost->id]);
			} else {
				echo json_encode(
					[
						"success" => $res,
						"error" => $error
					]
				);
			}
		}
	}

	private function viewAnime(): void
	{
		$animes = [];

		$animegroups = GroupAnimeModel::readAllAnime();
		$animenogroups = GroupAnimeModel::readAllAnimeNoGroup();

		foreach ($animegroups as $group) {
			foreach ($group->animes as $anime) {
				$animes[$anime["anime"]->id] = ["anime" => $anime["anime"], "group" => ["name" => $group->group_name, "position" => $anime["position"]]];
			}
		}

		foreach ($animenogroups as $anime) {
			$animes[$anime->id] = ["anime" => $anime];
		}

		ksort($animes);

		//Debugger::getInstance()->addDebug("anime", $animes);

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/admin/anime/table",
				config("template") . "/template/footer"
			],
			[
				"title" => "View Anime",
				"animes" => $animes
			]
		);
	}

	public function user(string $view): void
	{
		$this->isAdmin();

		if (empty($id)) {
			switch ($view) {
				case "view":
					$this->viewUser();
					break;
				case "create":
					// $this->createUser();
					Router::Redirect("/admin");
					break;
				default:
					Router::Redirect("/admin");
			}
		} else {
			switch ($view) {
				case "edit":
					// $this->editUser($id);
					Router::Redirect("/admin");
					break;
				default:
					Router::Redirect("/admin");
			}
		}
	}

	private function viewUser(): void
	{
		$users = UserModel::readAll();

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/admin/user/table",
				config("template") . "/template/footer"
			],
			[
				"title" => "View Utenti",
				"users" => $users
			]
		);
	}
}
