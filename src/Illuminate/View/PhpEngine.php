<?php namespace Illuminate\View;

class PhpEngine extends FileBasedEngine implements EngineInterface {

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
		$path = $this->findView($view);

		return $this->evaluateContents($path, $data);
	}

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string  $path
	 * @param  array   $data
	 * @return string
	 */
	protected function evaluateContents($__path, $__data)
	{
		ob_start();

		extract($__data);

		// We'll evaluate the contents of the view inside a try/catch block so we can
		// flush out any stray output that might get out before an error occurs or
		// an exception is thrown. This prevents any partial views from leaking.
		try
		{
			eval('?>'.$this->files->get($__path));
		}
		catch (\Exception $e)
		{
			ob_get_clean(); throw $e;
		}

		return ob_get_clean();
	}

}