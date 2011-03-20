# DC Pagecache

A simple page caching for kohana.

## When to use?

* When you are serving mostly static content
* When content does not change for a long period
* When you simply want page caching and knows when to clear cache

## How to use?

Simple. For a given _URI_ (request), you will capture the output
and feed it to the `pagecache` object. The `pagecache` will then save
the output to a directory. The next time the user requests the
same _URI_, your webserver will serve the cached content without
bothering Kohana.

You will just need to configure the following:

* Publicly accessible directory where you save the cached content
* A certain URI pattern that can actually match a valid filename (ex: no space)
* A `RewriteRule` that will serve the cached content for a given _URI_
* A `controller` that will capture the output of the request and will save it to cache

## Configuration

The default cache directory is at `DOCROOT/media/pagecache` but you can change
it anytime. This is the default config at `MODPATH/pagecache/config/pagecache.php`

	return array(
		'cache_dir'		=> DOCROOT.'media/pagecache'
	);

Assuming that `DOCROOT/media` is accessible directly from the outside world.

## URI pattern

It is assumed that your _URI_ can be directly mapped to a valid filename. Ex:

* `/` will map to `/index.html`
* `/contact` will map to `/contact/index.html`
* `/about/mission` will map to `/about/mission/index.html`

Using the default config, it will map to:

* `/` will map to `/media/pagecache/index.html`
* `/contact` will map to `/media/pagecache/contact/index.html`
* `/about/mission` will map to `/media/pagecache/about/mission/index.html`

Simple isn't it?

## The Rewrite Rule

	# BEGIN Page cache

	RewriteRule ^/(.*)/$ /$1 [QSA]
	RewriteRule ^$ media/pagecache/index.html [QSA]
	RewriteRule ^([^.]+)/$ media/pagecache/$1/index.html [QSA]
	RewriteRule ^([^.]+)$ media/pagecache/$1/index.html [QSA]
	
	 # END Page cache

The Rewrite rule above will accomplish the URI pattern matching. However,
you must configure Kohana so that it will hide `index.php` from the URL
by setting `index_file` in bootstrap to `FALSE`.

The above rewrite rule will need the following rewrite rule for Kohana:

	# Protect application and system files from being viewed
	RewriteRule ^(?:application|modules|system)\b - [F,L]

	RewriteCond %{REQUEST_FILENAME} -s [OR]
	RewriteCond %{REQUEST_FILENAME} -l [OR]
	RewriteCond %{REQUEST_FILENAME} -d

	RewriteRule ^.*$ - [NC,L]
	RewriteRule ^.*$ index.php [NC,L]

If I'm not mistaken, the above rewrite rule is from either an old version of
Kohana's rewrite rule or it may came from Zend Framework style rewrite. Below is the
current rewrite sample for Kohana 3.1.2 which does not work for the Pagecache.

	# Protect application and system files from being viewed
	RewriteRule ^(?:application|modules|system)\b.* index.php/$0 [L]

	# Allow any files or directories that exist to be displayed directly
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d

	# Rewrite all other URLs to index.php/URL
	RewriteRule .* index.php/$0 [PT]

## Capturing and saving output / page

The basic usage goes like this:

	Pagecache::factory('/the/requested/uri')
		->write($the_page_output);

Currently, I used a controller to facilitate the page caching. Once a controller
extends to this controller, it will automatically trigger output to be cached.

	abstract class Controller_Cached extends Controller_Template {
		
		public function after()
		{
			parent::after();

			$uri = Arr::get($_SERVER, 'REQUEST_URI', $this->request->uri());

			Pagecache::factory($uri)
				->write($this->response->body());
		}
	}


It assumes that the controller uses a template. Once the `after()` method is called,
it will capture the response and save it to cache using the current request _URI_.

For example you have a controller `Controller_Faq`, you can serve cached page by
extending the `Controller_Cached`.

	class Controller_Faq extends Controller_Cached {
		// The rest of the code here

## Cleaning up

To clear your cached files, call `Pagecache::cleanup()`. You can schedule clearing
the cache via CRON, whatever fits your need. Note that `cleanup()` will delete
all the cached pages, which effectively refreshes your pages contents.

Currently, there is no such thing as garbage collection (as you may observe on
several caching solutions such as _WP Super Cache_. This feature may be added
in the future if need arises.

## Web Administration

In development environment, you can administer the Pagecache module by visiting
`/pagecache/console`. Currently, it has two operations:

* Test cache - to test it page caching works in your current setup
* Clear cache - clears / deletes all cached pages

It can only be accessible when `Kohana::$environment === Kohana::DEVELOPMENT`.

## Tests?

Yes, there are tests for the code Pagecache class. 