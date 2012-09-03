<?php namespace Illuminate\View;

use Illuminate\Events\Dispatcher;

class Environment {

	/**
	 * The engine implmentation.
	 *
	 * @var Illuminate\View\EngineInterface
	 */
	protected $engine;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = array();

	/**
	 * Create a new view enviornment instance.
	 *
	 * @param  Illuminate\View\EngineInterface  $engine
	 * @return void
	 */
	public function __construct(EngineInterface $engine)
	{
		$this->engine = $engine;
	}

	/**
	 * Get a evaluated view contents for the given view.
	 *
	 * @param  string  $view
	 * @param  array   $parameters
	 * @return string
	 */
	public function make($view, array $data = array())
	{
		$data = array_merge($data, $this->shared);

		return $this->engine->get($this, $this->view, $data);
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
	 * Get the engine implementation.
	 *
	 * @return Illuminate\View\EngineInterface
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