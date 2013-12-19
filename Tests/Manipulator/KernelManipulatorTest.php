<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Tests\Manipulator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\ClassLoader\UniversalClassLoader;

use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;

class KernelManipulatorTest extends \PHPUnit_Framework_TestCase
{
    const STUB_BUNDLE_CLASS_NAME = 'Sensio\\Bundle\\GeneratorBundle\\Tests\\Manipulator\\Stubs\\StubBundle';
    const STUB_NAMESPACE         = 'KernelManipulatorTest\\Stubs';

    /** @var Filesystem */
    protected static $filesystem;
    protected static $tmpDir;

    public static function setUpBeforeClass()
    {
        self::$tmpDir     = sys_get_temp_dir() . '/sf2';
        self::$filesystem = new Filesystem();
        self::$filesystem->remove(self::$tmpDir);
    }

    public static function tearDownAfterClass()
    {
        self::$filesystem->remove(self::$tmpDir);
    }

    /**
     * @dataProvider kernelStubFilenamesProvider
     *
     * @param string $kernelOriginFilePath
     */
    public function testAddToArray($kernelOriginFilePath)
    {
        $params = $this->prepareTestKernel($kernelOriginFilePath);

        $this->registerClassLoader(self::$tmpDir);

        list($kernelClassName, $fullpath) = $params;
        $kernelClassName = '\\' . self::STUB_NAMESPACE . '\\' . $kernelClassName;
        $kernel          = new  $kernelClassName('test', true);
        $manipulator     = new KernelManipulator($kernel);
        $manipulator->addBundle(self::STUB_BUNDLE_CLASS_NAME);

        $phpFinder     = new PhpExecutableFinder();
        $phpExecutable = $phpFinder->find();

        $this->assertNotSame(false, $phpExecutable, 'Php executable binary found');

        $pb      = new ProcessBuilder();
        $process = $pb->add($phpExecutable)->add('-l')->add($fullpath)->getProcess();
        $process->run();

        $result = strpos($process->getOutput(), 'No syntax errors detected');
        $this->assertNotSame(false, $result, 'Manipulator should not provoke syntax errors');
    }

    /**
     * @return array
     */
    public function kernelStubFilenamesProvider()
    {
        return array(
            'With empty bundles array'               => array(__DIR__ . '/Stubs/EmptyBundlesKernelStub.php'),
            'With empty multiline bundles array'     => array(__DIR__ . '/Stubs/EmptyBundlesMultilineKernelStub.php'),
            'With bundles array contains comma'      => array(__DIR__ . '/Stubs/ContainsCommaKernelStub.php'),
            'With bundles added w/o trailing comma'  => array(__DIR__ . '/Stubs/ContainsBundlesKernelStub.php'),
            'With some extra code and bad formatted' => array(__DIR__ . '/Stubs/ContainsExtraCodeKernelStub.php')
        );
    }

    /**
     * Copy stub file to tmp
     *
     * @param string $kernelOriginFilePath
     *
     * @return array
     */
    protected function prepareTestKernel($kernelOriginFilePath)
    {
        $pathInfo  = pathinfo($kernelOriginFilePath);
        $fileName  = $pathInfo['basename'];
        $className = $pathInfo['filename'];

        $targetDir = self::$tmpDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, self::STUB_NAMESPACE);
        self::$filesystem->mkdir($targetDir);

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        self::$filesystem->copy($kernelOriginFilePath, $targetPath, true);

        return array($className, $targetPath);
    }

    /**
     * Registers the stubs namespace in the autoloader.
     *
     * @param string $cacheDir
     */
    protected function registerClassLoader($cacheDir)
    {
        $loader = new UniversalClassLoader();
        $loader->registerNamespace(self::STUB_NAMESPACE, $cacheDir);
        $loader->register();
    }
}
