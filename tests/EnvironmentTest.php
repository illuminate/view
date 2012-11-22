<?php

use Mockery as m;
use Illuminate\View\Environment;

class EnvironmentTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
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
			m::mock('Illuminate\Events\Dispatcher'),
			m::mock('Illuminate\Filesystem'),
			array(__DIR__)
		);
	}

}