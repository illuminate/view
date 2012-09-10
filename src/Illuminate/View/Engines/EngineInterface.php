<?php namespace Illuminate\View\Engines;

use Illuminate\View\Environment;

interface EngineInterface {

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  Illuminate\View\Environment  $environment
	 * @param  string  $view
	 * @param  array   $data
	 * @return string
	 */
	public function get(Environment $environment, $view, array $data = array());

	/**
	 * Add a new namespace to the loader.
	 *
	 * @param  string  $namespace
	 * @param  string  $hint
	 * @return void
	 */
	public function addNamespace($namespace, $hint);

}