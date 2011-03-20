<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Pagecache administration console
 */
class Controller_Pagecache_Console extends Controller_Template
{
	/**
	 * @var  View  page template
	 */
	public $template = 'pagecache/template';

	public function before()
	{
		parent::before();

		if (Kohana::$environment !== Kohana::DEVELOPMENT)
		{
			throw new Http_Exception_404('Viewing pagecache administration is not allowed in non-development environment');
		}
	}
	
	/**
	 * Main admin page
	 */
	public function action_index()
	{
		$this->template->content = View::factory('pagecache/console/index');
	}

	/**
	 * Tests the cache setup if it really works
	 */
	public function action_testcache()
	{
		$url = URL::site('/pagecache/console/test', 'http');

		// Trigger caching a page
		Request::factory($url)
			->execute()
			->body();

		// Should contain a cached page
		$content2 = Request::factory($url)
			->execute()
			->body();

		// Still a cached page
		$content3 = Request::factory($url)
			->execute()
			->body();
		
		if ($content2 === $content3)
		{
			$created = Pagecache::parse_status($content2);
		}
		
		$this->template->content = View::factory('pagecache/console/testcache')
			->bind('created', $created);
	}

	/** 
	 * Clears all cached pages
	 */
	public function action_clearcache()
	{
		Pagecache::cleanup();
		
		$cache_dir = Kohana::config('pagecache.cache_dir');
		
		$this->template->content = View::factory('pagecache/console/clearcache')
			->bind('cache_dir', $cache_dir);
	}

	/**
	 * Test cache page
	 */
	public function action_test()
	{
		$this->auto_render = FALSE;
		$this->template->content = View::factory('pagecache/console/test');
		
		$output = (string) $this->template;
		$this->response->body($output);

		$uri = Arr::get($_SERVER, 'REQUEST_URI', $this->request->uri());
		
		Pagecache::factory($uri)
			->write($output);
	}
}