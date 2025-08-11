<?php

namespace Mvc\Models;

use Mvc\Helpers\Session;
use Mvc\Model;

class UserModel extends Model
{
	protected static $table = "user";

	/** @var UserModel $loggedUser */
	protected static $loggedUser = null;

	protected $mandatory = ["username", "password"];
	protected $filliable = ["id", "username", "password", "level", "last_login", "registration_date"];
	protected $hidden = ["password"];

	protected static $pepper = "";

	public function isAdmin(): bool
	{
		return $this->level->description != "PRIVILEGE_USER";
	}

	public function logout()
	{
		$res = Session::close();

		if ($res) {
			self::$loggedUser = null;
		}

		return $res;
	}

	public function save(bool $forceCreate = false)
	{
		if (!$forceCreate && (isset($this->id) && self::exist($this->id))) {
			return parent::db()->table(self::$table)
				->update(["last_login" => $this->last_login])
				->where("id", $this->id)
				->run();
		}

		return parent::db()->table(self::$table)
			->insert([$this->username, $this->password, (int) $this->level->code, $this->registration_date], ["username", "password", "level", "registration_date"])
			->run();
	}

	public function verifyPassword(string $password): bool
	{
		return password_verify($password, $this->password);

		//return hash("sha256", $this->data_iscrizione . $password . $this->username) == $this->password;
	}

	public function getSerie(string $animeId): ?SeriesModel
	{
		if (!isset($this->series)) {
			return null;
		}

		return $this->series[$animeId] ?? null;
	}

	public function addSerie(string $animeId)
	{
		$serie = $this->getSerie($animeId);

		if ($serie) {
			return false;
		}

		$serie = new SeriesModel(["user_id" => $this->id, "anime_id" => $animeId, "status" => CodesModel::read(300)]);
		$res = $serie->save();

		if (!$res) {
			return false;
		}

		$this->series[$animeId] = $serie;

		$this->updateCurrentUser();

		return $serie;
	}

	public function removeSerie(string $animeId): bool
	{
		$serie = $this->getSerie($animeId);

		if (!$serie) {
			return false;
		}

		$res = $serie->delete();

		if (!$res) {
			return false;
		}

		unset($this->series[$animeId]);

		$this->updateCurrentUser();

		return true;
	}

	public function hideSerie(string $animeId): bool
	{
		$serie = $this->getSerie($animeId);

		if (!$serie) {
			return false;
		}

		$serie->hide(!$serie->isHidden());

		if (!$serie->save()) {
			return false;
		}

		$this->series[$animeId] = $serie;

		$this->updateCurrentUser();

		return true;
	}

	public function updateSerie(string $animeId, array $groups): bool
	{
		$serie = $this->getSerie($animeId);

		if (!$serie) {
			return false;
		}

		$res = $serie->updateViewed($groups);

		if (!$res) {
			$this->lastError = $serie->lastError ?? "Error";
			return false;
		}

		$this->series[$animeId] = $serie;

		$this->updateCurrentUser();

		return true;
	}

	public function refresh(): self
	{
		$user = self::read($this->username);
		Session::set("user", $user, true);
		return $user;
	}

	public function refreshAllSeries(): void
	{
		foreach ($this->series as $animeid => $serie) {
			$serieStatus = $serie->status;
			$serie->updateStatus();

			if ($serieStatus != $serie->status) {
				$serie->save();
			}
		}
	}

	public static function hashPassword(string $password, int $cost = 10): string
	{
		return password_hash($password, PASSWORD_BCRYPT, ["cost" => $cost]);
	}

	public static function readAll()
	{
		parent::initDB();

		parent::db()->selectAll(self::$table)->run();

		$users = [];
		foreach (parent::db()->getRows() as $index => $user) {
			$users[$index] = new self($user);
			$users[$index]->level = CodesModel::read($user["level"]);
			$users[$index]->series = SeriesModel::read($user["id"]);
		}

		return $users;
	}

	public static function read(string $username)
	{
		parent::initDB();

		parent::db()->selectAll(self::$table)->where("username", $username)->limit(1)->run();

		if (parent::db()->isEmpty()) {
			return null;
		}
		$userRow = parent::db()->getFirstRow();

		$user = new self($userRow);
		$user->level = CodesModel::read($user->level);
		$user->series = SeriesModel::read($user->id);

		return $user;
	}

	public static function login(string $username, string $password)
	{
		$user = self::read($username);

		if (empty($user)) {
			return false;
		}

		$valid = $user->verifyPassword($password);

		if ($valid) {
			$user->last_login = date("Y-m-d H:i:s");
			$user->save();

			self::$loggedUser = $user;
			Session::set("user", $user, true);
			return $user;
		} else {
			return false;
		}
	}

	public static function register(string $username, string $password, int $level = 3, bool $autologin = true)
	{
		$user = new self(["username" => $username, "password" => self::hashPassword($password)]);
		$user->registration_date = date("Y-m-d");
		$user->level = CodesModel::read($level);

		$res = $user->save();

		return !$res || ($autologin && !self::login($username, $password)) ? false : $user;
	}

	public static function getCurrentUser(): ?self
	{
		if (!empty(self::$loggedUser)) {
			return self::$loggedUser;
		}

		$user = null;
		if (Session::has("user")) {// && Session::get("user", true)->isLogged) {
			$user = Session::get("user", true);
		}

		return $user;
	}

	public function updateCurrentUser(?self $user = null): bool
	{
		$cuser = self::getCurrentUser();
		if (empty($cuser) || $cuser->username != ($user ? $user->username : $this->username)) {
			return false;
		}

		if ($user) {
			Session::set("user", $user, true);
			self::$loggedUser = $user;

			return true;
		}

		Session::set("user", $this, true);
		self::$loggedUser = $this;

		return true;
	}

	public static function exist(string $id): bool
	{
		parent::initDB();

		parent::db()->table(self::$table)
			->select("id")
			->where("id", $id)
			->limit(1)->run();

		return (bool) parent::db()->getNumRows();
	}
}

?>

