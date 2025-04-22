<?php

namespace Mvc\Models;

use Mvc\Helpers\Strings;
use Mvc\Model;

class AnimeModel extends Model
{
	protected static $table = "anime";
	protected static $secondaryTables = ["info" => "anime_info"];

	protected $mandatory = ["name"];
	protected $filliable = ["id", "path", "name", "name_en", "imageurl", "status"];

	protected static $files = [];
	protected static $sortedFiles = [];

	public function save(bool $forceCreate = false)
	{
		if (!$forceCreate && (isset($this->id) && self::exist($this->id))) {
			parent::db()->beginTransaction();

			$lastUpdate = date("Y-m-d H:i:s");

			// anime
			$res = parent::db()->table(self::$table)
				->update(["path" => $this->path, "last_update" => $lastUpdate])
				->where("id", $this->id)
				->run();

			if (!$res) {
				parent::db()->endTransaction(false);
				$this->lastError = parent::db()->getLastError();
				return false;
			}

			// anime_info
			$res &= parent::db()->table(self::$secondaryTables["info"])
				->update([
					"name" => $this->name,
					"name_en" => ($this->name_en ?? NULL) ?: NULL,
					"imageurl" => ($this->imageurl ?? NULL) ?: NULL,
					"status" => !empty($this->status) ? $this->status->code : 201,
					"release_date" => ($this->release_date ?? date("Y-m-d")) ?: date("Y-m-d"),
					"description" => ($this->description ?? "") ?: "",
					"note" => ($this->note ?? NULL) ?: NULL,
				])
				->where("anime_id", $this->id)
				->run();

			if (!$res) {
				parent::db()->endTransaction(false);
				$this->lastError = parent::db()->getLastError();
				return false;
			}
			// episode
			if (isset($this->episode) && !empty($this->episode)) {
				foreach ($this->episode as $episode) {
					$res &= (bool)$episode->save();

					if (!$res) {
						$this->lastError = $episode->lastError;
						parent::db()->endTransaction(false);
						return false;
					}
				}
			}

			if (!$res) {
				parent::db()->endTransaction(false);
				$this->lastError = parent::db()->getLastError();
				return false;
			}

			// tags
			if (isset($this->tags) && !empty($this->tags)) {
				$originalTags = TagsModel::readAnime($this->id);

				$compareFun = function ($a, $b) {
					return $a->name <=> $b->name;
				};

				$deletedTags = array_udiff($originalTags, $this->tags, $compareFun);
				foreach ($deletedTags as $tag) {
					if (!isset($tag->id)) {
						continue;
					}
					if (!$res) {
						break;
					}

					$res &= $tag->deleteLink($this->id);
				}
				$addedTags = array_udiff($this->tags, $originalTags, $compareFun);
				foreach ($addedTags as $tag) {
					if (!isset($tag->id)) {
						continue;
					}

					$res &= $tag->createLink($this->id, true);

					if (!$res) {
						$this->lastError = $tag->lastError ?? "Error Tags";
						parent::db()->endTransaction(false);
						return false;
					}
				}
			}

			$res &= parent::db()->endTransaction($res);

			if ($res) {
				$this->last_update = $lastUpdate;
				return $this;
			} else {
				$this->lastError = "Error";
				return $res;
			}
		}

		parent::db()->beginTransaction();

		// anime
		$res = parent::db()->table(self::$table)
			->insert([$this->path], ["path"])
			->run();

		if (!$res) {
			$this->lastError = "Anime già presente nel Database!";
			parent::db()->endTransaction(false);
			return false;
		}

		$animeId = parent::db()->getLastInsertedId();

		// anime_info
		$res &= (bool)parent::db()->table(self::$secondaryTables["info"])
			->insert(
				[
					$animeId,
					$this->name,
					($this->name_en ?? NULL) ?: NULL,
					($this->imageurl ?? NULL) ?: NULL,
					!empty($this->status) ? $this->status->code : 201,
					($this->release_date ?? date("Y-m-d")) ?: date("Y-m-d"),
					($this->description ?? "") ?: "",
					($this->note ?? NULL) ?: NULL,
				],
				["anime_id", "name", "name_en", "imageurl", "status", "release_date", "description", "note"]
			)
			->run();

		if (!$res) {
			$this->lastError = parent::db()->getLastError();
			parent::db()->endTransaction(false);
			return false;
		}

		// episode
		if (isset($this->episode) && !empty($this->episode)) {
			foreach ($this->episode as $episode) {
				$episode->anime_id = $animeId;
				$res &= (bool)$episode->save();

				if (!$res) {
					$this->lastError = $episode->lastError;
					parent::db()->endTransaction(false);
					return false;
				}
			}
		}

		// tags
		if (isset($this->tags) && !empty($this->tags)) {
			foreach ($this->tags as $tag) {
				if (!isset($tag->id)) {
					continue;
				}

				$res &= $tag->createLink($animeId);

				if (!$res) {
					$this->lastError = $tag->lastError ?? "Error Tags";
					parent::db()->endTransaction(false);
					return false;
				}
			}
		}

		$res &= parent::db()->endTransaction($res);

		if ($res) {
			$this->id = $animeId;
			return $this;
		} else {
			$this->lastError = "Error";
			return $res;
		}
	}

	public function getEncodedName(): string
	{
		return urlencode(str_replace(" ", "_", $this->name));
	}

	public function getImgUrl(): string
	{
		return $this->imageurl ?? config("subdir") . (!self::$app->singleController ? "/View" : "") . "/viewImages/" . urlencode($this->name);
	}

	public function getDateTime(): \DateTime
	{
		if (!isset($this->release_date)) {
			return new \DateTime();
		}

		return new \DateTime($this->release_date);
	}

	public function getAnimeUrl(): string
	{
		return config("subdir") . (!self::$app->singleController ? "/Anime" : "") . "/page/" . $this->getEncodedName();
	}

	public function getPlayerUrl(string $type, int $num): string
	{
		return config("subdir") . (!self::$app->singleController ? "/View" : "") . "/player/" . $this->getEncodedName() . "/$type/" . sprintf('%02d', $num);
	}

	public function getFilePath(string $type, int $num, bool $fromRoot = true): string
	{
		if (!isset($this->path)) {
			return "";
		}

		return ($fromRoot ? path("animedir") : "") . '/' . trim($this->path . $this->getSortedFile()[$type][$num], '/');
	}

	public function getFileUrl(string $type, int $num): string
	{
		if (!isset($this->path)) {
			return config("subdir") . (!self::$app->singleController ? "/View" : "") . "/viewAnime/no_path_found";
		}

		return config("subdir") . (!self::$app->singleController ? "/View" : "") . "/viewAnime" . $this->getFilePath($type, $num, false);
	}

	public function getSortedFile()
	{
		if (empty($this->path))
			return null;

		if (!empty(self::$sortedFiles[$this->id]))
			return self::$sortedFiles[$this->id];

		$files = $this->getFile();
		if (empty($files))
			return null;

		$ep = [];
		foreach ($files as $file) {
			$name = strtolower($file);
			if (Strings::contains($name, [" ep ", "_ep_", " episodio ", "_episodio_"])) {
				$code = 51;
			} else if (Strings::contains($name, [" oav ", "oav.", "_oav_", " ova ", " ova.", "_ova_"])) {
				$code = 52;
			} else if (Strings::contains($name, [" special ", " special.", "_special_", " sp ", " sp.", "_sp_"])) {
				$code = 53;
			} else if (Strings::contains($name, [" movie ", " movie.", "_movie_", " film ", " film.", "_film_"])) {
				$code = 54;
			} else {
				$code = 50;
			}

			$ep[$code][] = $file;
		}

		ksort($ep);
		self::$sortedFiles[$this->id] = $ep;

		return $ep;
	}

	public function getFile()
	{
		if (empty($this->path))
			return null;

		if (!empty(self::$files[$this->id]))
			return self::$files[$this->id];

		return self::$files[$this->id] = $this->getFileFromDir(path("animedir") . "/" . trim($this->path, "/"));
	}

	private function getFileFromDir($path): array
	{
		if (!file_exists($path)) {
			return [];
		}

		$files = array_diff(scandir($path), [".", ".."]);

		if (empty($files)) {
			return [];
		}

		$ar = [];
		foreach ($files as $file) {
			if (is_dir($path . "/" . $file)) {
				$tmp = $this->getFileFromDir($path . "/" . $file);
				foreach ($tmp as $tmpFile) {
					$ar[] = $file . "/" . $tmpFile;
				}
			} else if (Strings::endsWith($file, [".mp4", ".mkv", ".avi"])) {
				$ar[] = $file;
			}
		}

		return $ar;
	}

	public static function readAll()
	{
		parent::db()->selectAll(self::$table)
			->join(self::$secondaryTables["info"], self::$table . ".id=anime_info.anime_id")
			->run();

		$animes = [];
		foreach (parent::db()->getRows() as $anime) {
			$animes[] = self::newAnime($anime);
		}

		return $animes;
	}

	public static function exist(string $id): bool
	{
		parent::db()
			->table(self::$table)
			->select("id")
			->where("id", $id)
			->limit(1)
			->run();

		return (bool)parent::db()->getNumRows();
	}

	public static function read(string $anime)
	{
		parent::db()
			->selectAll(self::$table)
			->join(self::$secondaryTables["info"], self::$table . ".id=anime_info.anime_id");
		if (is_numeric($anime)) {
			parent::db()->where("id", $anime);
		} else {
			parent::db()->where("name", $anime);
		}
		parent::db()->limit(1)->run();

		if (parent::db()->isEmpty()) {
			return null;
		}

		$animeRow = parent::db()->getFirstRow();

		return self::newAnime($animeRow);
	}

	private static function newAnime(array $animeRow): self
	{
		$anime = new self($animeRow);
		$anime->status = CodesModel::read($anime->status);
		$anime->episode = EpisodeModel::read($anime->id);
		$anime->tags = TagsModel::readAnime($anime->id);
		$anime->release_date = $animeRow["release_date"];
		$anime->description = $animeRow["description"];
		$anime->note = $animeRow["note"];
		$anime->last_update = $animeRow["last_update"];

		return $anime;
	}
}
