<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Page caching
 *
 * Usage: Set your .htaccess into something like:
 *
 * # BEGIN Page cache
 *
 * RewriteRule ^/(.*)/$ /$1 [QSA]
 * RewriteRule ^$ media/pagecache/index.html [QSA]
 * RewriteRule ^([^.]+)/$ media/pagecache/$1/index.html [QSA]
 * RewriteRule ^([^.]+)$ media/pagecache/$1/index.html [QSA]
 *
 * # END Page cache
 * RewriteCond %{REQUEST_FILENAME} -s [OR]
 * RewriteCond %{REQUEST_FILENAME} -l [OR]
 * RewriteCond %{REQUEST_FILENAME} -d 
 *	
 * RewriteRule ^.*$ - [NC,L]
 * RewriteRule ^.*$ index.php [NC,L]
 *
 * Save the page like this:
 *		Pagecache::factory('/the/requested/uri')
 *			->write($the_page_output);
 *
 * You can change the cache directory by creating your own config/pagecache.php
 */
class Dc_Pagecache
{	
	/**
	 * File name
	 *
	 * @var string
	 */
	protected $_file;
	
	/**
	 * Cache directory
	 *
	 * @var string
	 */
	protected static $_cache_dir;
	
	/**
	 * Factory pattern for creating page cache
	 *
	 * @param string $uri
	 * @return Pagecache
	 */
	public static function factory($uri)
	{
		return new self($uri);
	}
	
	/**
	 * Returns the cache directory
	 *
	 * @return string
	 */
	public static function cache_dir()
	{
		if (self::$_cache_dir === null)
		{
			$config = Kohana::config('pagecache');
			if (!$config->offsetExists('cache_dir'))
			{
				throw new Exception('No cache directory is specified');
			}
			
			self::$_cache_dir = $config->get('cache_dir');
		}
		return self::$_cache_dir;
	}
	
	/**
	 * Cleans the whole cache
	 *
	 * @return void
	 */
	public static function cleanup()
	{
		$path = self::cache_dir();
		
		// only delete files
		return self::_delete_all($path, true);
	}
	
	/**
	 * Deletes files and directories recursively
	 *
	 * @param string $directory		target dir
	 * @param boolean $empty		whether to delete the dir or just empty it
	 * @return boolean
	 */
	protected static function _delete_all($directory, $empty = false)
	{
		// always check since we could accidentally delete root
		if ($directory == '/')
		{
			return false;
		}
		
		// remove trailing slash
		if(substr($directory,-1) == "/")
		{ 
			$directory = substr($directory,0,-1); 
		} 
		
		// should be a valid dir
		if(!file_exists($directory) || !is_dir($directory))
		{ 
			return false; 
		}
		
		// dir should be readable
		if(!is_readable($directory))
		{ 
			return false; 
		}
		
		$directoryHandle = opendir($directory); 
	
		while ($contents = readdir($directoryHandle))
		{ 
			if($contents != '.' && $contents != '..')
			{ 
				$path = $directory . "/" . $contents; 
	
				if(is_dir($path))
				{ 
					self::_delete_all($path); 
				}
				else
				{
					unlink($path);
				} 
			} 
		}
	
		closedir($directoryHandle); 
	
		if($empty == false)
		{ 
			if(!rmdir($directory))
			{ 
				return false; 
			} 
		} 
	
		return true; 
	}
	
	/**
	 * __construct()
	 *
	 * @param string $uri
	 * @return void
	 */
	protected function __construct($uri)
	{
		$this->_init_file($uri);
	}
	
	/**
	 * Initializes the file based on the uri
	 *
	 * @param string $uri
	 * @return $this
	 */
	protected function _init_file($uri)
	{
		$base = self::cache_dir();
		
		// create base path under the cache dir
		if (!is_dir($base))
		{
			mkdir($base, 0777);
			chmod($base, 0777);
		}

		// ensure that we only loop on path if the uri
		// is not empty
		$paths = array();
		if ($uri)
		{	
			$paths = explode('/', $uri);
		}

		// create the path to uri except for index.html
		$path = $base;
		foreach ($paths as $sub)
		{
			$path .= "/$sub";
			if (!is_dir($path))
			{
				mkdir($path, 0777);
				chmod($path, 0777);
			}
		}
		
		// cached page
		$this->_file = "$path/index.html";

		if (!file_exists($this->_file))
		{
			// Create the cache file
			file_put_contents($this->_file, '');
			
			// Allow anyone to write to log files
			chmod($this->_file, 0666);
		}
		
		return $this;
	}
	
	/**
	 * Writes to cache
	 *
	 * @param string $data
	 * @return $this
	 */
	public function write($data)
	{
		file_put_contents($this->_file, $data);
		return $this;
	}

	/** 
	 * Reads the cached page and returns it as string
	 *
	 * @return string
	 */
	public function read()
	{
		return file_get_contents($this->_file);
	}
	
	/**
	 * Deletes a cached page
	 *
	 * @return boolean
	 */
	public function delete()
	{
		return unlink($this->_file);
	}
}

