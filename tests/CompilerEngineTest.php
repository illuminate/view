<?php

use Mockery as m;
use Illuminate\View\CompilerEngine;

class CompilerEngineTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testViewsMayBeRecompiledAndRendered()
	{
		$engine = $this->getEngine();
		$engine->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(true);
		$engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/foo.php')->andReturn(__DIR__.'/compiled.php');
		$engine->getCompiler()->shouldReceive('isExpired')->once()->with(__DIR__.'/foo.php')->andReturn(true);
		$engine->getCompiler()->shouldReceive('compile')->once()->with(__DIR__.'/foo.php')->andReturn('Hello World');
		$results = $engine->get(m::mock('Illuminate\View\Environment'), 'foo');

		$this->assertEquals('Hello World', $results);
	}


	public function testViewsAreNotRecompiledIfTheyAreNotExpired()
	{
		$engine = $this->getEngine();
		$engine->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(true);
		$engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/foo.php')->andReturn(__DIR__.'/compiled.php');
		$engine->getCompiler()->shouldReceive('isExpired')->once()->andReturn(false);
		$engine->getCompiler()->shouldReceive('compile')->never();
		$engine->getFilesystem()->shouldReceive('get')->once()->with(__DIR__.'/compiled.php')->andReturn('Hello World');
		$results = $engine->get(m::mock('Illuminate\View\Environment'), 'foo');

		$this->assertEquals('Hello World', $results);
	}


	protected function getEngine()
	{
		return new CompilerEngine(m::mock('Illuminate\View\Compilers\CompilerInterface'), m::mock('Illuminate\Filesystem'), array(__DIR__));
	}

}