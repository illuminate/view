<?php namespace Illuminate\View;

use Closure;
use Illuminate\Events\Dispatcher;

class Environment {

	/**
	 * The engine implmentation.
	 *
	 * @var Illuminate\View\Engines\EngineInterface
	 */
	protected $engine;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = array();

	/**
	 * The error handler callback.
	 *
	 * @var Closure
	 */
	protected $errorHandler;

	/**
	 * Create a new view enviornment instance.
	 *
	 * @param  Illuminate\View\Engines\EngineInterface  $engine
	 * @return void
	 */
	public function __construct(Engines\EngineInterface $engine)
	{
		$this->engine = $engine;

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