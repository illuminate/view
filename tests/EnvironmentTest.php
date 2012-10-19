<?php

use Mockery as m;
use Illuminate\View\Environment;

class EnvironmentTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testEnvironmentCorrectlyCallsEngine()
	{
		$engine = m::mock('Illuminate\View\Engines\EngineInterface');
		$events = m::mock('Illuminate\Events\Dispatcher');
		$env = new Environment($engine, $events);
		$env->share('baz', 'breeze');
		$engine->shouldReceive('get')->once()->with($env, 'foo.bar', array('foo' => 'bar', '__env' => $env, 'baz' => 'breeze'))->andReturn('view');
		$view = $env->make('foo.bar', array('foo' => 'bar'));
		$events->shouldReceive('fire')->once()->with('composing: foo.bar', array($view));
		$results = $view->render();

		$this->assertEquals('view', $results);
	}


	public function testAddingNamespaceCallsEngine()
	{
		$engine = m::mock('Illuminate\View\Engines\EngineInterface');
		$events = m::mock('Illuminate\Events\Dispatcher');
		$env = new Environment($engine, $events);
		$engine->shouldReceive('addNamespace')->once()->with('foo', 'bar');
		$env->addNamespace('foo', 'bar');
	}

}