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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generates a Controller inside a bundle.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
class ControllerGenerator extends Generator
{
    private $filesystem;
    private $skeletonDir;
    private $customSkeletonDirs;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem         A Filesystem instance
     * @param string     $skeletonDir        The main skeleton directory
     * @param array      $customSkeletonDirs Additional, custom, skeleton dirs
     */
    public function __construct(Filesystem $filesystem, $skeletonDir, $customSkeletonDirs = array())
    {
        $this->filesystem  = $filesystem;
        $this->skeletonDir = $skeletonDir;
        $this->customSkeletonDirs = is_array($customSkeletonDirs) ? $customSkeletonDirs : array();
    }

    /**
     * Finds the most specific skeleton dir where the template lies.
     *
     * Assertion: $this->customSkeletonDirs must be sorted from the most specific
     * to the most general directory.
     * If no custom dir contains the template, the global dir is returned.
     *
     * @param string $template The template filename we are looking for
     */
    protected function findTemplateDir($template)
    {
        foreach ($this->customSkeletonDirs as $dir) {
            if (is_readable($dir . '/' . $template)) {
                return $dir;
            }
        }

        return $this->skeletonDir;
    }

    public function generate(BundleInterface $bundle, $controller, $routeFormat, $templateFormat, array $actions = array())
    {
        $dir = $bundle->getPath();
        $controllerFile = $dir.'/Controller/'.$controller.'Controller.php';
        if (file_exists($controllerFile)) {
            throw new \RuntimeException(sprintf('Controller "%s" already exists', $controller));
        }

        $parameters = array(
            'namespace'  => $bundle->getNamespace(),
            'bundle'     => $bundle->getName(),
            'format'     => array(
                'routing'    => $routeFormat,
                'templating' => $templateFormat,
            ),
            'controller' => $controller,
        );

        foreach ($actions as $i => $action) {
            // get the actioname without the sufix Action (for the template logical name)
            $actions[$i]['basename'] = $basename = substr($action['name'], 0, -6);
            $params = $parameters;
            $params['action'] = $actions[$i];

            // create a template
            $template = $actions[$i]['template'];
            if ('default' == $template) {
                $template = $bundle->getName().':'.$controller.':'.substr($action['name'], 0, -6).'.html.'.$templateFormat;
            }

            $skeletonDir = $this->findTemplateDir('Template.html.twig');

            if ('twig' == $templateFormat) {
                $this->renderFile($skeletonDir, 'Template.html.twig', $dir.'/Resources/views/'.$this->parseTemplatePath($template), $params);
            } else {
                $this->renderFile($skeletonDir, 'Template.html.php', $dir.'/Resources/views/'.$this->parseTemplatePath($template), $params);
            }

            $this->generateRouting($bundle, $controller, $actions[$i], $routeFormat);
        }

        $parameters['actions'] = $actions;

        $template = 'Controller.php';
        $skeletonDir = $this->findTemplateDir($template);
        $this->renderFile($skeletonDir, $template, $controllerFile, $parameters);

        $template = 'ControllerTest.php';
        $skeletonDir = $this->findTemplateDir($template);
        $this->renderFile($skeletonDir, $template, $dir.'/Tests/Controller/'.$controller.$template, $parameters);
    }

    public function generateRouting(BundleInterface $bundle, $controller, array $action, $format)
    {
        // annotation is generated in the templates
        if ('annotation' == $format) {
            return true;
        }

        $file = $bundle->getPath().'/Resources/config/routing.'.$format;
        if (file_exists($file)) {
            $content = file_get_contents($file);
        } elseif (!is_dir($dir = $bundle->getPath().'/Resources/config')) {
            mkdir($dir);
        }

        $controller = $bundle->getName().':'.$controller.':'.$action['basename'];
        $name = strtolower(preg_replace('/([A-Z])/', '_\\1', $action['basename']));

        if ('yml' == $format) {
            // yaml
            if (!isset($content)) {
                $content = '';
            }

            $content .= sprintf(
                "\n%s:\n    pattern: %s\n    defaults: { _controller: %s }\n",
                $name,
                $action['route'],
                $controller
            );
        } elseif ('xml' == $format) {
            // xml
            if (!isset($content)) {
                // new file
                $content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<routes xmlns="http://symfony.com/schema/routing"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/routing http://symfony.com/schema/routing/routing-1.0.xsd">
</routes>
EOT;
            }

            $sxe = simplexml_load_string($content);

            $route = $sxe->addChild('route');
            $route->addAttribute('id', $name);
            $route->addAttribute('pattern', $action['route']);

            $default = $route->addChild('default', $controller);
            $default->addAttribute('key', '_controller');

            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($sxe->asXML());
            $content = $dom->saveXML();
        } elseif ('php' == $format) {
            // php
            if (isset($content)) {
                // edit current file
                $pointer = strpos($content, 'return');
                if (!preg_match('/(\$[^ ]*).*?new RouteCollection\(\)/', $content, $collection) || false === $pointer) {
                    throw new \RunTimeException('Routing.php file is not correct, please initialize RouteCollection.');
                }

                $content = substr($content, 0, $pointer);
                $content .= sprintf("%s->add('%s', new Route('%s', array(", $collection[1], $name, $action['route']);
                $content .= sprintf("\n    '_controller' => '%s',", $controller);
                $content .= "\n)));\n\nreturn ".$collection[1];
            } else {
                // new file
                $content = <<<EOT
<?php
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

\$collection = new RouteCollection();
EOT;
                $content .= sprintf("\n\$collection->add('%s', new Route('%s', array(", $name, $action['route']);
                $content .= sprintf("\n    '_controller' => '%s',", $controller);
                $content .= "\n)));\n\nreturn \$collection";
            }
        }

        $flink = fopen($file, 'w');
        if ($flink) {
            $write = fwrite($flink, $content);

            if ($write) {
                fclose($flink);
            } else {
                throw new \RunTimeException(sprintf('We cannot write into file "%s", has that file the correct access level?', $file));
            }
        } else {
            throw new \RunTimeException(sprintf('Problems with generating file "%s", did you gave write access to that directory?', $file));
        }
    }

    protected function parseTemplatePath($template)
    {
        $data = $this->parseLogicalTemplateName($template);

        return $data['controller'].'/'.$data['template'];
    }

    protected function parseLogicalTemplateName($logicalname, $part = '')
    {
        $data = array();

        list($data['bundle'], $data['controller'], $data['template']) = explode(':', $logicalname);

        return ($part ? $data[$part] : $data);
    }
}
