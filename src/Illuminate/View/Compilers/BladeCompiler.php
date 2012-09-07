<?php namespace Illuminate\View\Compilers;

use Illuminate\Filesystem;

class BladeCompiler implements CompilerInterface {

	/**
	 * The Filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Get the cache path for the compiled views.
	 *
	 * @var string
	 */
	protected $cachePath;

	/**
	 * Create a new Blade compiler instance.
	 *
	 * @param  string  $cachePath
	 * @return void
	 */
	public function __construct(Filesystem $files, $cachePath)
	{
		$this->files = $files;
		$this->cachePath = $cachePath;
	}

	/**
	 * Get the path to the compiled version of a view.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function getCompiledPath($path)
	{
		return $this->cachePath.'/'.md5($path);
	}

	/**
	 * Determine if the view at the given path is expired.
	 *
	 * @param  string  $path
	 * @param  string  $compiled
	 * @return bool
	 */
	public function isExpired($path, $compiled)
	{
		if ( ! $this->files->exists($compiled))
		{
			return true;
		}

		$lastModified = $this->files->lastModified($path);

		return $lastModified >= $this->files->lastModified($compiled);
	}

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path)
	{
		$contents = $this->files->get($path);

		$this->files->put($this->getCompiledPath($path), $contents);
	}

}