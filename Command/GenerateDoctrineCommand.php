<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\DoctrineBundle\Mapping\MetadataFactory;

abstract class GenerateDoctrineCommand extends Command
{
    protected function parseShortcutNotation($shortcut)
    {
        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            throw new \InvalidArgumentException(sprintf('The entity name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Blog/Post)', $entity));
        }

        $bundle = substr($entity, 0, $pos);
        $entity = substr($entity, $pos + 1);

        return array($bundle, $entity);
    }

    protected function getEntityMetadata($entity)
    {
        $container = $this->getApplication()->getKernel()->getContainer();

        $factory = new MetadataFactory($container->get('doctrine'));

        return $factory->getClassMetadata($entity)->getMetadata();
    }
}
