<?php
namespace Mvc;

use Mvc\Helpers\Singleton;
use Mvc\Helpers\Strings;

// TODO: Use PDO
// TODO: Make pquery

class Database extends Singleton
{
	/** @var \mysqli $db */
	protected $db;

	/** @var string */
	protected $query;

	/** @var string */
	protected $table;


	/** @var int */
	protected $affectedRow;

	/** @var int */
	protected $lastInsertedId;

	/** @var int */
	protected $numRows;

	/** @var array */
	protected $rows;

	public function init(): self
	{
		if(!is_null($this->db)) {
			return $this;
			// throw new \Exception("DB in already set", 1);
		}

		$db = new \mysqli(config("database")["hostname"], config("database")["user"], config("database")["passwd"], config("database")["dbname"]);
		
		if (!$db || $db->connect_errno) {
			throw new \Exception("Failed to connect: " . $db->connect_error, 1);
		}

		$db->set_charset((config("database")["charset"] ?? "utf8mb4") ?: "utf8mb4");

		$this->db = $db;

		$this->resetQuery();

		return $this;
	}

	public function escapeString(string $string, bool $extra = false): string
	{
		$string = str_replace("--", "-", $string);

		//return $this->db->real_escape_string($string);

		if ($extra)
			return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', $string);
		else
			return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $string);
	}

	public function resetQuery(): void
	{
		$this->query = "";
		$this->table = "";

		$this->rows = [];

		$this->numRows = 0;
		$this->affectedRow = 0;
	}

	protected function setQuery(string $query): self
	{
		$this->query .= " " . trim($query);
		$this->query = trim($this->query);

		return $this;
	}

	public function table(string $table): self
	{
		$this->resetQuery();

		$this->table = $table;

		return $this;
	}

	public function selectAll(string $table): self
	{
		$this->resetQuery();

		$this->table = $table;

		return $this->select();
	}

	public function select(string ...$columns): self
	{
		if (empty($this->table)) {
			throw new \Exception("The statement MUST start with table method", 1);
		}

		$params = "";
		if(empty($columns) || $columns[0] == "*") {
			$params = "*";
		}
		else {
			$params = implode(",", $columns);
		}

		$query = "SELECT $params FROM " . $this->table;

		return $this->setQuery($query);
	}

	public function selectDistinct(string ...$columns): self
	{
		if (empty($this->table)) {
			throw new \Exception("The statement MUST start with table method", 1);
		}

		$params = "";
		if(count($columns) == 0) {
			$params = "*";
		}
		else {
			$params = implode(",", $columns);
		}

		$query = "SELECT DISTINCT $params FROM " . $this->table;

		return $this->setQuery($query);
	}

	public function insert(array $data, ?array $columns = null): self
	{
		if (empty($this->table)) {
			throw new \Exception("The statement MUST start with table method", 1);
		}

		if (empty($data)) {
			throw new \Exception("Error Processing Request", 1);
		}

		$paramsFields = "";
		if(!empty($columns)) {
			$paramsFields = implode(",", $columns);
			$paramsFields = "($paramsFields)";
		}

		$paramsData = "";
		if (is_array(reset($data))) {
			foreach ($data as $set) {
				$fields = [];
				foreach ($set as $field) {
					if (empty($field)) {
						$fields[] = "NULL";
					}
					else {
						$fields[] = is_string($field) ? "'" . $this->escapeString($field, true) . "'" : $field;
					}
				}
				if (!empty($paramsData))
					$paramsData .= ',';

				$paramsData .= '(' . implode(',', $fields) . ')';
			}
		} else {
			$fields = [];
			foreach ($data as $field) {
				if (empty($field)) {
					$fields[] = "NULL";
				}
				else {
					$fields[] = is_string($field) ? "'" . $this->escapeString($field, true) . "'" : $field;
				}
			}
			$paramsData .= '(' . implode(',', $fields) . ')';
		}

		$query = "INSERT INTO " . $this->table . " $paramsFields VALUES $paramsData";

		return $this->setQuery($query);
	}

	public function delete(): self
	{
		if (empty($this->table)) {
			throw new \Exception("The statement MUST start with table method", 1);
		}

		$query = "DELETE FROM " . $this->table;

		return $this->setQuery($query);
	}

	public function update(array $data): self
	{
		if (empty($this->table)) {
			throw new \Exception("The statement MUST start with table method", 1);
		}

		if (empty($data)) {
			throw new \Exception("Error Processing Request", 1);
		}

		$values = [];
		foreach ($data as $key => $value) {
			$values[] = is_null($value) ? "$key=NULL" : "$key=" . (is_string($value) ? "'" . $this->escapeString($value, true) . "'" : $value);
		}

		if (empty($values)) {
			throw new \Exception("No Value to Update", 1);
		}

		$query = "UPDATE " . $this->table . " SET " . implode(",", $values);

		return $this->setQuery($query);
	}

	public function where(string $param1, $param2, string $operation = "="): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (is_string($param2)) {
			$param2 =  $this->escapeString($param2, false); //$this->escapeString($param2, strtoupper($operation) != "LIKE");
			$param2 = "'$param2'";
		}

		$query = "WHERE $param1 $operation $param2";

		return $this->setQuery($query);
	}

	public function whereRaw(string $where): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		// $where = $this->escapeString($where);

		$query = "WHERE $where";

		return $this->setQuery($query);
	}

	public function whereRawSubQuery(string $param1, string $query, string $operation = "="): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		// $query = $this->escapeString($query);

		$query = "WHERE $param1 $operation ($query)";

		return $this->setQuery($query);
	}

	public function whereIsNull(string $param): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		$query = "WHERE " . $this->isNull($param);

		return $this->setQuery($query);
	}

	public function andIsNull(string $param): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "WHERE")) {
			throw new \Exception("The statement MUST contains the WHERE keyword", 1);
		}

		$query = "AND " . $this->isNull($param);

		return $this->setQuery($query);
	}

	public function orIsNull(string $param): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "WHERE")) {
			throw new \Exception("The statement MUST contains the WHERE keyword", 1);
		}

		$query = "OR " . $this->isNull($param);

		return $this->setQuery($query);
	}

	public function whereIsNotNull(string $param): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		$query = "WHERE " . $this->isNull($param, true);

		return $this->setQuery($query);
	}

	public function andIsNotNull(string $param): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "WHERE")) {
			throw new \Exception("The statement MUST contains the WHERE keyword", 1);
		}

		$query = "AND " . $this->isNull($param, true);

		return $this->setQuery($query);
	}

	public function orIsNotNull(string $param): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "WHERE")) {
			throw new \Exception("The statement MUST contains the WHERE keyword", 1);
		}

		$query = "OR " . $this->isNull($param, true);

		return $this->setQuery($query);
	}

	private function isNull(string $param, bool $not = false): string
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		return "$param IS " . ($not ? "NOT" : "") . " NULL";
	}


	public function and(string $param1, $param2, string $operation = "="): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "WHERE")) {
			throw new \Exception("The statement MUST contains the WHERE keyword", 1);
		}

		if (is_string($param2)) {
			$param2 = $this->escapeString($param2, false); //$this->escapeString($param2, strtoupper($operation) != "LIKE");
			$param2 = "'$param2'";
		}

		$query = "AND $param1 $operation $param2";

		return $this->setQuery($query);
	}

	public function or(string $param1, $param2, string $operation = "="): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "WHERE")) {
			throw new \Exception("The statement MUST contains the WHERE keyword", 1);
		}

		if (is_string($param2)) {
			$param2 = $this->escapeString($param2, false); //$this->escapeString($param2, strtoupper($operation) != "LIKE");
			$param2 = "'$param2'";
		}

		$query = "OR $param1 $operation $param2";

		return $this->setQuery($query);
	}

	public function join(string $table, string $on = "", string $type = "INNER"): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		$type = strtoupper($type);
		$types = ["INNER", "NATURAL", "CROSS", "LEFT" , "LEFT OUTER", "NATURAL LEFT", "RIGHT" , "RIGHT OUTER", "NATURAL RIGHT", "FULL" , "FULL OUTER" /*. "SELF" */];
		if (!in_array($type, $types))
			throw new \Exception("The type MUST be with one of the standard SQL type", 1);

		if (Strings::startsWith($type, "NATURAL")) {
			$query = "$type JOIN $table";
		}
		else {
			$query = "$type JOIN $table ON $on";
		}

		return $this->setQuery($query);
	}

	public function orderBy(string ...$columns): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		$params = "";
		if(count($columns) == 0) {
			throw new \Exception("There MUST be at least one param", 1);
		}
		else {
			$params = implode(',', $columns);
		}

		$query = "ORDER BY $params";

		return $this->setQuery($query);
	}

	public function orderDesc(): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		if (!Strings::contains($this->query, "ORDER BY")) {
			throw new \Exception("The statement MUST contains the ORDER BY keyword", 1);
		}

		return $this->setQuery("DESC");
	}

	public function limit(int $limit, int $offset = 0): self
	{
		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		return $this->setQuery("LIMIT $offset, $limit");
	}

	public function run(): bool
	{
		if (empty($this->table)) {
			throw new \Exception("The statement MUST start with table method", 1);
		}

		if (empty($this->query)) {
			throw new \Exception("The statement MUST start with one of select, insert, update or delete", 1);
		}

		$result = $this->db->query($this->query);

		if ($result === false) {
			$this->lastError = $this->db->error;
			return false;
		}
		
		if (Strings::startsWith($this->query, "SELECT")) {
			$this->numRows = $result->num_rows;
			$this->rows = $result->fetch_all(MYSQLI_ASSOC) ?: [];
		} else if (Strings::startsWith($this->query,["DELETE", "UPDATE"])) {
			$this->affectedRow = $this->db->affected_rows;
		} else if (Strings::startsWith($this->query,["INSERT"])) {
			$this->affectedRow = $this->db->affected_rows;
			$this->lastInsertedId = $this->db->insert_id;
		}

		if (!config("debug", false)) {
			$this->query = "";
		}

		return true;
	}

	public function rawQuery(string $query): self //, ...$params): self
	{
		$query = trim(rtrim($query, ';'));

		$this->resetQuery();
		
		$this->table = "raw";
		$this->setQuery($query);

		return $this;
	}

	public function beginTransaction(): bool
	{
		return $this->db->begin_transaction();
	}

	public function endTransaction(bool $result): bool
	{
		if ($result) {
			return $this->db->commit();
		}
		else {
			return $this->db->rollback();
		}
	}

	public function getRows(): array
	{
		return $this->rows ?: [];
	}

	public function getFirstRow() : ?array
	{
		return reset($this->rows) ?: null;
	}

	public function getAffectedRow(): int
	{
		return $this->affectedRow;
	}

	public function getLastInsertedId(): int
	{
		return $this->lastInsertedId;
	}

	public function getNumRows(): int
	{
		return $this->numRows;
	}

	public function isEmpty(): bool
	{
		return empty($this->getRows());
	}

	public function close(): void
	{
		if(is_null($this->db)) {
			throw new \Exception("DB is not init", 1);
		}

		$this->resetQuery();

		$this->db->close();

		$this->db = null;
	}

	public function debug()
	{
		var_dump($this);
	}
}
