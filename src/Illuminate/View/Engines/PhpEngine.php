<?php namespace Illuminate\View\Engines;

use Illuminate\View\Exception;
use Illuminate\View\Environment;

class PhpEngine extends FileBasedEngine implements EngineInterface, SectionableInterface {

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

		return $this->evaluatePath($this->findView($view), $data);
	}

	/**
	 * Get the evaluated contents of the view at the given path.
	 *
	 * @param  string  $path
	 * @param  array   $data
	 * @return string
	 */
	protected function evaluatePath($__path, $__data)
	{
		ob_start();

		extract($__data);

		// We'll evaluate the contents of the view inside a try/catch block so we can
		// flush out any stray output that might get out before an error occurs or
		// an exception is thrown. This prevents any partial views from leaking.
		try
		{
			include $__path;
		}
		catch (\Exception $e)
		{
			ob_get_clean(); throw $e;
		}

		return ob_get_clean();
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
	 * Get the entire array of sections.
	 *
	 * @return array
	 */
	public function getSections()
	{
		return $this->sections;
	}

}