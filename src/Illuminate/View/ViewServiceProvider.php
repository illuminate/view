<?php namespace Illuminate\View;

use Illuminate\Support\MessageBag;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\BladeEngine;
use Illuminate\View\Engines\EngineResolver;

class ViewServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$this->registerEngineResolver($app);

		$this->registerViewFinder($app);

		$this->registerEnvironment($app);
	}

	/**
	 * Register the engine resolver instance.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function registerEngineResolver($app)
	{
		$me = $this;

		$app['view.engine.resolver'] = $app->share(function($app) use ($me)
		{
			$resolver = new EngineResolver;

			// Next we will register the various engines with the resolver so that the
			// environment can resolve the engines it needs for various views based
			// on the extension of view files. We call a method for each engines.
			foreach (array('php', 'blade') as $engine)
			{
				$me->{'register'.ucfirst($engine).'Engine'}($app, $resolver);
			}

			return $resolver;
		});
	}

	/**
	 * Register the PHP engine implementation.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  Illuminate\View\Engines\EngineResolver  $resolver
	 * @return void
	 */
	public function registerPhpEngine($app, $resolver)
	{
		$resolver->register('php', function() { return new PhpEngine; });
	}

	/**
	 * Register the Blade engine implementation.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  Illuminate\View\Engines\EngineResolver  $resolver
	 * @return void
	 */
	public function registerBladeEngine($app, $resolver)
	{	
		$resolver->register('blade', function() use ($app)
		{
			$cache = $app['path'].'/storage/views';

			// The Compiler engine requires an instance of the CompilerInterface, which in
			// this case will be the Blade compiler, so we'll first create the compiler
			// instance to pass into the engine so it can compile the views properly.
			$compiler = new BladeCompiler($app['files'], $cache);

			return new CompilerEngine($compiler, $app['files']);
		});
	}

	/**
	 * Register the view finder implementation.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function registerViewFinder($app)
	{
		$app['view.finder'] = $app->share(function($app)
		{
			$paths = array($app['path'].'/views');

			return new FileViewFinder($app['files'], $paths);
		});
	}

	/**
	 * Register the view environment.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function registerEnvironment($app)
	{
		$me = $this;

		$app['view'] = $app->share(function($app) use ($me)
		{
			// Next we need to grab the engine resolver instance that will be used by the
			// environment. The resolver will be used by an environment to get each of
			// the various engine implementations such as plain PHP or Blade engine.
			$resolver = $app['view.engine.resolver'];

			$finder = $app['view.finder'];

			$environment = new Environment($resolver, $finder, $app['events']);

			// If the current session has an "errors" variable bound to it, we will share
			// its value with all view instances so the views can easily access errors
			// without having to bind. An empty bag is set when there aren't errors.
			if ($me->sessionHasErrors($app))
			{
				$errors = $app['session']->get('errors');

				$environment->share('errors', $errors);
			}

			// Putting the errors in the view for every view allows the developer to just
			// assume that some errors are always available, which is convenient since
			// they don't have to continually run checks for the presence of errors.
			else
			{
				$environment->share('errors', new MessageBag);
			}

			// We will also set the container instance on this view environment since the
			// view composers may be classes registered in the container, which allows
			// for great testable, flexible composers for the application developer.
			$environment->setContainer($app);

			$environment->share('app', $app);

			return $environment;
		});
	}

}