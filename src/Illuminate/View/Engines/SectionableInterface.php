<?php namespace Illuminate\View\Engines;

interface SectionableInterface {

	/**
	 * Flush all of the section contents.
	 *
	 * @return void
	 */
	public function flushSections();

}