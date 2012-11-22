<?php namespace Illuminate\View;

use Illuminate\Filesystem;

class ViewFinder {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The array of active view paths.
	 *
	 * @var array
	 */
	protected $paths;

	/**
	 * The namespace to file path hints.
	 *
	 * @var array
	 */
	protected $hints = array();

	/**
	 * Create a new file view loader instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @param  array  $paths
	 * @return void
	 */
	public function __construct(Filesystem $files, array $paths)
	{
		$this->files = $files;
		$this->paths = $paths;
	}

	/**
	 * Get the full path to a view.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function find($name)
	{
		if (strpos($name, '::') !== false) return $this->findNamedPathView($name);

		return $this->findInPaths($name, $this->paths);
	}

	/**
	 * Get the path to a template with a named path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function findNamedPathView($name)
	{
		list($namespace, $view) = $this->getNamespaceSegments($name);

		return $this->findInPaths($view, $this->hints[$namespace]);
	}

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
	 * Find the given view in the list of paths.
	 *
	 * @param  string  $name
	 * @param  array   $paths
	 * @return string
	 */
	protected function findInPaths($name, $paths)
	{
		foreach ((array) $paths as $path)
		{
			foreach ($this->getPossibleViewFiles($name) as $viewPath)
			{
				if ($this->files->exists($viewPath)) return $viewPath;
			}
		}

		throw new \InvalidArgumentException("View [$name] not found.");
	}

	/**
	 * Get an array of fully formatted possible view files.
	 *
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getPossibleViewFiles($name)
	{
		return array_map(function($extension) use ($name)
		{
			return str_replace('.', '/', $name).'.'.$extension;

		}, array_keys($this->extensions));
	}

	/**
	 * Add a path to the finder.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function addPath($path)
	{
		$this->paths[] = $path;
	}

	/**
	 * Add a namespace hint to the finder.
	 *
	 * @param  string  $namespace
	 * @param  string  $hint
	 * @return void
	 */
	public function addNamespace($namespace, $hint)
	{
		$this->hints[$namespace] = $hint;
	}

}