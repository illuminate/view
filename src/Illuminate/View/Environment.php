<?php namespace Illuminate\View;

use Closure;
use Illuminate\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Engines\SectionableInterface;

class Environment {

	/**
	 * The engine implmentation.
	 *
	 * @var Illuminate\View\Engines\EngineInterface
	 */
	protected $engine;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = array();

	/**
	 * The view composer events.
	 *
	 * @var array
	 */
	protected $composers = array();

	/**
	 * The error handler callback.
	 *
	 * @var Closure
	 */
	protected $errorHandler;

	/**
	 * The number of active rendering operations.
	 *
	 * @var int
	 */
	protected $renderCount = 0;

	/**
	 * Create a new view enviornment instance.
	 *
	 * @param  Illuminate\View\Engines\EngineInterface  $engine
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(Engines\EngineInterface $engine, Dispatcher $events)
	{
		$this->engine = $engine;

		$this->events = $events;

		$this->share('__env', $this);
	}

	/**
	 * Get a evaluated view contents for the given view.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @return Illuminate\View\View
	 */
	public function make($view, array $data = array())
	{
		return new View($this, $view, $data);
	}

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function share($key, $value)
	{
		$this->shared[$key] = $value;
	}

	/**
	 * Register a view composer event.
	 *
	 * @param  string  $view
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function composer($view, $callback)
	{
		if ($callback instanceof Closure)
		{
			$this->events->listen('composing: '.$view, $callback);
		}
		elseif (is_string($callback))
		{
			$this->addClassComposer($view, $callback);
		}
	}

	/**
	 * Register a class based view composer.
	 *
	 * @param  string  $view
	 * @param  string  $class
	 * @return void
	 */
	protected function addClassComposer($view, $class)
	{
			$name = 'composing: '.$view;

			// When registering a class based view composer, we will simply resolve the
			// class from the application IoC container then call the compose method
			// on the instance. It allows for convenient, testable view composers.
			$container = $this->container;

			$this->events->listen($name, function($view) use ($class, $container)
			{
				return $container->make($class)->compose($view);
			});
	}

	/**
	 * Call the composer for a given view.
	 *
	 * @param  Illuminate\View\View  $view
	 * @return void
	 */
	public function callComposer(View $view)
	{
		$this->events->fire('composing: '.$view->getName(), array($view));
	}

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string  $hint
	 * @return void
	 */
	public function addNamespace($namespace, $hint)
	{
		return $this->engine->addNamespace($namespace, $hint);
	}

	/**
	 * Increment the rendering counter.
	 *
	 * @return void
	 */
	public function incrementRender()
	{
		$this->renderCount++;
	}

	/**
	 * Decrement the rendering counter.
	 *
	 * @return void
	 */
	public function decrementRender()
	{
		$this->renderCount--;
	}

	/**
	 * Check if there are no active render operations.
	 *
	 * @return bool
	 */
	public function doneRendering()
	{
		return $this->renderCount == 0;
	}

	/**
	 * Determine if the engine is sectionable.
	 *
	 * @return bool
	 */
	public function isSectionable()
	{
		return $this->engine instanceof SectionableInterface;
	}

	/**
	 * Handle the given exception with the error handler.
	 *
	 * @param  Exception  $e
	 * @return void
	 */
	public function handleError(\Exception $e)
	{
		call_user_func($this->errorHandler, $e);

		die;
	}

	/**
	 * Set the error handler for the environment.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function setErrorHandler(Closure $callback)
	{
		$this->errorHandler = $callback;
	}

	/**
	 * Get the engine implementation.
	 *
	 * @return Illuminate\View\Engines\EngineInterface
	 */
	public function getEngine()
	{
		return $this->engine;
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return Illuminate\Events\Dispatcher
	 */
	public function getDispatcher()
	{
		return $this->events;
	}

	/**
	 * Get the IoC container instance.
	 *
	 * @return Illuminate\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Get all of the shared data for the environment.
	 *
	 * @return array
	 */
	public function getShared()
	{
		return $this->shared;
	}

	/**
	 * Dynamically call methods on the view engine.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (method_exists($this->engine, $method))
		{
			return call_user_func_array(array($this->engine, $method), $parameters);
		}

		throw new \BadMethodCallException("Method [$method] does not exist.");
	}

}