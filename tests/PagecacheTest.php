<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Unit test cases for Pagecache
 * Set your cache dir to other location for this test
 */
class PagecacheTest extends Kohana_UnitTest_TestCase {

	/**
	 * @test
	 */
	public function test_object()
	{
		$page = Pagecache::factory('/');

		$this->assertType('Dc_Pagecache', $page);
	}

	public function parse_status_provider()
	{
		// Param1 = html string data
		// Param2 = expected result
		return array(
			array('', FALSE),
			array('food', FALSE),
			array('<!-- not yet -->', FALSE),
			array('<html><!-- {PAGECACHE_CREATED}this is it{/PAGECACHE_CREATED} --></html>', TRUE),
			array('<html><!-- {PAGECACHE_CREATED}2011-03-20-15-00-00{/PAGECACHE_CREATED} --></html>', TRUE),
			array('<!-- {PAGECACHE_CREATED}2011-01-01 23:00:00{/PAGECACHE_CREATED} -->', TRUE),
			array('foo<!-- {PAGECACHE_CREATED}2011-01-01 21:00:00{/PAGECACHE_CREATED} -->bar', TRUE),
			array("aaaaaaaaaaaaaaaaaaaa\n\n\n<!-- {PAGECACHE_CREATED}2011-01-01 09:00:00{/PAGECACHE_CREATED} -->", TRUE),
		);
	}

	/**
	 * @dataProvider parse_status_provider
	 * @test
	 * @param  string	data
	 * @param  boolean	expected
	 */
	public function test_parse_status($data, $expected)
	{
		$result = Pagecache::parse_status($data);

		$this->assertSame( ! empty($result), $expected);
	}

	public function init_and_write_provider()
	{
		// Param1 = uri
		// Param2 = write content
		// Param3 = expected result check filename
		// Param4 = expected content (empty or not) empty = FALSE
		return array(
			array('/', FALSE, TRUE, FALSE),
			array('/index/page/1', TRUE, TRUE, TRUE),
			array('/contact/this-is-a-sample-slug.html', TRUE, TRUE, TRUE),
			array('/about/mission', FALSE, TRUE, FALSE),
		);
	}

	/**
	 * @dataProvider init_and_write_provider
	 * @test
	 * @param  string	uri
	 * @param  boolean	write content
	 * @param  boolean	expected filename check
	 * @param  boolean	expected content check
	 */
	public function test_write($uri, $write_content, $expected_file, $expected_content)
	{
		$base_path = Kohana::$config->load('pagecache.cache_dir');

		$page = Pagecache::factory($uri);

		if ($write_content)
		{
			$page->write('foo');
		}

		// Check if file really exists on cache dir
		$filename = $base_path.$uri.'/index.html';
		$filename_check = is_file($filename);

		$this->assertSame($filename_check, $expected_file);

		$content = file_get_contents($filename);

		$this->assertSame( ! empty($content), $expected_content);
	}

	/**
	 * @test
	 */
	public function test_read()
	{
		$input = "bar bar bar \n bar bar \n foo foo \n\n";
		$config = Kohana::$config->load('pagecache');

		$page = Pagecache::factory('/foo')->write($input);

		$content = Pagecache::factory('/foo')->read();

		if ($config->append_status && $status = Pagecache::parse_status($content))
		{
			// Remove status from content first
			$status = '<!-- {PAGECACHE_CREATED}'.$status.'{/PAGECACHE_CREATED} -->';

			$content = str_replace($status, '', $content);
		}

		$this->assertSame($content, $input);

		return $page;
	}

	/** 
	 * @test
	 * @depends test_read
	 * @param  Pagecache	page cache object
	 */
	public function test_delete(Pagecache $page)
	{
		$base_path = Kohana::$config->load('pagecache.cache_dir');

		$page->delete();

		$file_check = is_file($base_path.'/foo/index.html');

		$this->assertFalse($file_check);
	}

	/**
	 * @test
	 */
	public function test_cleanup()
	{
		Pagecache::cleanup();

		$directory = Kohana::$config->load('pagecache.cache_dir');

		$directory_handle = opendir($directory);
		$dir_empty = TRUE;

		while ($contents = readdir($directory_handle))
		{
			// Do not include files starting with .
			if(strpos($contents, '.') !== 0)
			{
				$dir_empty = FALSE;
				break;
			}
		}

		$this->assertTrue($dir_empty);
	}
}
