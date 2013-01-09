<?php namespace Illuminate\View\Engines;

use Illuminate\View\Compilers\CompilerInterface;

class CompilerEngine extends PhpEngine {

	/**
	 * The Blade compiler instance.
	 *
	 * @var Illuminate\View\Compilers\CompilerInterface
	 */
	protected $compiler;

	/**
	 * The path to the last rendered view.
	 *
	 * @var string
	 */
	protected $lastViewPath;

	/**
	 * Create a new Blade view engine instance.
	 *
	 * @param  Illuminate\View\Compilers\CompilerInterface  $compiler
	 * @return void
	 */
	public function __construct(CompilerInterface $compiler)
	{
		$this->compiler = $compiler;
	}

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  Illuminate\View\Environment  $environment
	 * @param  string  $view
	 * @param  array   $data
	 * @return string
	 */
	public function get($path, array $data = array())
	{
		$this->lastViewPath = $path;

		// If this given view has expired, which means it has simply been edited since
		// it was last compiled, we will re-compile the views so we can evaluate a
		// fresh copy of the view. We'll pass the compiler the path of the view.
		if ($this->compiler->isExpired($path))
		{
			$this->compiler->compile($path);
		}

		$compiled = $this->compiler->getCompiledPath($path);

		return $this->evaluatePath($compiled, $data);
	}

	/**
	 * Handle a view exception.
	 *
	 * @param  Exception  $e
	 * @return void
	 */
	protected function handleViewException($e)
	{
		ob_get_clean();

		$message = $e->getMessage()." [Real Path: {$this->lastViewPath}]";

		throw new \Exception($message, $e->getCode(), $e);
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