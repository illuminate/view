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
		$engine = m::mock('Illuminate\View\EngineInterface');
		$env = new Environment($engine);
		$env->share('baz', 'breeze');
		$engine->shouldReceive('get')->once()->with($env, 'foo.bar', array('foo' => 'bar', '__env' => $env, 'baz' => 'breeze'))->andReturn('view');
		$results = $env->make('foo.bar', array('foo' => 'bar'));

		$this->assertEquals('view', $results);
	}


	public function testAddingNamespaceCallsEngine()
	{
		$engine = m::mock('Illuminate\View\EngineInterface');
		$env = new Environment($engine);
		$engine->shouldReceive('addNamespace')->once()->with('foo', 'bar');
		$env->addNamespace('foo', 'bar');
	}

}