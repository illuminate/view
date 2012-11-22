<?php namespace Illuminate\View;

use Closure;
use Illuminate\Container;
use Illuminate\Filesystem;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Engines\EngineResolver;

class Environment {

	/**
	 * The engine implmentation.
	 *
	 * @var Illuminate\View\Engines\EngineResolver
	 */
	protected $engines;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * All of the active view paths.
	 *
	 * @var array
	 */
	protected $paths = array();

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = array();

	/**
	 * All of the namesapce hint paths.
	 *
	 * @var array
	 */
	protected $hints = array();

	/**
	 * The view composer events.
	 *
	 * @var array
	 */
	protected $composers = array();

	/**
	 * All of the finished, captured sections.
	 *
	 * @var array
	 */
	protected $sections = array();

	/**
	 * The stack of in-progress sections.
	 *
	 * @var array
	 */
	protected $sectionStack = array();

	/**
	 * The number of active rendering operations.
	 *
	 * @var int
	 */
	protected $renderCount = 0;

	/**
	 * Create a new view enviornment instance.
	 *
	 * @param  Illuminate\View\Engines\EngineResolver  $engines
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @param  Illuminate\Filesystem  $files
	 * @param  array  $paths
	 * @return void
	 */
	public function __construct(EngineResolver $engines, Dispatcher $events, Filesystem $files, array $paths)
	{
		$this->files = $files;
		$this->paths = $paths;
		$this->events = $events;
		$this->engines = $engines;

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
		$path = $this->findView($view);

		return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
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
	 * Get the appropriate view engine for the given path.
	 *
	 * @param  string  $path
	 * @return Illuminate\View\Engines\EngineInterface
	 */
	protected function getEngineFromPath($path)
	{
		$engine = $this->extensions[$this->files->extension($path)];

		return $this->engines->resolve($engine);
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

		// When registering a class based view "composer", we will simply resolve the
		// classes from the application IoC container then call the compose method
		// on the instance. This allows for convenient, testable view composers.
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
	 * Start injecting content into a section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	public function startSection($section, $content = '')
	{
		if ($content === '')
		{
			ob_start() and $this->sectionStack[] = $section;
		}
		else
		{
			$this->extendSection($section, $content);
		}
	}

	/**
	 * Inject inline content into a section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	public function inject($section, $content)
	{
		return $this->startSection($section, $content);
	}

	/**
	 * Stop injecting content into a section and return its contents.
	 *
	 * @return string
	 */
	public function yieldSection()
	{
		return $this->yield($this->stopSection());
	}

	/**
	 * Stop injecting content into a section.
	 *
	 * @return string
	 */
	public function stopSection()
	{
		$last = array_pop($this->sectionStack);

		$this->extendSection($last, ob_get_clean());

		return $last;
	}

	/**
	 * Append content to a given section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	protected function extendSection($section, $content)
	{
		if (isset($this->sections[$section]))
		{
			$content = str_replace('@parent', $content, $this->sections[$section]);

			$this->sections[$section] = $content;
		}
		else
		{
			$this->sections[$section] = $content;
		}
	}

	/**
	 * Get the string contents of a section.
	 *
	 * @param  string  $section
	 * @return string
	 */
	public function yield($section)
	{
		return isset($this->sections[$section]) ? $this->sections[$section] : '';
	}

	/**
	 * Flush all of the section contents.
	 *
	 * @return void
	 */
	public function flushSections()
	{
		$this->sections = array();

		$this->sectionStack = array();
	}

	/**
	 * Add a path to the array of view paths.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function addPath($path)
	{
		$this->paths[] = $path;
	}

	/**
	 * Register a valid view extension and its engine.
	 *
	 * @param  string  $extension
	 * @param  string  $engine
	 * @return void
	 */
	public function addExtension($extension, $engine)
	{
		$this->extensions[$extension] = $engine;
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
		$this->hints[$namespace] = $hint;
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
	 * Get the engine resolver instance.
	 *
	 * @return Illuminate\View\Engines\EngineResolver
	 */
	public function getEngineResolver()
	{
		return $this->engines;
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
	 * Get the entire array of sections.
	 *
	 * @return array
	 */
	public function getSections()
	{
		return $this->sections;
	}

}