<?php

namespace Posvenda;

class AjaxCache 
{
	private $cacheDir = '/mnt/temporario/cache/ajax';

	private $cacheFile;

	private $useCache = true;

	public function __construct($fabrica, $admin, $file)
	{
		if (!is_dir($this->cacheDir) or !is_writable($this->cacheDir)) {
			$this->useCache = false;
		}

		$this->cacheFile = $this->cacheDir . '/' . $fabrica . '-' . $admin . '-' . preg_replace(['/\//', '/\./'], '__', $file) . '.json';
	}

	public function cacheFileExists()
	{
		return file_exists($this->cacheFile);
	}

	public function writeCache($content)
	{
		return file_put_contents($this->cacheFile, $content);
	}

	public function getFromCache()
	{
		if (false === $this->useCache) {
			return '';
		}

		if ($this->cacheFileExists()) {
			return file_get_contents($this->cacheFile);
		}

		return '';
	}

	public function cleanCache()
	{
		unlink($this->cacheFile);

		return '';
	}
}
