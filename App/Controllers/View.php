<?php

namespace Mvc\Controllers;

use Mvc\Controller;
use Mvc\Helpers\Debugger;
use Mvc\Helpers\HelpFunction;
use Mvc\Helpers\Router;
use Mvc\Helpers\Strings;
use Mvc\Helpers\VideoStream;
use Mvc\Models\AnimeModel;
use Mvc\Models\CodesModel;

class View extends Controller
{
	public function player($animeName, $typeCode, $num)
	{
		/** @var CodesModel $code */
		$code = CodesModel::read($typeCode);

		if (!$code) Router::Redirect($this->getFailSafePageUrl());

		$animeName = urldecode(str_replace("_", " ", $animeName));

		/** @var AnimeModel $anime */
		$anime = AnimeModel::read($animeName);

		if(!$anime) Router::Redirect($this->getFailSafePageUrl());

		$files = $anime->getSortedFile();

		$src = "";
		$prev = false;
		$post = false;
		if (!empty($files) && array_key_exists($typeCode, $files) && !empty($files[$typeCode])) {
			$numep = ((int)$num) - (Strings::contains($files[$typeCode][0], [" 00 ", " 00."]) ? 0 : 1);
			if (array_key_exists($numep, $files[$typeCode])) {
				$prev = $numep != 0;
				$post = $numep != count($files[$typeCode]) - 1;

				$file = $anime->getFilePath($typeCode, $numep);
				$src = $anime->getFileUrl($typeCode, $numep);
				$mime = !Strings::endsWith($file, ".mkv") ? mime_content_type($file) : "video/webm";
			}
		}

		$this->view([
						config("template") . "/template/header",
						config("template") . "/template/navbar",
						config("template") . "/template/banner",
						config("template") . "/anime/player",
						config("template") . "/template/footer"
					],
					[
						"title"	=> "Anime Player | " . $anime->name,
						"ogimg"	=> $anime->getImgUrl(),
						"anime"	=> [
										"pageurl"	=> $anime->getAnimeUrl(),
										"name"		=> $anime->name,
										"type"		=> $code,
										"num"		=> sprintf('%02d', $num),
										"src"		=> $src,
										"mime"		=> $mime
									],
						"btn"	=> [
										"prev"	=> $prev,
										"post"	=> $post
									]
					]
				);
	}

	public function viewImages(string $imgName): void
	{
		$imgName = urldecode($imgName);
		$src = HelpFunction::getImgSrc($imgName);

		if(empty($src)) {
			$src = path("resourcesdir", true) . "/images/copertinaInesistente.jpg";
		}

		ob_clean();

		$time = 60 * 60 * 24 * 365;
		Router::Mine(mime_content_type($src));
		Router::Header("Content-Length", filesize($src));
		Router::Header("Content-Disposition", "inline; filename=\"" . \basename($src) . "\";");
		Router::Header("Cache-Control", "public, max-age=$time", true);
		Router::Header("Expires", gmdate("D, d M Y H:i:s", time() + $time) . " GMT", true);
		Router::Header("Last-Modified", gmdate("D, d M Y H:i:s", @filemtime($src)) . " GMT", true);
		Router::Header("X-Content-Type-Options", "nosniff");
		Router::Header("X-Frame-Options", "SAMEORIGIN");
		Router::RemoveHeaders("Pragma");

		if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
			if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == @filemtime($src)) {
				Router::HeaderRaw("HTTP/1.1 304 Not Modified");
				return;
			}
		}

		$this->view("file", ["file" => readfile($src)]);
		
	}

	public function viewAnime(string ...$path): void
	{
		$path = implode('/', $path);
		$realpath = path("animedir") . "/" . $path;
		$realpath = str_replace("../", '', $realpath);

		if (!empty($path) && Strings::endsWith($realpath, [".mp4", ".mkv", ".avi"]) && file_exists($realpath) && is_file($realpath)) {
			try {
				$videostream = new VideoStream($realpath);
				$this->view("viewanime", ["videostream" => $videostream]);
			}
			catch (\Exception $ex) {
				$this->view("viewanime", ["videostream" => null, "error" => $ex->getMessage()]);
			}
		}
		else {
			$this->view("viewanime", ["videostream" => null, "error" => "File not found '$path' or it is not a video"]);
		}
	}
}

?>
