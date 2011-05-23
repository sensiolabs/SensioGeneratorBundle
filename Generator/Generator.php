<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Generator;

/**
 * Generator is the base class for all generators.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Generator
{
    private $twig;
    private $stringLoader;
    private $fileLoader;

    public function __construct()
    {
        $this->fileLoader = new \Twig_Loader_Filesystem('/');
        $this->stringLoader = new \Twig_Loader_String();
        $this->twig = new \Twig_Environment($this->fileLoader, array(
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ));
    }

    /**
     * Renders a string.
     *
     * @param string $string     The string to render
     * @param array  $parameters The parameters
     *
     * @return string The rendered string
     */
    public function renderString($string, array $parameters)
    {
        $this->twig->setLoader($this->stringLoader);

        $ret = $this->twig->render($string, $parameters);

        $this->twig->setLoader($this->fileLoader);

        return $ret;
    }

    /**
     * Renders a file in-place.
     *
     * @param string $file       The template filename to render
     * @param array  $parameters The parameters
     */
    public function renderFile($file, array $parameters)
    {
        file_put_contents($file, $this->twig->render($file, $parameters));
    }

    /**
     * Renders a directory recursively
     *
     * @param string $dir Path to the directory that will be recursively rendered
     * @param array $parameters
     */
    public function renderDir($dir, array $parameters)
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if ($file->isFile()) {
                $this->renderFile((string) $file, $parameters);
            }
        }
    }
}
