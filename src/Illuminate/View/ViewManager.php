<?php namespace Illuminate\View;

use Illuminate\Support\Manager;
use Illuminate\Support\MessageBag;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\TwigEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;

class ViewManager extends Manager {

	/**
	 * Create a new driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 */
	protected function createDriver($driver)
	{
		$driver = parent::createDriver($driver);

		// If the current session has an "errors" variable bound to it, we will share
		// its value with all view instances so the views can easily access errors
		// without having to bind. An empty bag is set when there aren't errors.
		if ($this->sessionHasErrors())
		{
			$errors = $this->app['session']->get('errors');

			$driver->share('errors', $errors);
		}

		// Putting the errors in the view for every view allows the developer to just
		// assume that some errors are always available, which is convenient since
		// they don't have to continually run checks for the presence of errors.
		else
		{
			$driver->share('errors', new MessageBag);
		}

		$driver->setErrorHandler($this->app['exception.function']);

		// We will also set the container instance on this view environment since the
		// view composers may be classes registered in the container, which allows
		// for great testable, flexible composers for the application developer.
		$driver->setContainer($this->app);

		$driver->share('app', $this->app);

		return $driver;
	}

	/**
	 * Create an instance of the PHP view driver.
	 *
	 * @return Illuminate\View\Environment
	 */
	protected function createPhpDriver()
	{
		$engine = new PhpEngine($this->app['files'], $this->getPaths());

		return new Environment($engine, $this->app['events']);
	}

	/**
	 * Create an instance of the Blade view driver.
	 *
	 * @return Illuminate\View\Environment
	 */
	protected function createBladeDriver()
	{
		$files = $this->app['files'];

		// The Compiler engine requires an instance of the CompilerInterface, which in
		// this case will be the Blade compiler, so we'll first create the compiler
		// instance to pass into the engine so it can compile the views properly.
		$compiler = new BladeCompiler($files, $this->getCachePath());

		$paths = $this->getPaths();

		$engine = new CompilerEngine($compiler, $files, $paths, '.blade.php');

		return new Environment($engine, $this->app['events']);
	}

	/**
	 * Determine if the application session has errors.
	 *
	 * @return bool
	 */
	public function sessionHasErrors()
	{
		return isset($this->app['session']) and $this->app['session']->has('errors');
	}

	/**
	 * Get the view location paths.
	 *
	 * @return array
	 */
	protected function getPaths()
	{
		return $this->app['config']['view.paths'];
	}

	/**
	 * Get the view cache path.
	 *
	 * @return string
	 */
	protected function getCachePath()
	{
		return $this->app['path'].'/storage/views';
	}

	/**
	 * Get the default cache driver name.
	 *
	 * @return string
	 */
	protected function getDefaultDriver()
	{
		return $this->app['config']['view.driver'];
	}

}