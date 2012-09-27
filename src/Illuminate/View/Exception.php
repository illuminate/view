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
		parent::__construct($this->formatMessage($e, $file));

		$this->file = $file;
		$this->line = $e->getLine();
	}

	/**
	 * Format the message from the exception.
	 *
	 * @param  Exception  $e
	 * @param  string     $file
	 * @return string
	 */
	protected function formatMessage(\Exception $e, $file)
	{
		return $e->getMessage().' [From view: '.realpath($file).':'.$e->getLine().']';
	}

}