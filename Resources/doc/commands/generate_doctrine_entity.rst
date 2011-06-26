Generating a New Doctrine Entity Stub
=====================================

Usage
-----

The ``generate:doctrine:entity`` generates a new Doctrine entity stub including
the mapping definition and the class properties, getters and setters.

By default the command is run in the interactive mode and asks questions to
determine the bundle name, location, configuration format and default
structure.::

    $ ./app/console generate:doctrine:entity

Available Options
-----------------

* ``--entity``
    The entity name given as a shortcut notation containing the bundle name in
    which the entity is located and the name of the entity. For example:
    ``AcmeBlogBundle:Post``.::

    $ ./app/console generate:doctrine:entity --entity=AcmeBlogBundle:Post

* ``--fields``:
    The list of fields to generate in the entity class.::

    $ ./app/console generate:doctrine:entity --fields="title:string(100) body:text"

* ``--format``: (**annotation**) [values: yml, xml, php or annotation]
    Determine the format to use for the generated configuration files like
    routing. By default, the command uses the ``annotation`` format.::

    $ ./app/console generate:doctrine:entity --format=annotation