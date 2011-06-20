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
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;

/**
 * Generates bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateBundleCommand extends ContainerAwareCommand
{
    private $generator;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The namespace of the bundle to create', null),
                new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', null),
                new InputOption('bundle-name', '', InputOption::VALUE_REQUIRED, 'The optional bundle name', null),
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
        if ($input->isInteractive()) {
            if (false === $elements = $this->getInteractiveParameters($input, $output)) {
                return 1;
            }
            list($namespace, $bundle, $dir, $format, $structure) = $elements;
        } else {
            list($namespace, $bundle, $dir, $format, $structure) = $this->getParameters($input, $output);
        }

        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Bundle generation');

        if (!$this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
            $dir = getcwd().'/'.$dir;
        }

        $generator = $this->getGenerator();
        $generator->generate($namespace, $bundle, $dir, $format, $structure);

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $errors = array();
        $runner = $dialog->getRunner($output, $errors);

        // check that the namespace is already autoloaded
        $runner($this->checkAutoloader($output, $namespace, $bundle, $dir));

        // register the bundle in the Kernel class
        $runner($this->updateKernel($dialog, $input, $output, $this->getContainer()->get('kernel'), $namespace, $bundle));

        // routing
        $runner($this->updateRouting($dialog, $input, $output, $bundle, $format));

        $dialog->writeGeneratorSummary($output, $errors);
    }

    private function getParameters(InputInterface $input, OutputInterface $output)
    {
        foreach (array('namespace', 'dir') as $option) {
            if (null === $input->getOption($option)) {
                throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
            }
        }

        $namespace = Validators::validateNamespace($input->getOption('namespace'));

        if (!$bundle = $input->getOption('bundle-name')) {
            $bundle = strtr($namespace, array('\\' => ''));
        }
        $bundle = Validators::validateBundleName($bundle);

        $dir = Validators::validateTargetDir($input->getOption('dir'), $bundle, $namespace);

        $format = $input->getOption('format') ?: 'annotation';
        $structure = $input->getOption('structure');

        return array($namespace, $bundle, $dir, $format, $structure);
    }

    private function getInteractiveParameters(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Symfony2 bundle generator');

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

        $namespace = $dialog->askAndValidate($output, $dialog->getQuestion('Bundle namespace', $input->getOption('namespace')), array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateNamespace'), false, $input->getOption('namespace'));
        $namespace = Validators::validateNamespace($namespace);

        // bundle name
        $bundle = $input->getOption('bundle-name') ?: strtr($namespace, array('\\' => ''));
        $output->writeln(array(
            '',
            'In your code, a bundle is often referenced by its name. It can be the',
            'concatenation of all namespace parts but it\'s really up to you to come',
            'up with a unique name (a good practice is to start with the vendor name).',
            'Based on the namespace, we suggest <comment>'.$bundle.'</comment>.',
            '',
        ));
        $bundle = $dialog->askAndValidate($output, $dialog->getQuestion('Bundle name', $bundle), array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateBundleName'), false, $bundle);
        $bundle = Validators::validateBundleName($bundle);

        // target dir
        $dir = $input->getOption('dir') ?: dirname($this->getContainer()->getParameter('kernel.root_dir')).'/src';
        $output->writeln(array(
            '',
            'The bundle can be generated anywhere. The suggested default directory uses',
            'the standard conventions.',
            '',
        ));
        $dir = $dialog->askAndValidate($output, $dialog->getQuestion('Target directory', $dir), function ($dir) use ($bundle, $namespace) { return Validators::validateTargetDir($dir, $bundle, $namespace); }, false, $dir);
        $dir = Validators::validateTargetDir($dir, $bundle, $namespace);

        // format
        $format = $input->getOption('format') ?: 'annotation';
        $output->writeln(array(
            '',
            'Determine the format to use for the generated configuration.',
            '',
        ));
        $format = $dialog->askAndValidate($output, $dialog->getQuestion('Configuration format (yml, xml, php, or annotation)', $format), array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateFormat'), false, $format);
        $format = Validators::validateFormat($format);

        // optional files to generate
        $output->writeln(array(
            '',
            'To help you getting started faster, the command can generate some',
            'code snippets for you.',
            '',
        ));

        $structure = $input->getOption('structure');
        if (!$structure && $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate the whole directory structure', 'no', '?'), false)) {
            $structure = true;
        }

        // summary
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a \"<info>%s\\%s</info>\" bundle\nin \"<info>%s</info>\" using the \"<info>%s</info>\" format.", $namespace, $bundle, $dir, $format),
            '',
        ));

        if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
            $output->writeln('<error>Command aborted</error>');

            return false;
        }

        return array($namespace, $bundle, $dir, $format, $structure);
    }

    protected function checkAutoloader(OutputInterface $output, $namespace, $bundle, $dir)
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

    protected function updateKernel($dialog, InputInterface $input, OutputInterface $output, KernelInterface $kernel, $namespace, $bundle)
    {
        $auto = true;
        if ($input->isInteractive()) {
            $auto = $dialog->askConfirmation($output, $dialog->getQuestion('Confirm automatic update of your Kernel', 'yes', '?'), true);
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

    protected function updateRouting($dialog, InputInterface $input, OutputInterface $output, $bundle, $format)
    {
        $auto = true;
        if ($input->isInteractive()) {
            $auto = $dialog->askConfirmation($output, $dialog->getQuestion('Confirm automatic update of the Routing', 'yes', '?'), true);
        }

        $output->write('Importing the bundle routing resource: ');
        $routing = new RoutingManipulator($this->getContainer()->getParameter('kernel.root_dir').'/config/routing.yml');
        $ret = $auto ? $routing->addResource($bundle, $format) : false;
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

    protected function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = new BundleGenerator($this->getContainer()->get('filesystem'), __DIR__.'/../Resources/skeleton/bundle');
        }

        return $this->generator;
    }

    public function setGenerator(BundleGenerator $generator)
    {
        $this->generator = $generator;
    }

    protected function getDialogHelper()
    {
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog || get_class($dialog) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper') {
            $this->getHelperSet()->set($dialog = new DialogHelper());
        }

        return $dialog;
    }
}
