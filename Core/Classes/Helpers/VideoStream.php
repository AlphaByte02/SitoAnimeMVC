<?php
/**
 * Description of VideoStream
 *
 * @author Rana
 * @link http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 */

namespace Mvc\Helpers;

class VideoStream
{
	protected $path;
	protected $stream;
	protected static $buffer = 102400;
	protected $start;
	protected $end;
	protected $size;

	function __construct($filePath)
	{
		$this->path = $filePath;

		$this->stream = "";
		$this->start = -1;
		$this->end = -1;
		$this->size = 0;
	}

	/**
	 * Open stream
	 */
	private function open()
	{
		if (!($this->stream = fopen($this->path, 'rb'))) {
			throw new \Exception("Could not open stream for reading", 1);
		}
	}

	/**
	 * Set proper header to serve the video content
	 */
	private function setHeader()
	{
		ob_clean();
		Router::Mine(!Strings::endsWith($this->path, ".mkv") ? mime_content_type($this->path) : "video/webm");
		Router::Header("Content-Disposition", "inline; filename=\"" . \basename($this->path) . "\"");
		Router::Header("Cache-Control", "public, max-age=2592000", true);
		Router::Header("Expires", gmdate("D, d M Y H:i:s", time() + 2592000) . " GMT", true);
		Router::Header("Last-Modified", gmdate("D, d M Y H:i:s", @filemtime($this->path)) . " GMT", true);
		Router::Header("X-Content-Type-Options", "nosniff");
		Router::Header("X-Frame-Options", "SAMEORIGIN");
		$this->start = 0;
		$this->size = filesize($this->path);
		$this->end = $this->size - 1;
		Router::Header("Accept-Ranges", "0-" . $this->end, true);

		if (isset($_SERVER['HTTP_RANGE'])) {

			$c_start = $this->start;
			$c_end = $this->end;

			list(, $range) = explode(Strings::contains($_SERVER["HTTP_RANGE"], ':') ? ':' : '=', $_SERVER["HTTP_RANGE"], 2);
			if (strpos($range, ',') !== false) {
				Router::HeaderRaw("HTTP/1.1 416 Requested Range Not Satisfiable");
				Router::Header("Content-Range", "bytes $this->start-$this->end/$this->size");
				exit;
			}
			if ($range == '-') {
				$c_start = $this->size - substr($range, 1);
			} else {
				$range = explode('-', $range);
				$c_start = $range[0];

				$c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
			}
			$c_end = ($c_end > $this->end) ? $this->end : $c_end;
			if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
				Router::HeaderRaw("HTTP/1.1 416 Requested Range Not Satisfiable");
				Router::Header("Content-Range", "bytes $this->start-$this->end/$this->size");
				exit;
			}
			$this->start = $c_start;
			$this->end = $c_end;
			$length = $this->end - $this->start + 1;
			fseek($this->stream, $this->start);
			Router::HeaderRaw('HTTP/1.1 206 Partial Content');
			Router::Header("Content-Length", $length);
			Router::Header("Content-Range", "bytes $this->start-$this->end/" . $this->size);
		} else {
			Router::Header("Content-Length", $this->size);
		}
	}

	/**
	 * close curretly opened stream
	 */
	private function end()
	{
		fclose($this->stream);
		exit;
	}

	/**
	 * perform the streaming of calculated range
	 */
	private function stream()
	{
		$start = $this->start;
		set_time_limit(0);
		while (!feof($this->stream) && $start <= $this->end) {
			$bytesToRead = self::$buffer;
			if (($start + $bytesToRead) > $this->end) {
				$bytesToRead = $this->end - $start + 1;
			}
			$data = fread($this->stream, $bytesToRead);
			echo $data;
			flush();
			$start += $bytesToRead;
		}
	}

	/**
	 * Start streaming video content
	 */
	public function start()
	{
		$this->open();
		$this->setHeader();
		$this->stream();
		$this->end();
	}
}
