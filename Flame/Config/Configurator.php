<?php
/**
 * Configurator.php
 *
 * @author  Jiří Šifalda <sifalda.jiri@gmail.com>
 * @package Flame
 *
 * @date    21.07.12
 */

namespace Flame\Config;

use Nette;
use Nette\Config\Extensions;
use Nette\Config\Helpers;

class Configurator extends \Nette\Config\Configurator
{

	/**
	 * @param string $containerClass
	 */
	public function __construct($containerClass = 'Flame\DI\Container')
	{
		parent::__construct();

		$this->addParameters(array(
			'container' => array(
				'class' => 'SystemContainer',
				'parent' => $containerClass
			),
			'rootDir' => '%appDir%/..',
			'wwwDir' => '%appDir%/../www'
		));
	}

	public function registerModulesExtension()
	{
		$this->registerExtension('modules', '\Flame\Config\Extensions\ModulesExtension');
	}

	public function registerDoctrineExtension()
	{
		$this->registerExtension('doctrine', '\Flame\Doctrine\Config\Extension');
	}

	/**
	 * @param $name
	 * @param $class
	 */
	public function registerExtension($name, $class)
	{
		$this->onCompile[] = function ($configurator, $compiler) use ($name, $class) {
			$compiler->addExtension($name, new $class);
		};
	}

	/**
	 * @return \Nette\Config\Compiler
	 */
	protected function createCompiler()
	{
		$compiler = new \Nette\Config\Compiler();
		$compiler->addExtension('php', new Extensions\PhpExtension)
			->addExtension('constants', new Extensions\ConstantsExtension)
			->addExtension('nette', new \Flame\Config\Extensions\NetteExtension)
			->addExtension('extensions', new Extensions\ExtensionsExtension);
		return $compiler;
	}
}
