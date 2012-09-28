<?php namespace Illuminate\View\Engines;

abstract class Engine {

	/**
	 * The array of named path hints.
	 *
	 * @var array
	 */
	protected $hints = array();

	/**
	 * The view that was last to be rendered.
	 *
	 * @var string
	 */
	protected $lastRendered;

	/**
	 * Get the segments of a template with a named path.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getNamespaceSegments($name)
	{
		$segments = explode('::', $name);

		if (count($segments) != 2)
		{
			throw new \InvalidArgumentException("View [$name] has an invalid name.");
		}

		if ( ! isset($this->hints[$segments[0]]))
		{
			throw new \InvalidArgumentException("No hint path defined for [{$segments[0]}].");
		}

		return $segments;
	}

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $name
	 * @param  string  $hint
	 * @return void
	 */
	public function addNamespace($namespace, $hint)
	{
		$this->hints[$namespace] = $hint;
	}

	/**
	 * Get the last view that was rendered.
	 *
	 * @var string
	 */
	public function getLastRendered()
	{
		return $this->lastRendered;
	}

}