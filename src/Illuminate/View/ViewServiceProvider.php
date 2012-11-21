<?php namespace Illuminate\View;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$app['view'] = $app->share(function($app)
		{
			return new ViewManager($app);
		});
	}

}