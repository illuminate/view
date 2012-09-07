<?php

use Mockery as m;
use Illuminate\View\Compilers\BladeCompiler;

class BladeCompilerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testIsExpiredReturnsTrueIfCompiledFileDoesntExist()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/'.md5('foo'))->andReturn(false);
		$this->assertTrue($compiler->isExpired('foo'));
	}


	public function testIsExpiredReturnsTrueWhenModificationTimesWarrant()
	{
		$compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/'.md5('foo'))->andReturn(true);
		$files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
		$files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.md5('foo'))->andReturn(0);
		$this->assertTrue($compiler->isExpired('foo'));
	}


	public function testCompilePathIsProperlyCreated()
	{
		$compiler = new BladeCompiler($this->getFiles(), __DIR__);
		$this->assertEquals(__DIR__.'/'.md5('foo'), $compiler->getCompiledPath('foo'));
	}


	protected function getFiles()
	{
		return m::mock('Illuminate\Filesystem');
	}

}