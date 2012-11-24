<?php namespace Illuminate\View;

use Closure;
use Illuminate\Container;
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
	 * Create a new view enviornment instance.
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
	public function make($view, array $data = array())
	{
		$path = $this->finder->find($view);

		return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
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
	 * @param  string  $view
	 * @param  Closure|string  $callback
	 * @return Closure
	 */
	public function composer($view, $callback)
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
		$container = $this->container;

		$callback = function($view) use ($class, $container)
		{
			return $container->make($class)->compose($view);
		};

		// Once we've created a function to handle each class callback we'll register
		// it with an event dispatcher so that it will be called when the composer
		// event is fired for the view, and the composers will be resolved then.
		$this->events->listen($name, $callback);

		return $callback;
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

		$this->extensions[$extension] = $engine;
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