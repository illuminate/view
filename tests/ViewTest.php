<?php

use Mockery as m;
use Illuminate\View\View;

class ViewTest extends PHPUnit_Framework_TestCase {

	public function __construct()
	{
		m::close();
	}


	public function testDataCanBeSetOnView()
	{
		$view = new View(m::mock('Illuminate\View\Environment'), m::mock('Illuminate\View\Engines\EngineInterface'), 'view', 'path', array());
		$view->with('foo', 'bar');
		$view->with(array('baz' => 'boom'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'boom'), $view->getData());
	}

}