<?php namespace Illuminate\View;

use ArrayAccess;
use Illuminate\Support\RenderableInterface as Renderable;
use Illuminate\View\Engines\SectionableInterface as Sectionable;

class View implements ArrayAccess, Renderable {

	/**
	 * The environment instance.
	 *
	 * @var Illuminate\View\Environment
	 */
	protected $environment;

	/**
	 * The name of the view.
	 *
	 * @var string
	 */
	protected $view;

	/**
	 * The array of view data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Create a new view instance.
	 *
	 * @param  Illuminate\View\Environment  $environment
	 * @param  string  $view
	 * @param  array   $data
	 * @return void
	 */
	public function __construct(Environment $environment, $view, array $data = array())
	{
		$this->view = $view;
		$this->data = $data;
		$this->environment = $environment;
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function render()
	{
		$env = $this->environment;

		// We will keep track of the amount of views being rendered so we can flush
		// the section after the complete rendering operation is done. This will
		// clear out the sections for any separate views that may be rendered.
		$env->incrementRender();

		$env->callComposer($this);

		$contents = $this->getContents();

		$env->decrementRender();

		// Once we've finished rendering the view, we'll decrement the render count
		// then if we are at the bottom of the stack we'll flush out sections as
		// they might interfere with totally separate view's evaluations later.
		if ($env->doneRendering() and $env->isSectionable())
		{
			$env->getEngine()->flushSections();
		}

		return $contents;
	}

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @return string
	 */
	protected function getContents()
	{
		$data = array_merge($this->environment->getShared(), $this->data);

		return $this->environment->get($this->environment, $this->view, $data);
	}

	/**
	 * Add a piece of data to the view.
	 *
	 * @param  string|array  $key
	 * @param  mixed   $value
	 * @return Illuminate\View\View
	 */
	public function with($key, $value = null)
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
	 * Get the name of the view.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->view;
	}

	/**
	 * Get the array of view data.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Determine if a piece of data is bound.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * Get a piece of bound data to the view.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->data[$key];
	}

	/**
	 * Set a piece of data on the view.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->with($key, $value);
	}

	/**
	 * Unset a piece of data from the view.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->data[$key]);
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			$this->environment->handleError($e);
		}
	}

}