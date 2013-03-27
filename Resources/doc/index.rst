SensioGeneratorBundle
==========================

The ``SensioGeneratorBundle`` extends the default Symfony2 command line
interface by providing new interactive and intuitive commands for generating
code skeletons like bundles, form classes or CRUD controllers based on a
Doctrine 2 schema.

Installation
------------

`Download`_ the bundle and put it under the ``Sensio\\Bundle\\`` namespace.
Then, like for any other bundle, include it in your Kernel class::

    public function registerBundles()
    {
        $bundles = array(
            ...

            new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle(),
        );

        ...
    }

List of Available Commands
--------------------------

The ``SensioGeneratorBundle`` comes with four new commands that can be run in
interactive mode or not. The interactive mode asks you some questions to
configure the command parameters to generate the definitive code. The list of
new commands are listed below:

.. toctree::
   :maxdepth: 1

   commands/generate_bundle
   commands/generate_doctrine_crud
   commands/generate_doctrine_entity
   commands/generate_doctrine_form

.. _Download: http://github.com/sensio/SensioGeneratorBundle

Overriding Skeleton Templates
-----------------------------

.. versionadded:: 2.3
  The possibility to override the dkeleton templates was added in 2.3.

All generators use a template skeleton to generate files. By default, the
commands use templates provided by the bundle under its ``Resources/skeleton``
directory.

You can define custom skeleton templates by creating the same directory and
file structure in ``APP_PATH/Resources/SensioGeneratorBundle/skeleton`` or
``BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton`` if you want to extend
the generator bundle (to be able to share your templates for instance in
several projects).

For instance, if you want to override the ``edit`` template for the CRUD
generator, create a ``crud/views/edit.html.twig.twig`` file under
``APP_PATH/Resources/SensioGeneratorBundle/skeleton``.

When overriding a template, have a look at the default templates to learn more
about the available templates, their path, and the variables they have access.
