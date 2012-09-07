<?php namespace Illuminate\View\Compilers;

interface CompilerInterface {

	/**
	 * Get the path to the compiled version of a view.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function getCompiledPath($path);

	/**
	 * Determine if the given view is expired.
	 *
	 * @param  string  $path
	 * @param  string  $compiled
	 * @return bool
	 */
	public function isExpired($path, $compiled);

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function compile($path);

}