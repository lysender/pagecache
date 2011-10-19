<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Simple page caching for kohana 3.1
 *
 * Save the page like this:
 *		Pagecache::factory('/the/requested/uri')
 *			->write($the_page_output);
 *
 * You can change the cache directory by creating your own config/pagecache.php
 */
abstract class Dc_Pagecache {
	
	/**
	 * @var string
	 */
	protected $_file;

	/** 
	 * @var string
	 */
	protected $_cache_dir;

	/** 
	 * @var boolean
	 */
	protected $_append_status;
	
	/**
	 * Factory pattern for creating page cache
	 *
	 * @param   string		uri
	 * @param   array		options
	 * @return  Pagecache
	 */
	public static function factory($uri, array $options = NULL)
	{
		if ($options === NULL)
		{
			$options = Kohana::$config->load('pagecache')->as_array();
		}

		// Strip base url on path when found
		$base_url = Kohana::$base_url;

		if (strpos($uri, $base_url) === 0)
		{
			$uri = '/'.substr($uri, strlen($base_url));
		}

		return new Pagecache($uri, $options);
	}
	
	/**
	 * Cleans the whole cache
	 *
	 * @return  void
	 */
	public static function cleanup()
	{
		$path = Kohana::$config->load('pagecache.cache_dir');
		
		// Only delete files, not the cache dir
		return self::_delete_all($path, TRUE);
	}
	
	/**
	 * Deletes files and directories recursively
	 *
	 * @param   string		target directory
	 * @param   boolean		whether to delete the dir or just empty it
	 * @return  boolean
	 */
	protected static function _delete_all($directory, $empty = FALSE)
	{
		// Always check since we could accidentally delete root
		if ($directory == '/')
		{
			return FALSE;
		}
		
		// Remove trailing slash
		if(substr($directory, -1) == '/')
		{ 
			$directory = substr($directory, 0, -1);
		} 
		
		// Should be a valid dir
		if( ! is_dir($directory))
		{ 
			return FALSE;
		}
		
		// Dir should be readable
		if( ! is_readable($directory))
		{ 
			return FALSE;
		}
		
		$directory_handle = opendir($directory);
	
		while ($contents = readdir($directory_handle))
		{
			// Do not include directories starting with dot (.)
			if(strpos($contents, '.') !== 0)
			{ 
				$path = $directory . '/' . $contents;
	
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
	
		closedir($directory_handle);
	
		if($empty === FALSE)
		{ 
			if( ! rmdir($directory))
			{ 
				return FALSE;
			} 
		} 
	
		return TRUE;
	}
	
	/**
	 * __construct()
	 *
	 * @param   string	URI
	 * @param   array	options
	 * @return  void
	 */
	protected function __construct($uri, array $options)
	{
		$this->_cache_dir = $options['cache_dir'];
		$this->_append_status = $options['append_status'];
		
		$this->_init_file($uri);
	}

	/**
	 * Initializes the file based on the uri
	 *
	 * @param   string	uri
	 * @return  $this
	 */
	protected function _init_file($uri)
	{
		$base = $this->_cache_dir;
		
		// Create base path under the cache dir
		if ( ! is_dir($base))
		{
			mkdir($base, 0777);
			chmod($base, 0777);
		}

		// Ensure that we only loop on path if the uri
		// is not empty
		$paths = array();

		if ($uri)
		{	
			$paths = explode('/', $uri);
		}

		// Create the path to uri except for index.html
		$path = $base;
		
		foreach ($paths as $sub)
		{
			$path .= "/$sub";
			if ( ! is_dir($path))
			{
				mkdir($path, 0777);
				chmod($path, 0777);
			}
		}
		
		// Cached page
		$this->_file = "$path/index.html";

		if ( ! is_file($this->_file))
		{
			// Create the cache file with empty contents
			file_put_contents($this->_file, '');
			
			// Allow anyone to write to cached files
			chmod($this->_file, 0666);
		}
		
		return $this;
	}
	
	/**
	 * Writes to cache
	 *
	 * @param   string	data
	 * @return  $this
	 */
	public function write($data)
	{
		if ($this->_append_status)
		{
			$data .= $this->_create_status();
		}
		
		file_put_contents($this->_file, $data);
		
		return $this;
	}

	/**
	 * Generates a status to be appended to the cached page
	 * as HTML comment
	 *
	 * @return  string
	 */
	protected function _create_status()
	{
		return '<!-- {PAGECACHE_CREATED}'.date('Y-m-d H:i:s').'{/PAGECACHE_CREATED} -->';
	}

	/**
	 * Parses the cached data and returns the time the cache was created
	 * Returns false when status is not found
	 *
	 * @param   string	data
	 * @return  string
	 */
	public static function parse_status($data)
	{
		$regex = '/<\\!-- {PAGECACHE_CREATED}(.+){\/PAGECACHE_CREATED} -->/';
		$matches = array();

		if (preg_match($regex, $data, $matches))
		{
			if (isset($matches[1]))
			{
				return $matches[1];
			}
		}

		return FALSE;
	}

	/** 
	 * Reads the cached page and returns it as string
	 *
	 * @return  string
	 */
	public function read()
	{
		return file_get_contents($this->_file);
	}
	
	/**
	 * Deletes a cached page
	 *
	 * @return  boolean
	 */
	public function delete()
	{
		return unlink($this->_file);
	}
	
} // End Dc_Pagecache
