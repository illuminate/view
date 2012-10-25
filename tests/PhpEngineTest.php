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
		$engine = new PhpEngine($files, array(__DIR__.'/fixtures', __DIR__.'/fixtures/nested'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/basic.php')->andReturn(false);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/nested/basic.php')->andReturn(true);

		$this->assertEquals('Hello World', $engine->get($env, 'basic'));
		$this->assertEquals('basic', $engine->getLastRendered());
	}


	public function testNestedViewsMayBeProperlyRendered()
	{
		$files = m::mock('Illuminate\Filesystem');
		$events = m::mock('Illuminate\Events\Dispatcher');
		$env = new Illuminate\View\Environment(new PhpEngine($files, array(__DIR__.'/fixtures')), $events);
		$view = $env->make('nested.child');
		$view->with('sub', $sub = $env->make('basic'));
		$events->shouldReceive('fire')->once()->with('composing: basic', array($sub));
		$events->shouldReceive('fire')->once()->with('composing: nested.child', array($view));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/nested/child.php')->andReturn(true);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/basic.php')->andReturn(true);

		$this->assertEquals('Hello World Hello World', $view->render());
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionThrownWhenViewNotFound()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__.'/fixtures', __DIR__.'/fixtures/nested'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/basic.php')->andReturn(false);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/nested/basic.php')->andReturn(false);
		$engine->get($env, 'basic');
	}


	public function testNamespacedViewsCanBeFound()
	{
		$files = m::mock('Illuminate\Filesystem');
		$env = m::mock('Illuminate\View\Environment');
		$engine = new PhpEngine($files, array(__DIR__.'/fixtures'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/fixtures/namespaced/basic.php')->andReturn(true);
		$engine->addNamespace('foo', __DIR__.'/fixtures/namespaced');

		$this->assertEquals('Hello World', $engine->get($env, 'foo::basic'));
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


	public function testSectionFlushing()
	{
		$engine = new PhpEngine(m::mock('Illuminate\Filesystem'), array(__DIR__));
		$engine->startSection('foo');
		echo 'hi';
		$engine->stopSection();

		$this->assertEquals(1, count($engine->getSections()));

		$engine->flushSections();

		$this->assertEquals(0, count($engine->getSections()));
	}

}