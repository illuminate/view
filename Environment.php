<?php namespace Illuminate\View;

use Closure;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\View\Engines\EngineResolver;

class Environment {

	/**
	 * The engine implementation.
	 *
	 * @var Illuminate\View\Engines\EngineResolver
	 */
	protected $engines;

	/**
	 * The view finder implementation.
	 *
	 * @var Illuminate\View\ViewFinderInterface
	 */
	protected $finder;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $events;

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
	 * The extension to engine bindings.
	 *
	 * @var array
	 */
	protected $extensions = array('blade.php' => 'blade', 'php' => 'php');

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
	 * Create a new view environment instance.
	 *
	 * @param  Illuminate\View\Engines\EngineResolver  $engines
	 * @param  Illuminate\View\ViewFinderInterface  $finder
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
	{
		$this->finder = $finder;
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
	public function make($view, $data = array())
	{
		if (is_object($data))
		{
			$data = get_object_vars($data);
		}
		
		$path = $this->finder->find($view);

		return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
	}

	/**
	 * Determine if a given view exists.
	 *
	 * @param  string  $view
	 * @return bool
	 */
	public function exists($view)
	{
		try
		{
			$this->finder->find($view);
		}
		catch (\InvalidArgumentException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the rendered contents of a partial from a loop.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @param  string  $iterator
	 * @param  string  $empty
	 * @return string
	 */
	public function renderEach($view, $data, $iterator, $empty = 'raw|')
	{
		$result = '';

		// If is actually data in the array, we will loop through the data and append
		// an instance of the partial view to the final result HTML passing in the
		// iterated value of this data array, allowing the views to access them.
		if (count($data) > 0)
		{
			foreach ($data as $key => $value)
			{
				$data = array('key' => $key, $iterator => $value);

				$result .= $this->make($view, $data)->render();
			}
		}

		// If there is no data in the array, we will render the contents of the empty
		// view. Alternatively, the "empty view" could be a raw string that begins
		// with "raw|" for convenience and to let this know that it is a string.
		else
		{
			if (starts_with($empty, 'raw|'))
			{
				$result = substr($empty, 4);
			}
			else
			{
				$result = $this->make($empty)->render();
			}
		}

		return $result;
	}

	/**
	 * Get the appropriate view engine for the given path.
	 *
	 * @param  string  $path
	 * @return Illuminate\View\Engines\EngineInterface
	 */
	protected function getEngineFromPath($path)
	{
		$engine = $this->extensions[$this->getExtension($path)];

		return $this->engines->resolve($engine);
	}

	/**
	 * Get the extension used by the view file.
	 *
	 * @param  string  $path
	 * @return string
	 */
	protected function getExtension($path)
	{
		$extensions = array_keys($this->extensions);

		return array_first($extensions, function($key, $value) use ($path)
		{
			return ends_with($path, $value);
		});
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
	 * @param  array|string  $views
	 * @param  Closure|string  $callback
	 * @return Closure
	 */
	public function composer($views, $callback)
	{
		$composers = array();

		foreach ((array) $views as $view)
		{
			$composers[] = $this->addComposer($view, $callback);
		}

		return $composers;
	}

	/**
	 * Add a composer for a given view.
	 *
	 * @param  string  $view
	 * @param  Closure|string  $callback
	 * @return Closure
	 */
	protected function addComposer($view, $callback)
	{
		if ($callback instanceof Closure)
		{
			$this->events->listen('composing: '.$view, $callback);

			return $callback;
		}
		elseif (is_string($callback))
		{
			return $this->addClassComposer($view, $callback);
		}
	}

	/**
	 * Register a class based view composer.
	 *
	 * @param  string   $view
	 * @param  string   $class
	 * @return Closure
	 */
	protected function addClassComposer($view, $class)
	{
		$name = 'composing: '.$view;

		// When registering a class based view "composer", we will simply resolve the
		// classes from the application IoC container then call the compose method
		// on the instance. This allows for convenient, testable view composers.
		$callback = $this->buildClassComposerCallback($class);

		$this->events->listen($name, $callback);

		return $callback;
	}

	/**
	 * Build a class based container callback Closure.
	 *
	 * @param  string   $class
	 * @return Closure
	 */
	protected function buildClassComposerCallback($class)
	{
		$container = $this->container;

		list($class, $method) = $this->parseClassComposer($class);

		// Once we have the class and method name, we can build the Closure to resolve
		// the instance out of the IoC container and call the method on it with the
		// given arguments that are passed to the Closure as the composer's data.
		return function() use ($class, $method, $container)
		{
			$callable = array($container->make($class), $method);

			return call_user_func_array($callable, func_get_args());
		};
	}

	/**
	 * Parse a class based composer name.
	 *
	 * @param  string  $class
	 * @return array
	 */
	protected function parseClassComposer($class)
	{
		return str_contains($class, '@') ? explode('@', $class) : array($class, 'compose');
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
		return $this->yieldContent($this->stopSection());
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
	public function yieldContent($section)
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
	 * Add a location to the array of view locations.
	 *
	 * @param  string  $location
	 * @return void
	 */
	public function addLocation($location)
	{
		$this->finder->addLocation($location);
	}

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string|array  $hints
	 * @return void
	 */
	public function addNamespace($namespace, $hints)
	{
		$this->finder->addNamespace($namespace, $hints);
	}

	/**
	 * Register a valid view extension and its engine.
	 *
	 * @param  string   $extension
	 * @param  string   $engine
	 * @param  Closure  $resolver
	 * @return void
	 */
	public function addExtension($extension, $engine, $resolver = null)
	{
		$this->finder->addExtension($extension);

		if (isset($resolver))
		{
			$this->engines->register($engine, $resolver);
		}

		unset($this->extensions[$engine]);

		$this->extensions = array_merge(array($extension => $engine), $this->extensions);
	}

	/**
	 * Get the extension to engine bindings.
	 *
	 * @return array
	 */
	public function getExtensions()
	{
		return $this->extensions;
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
	 * Get the view finder instance.
	 *
	 * @return Illuminate\View\ViewFinder
	 */
	public function getFinder()
	{
		return $this->finder;
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
