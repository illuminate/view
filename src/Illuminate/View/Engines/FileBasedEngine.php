<?php namespace Illuminate\View\Engines;

use Illuminate\Filesystem;

abstract class FileBasedEngine extends Engine {

	/**
	 * The Filesystem instance.
	 *
	 * @var Illuminate\Filesystem;
	 */
	protected $files;

	/**
	 * The array of template paths.
	 *
	 * @var array
	 */
	protected $paths = array();

	/**
	 * The file extension for the views.
	 *
	 * @var string
	 */
	protected $extension;

	/**
	 * Create a new PHP engine instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @param  array  $paths
	 * @return void
	 */
	public function __construct(Filesystem $files, array $paths, $extension = '.php')
	{
		$this->files = $files;
		$this->paths = $paths;
		$this->extension = $extension;
	}

	/**
	 * Get the full path to a template.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function findView($name)
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
	 * Find the given view in the list of paths.
	 *
	 * @param  string  $name
	 * @param  array   $paths
	 * @return string
	 */
	protected function findInPaths($name, $paths)
	{
		$fullName = $this->formatViewName($name);

		foreach ((array) $paths as $path)
		{
			if ($this->files->exists($full = $path.'/'.$fullName))
			{
				return $full;
			}
		}

		$this->notFound($name);
	}

	/**
	 * Format the given view name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function formatViewName($name)
	{
		return str_replace('.', '/', $name).$this->extension;
	}

	/**
	 * Throw a not fond exception for the given view.
	 *
	 * @param  string  $name
	 * @return void
	 */
	protected function notFound($name)
	{
		throw new \InvalidArgumentException("Unable to find view [$name].");
	}

	/**
	 * Add a new path to the loader.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function addPath($path)
	{
		$this->paths[] = $path;
	}

	/**
	 * Set the extension for the views.
	 *
	 * @param  string  $extension
	 * @return void
	 */
	public function setExtension($extension)
	{
		$this->extension = $extension;
	}

	/**
	 * Get the Filesystem instance.
	 *
	 * @return Illuminate\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

}