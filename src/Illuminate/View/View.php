<?php namespace Illuminate\View;

use ArrayAccess;

class View implements ArrayAccess {

	/**
	 * The view enviornment instance.
	 *
	 * @var Illuminate\View\Enviornment
	 */
	protected $enviornment;

	/**
	 * The name of the view instance.
	 *
	 * @var string
	 */
	protected $view;

	/**
	 * The data bound to the view.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Create a new view instance.
	 *
	 * @param  Illuminate\View\Environment  $enviornment
	 * @param  string  $view
	 * @param  array   $data
	 * @return void
	 */
	public function __construct(Environment $enviornment, $view, array $data = array())
	{
		$this->view = $view;
		$this->data = $data;
		$this->enviornment = $enviornment;
	}

	/**
	 * Bind the given data to the view.
	 *
	 * @param  string|array  $key
	 * @param  mixed  $value
	 * @return Illuminate\View\View
	 */
	public function with($key, $value)
	{
		if (is_array($key))
		{
			$this->data = array_merge($this->data, $key);
		}
		else
		{
			$this->data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function get()
	{
		$engine = $this->environment->getEngine();

		return $engine->get($this->environment, $this->view, $data);
	}

	/**
	 * Get the name of the view instance.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->view;
	}

	/**
	 * Get all of the data bound to the view.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Determine if the given value is bound.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * Get the specified value from the view.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->data[$key];
	}

	/**
	 * Set the specified value on the view.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Unset the specified value from the view.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->data[$key]);
	}

	/**
	 * Get a piece of bound data from the view.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->data[$key];
	}

	/**
	 * Set a piece of bound data on the view.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->get();
	}

}