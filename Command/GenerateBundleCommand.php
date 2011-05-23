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

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;

/**
 * Generates bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateBundleCommand extends Command
{
    private $container;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The namespace of the bundle to create', null),
                new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', null),
                new InputOption('bundleName', '', InputOption::VALUE_REQUIRED, 'The optional bundle name', null),
                new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or , annotation)', null),
                new InputOption('structure', '', InputOption::VALUE_NONE, 'Whether to generate the whole directory structure', null),
            ))
            ->setDescription('Generates a bundle')
            ->setHelp(<<<EOT
The <info>generate:bundle</info> command helps you generates new bundles.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--namespace</comment> is the only one needed if you follow the
conventions):

<info>./app/console generate:bundle --namespace='Acme/BlogBundle'</info>

Note that you can use <comment>/</comment> instead of <comment>\\</comment> for the namespace delimiter to avoid any
problem.

If you want to disable any user interaction, use `--no-interaction` but don't
forget to pass all needed options:

<info>./app/console generate:bundle "Acme/BlogBundle" src [bundleName]</info>

Note that the bundle namespace must end with "Bundle".
EOT
            )
            ->setName('generate:bundle')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getApplication()->getKernel()->getContainer();

        $filesystem = $this->container->get('filesystem');
        $generator = new BundleGenerator($filesystem, __DIR__.'/../Resources/skeleton');
        $dialog = $this->getHelper('dialog');

        if ($input->isInteractive()) {
            if (false === $elements = $this->getInteractiveParameters($generator, $input, $output)) {
                return 1;
            }
            list($namespace, $bundle, $dir, $format, $structure) = $elements;
        } else {
            list($namespace, $bundle, $dir, $format, $structure) = $this->getParameters($generator, $input, $output);
        }

        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Bundle generation', 'bg=blue;fg=white', true),
            '',
        ));

        if (!$filesystem->isAbsolutePath($dir)) {
            $dir = getcwd().'/'.$dir;
        }

        $generator->generate($namespace, $bundle, $dir, $format, $structure);

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $errors = array();
        $runner = function ($err) use ($output, &$errors) {
            if ($err) {
                $output->writeln('<fg=red>FAILED</>');
                $errors = array_merge($errors, $err);
            } else {
                $output->writeln('<info>OK</info>');
            }
        };

        // check that the namespace is already autoloaded
        $runner($this->checkAutoloader($output, $namespace, $bundle, $dir));

        // register the bundle in the Kernel class
        $runner($this->updateKernel($dialog, $input, $output, $this->getApplication()->getKernel(), $namespace, $bundle));

        // routing
        $runner($this->updateRouting($dialog, $input, $output, $namespace, $bundle, $format));

        // errors?
        if (!$errors) {
            $output->writeln(array(
                '',
                $this->getHelper('formatter')->formatBlock('Start building your bundle!', 'bg=blue;fg=white', true),
                '',
            ));
        } else {
            $output->writeln(array(
                '',
                $this->getHelper('formatter')->formatBlock(array(
                    'The command was not able to configure your bundle automatically.',
                    'You must do the following changes manually.',
                ), 'error', true),
                '',
            ));

            $output->writeln($errors);
        }
    }

    private function getParameters(BundleGenerator $generator, InputInterface $input, OutputInterface $output)
    {
        foreach (array('namespace', 'dir') as $option) {
            if (null === $input->getOption($option)) {
                throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
            }
        }

        $namespace = $generator->validateNamespace($input->getOption('namespace'));

        if (!$bundle = $input->getOption('bundleName')) {
            $bundle = strtr($namespace, array('\\' => ''));
        }
        $bundle = $generator->validateBundleName($bundle);

        $dir = $generator->validateTargetDir($input->getOption('dir'), $bundle, $namespace);

        $format = $input->getOption('format') ?: 'annotation';
        $structure = $input->getOption('structure');

        return array($namespace, $bundle, $dir, $format, $structure);
    }

    private function getInteractiveParameters(BundleGenerator $generator, InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');
        $formatter = $this->getHelper('formatter');

        $output->writeln($formatter->formatBlock('Welcome to the Symfony2 bundle generator', 'bg=blue;fg=white', true));

        // namespace
        $output->writeln(array(
            '',
            'Your application code must be written in <comment>bundles</comment>. This command helps',
            'you generate them easily.',
            '',
            'Each bundle is hosted under a namespace (like <comment>Acme/Bundle/BlogBundle</comment>).',
            'The namespace should begin with a "vendor" name like your company name, your',
            'project name, or your client name, followed by one or more optional category',
            'sub-namespaces, and it should end with the bundle name itself',
            '(which must have <comment>Bundle</comment> as a suffix).',
            '',
            'Use <comment>/</comment> instead of <comment>\\</comment> for the namespace delimiter to avoid any problem.',
            '',
        ));
        $namespace = $dialog->askAndValidate($output, $this->getQuestion('Bundle namespace', $input->getOption('namespace')), array($generator, 'validateNamespace'), false, $input->getOption('namespace'));
        $namespace = $generator->validateNamespace($namespace);

        // bundle name
        $bundle = $input->getOption('bundleName') ?: strtr($namespace, array('\\' => ''));
        $output->writeln(array(
            '',
            'In your code, a bundle is often referenced by its name. It can be the',
            'concatenation of all namespace parts but it\'s really up to you to come',
            'up with a unique name (a good practice is to start with the vendor name).',
            'Based on the namespace, we suggest <comment>'.$bundle.'</comment>.',
            '',
        ));
        $bundle = $dialog->askAndValidate($output, $this->getQuestion('Bundle name', $bundle), array($generator, 'validateBundleName'), false, $bundle);
        $bundle = $generator->validateBundleName($bundle);

        // target dir
        $dir = $input->getOption('dir') ?: dirname($this->container->getParameter('kernel.root_dir')).'/src';
        $output->writeln(array(
            '',
            'The bundle can be generated anywhere. The suggested default directory uses',
            'the standard conventions.',
            '',
        ));
        $dir = $dialog->askAndValidate($output, $this->getQuestion('Target directory', $dir), function ($dir) use ($generator, $bundle, $namespace) { return $generator->validateTargetDir($dir, $bundle, $namespace); }, false, $dir);
        $dir = $generator->validateTargetDir($dir, $bundle, $namespace);

        // format
        $format = $input->getOption('format') ?: 'annotation';
        $output->writeln(array(
            '',
            'Determine the format to use for the generated configuration.',
            '',
        ));
        $format = $dialog->askAndValidate($output, $this->getQuestion('Configuration format (yml, xml, php, or annotation)', $format), array($generator, 'validateFormat'), false, $format);
        $format = $generator->validateFormat($format);

        // optional files to generate
        $output->writeln(array(
            '',
            'To help you getting started faster, the command can generate some',
            'code snippets for you.',
            '',
        ));

        $structure = $input->getOption('structure');
        if (!$structure && $dialog->askConfirmation($output, $this->getQuestion('Do you want to generate the whole directory structure', 'yes', '?'), true)) {
            $structure = true;
        }

        // summary
        $output->writeln(array(
            '',
            $formatter->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a \"<info>%s\\%s</info>\" bundle\nin \"<info>%s</info>\" using the \"<info>%s</info>\" format.", $namespace, $bundle, $dir, $format),
            '',
        ));

        if (!$dialog->askConfirmation($output, $this->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
            $output->writeln('<error>Command aborted</error>');

            return false;
        }

        return array($namespace, $bundle, $dir, $format, $structure);
    }

    private function getQuestion($question, $default, $sep = ':')
    {
        return $default ? sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) : sprintf('%s%s ', $question, $sep);
    }

    private function checkAutoloader(OutputInterface $output, $namespace, $bundle, $dir)
    {
        $output->write('Checking that the bundle is autoloaded: ');
        if (!class_exists($namespace.'\\'.$bundle)) {
            return array(
                '- Edit the <comment>app/autoloader.php</comment> file and register the bundle',
                '  namespace at the top of the <comment>registerNamespaces()</comment> call:',
                '',
                sprintf('<comment>    \'%s\' => \'%s\',</comment>', $namespace, realpath($dir)),
                '',
            );
        }
    }

    private function updateKernel($dialog, InputInterface $input, OutputInterface $output, KernelInterface $kernel, $namespace, $bundle)
    {
        $auto = true;
        if ($input->isInteractive()) {
            $auto = $dialog->askConfirmation($output, $this->getQuestion('Confirm automatic update of your Kernel', 'yes', '?'), true);
        }

        $output->write('Enabling the bundle inside the Kernel: ');
        $manip = new KernelManipulator($kernel);
        $ret = $auto ? $manip->addBundle($namespace.'\\'.$bundle) : false;
        if (!$ret) {
            $reflected = new \ReflectionObject($kernel);

            return array(
                sprintf('- Edit <comment>%s</comment>', $reflected->getFilename()),
                '  and add the following bundle in the <comment>AppKernel::registerBundles()</comment> method:',
                '',
                sprintf('    <comment>new %s(),</comment>', $namespace.'\\'.$bundle),
                '',
            );
        }
    }

    private function updateRouting($dialog, InputInterface $input, OutputInterface $output, $namespace, $bundle, $format)
    {
        $auto = true;
        if ($input->isInteractive()) {
            $auto = $dialog->askConfirmation($output, $this->getQuestion('Confirm automatic update of the Routing', 'yes', '?'), true);
        }

        $output->write('Importing the bundle routing resource: ');
        $routing = new RoutingManipulator($this->container->getParameter('kernel.root_dir').'/config/routing.yml');
        $ret = $auto ? $routing->addResource($namespace, $bundle, $format) : false;
        if (!$ret) {
            if ('annotation' === $format) {
                $help = sprintf("        <comment>resource: \"@%s/Resources/Controller/\"</comment>\n        <comment>type:     annotation</comment>", $bundle);
            } else {
                $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing.%s\"</comment>\n", $bundle, $format);
            }
            $help .= "        <comment>prefix:   /</comment>\n";

            return array(
                '- Import the bundle\'s routing resource in the app main routing file:',
                '',
                sprintf('    <comment>%s:</comment>', $bundle),
                $help,
                '',
            );
        }
    }
}
