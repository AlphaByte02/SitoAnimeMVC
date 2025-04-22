<?php

namespace Mvc\Models;

use Mvc\Model;

class EpisodeModel extends Model
{
	protected static $table = "anime_number";

	protected $mandatory = ["anime_id", "type"];
	protected $filliable = ["anime_id", "type", "number"];

	public function save(bool $forceCreate = false)
	{
		if(!$forceCreate && self::exist($this->anime_id, $this->type->code)) {
			$res = parent::db()->beginTransaction();

			if (!isset($this->number) || empty($this->number)) {
				$res &= $this->delete();
			}
			else {
				$res &= parent::db()->table(self::$table)
						->update(["number" => $this->number])
						->where("anime_id", $this->anime_id)->and("type", $this->type->code)
						->run();

			}

			$res &= parent::db()->endTransaction($res);

			if (!$res) {
				$this->lastError = parent::db()->getLastError();
				return $res;
			}

			return $this;
		} else {
			if (!isset($this->number) || empty($this->number)) {
				$this->lastError = "Number must be greater than 0";
				return false;
			}

			$res = parent::db()->beginTransaction();

			$res &= parent::db()->table(self::$table)
						->insert([$this->anime_id, (int)$this->type->code, $this->number])
						->run();

			if (!$res) {
				$this->lastError = parent::db()->getLastError();
				return $res;
			}

			return $this;
		}
	}

	public static function read(string $idAnime): array
	{
		parent::db()->selectAll(self::$table)->where("anime_id", $idAnime)->run();

		$rows = parent::db()->getRows();

		$episode = [];
		foreach ($rows as $row) {
			$code = CodesModel::read($row["type"]);
			$episode[$code->code] = new self([
									"anime_id"	=> $row["anime_id"],
									"type"		=> $code,
									"number"	=> $row["number"]
									]);
		}

		return $episode;
	}

	public static function exist(string $anime_id, int $type): bool
	{
		parent::db()->table(self::$table)
			->select("anime_id", "type")
			->where("anime_id", $anime_id)->and("type", $type)
			->limit(1)->run();

		return !parent::db()->isEmpty();
	}
}
