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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Command\Command;
use Sensio\Bundle\GeneratorBundle\Generator\CrudGenerator;
use Sensio\Bundle\GeneratorBundle\Generator\FormGenerator;

/**
 * Generates a CRUD for a Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateDoctrineCrudCommand extends GenerateDoctrineCommand
{
    private $container;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('entity', InputArgument::REQUIRED, 'The entity class name to initialize (shortcut notation)'),
                new InputArgument('route_prefix', InputArgument::REQUIRED, 'The route prefix'),
                new InputOption('with-write', '', InputOption::VALUE_NONE, 'Whether or not to generate create, new and delete actions'),
                new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)'),
            ))
            ->setDescription('Generates a CRUD based on a Doctrine entity')
            ->setHelp(<<<EOT
The <info>doctrine:generate:crud</info> command generates a CRUD based on a Doctrine entity.

The default command only generates the list and show actions.

<info>./app/console doctrine:generate:crud AcmeBlogBundle:Post post_admin</info>

Using the --write option allows to generate the new, edit and delete actions.

<info>./app/console doctrine:generate:crud AcmeBlogBundle:Post post_admin --with-write</info>
EOT
            )
            ->setName('doctrine:generate:crud')
            ->setAliases(array('generate:doctrine:crud'))
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getKernel()->getContainer();

        list($bundle, $entity) = $this->parseShortcutNotation($input->getArgument('entity'));

        $entityClass = $this->container->get('doctrine')->getEntityNamespace($bundle).'\\'.$entity;
        $metadata    = $this->getEntityMetadata($entityClass);
        $bundle      = $this->getApplication()->getKernel()->getBundle($bundle);
        $filesystem  = $this->container->get('filesystem');

        // Try to generate forms if they don't exist yet and if we need write
        // operations on entities.
        if ($input->getOption('with-write')) {
            try {
                $formGenerator = new FormGenerator($filesystem,  __DIR__.'/../Resources/skeleton/form');
                $formGenerator->generate($bundle, $entity, $metadata[0]);
            } catch (\RuntimeException $e ) {

            }
        }

        $generator = new CrudGenerator(
            $filesystem,
            __DIR__.'/../Resources/skeleton/crud',
            $input->getArgument('route_prefix'),
            $input->getOption('with-write')
        );
        $generator->generate($bundle, $entity, $metadata[0], $input->getOption('format'));
    }
}
