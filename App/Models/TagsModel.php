<?php

namespace Mvc\Models;

use Mvc\Model;

class TagsModel extends Model
{
	protected static $table = "tags";
	protected static $secondaryTables = ["mtom" => "anime_tag"];

	protected $mandatory = ["name"];
	protected $filliable = ["id", "name"];

	public function save(bool $forceCreate = false)
	{
		if (!$forceCreate && (isset($this->id) && self::exist($this->id))) {
			$res = parent::db()->beginTransaction();

			$res &= parent::db()->table(self::$table)
				->update(["name" => $this->name])
				->where("id", $this->id)
				->run();

			$res &= parent::db()->endTransaction($res);

			if (!$res) {
				$this->lastError = parent::db()->getLastError();
				return $res;
			}

			return $this;
		}

		$res = parent::db()->beginTransaction();

		$res &= parent::db()->table(self::$table)
			->insert([$this->name], ["name"])
			->run();

		if (!$res) {
			$this->lastError = parent::db()->getLastError();
			parent::db()->endTransaction(false);
			return $res;
		}

		$res &= parent::db()->endTransaction($res);

		if ($res) {
			$this->id = parent::db()->getLastInsertedId();
			return $this;
		}
		else {
			$this->lastError = parent::db()->getLastError();
			return $res;
		}
	}

	public function createLink($animeId, bool $createTag = false): bool
	{
		if (!isset($this->id) && $createTag) {
			if (!$this->save()) {
				return false;
			}
		} elseif (!isset($this->id) && $createTag) {
			$this->lastError = "Tag was not saved";
			return false;
		}

		$res = parent::db()->beginTransaction();

		$res &= parent::db()->table(self::$secondaryTables["mtom"])
			->insert([$animeId, $this->id])
			->run();

		return $res & parent::db()->endTransaction($res);
	}

	public function deleteLink($animeId): bool
	{
		if (!isset($this->id)) {
			$this->lastError = "Tag was not saved";
			return false;
		}

		$res = parent::db()->beginTransaction();

		$res &= parent::db()->table(self::$secondaryTables["mtom"])
			->delete()
			->where("anime_id", $animeId)->and("tag_id", $this->id)
			->run();

		return $res & parent::db()->endTransaction($res);
	}

	public static function read(string $tag): ?self
	{
		parent::db()->selectAll(self::$table);
		if (is_numeric($tag)) {
			parent::db()->where("id", $tag);
		} else {
			parent::db()->where("name", $tag);
		}
		parent::db()->run();

		if (parent::db()->isEmpty()) {
			return null;
		}

		$row = parent::db()->getFirstRow();

		return new self($row);
	}

	public static function readAnime(string $animeId): array
	{
		parent::db()->selectAll(self::$secondaryTables["mtom"])
			->where("anime_id", $animeId)
			->run();

		if (parent::db()->isEmpty()) {
			return [];
		}

		$rows = parent::db()->getRows();

		$tags = [];
		foreach ($rows as $row) {
			$tags[$row["tag_id"]] = self::read($row["tag_id"]);
		}

		return $tags;
	}

	public static function exist(string $tagId): bool
	{
		parent::db()
			->selectAll(self::$table)
			->where("id", $tagId)
			->limit(1)->run();

		return !parent::db()->isEmpty();
	}

	public static function existLink(string $tagId): bool
	{
		parent::db()
			->selectAll(self::$table)
			->where("id", $tagId)
			->limit(1)->run();

		return !parent::db()->isEmpty();
	}
}
