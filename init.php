<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Pagecache administration console
 * 
 */
Route::set('pagecache/console', 'pagecache/console(/<action>(/<id>))')
	->defaults(array(
		'directory'  => 'pagecache',
		'controller' => 'console',
		'action'     => 'index',
	));
