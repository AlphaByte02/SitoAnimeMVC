<?php

namespace Mvc\Models;

use Mvc\Model;

/**
 * @var int user_id
 * @var int anime_id
 * @var int[] watched
 * @var CodesModel status
 * @var string last_update
 */
class SeriesModel extends Model
{
	protected static $table = "user_anime";

	protected $mandatory = ["user_id", "anime_id"];
	protected $filliable = ["user_id", "anime_id", "watched", "status", "last_update"];

	/** @var int[] $maxEps */
	protected $maxEps;

	public function save(bool $forceCreate = false)
	{
		if (!$forceCreate && self::exist($this->user_id, $this->anime_id)) {
			parent::db()->beginTransaction();

			$lastUpdate = date("Y-m-d H:i:s");

			$res = parent::db()->table(self::$table)
				->update(["watched" => !empty($this->watched) ? json_encode($this->watched) : null, "status" => $this->status->code, "last_update" => $lastUpdate])
				->where("user_id", $this->user_id)->and("anime_id", $this->anime_id)
				->run();

			$res &= parent::db()->endTransaction($res);

			if ($res) {
				$this->last_update = $lastUpdate;
				return $this;
			} else {
				$this->lastError = parent::db()->getLastError();
				;
				return $res;
			}
		} else {
			parent::db()->beginTransaction();

			$lastUpdate = date("Y-m-d H:i:s");

			$res = parent::db()->table(self::$table)
				->insert([$this->user_id, $this->anime_id, 300, $lastUpdate], ["user_id", "anime_id", "status", "last_update"])
				->run();

			$res &= parent::db()->endTransaction($res);

			if ($res) {
				$this->last_update = $lastUpdate;
				return $this;
			} else {
				$this->lastError = parent::db()->getLastError();
				;
				return $res;
			}
		}
	}

	public function updateMaxEps($force = false): void
	{
		if (!isset($this->maxEps) || empty($this->maxEps) || $force) {
			$this->maxEps = [];

			$eps = EpisodeModel::read($this->anime_id);

			foreach ($eps as $code => $ep) {
				$this->maxEps[$code] = (int) $ep->number ?? 0;
			}
		}
	}

	public function updateViewed(array $groups): bool
	{
		$watch = $this->isAdded(true) ? [] : $this->watched;
		foreach ($groups as $key => $eps) {
			$code = CodesModel::read($key);
			if (empty($code)) {
				return false;
			}

			$values = [];
			if (!isset($watch[$code->code])) {
				$values = $eps;
			} else {
				$values = array_diff(array_merge($watch[$code->code], $eps), array_intersect($watch[$code->code], $eps));
			}

			sort($values);
			$watch[$code->code] = $values;

			if (empty($watch[$code->code])) {
				unset($watch[$code->code]);
			}
		}

		ksort($watch);
		$this->watched = $watch;
		$this->updateStatus();

		return (bool) $this->save();
	}

	public function updateStatus($forceCheck = true): void
	{
		if ($this->isAdded($forceCheck)) {
			$this->status = CodesModel::read(300);
		} else if ($this->isHidden($forceCheck)) {
			$this->status = CodesModel::read(303);
		} else if ($this->isConcluded($forceCheck)) {
			$this->status = CodesModel::read(302);
		} else {
			$this->status = CodesModel::read(301);
		}
	}

	public function isAdded(bool $forceCheck = false): bool
	{
		if (!$forceCheck) {
			return $this->status->code == 300;
		}

		return empty($this->watched);
	}

	public function isConcluded(bool $forceCheck = false): bool
	{
		if (!$forceCheck) {
			return $this->status->code == 302;
		}

		if (!isset($this->watched) || empty($this->watched)) {
			return false;
		}

		$this->updateMaxEps();

		$isConcluded = !empty($this->maxEps);
		if ($isConcluded) {
			if (count($this->watched) < count($this->maxEps)) {
				$isConcluded = false;
			}
		}

		if ($isConcluded) {
			foreach ($this->watched as $code => $epWatched) {
				if (array_key_exists($code, $this->maxEps) && $code != 50) {
					$isConcluded &= (count($epWatched) >= $this->maxEps[$code]);
					if (!$isConcluded) {
						break;
					}
				}
			}
		}

		if (!$isConcluded) {
			$anime = AnimeModel::read($this->anime_id);
			if ($anime->status->code == 202) {
				$files = $anime->getSortedFile();

				if (!empty($files)) {
					$isConcluded = true;
					foreach ($this->watched as $code => $epWatched) {
						if (array_key_exists($code, $files) && $code != 50) {
							$isConcluded &= (count($epWatched) >= count($files[$code]));
							if (!$isConcluded) {
								break;
							}
						}
					}
				}
			}
		}

		return $isConcluded;
	}

	public function isInProgress(bool $forceCheck = false): bool
	{
		return !$this->isAdded($forceCheck) && !$this->isConcluded($forceCheck) && !$this->isHidden();
	}

	public function isHidden(): bool
	{
		return $this->status->code == 303;
	}

	public function hide(bool $value = true): bool
	{
		$this->status->code = $value ? 303 : 300;
		$this->updateStatus();

		return $this->isHidden();
	}

	public function isViewed(int $type, int $num): bool
	{
		if (!isset($this->watched) || empty($this->watched)) {
			return false;
		}

		return array_key_exists($type, $this->watched) && in_array($num, $this->watched[$type]);
	}

	public static function read(string $idUser, string $idAnime = "")
	{
		parent::db()->selectAll(self::$table)->where("user_id", $idUser);
		if (!empty($idAnime)) {
			parent::db()->and("anime_id", $idAnime);
		}
		parent::db()->run();

		$rows = parent::db()->getRows();

		$series = [];
		foreach ($rows as $row) {
			$serie = new self($row);
			if (!empty($serie->watched)) {
				$serie->watched = json_decode($serie->watched, true);
			}
			$serie->status = CodesModel::read($row["status"]);

			$serie->updateMaxEps();
			$serie->updateStatus(false);

			$series[$serie->anime_id] = $serie;
		}

		return $series;
	}

	public static function exist(string $user_id, string $anime_id): bool
	{
		parent::db()->table(self::$table)
			->select("user_id", "anime_id")
			->where("user_id", $user_id)->and("anime_id", $anime_id)
			->limit(1)->run();

		return !parent::db()->isEmpty();
	}
}
