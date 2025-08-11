<?php

namespace Mvc\Models;

use Mvc\Model;

class CodesModel extends Model
{
	protected static $table = "codes";

	protected $mandatory = ["code", "description"];

	protected static $codes = [];

	public static function read($code)
	{
		if (!empty(self::$codes)) {
			if (array_key_exists($code, self::$codes)) {
				if (is_numeric($code)) {
					return new self(["code" => $code, "description" => self::$codes[$code]]);
				} else {
					return new self(["code" => self::$codes[$code], "description" => $code]);
				}
			}
		}

		parent::db()->selectAll(self::$table);
		if (is_numeric($code)) {
			parent::db()->where("code", $code);
		} else {
			parent::db()->where("description", $code);
		}
		parent::db()->limit(1)->run();

		if (parent::db()->isEmpty()) {
			return null;
		}

		$codeRow = parent::db()->getFirstRow();

		self::$codes[$code] = is_numeric($code) ? $codeRow["description"] : $codeRow["code"];

		return new self($codeRow);
	}

	public function save(bool $forceCreate = false)
	{
		if (!$forceCreate && self::exist($this->code)) {
			parent::db()->beginTransaction();

			$res = parent::db()->table(self::$table)
				->update(["description" => $this->description])
				->run();

			$res &= parent::db()->endTransaction($res);

			if (!$res) {
				$this->lastError = parent::db()->getLastError();
				return $res;
			}

			return $this;
		} else {
			parent::db()->beginTransaction();

			$res = parent::db()->table(self::$table)
				->insert([$this->code, $this->description])
				->run();

			$res &= parent::db()->endTransaction($res);

			if (!$res) {
				$this->lastError = parent::db()->getLastError();
				return $res;
			}

			return $this;
		}
	}

	public static function exist($code): bool
	{
		parent::db()
			->table(self::$table)
			->select("code")
			->where("code", $code)
			->limit(1)
			->run();

		return !parent::db()->isEmpty();
	}

	public function getString(): string
	{
		return $this->code;
	}
}
