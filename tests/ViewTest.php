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
		$view = new View(m::mock('Illuminate\View\Environment'), 'view', array());
		$view->with('foo', 'bar');
		$view->with(array('baz' => 'boom'));
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'boom'), $view->getData());
	}


	public function testRenderCallsEnvironmentProperly()
	{
		$view = new View($env = m::mock('Illuminate\View\Environment'), 'view', array('foo' => 'bar'));
		$env->shouldReceive('callComposer')->once()->with($view);
		$env->shouldReceive('getShared')->once()->andReturn(array('baz' => 'boom'));
		$env->shouldReceive('get')->once()->with($env, 'view', array('foo' => 'bar', 'baz' => 'boom'))->andReturn('foo');
		$this->assertEquals('foo', $view->render());
	}


	public function testExceptionsInViewsCallErrorHandler()
	{
		$view = new View($env = m::mock('Illuminate\View\Environment'), 'view', array('foo' => 'bar'));
		$env->shouldReceive('callComposer')->once()->with($view);
		$env->shouldReceive('getShared')->once()->andReturn(array('baz' => 'boom'));
		$e = new Exception('foo');
		$env->shouldReceive('get')->once()->andReturnUsing(function() use ($e) { throw $e; });
		$env->shouldReceive('handleError')->once()->with($e);
		$view->__toString();
	}

}