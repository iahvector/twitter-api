<?php namespace Iahvector\TwitterApi;

use Illuminate\Support\ServiceProvider;

class TwitterApiServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
	  $this->package('iahvector/twitter-api');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['twitter-api'] = $this->app->share(function($app)
		{
		  return new TwitterApi;
		});

		$this->app->booting(function()
		{
		  $loader = \Illuminate\Foundation\AliasLoader::getInstance();
		  $loader->alias('TwitterApi', 'Iahvector\TwitterApi\Facades\TwitterApiFacade');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('twitter-api');
	}

}
