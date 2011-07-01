<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Tests\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Symfony\Component\HttpKernel\Util\Filesystem;

class BundleGeneratorTest extends GeneratorTest
{
    public function testGenerateYaml()
    {
        $generator = new BundleGenerator($this->filesystem, __DIR__.'/../../Resources/skeleton/bundle');
        $generator->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml', false);

        $files = array(
            'FooBarBundle.php',
            'Controller/DefaultController.php',
            'Resources/views/Default/index.html.twig',
            'Resources/config/routing.yml',
            'Tests/Controller/DefaultControllerTest.php',
            'Resources/config/services.yml',
            'DependencyInjection/Configuration.php',
            'DependencyInjection/FooBarExtension.php',
        );
        foreach ($files as $file) {
            $this->assertTrue(file_exists($this->tmpDir.'/Foo/BarBundle/'.$file), sprintf('%s has been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/FooBarBundle.php');
        $this->assertContains('namespace Foo\\BarBundle', $content);

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Controller/DefaultController.php');
        $this->assertContains('public function indexAction', $content);
        $this->assertNotContains('@Route("/hello/{name}"', $content);

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Resources/views/Default/index.html.twig');
        $this->assertContains('Hello {{ name }}!', $content);
    }

    public function testGenerateAnnotation()
    {
        $generator = new BundleGenerator($this->filesystem, __DIR__.'/../../Resources/skeleton/bundle');
        $generator->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'annotation', false);

        $this->assertFalse(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/config/routing.yml'));
        $this->assertFalse(file_exists($this->tmpDir.'/Foo/BarBundle/Resources/config/routing.xml'));

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Controller/DefaultController.php');
        $this->assertContains('@Route("/hello/{name}"', $content);
    }
}
