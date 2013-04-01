SensioGeneratorBundle
=====================

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
   commands/generate_controller
   commands/generate_doctrine_crud
   commands/generate_doctrine_entity
   commands/generate_doctrine_form

.. _Download: http://github.com/sensio/SensioGeneratorBundle
