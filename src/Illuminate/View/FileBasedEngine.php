<?php namespace Illuminate\View;

use Illuminate\Filesystem;

abstract class FileBasedEngine {

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
	 * The array of named path hints.
	 *
	 * @var array
	 */
	protected $hints = array();

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
		if (strpos($name, '::') !== false)
		{
			return $this->findNamedPathView($name);
		}

		// First we will format the template name to swap dots to slashes and add in
		// the Twig extension. Then we will simply iterate through each path that
		// is registerd with this loader, returning the first path we can find.
		$name = $this->formatViewName($name);

		foreach ($this->paths as $path)
		{
			if ($this->files->exists($full = $path.'/'.$name))
			{
				return $full;
			}
		}

		throw new \InvalidArgumentException("Unable to find view [$name].");
	}

	/**
	 * Get the path to a template with a named path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function findNamedPathView($name)
	{
		list($hint, $name) = $this->getNamedPathSegments($name);

		// For named path templates, the first segment is the hint name and the second
		// is the template name. We will get the segments then format the name like
		// usual. Then we'll simply check the named path's hint for the template.
		$name = $this->formatViewName($name);

		if ($this->files->exists($full = $this->hints[$hint].'/'.$name))
		{
			return $full;
		}

		throw new \InvalidArgumentException("Unable to find view [$name].");	
	}

	/**
	 * Get the segments of a template with a named path.
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getNamedPathSegments($name)
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
	 * Format the given template name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function formatTemplateName($name)
	{
		return str_replace('.', '/', $name).$this->extension;
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
	 * Add a new named path to the loader.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return void
	 */
	public function addNamedPath($name, $path)
	{
		$this->hints[$name] = $path;
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

}