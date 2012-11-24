<?php

use Mockery as m;
use Illuminate\View\Environment;

class EnvironmentTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMakeCreatesNewViewInstanceWithProperPathAndEngine()
	{
		$env = $this->getEnvironment();
		$env->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.php');
		$env->getEngineResolver()->shouldReceive('resolve')->once()->with('php')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));
		$env->getFinder()->shouldReceive('addExtension')->once()->with('php');
		$env->addExtension('php', 'php');
		$view = $env->make('view', array('data'));

		$this->assertTrue($engine === $view->getEngine());
	}


	public function testEnvironmentAddsExtensionWithCustomResolver()
	{
		$environment = $this->getEnvironment();

		$resolver = function(){};

		$environment->getFinder()->shouldReceive('addExtension')->once()->with('foo');
		$environment->getEngineResolver()->shouldReceive('register')->once()->with('bar', $resolver);
		$environment->getFinder()->shouldReceive('find')->once()->with('view')->andReturn('path.foo');
		$environment->getEngineResolver()->shouldReceive('resolve')->once()->with('bar')->andReturn($engine = m::mock('Illuminate\View\Engines\EngineInterface'));

		$environment->addExtension('foo', 'bar', $resolver);

		$view = $environment->make('view', array('data'));
		$this->assertTrue($engine === $view->getEngine());
	}


	public function testComposersAreProperlyRegistered()
	{
		$env = $this->getEnvironment();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$callback = $env->composer('foo', function() { return 'bar'; });

		$this->assertEquals('bar', $callback());
	}


	public function testClassCallbacks()
	{
		$env = $this->getEnvironment();
		$env->getDispatcher()->shouldReceive('listen')->once()->with('composing: foo', m::type('Closure'));
		$env->setContainer($container = m::mock('Illuminate\Container'));
		$container->shouldReceive('make')->once()->with('FooComposer')->andReturn($composer = m::mock('StdClass'));
		$composer->shouldReceive('compose')->once()->with('view')->andReturn('composed');
		$callback = $env->composer('foo', 'FooComposer');

		$this->assertEquals('composed', $callback('view'));
	}


	public function testCallComposerCallsProperEvent()
	{
		$env = $this->getEnvironment();
		$view = m::mock('Illuminate\View\View');
		$view->shouldReceive('getName')->once()->andReturn('name');
		$env->getDispatcher()->shouldReceive('fire')->once()->with('composing: name', array($view));

		$env->callComposer($view);
	}


	public function testRenderCountHandling()
	{
		$env = $this->getEnvironment();
		$env->incrementRender();
		$this->assertFalse($env->doneRendering());
		$env->decrementRender();
		$this->assertTrue($env->doneRendering());
	}


	public function testBasicSectionHandling()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi';
		$environment->stopSection();
		$this->assertEquals('hi', $environment->yield('foo'));
	}


	public function testSectionExtending()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi @parent';
		$environment->stopSection();
		$environment->startSection('foo');
		echo 'there';
		$environment->stopSection();
		$this->assertEquals('hi there', $environment->yield('foo'));	
	}


	public function testYieldSectionStopsAndYields()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi';
		$this->assertEquals('hi', $environment->yieldSection());
	}


	public function testInjectStartsSectionWithContent()
	{
		$environment = $this->getEnvironment();
		$environment->inject('foo', 'hi');
		$this->assertEquals('hi', $environment->yield('foo'));
	}


	public function testEmptyStringIsReturnedForNonSections()
	{
		$environment = $this->getEnvironment();
		$this->assertEquals('', $environment->yield('foo'));
	}


	public function testSectionFlushing()
	{
		$environment = $this->getEnvironment();
		$environment->startSection('foo');
		echo 'hi';
		$environment->stopSection();

		$this->assertEquals(1, count($environment->getSections()));

		$environment->flushSections();

		$this->assertEquals(0, count($environment->getSections()));
	}


	protected function getEnvironment()
	{
		return new Environment(
			m::mock('Illuminate\View\Engines\EngineResolver'),
			m::mock('Illuminate\View\ViewFinderInterface'),
			m::mock('Illuminate\Events\Dispatcher')
		);
	}

}