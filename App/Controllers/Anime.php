<?php

namespace Mvc\Controllers;

use Mvc\Controller;
use Mvc\Helpers\Debugger;
use Mvc\Helpers\Request;
use Mvc\Helpers\Router;
use Mvc\Helpers\Strings;
use Mvc\Models\AnimeModel;
use Mvc\Models\CodesModel;
use Mvc\Models\GroupAnimeModel;
use Mvc\Models\UserModel;

class Anime extends Controller
{
	public function index(): void
	{
		$allAnime = AnimeModel::readAll();

		$numAnimeRandom = 4;
		$entries =  array_intersect_key($allAnime, array_flip(array_rand($allAnime, $numAnimeRandom)));

		$numLastViewed = 4;
		$lastAnimeViewed = [];
		if ($user = UserModel::getCurrentUser()) {
			$userSeries = $user->series;
			uasort($userSeries, function ($a, $b) {
				return $b->last_update <=> $a->last_update;
			});
			foreach ($userSeries as $serie) {
				if (count($lastAnimeViewed) >= $numLastViewed) {
					break;
				}

				if ($serie->status->code == 301) {
					if ($anime = AnimeModel::read($serie->anime_id)) {
						$lastAnimeViewed[] = $anime;
					}
				}
			}
		}

		//Debugger::GetInstance()->addDebug("entries", $entries);

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/anime/main",
				config("template") . "/template/footer"
			],
			[
				"title"				=> "Main",
				"animeRandom"		=> $entries,
				"numAnimeRandom"	=> $numAnimeRandom,
				"lastAnimeViewed"	=> $lastAnimeViewed,
				"numLastViewed"		=> $numLastViewed,
			]
		);
	}

	public function archive(): void
	{
		$allAnime = GroupAnimeModel::readAllAnime() + GroupAnimeModel::readAllAnimeNoGroup();

		//Debugger::GetInstance()->addDebug("groups", $allAnime);

		$animes = array();
		$totalAnimeNum = 0;
		foreach ($allAnime as $anime) {
			if ($anime instanceof GroupAnimeModel) {
				$animes[is_numeric($anime->group_name[0]) ? "#" : $anime->group_name[0]][] = $anime;
				$totalAnimeNum += count($anime->animes);
			} else if ($anime instanceof AnimeModel) {
				$animes[is_numeric($anime->name[0]) ? "#" : $anime->name[0]][] = $anime;
				$totalAnimeNum += 1;
			}
		}
		ksort($animes);

		// Debugger::GetInstance()->addDebug("this", $animes);
		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/anime/archivio",
				config("template") . "/template/footer"
			],
			[
				"title"			=>	"Archivio",
				"animes"		=>	$animes,
				"totalAnimeNum"	=>	$totalAnimeNum
			]
		);
	}

	public function page(string $animeName): void
	{
		$animeName = urldecode(str_replace("_", " ", $animeName));

		/** @var AnimeModel $anime */
		$anime = AnimeModel::read($animeName);

		if (!$anime) Router::Redirect($this->getFailSafePageUrl()); // TODO: redirect error page

		$animeGroup = GroupAnimeModel::getGroup($anime->id);

		if (!empty($animeGroup)) {
			$relatedGroup = GroupAnimeModel::getRelatedAnime($anime->id);

			$groupPosition = $animeGroup->animes[0]["position"];
			$related = [];
			foreach ($relatedGroup as $group) {
				if ($group["anime_id"] != $anime->id) {
					$related[] = ["anime" => new AnimeModel($group), "group_position" =>  $group["group_position"]];
				}
			}
		}


		$currentUser = UserModel::getCurrentUser();
		$serie = !empty($currentUser) ? UserModel::getCurrentUser()->getSerie($anime->id) : false;

		$files = $anime->getSortedFile();
		$ep = [];
		if (!empty($files)) {
			foreach ($files as $key => $group) {
				$code = CodesModel::read($key);

				$abr = "";
				switch ($code->code) {
					case 51:
						$abr = "Ep";
						break;
					case 52:
						$abr = "OAV";
						break;
					case 53:
						$abr = "Special";
						break;
					case 54:
						$abr = "Movie";
						break;
					default:
						$abr = "";
				}

				$startZero = false;
				$allViewed = true;
				$someViewed = false;
				foreach ($group as $file) {
					$startZero |= (bool)Strings::contains($file, [" 00 ", " 00."]);
					$n = (!empty($ep) && !empty($ep[$code->description]["ep"]) ? count($ep[$code->description]["ep"]) : 0) + ($startZero ? 0 : 1);
					$sn = sprintf('%02d', $n);

					if ($code->code != 50) {
						$isViewed = $serie ? $serie->isViewed($code->code, $n) : false;
						$allViewed &= $isViewed;
						$someViewed |= $isViewed;

						$ep[$code->description]["allViewed"] = $allViewed;
						$ep[$code->description]["someViewed"] = $someViewed;
					}

					$ep[$code->description]["ep"][] = [
						"code" => $code->code,
						"name" => $anime->name . " - $abr $sn",
						"url" => $anime->getPlayerUrl($code->code, $n),
						"num" => $n,
						"isViewed" => $isViewed ?? false
					];
				}
			}
		}

		// Debugger::getInstance()->addDebug("ep", $ep);
		// Debugger::getInstance()->addDebug("serie", $serie);
		// Debugger::getInstance()->addDebug("user", $currentUser);

		$this->view(
			[
				config("template") . "/template/header",
				config("template") . "/template/navbar",
				config("template") . "/template/banner",
				config("template") . "/anime/animepage",
				config("template") . "/template/footer"
			],
			[
				"title" 		=> "AnimePage | " . $anime->name,
				"anime"			=> $anime,
				"related"		=> $related ?? [],
				"groupPosition" => $groupPosition ?? 0,
				"ep"			=> $ep,
				"ogimg"			=> $anime->getImgUrl(),
				"img"			=> [
					"src"		=> $anime->getImgUrl(),
					"cssclass"	=> "mx-auto rounded" . (config("template") == "bootstrap" ? " d-block img-fluid align-self-center" : " responsive-img"), // materialboxed
					"alt"		=> "AnimeCover"
				],
				"isLogged"		=> !empty($currentUser),
				"isAdmin"		=> !empty($currentUser) && $currentUser->isAdmin(),
				"serie"			=> $serie
			]
		);
	}

	public function toggleFollowPost(Request $request): void
	{
		if (!$request->isPost()) {
			echo "Error! No Post";
			return;
		}

		if (!$request->has("animeId") || empty($request->get("animeId"))) {
			echo json_encode(["success" => false, "error" => "No Anime ID Found"]);
			return;
		}

		/** @var UserModel $user */
		$user = UserModel::getCurrentUser();

		if (empty($user)) {
			echo json_encode(["success" => false, "error" => "No User logged in"]);
			return;
		}

		$animeId = $request->get("animeId");

		if (!AnimeModel::exist($animeId)) {
			echo json_encode(["success" => false, "error" => "No Anime Found"]);
			return;
		}

		$serie = $user->getSerie($animeId);

		if (!$serie) {
			echo json_encode(["success" => (bool)$user->addSerie($animeId)]);
		} else {
			echo json_encode(["success" => (bool)$user->removeSerie($animeId)]);
		}
	}

	public function toggleHideSeriePost(Request $request): void
	{
		if (!$request->isPost()) {
			echo "Error! No Post";
			return;
		}

		if (!$request->has("animeId") || empty($request->get("animeId"))) {
			echo json_encode(["success" => false, "error" => "No Anime ID Found"]);
			return;
		}

		/** @var UserModel $user */
		$user = UserModel::getCurrentUser();

		if (empty($user)) {
			echo json_encode(["success" => false, "error" => "No User logged in"]);
			return;
		}

		$animeId = $request->get("animeId");

		if (!AnimeModel::exist($animeId)) {
			echo json_encode(["success" => false, "error" => "No Anime Found"]);
			return;
		}

		$serie = $user->getSerie($animeId);

		if ($serie) {
			echo json_encode(["success" => $user->hideSerie($animeId)]);
		} else {
			echo json_encode(["success" => false, "error" => "No Serie Found!"]);
		}
	}

	public function updateSeriePost(Request $request): void
	{
		if (!$request->isPost()) {
			echo "Error! No Post";
			return;
		}

		if (!$request->has("animeId") || empty($request->get("animeId"))) {
			echo json_encode(["success" => false, "error" => "No Anime ID Found"]);
			return;
		}

		/** @var UserModel $user */
		$user = UserModel::getCurrentUser();

		if (empty($user)) {
			echo json_encode(["success" => false, "error" => "No User logged in"]);
			return;
		}

		$animeId = $request->get("animeId");

		if (!AnimeModel::exist($animeId)) {
			echo json_encode(["success" => false, "error" => "No Anime Found"]);
			return;
		}

		$keyGroup = ["EPISODE_EPISODE", "EPISODE_OAV", "EPISODE_SPECIAL", "EPISODE_MOVIE", "EPISODE_NONE"];
		$groups = [];
		foreach ($keyGroup as $key) {
			if ($request->has($key) && !empty($request->get($key))) {
				$code = CodesModel::read($key);
				if (!empty($code)) {
					$groups[$code->code] = $request->get($key);
				}
			}
		}

		if (empty($groups)) {
			echo json_encode(["success" => true]);
			return;
		}

		$res = $user->updateSerie($animeId, $groups);
		echo json_encode(["success" => $res, "error" => $user->lastError ?? ""]);
	}
}
