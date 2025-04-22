<?php
namespace Mvc\Models;

use Mvc\Model;

class GroupAnimeModel extends Model
{
	protected static $table = "anime_group";

	protected $mandatory = ["group_name", "anime_id"];

	public $group_name;

	/** ["anime" => <AnimeModel>, "position" => <position>] */
	public $animes = [];

	public function save(bool $forceCreate = false)
	{
		parent::db()->beginTransaction();

		foreach ($this->animes as $anime) {
			$res = $anime["anime"]->save();

			if ($res) {
				if (!self::exist($this->group_name, $anime["anime"]->id)) {
					$res = parent::db()->table(self::$table)
							->insert([$this->group_name, $anime["anime"]->id, $anime["position"]])
							->run();

				}
				else {
					$res = parent::db()->table(self::$table)
							->update(["group_position" => $anime["position"]])
							->where("group_name", $this->group_name)->and("anime_id", $anime["anime"]->id)
							->run();
				}

				if (!$res) {
					$this->lastError = parent::db()->getLastError();
				}
			}
			else {
				$this->lastError = $anime["anime"]->lastError;
			}

			if (!$res) {
				parent::db()->endTransaction(false);
				return false;
			}
		}

		return parent::db()->endTransaction(true) ? $this : false;
	}

	/**
	 * Class constructor.
	 */
	public function __construct($group_name, array $animes = [])
	{
		$this->group_name = $group_name;
		$this->animes[] = $animes;
	}

	public function addAnime(AnimeModel $anime, int $position)
	{
		$this->animes[] = ["anime" => $anime, "position" => $position];
	}

	public static function readAllAnime(): array
	{
		parent::db()
			->selectAll(self::$table)
			->orderBy("group_name", "group_position")
			->run();

		$groups = [];
		foreach (parent::db()->getRows() as $row)
		{
			//$anime = new AnimeModel($row);
			$anime = AnimeModel::read($row["anime_id"]);

			if (array_key_exists($row["group_name"], $groups)) {
				$groups[$row["group_name"]]->addAnime($anime, $row["group_position"]);
			} else {
				$groups[$row["group_name"]] = new self(
							$row["group_name"],
							["anime" => $anime, "position" => (int)$row["group_position"]]
						);
			}

		}

		return $groups;
	}

	public static function readAllAnimeNoGroup(): array
	{
		parent::initDB();

		parent::db()
			->table(self::$table)
			->select("anime_info.anime_id", "anime_info.name")
			->join("anime_info", "", "NATURAL RIGHT")
			->whereIsNull("group_name")
			->run();

		$animes = [];
		foreach (parent::db()->getRows() as $row)
		{
			//$anime = new AnimeModel($row);
			$animes[$row["name"]] = AnimeModel::read($row["anime_id"]);
		}

		return $animes;
	}

	public static function read(string $groupName): self
	{
		parent::initDB();

		parent::db()
			->selectAll(self::$table)
			->where("group_name", $groupName)
			->run();

		if (parent::db()->isEmpty()) {
			return null;
		}

		$groupRows = parent::db()->getRows();

		$animes = [];
		foreach ($groupRows as $group) {
			$animes[] = ["anime" => AnimeModel::read($group["anime_id"]), "position" => $group["group_position"]];
		}

		return new self($groupName, $animes);
	}

	public static function readAllName(): array
	{
		parent::db()
			->table(self::$table)
			->selectDistinct("group_name")
			->run();

		$groups = parent::db()->getRows();

		$names = [];

		if(!empty($groups)) {
			foreach ($groups as $group) {
				$names[] = $group["group_name"];
			}
		}

		return $names;
	}

	public static function getRelatedAnime(int $idAnime): array
	{
		parent::db()
			->table("anime_info")
			->select("anime_info.anime_id", "name", "group_position")
			->join(self::$table, self::$table.".anime_id=anime_info.anime_id")
			//->where(self::$table.".group_name", "") // SUB QUERY
			->whereRawSubQuery(self::$table.".group_name", "SELECT DISTINCT group_name FROM anime_group WHERE anime_group.anime_id = $idAnime LIMIT 1")
			//->and(self::$table.".anime_id", $idAnime, "!=")
			->orderBy(self::$table.".group_position")
			->run();


		/*
		parent::db()
			->rawQuery("SELECT name, group_position FROM anime_info JOIN anime_group ON anime_group.anime_id=anime_info.anime_id WHERE anime_group.group_name = (SELECT DISTINCT group_name FROM anime_group WHERE anime_id=$idAnime LIMIT 1) AND anime_id != $idAnime ORDER BY anime_group.group_position")
			->run();
		*/

		return parent::db()->getRows();
	}

	public static function exist(string $groupName, ?int $animeId = null): bool
	{
		parent::db()
			->table(self::$table)
			->select("group_name")
			->where("group_name", $groupName);
		if (!empty($animeId)) {
			parent::db()
				->and("anime_id", $animeId);
		}
		parent::db()
			->limit(1)
			->run();

		return (bool)parent::db()->getNumRows();
	}

	public static function getGroup(int $animeId): ?self
	{
		parent::db()
			->table(self::$table)
			->select("*")
			->where("anime_id", $animeId)
			->limit(1)
			->run();

		if (parent::db()->isEmpty()) {
			return null;
		}

		$group = parent::db()->getFirstRow();

		return new self($group["group_name"], ["anime" => AnimeModel::read($group["anime_id"]), "position" => $group["group_position"]]);
	}

	public function delete(): bool
	{
		parent::db()->table($this::$table)->delete();

		$where = "group_name = '" . parent::db()->escapeString($this->group_name) . "'";
		if (!empty($this->animes)) {
			$where .= " AND (";

			$animes = [];
			foreach ($this->animes as $anime) {
				$animes[] = "anime_id = '" . parent::db()->escapeString($anime["anime"]->id) . "'";
			}

			$where .= implode(" OR ", $animes) . ")";

			parent::db()->whereRaw($where);
		}
		parent::db()->run();

		return parent::db()->getAffectedRow() > 0;
	}
}

?>
