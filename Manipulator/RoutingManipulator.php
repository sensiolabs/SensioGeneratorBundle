<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Manipulator;

/**
 * Changes the PHP code of a YAML routing file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RoutingManipulator extends Manipulator
{
    private $file;

    /**
     * Constructor.
     *
     * @param string $file The YAML routing file path
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Adds a routing resource at the top of the existing ones.
     *
     * @param string $namespace
     * @param string $bundle
     * @param string $format
     *
     * @return Boolean true if it worked, false otherwise
     */
    public function addResource($namespace, $bundle, $format)
    {
        if (!file_exists($this->file)) {
            return false;
        }

        $code = sprintf("%s:\n", $bundle);
        if ('annotation' == $format) {
            $code .= sprintf("    resource: \"@%s/Resources/Controller/\"\n    type:     annotation\n", $bundle);
        } else {
            $code .= sprintf("    resource: \"@%s/Resources/config/routing.%s\"\n", $bundle, $format);
        }
        $code .= "    prefix:   /\n";

        $code .= "\n".file_get_contents($this->file);

        if (false === file_put_contents($this->file, $code)) {
            return false;
        }

        return true;
    }
}
