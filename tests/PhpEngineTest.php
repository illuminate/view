<?php

use Mockery as m;
use Illuminate\View\Engines\PhpEngine;

class PhpEngineTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testViewsMayBeProperlyRendered()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__, __DIR__.'/nested'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(false);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/nested/foo.php')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/nested/foo.php')->andReturn('Hello World');

		$this->assertEquals('Hello World', $engine->get($env, 'foo'));
		$this->assertEquals('foo', $engine->getLastRendered());
	}


	/**
	 * @expectedException Illuminate\View\Exception
	 */
	public function testViewExceptionsAreThrown()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__, __DIR__.'/nested'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(false);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/nested/foo.php')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/nested/foo.php')->andReturn('Hello World <?php throw new Exception("foo"); ?>');
		$engine->get($env, 'foo');
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionThrownWhenViewNotFound()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__, __DIR__.'/nested'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(false);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/nested/foo.php')->andReturn(false);
		$files->shouldReceive('get')->never();
		$engine->get($env, 'foo');
	}


	public function testNamespacedViewsCanBeFound()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/namespace/bar/baz.php')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/namespace/bar/baz.php')->andReturn('Hello World');
		$engine->addNamespace('foo', __DIR__.'/namespace');

		$this->assertEquals('Hello World', $engine->get($env, 'foo::bar.baz'));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionThrownWhenNamespaceNotRegistered()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__));

		$this->assertEquals('Hello World', $engine->get($env, 'foo::bar.baz'));
	}


	public function testBasicSectionHandling()
	{
		$files = m::mock('Illuminate\Filesystem');
		$engine = new PhpEngine($files, array(__DIR__));
		$engine->startSection('foo');
		echo 'hi';
		$engine->stopSection();
		$this->assertEquals('hi', $engine->yield('foo'));
	}


	public function testSectionExtending()
	{
		$files = m::mock('Illuminate\Filesystem');
		$engine = new PhpEngine($files, array(__DIR__));
		$engine->startSection('foo');
		echo 'hi @parent';
		$engine->stopSection();
		$engine->startSection('foo');
		echo 'there';
		$engine->stopSection();
		$this->assertEquals('hi there', $engine->yield('foo'));	
	}


	public function testYieldSectionStopsAndYields()
	{
		$files = m::mock('Illuminate\Filesystem');
		$engine = new PhpEngine($files, array(__DIR__));
		$engine->startSection('foo');
		echo 'hi';
		$this->assertEquals('hi', $engine->yieldSection());
	}


	public function testInjectStartsSectionWithContent()
	{
		$files = m::mock('Illuminate\Filesystem');
		$engine = new PhpEngine($files, array(__DIR__));
		$engine->inject('foo', 'hi');
		$this->assertEquals('hi', $engine->yield('foo'));
	}


	public function testEmptyStringIsReturnedForNonSections()
	{
		$files = m::mock('Illuminate\Filesystem');
		$engine = new PhpEngine($files, array(__DIR__));
		$this->assertEquals('', $engine->yield('foo'));
	}

}