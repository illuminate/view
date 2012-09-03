<?php namespace Illuminate\View;

use Twig_Environment;
use Twig_LoaderInterface;
use Illuminate\Filesystem;

class TwigEngine extends FileBasedEngine implements EngineInterface, Twig_LoaderInterface {

	/**
	 * The Twig environment instance.
	 *
	 * @var Twig_Environment
	 */
	protected $twig;

	/**
	 * Create a new Twig engine instance.
	 *
	 * @param  Twig_Environment  $twig
	 * @param  Illuminate\Filesystem  $files
	 * @param  array   $paths
	 * @param  string  $extension
	 * @return void
	 */
	public function __construct(Twig_Environment $twig, Filesystem $files, array $paths, $extension = '.twig.html')
	{
		$this->twig = $twig;

		parent::__construct($files, $paths, $extension);
	}

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  Illuminate\View\Environment  $environment
	 * @param  string  $view
	 * @param  array   $data
	 * @return string
	 */
	public function get(Environment $environment, $view, array $data = array())
	{
		return $this->twig->render($view, $data);
	}

	/**
	 * Get the source of the given template.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function getSource($name)
	{
		return $this->files->get($this->findView($name));
	}

	/**
	 * Get the cache key for the given template.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function getCacheKey($name)
	{
		return $this->findView($name);
	}

	/**
	 * Determine if the given template is fresh.
	 *
	 * @param  string  $name
	 * @param  int     $time
	 * @return bool
	 */
	public function isFresh($name, $time)
	{
		return $this->files->lastModified($this->findView($name)) <= $time;
	}

	/**
	 * Get the Twig environment instance.
	 *
	 * @return Twig_Environment
	 */
	public function getTwig()
	{
		return $this->twig;
	}

}