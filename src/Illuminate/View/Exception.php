<?php namespace Illuminate\View;

class Exception extends \RuntimeException {

	/**
	 * Create a new View exception instance.
	 *
	 * @param  Exception $e
	 * @param  string    $file
	 * @return void
	 */
	public function __construct(\Exception $e, $file)
	{
		parent::__construct($e->getMessage().' [From view: '.realpath($file).']');

		$this->file = $file;
		$this->line = $e->getLine();
	}

}