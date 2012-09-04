<?php namespace Illuminate\View;

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
	 * Add a new named path to the loader.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return void
	 */
	public function addNamedPath($name, $path);

}