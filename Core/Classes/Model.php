<?php
namespace Mvc;

abstract class Model
{
	protected static $table;

	/** @var Database $db */
	private static $db;

	/** @var App $app */
	protected static $app;

	protected $mandatory = [];
	protected $filliable = [];
	protected $hidden = [];

	public static function initDB()
	{
		if (empty(self::$db)) {
			self::$db = Database::getInstance()->init();
		}
	}

	protected static function db(): Database
	{
		self::initDB();

		return self::$db;
	}

	/**
	 * Class constructor.
	 */
	public function __construct(array $params)
	{
		self::initDB();

		if (empty($this->mandatory))
			throw new \Exception("No Mandatory Fields Set!", 1);

		if (empty($this->filliable))
			$this->filliable = $this->mandatory;

		$allset = true;
		foreach ($this->mandatory as $var) {
			$allset &= isset($params[$var]) && !is_null($params[$var]);
		}
		if (!$allset)
			throw new \Exception("Mandatory Fields Missing!", 1);

		foreach ($this->filliable as $var) {
			if (isset($params[$var]) && !empty($params[$var]))
				$this->$var = $params[$var];
		}

		if (empty(self::$app)) {
			self::$app = App::getInstance();
		}
	}

	public function delete(): bool
	{
		self::db()->beginTransaction();

		self::db()->table($this::$table)->delete();
		$nwhere = 0;
		foreach ($this->mandatory as $field) {
			if ($nwhere++ == 0) {
				self::db()->where($field, $this->$field);
			}
			else {
				self::db()->and($field, !is_object($this->$field) ? $this->$field : $this->$field->getString());
			}
		}
		$res = self::db()->run();

		return self::db()->endTransaction($res);
	}

	public static function readAll()
	{
		self::db()
			->selectAll(static::$table)
			->run();

		if (self::db()->isEmpty()) {
			return [];
		}

		$rows = self::db()->getRows();

		$data = [];
		foreach ($rows as $row) {
			$data[] = new static($row);
		}

		return $data;
	}

	abstract public function save(bool $forceCreate);
}

?>
