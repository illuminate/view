<?php namespace Illuminate\View\Engines;

use Illuminate\Filesystem;
use Illuminate\View\Environment;
use Illuminate\View\Compilers\CompilerInterface;

class CompilerEngine extends PhpEngine {

	/**
	 * The Blade compiler instance.
	 *
	 * @var Illuminate\View\Compilers\CompilerInterface
	 */
	protected $compiler;

	/**
	 * Create a new Blade view engine instance.
	 *
	 * @param  Illuminate\View\Compilers\CompilerInterface  $compiler
	 * @param  Illuminate\Filesystem  $files
	 * @param  array   $paths
	 * @param  string  $extension
	 * @return void
	 */
	public function __construct(CompilerInterface $compiler, Filesystem $files, array $paths, $extension = '.php')
	{
		$this->compiler = $compiler;

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
		$this->lastRendered = $view;

		$path = $this->findView($view);

		// If this given view has expired, which means it has simply been edited since
		// it was last compiled, we will re-compile the views so we can evaluate a
		// fresh copy of the view. We'll pass the compiler the path of the view.
		if ($this->compiler->isExpired($path))
		{
			$contents = $this->compiler->compile($path);

			return $this->evaluateContents($contents, $data, $path);
		}
		else
		{
			$compiled = $this->compiler->getCompiledPath($path);

			return $this->evaluatePath($compiled, $data);
		}
	}

	/**
	 * Get the compiler implementation.
	 *
	 * @return Illuminate\View\Compilers\CompilerInterface
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}

}