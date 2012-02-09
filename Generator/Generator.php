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
    protected function render($skeletonDir, $template, $parameters)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($skeletonDir), array(
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ));

        return $twig->render($template, $parameters);
    }

    protected function renderFile($skeletonDir, $template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($skeletonDir, $template, $parameters));
    }
}
