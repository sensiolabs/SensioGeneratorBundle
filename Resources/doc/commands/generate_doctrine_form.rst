Generating a New Form Type Class Based on a Doctrine Entity
===========================================================

Usage
-----

The ``generate:doctrine:form`` generates a basic form type class by using the
metadata mapping of a given entity class.

    php app/console generate:doctrine:form AcmeBlogBundle:Post

Required Arguments
------------------

* ``entity``
    The entity name given as a shortcut notation containing the bundle name in
    which the entity is located and the name of the entity. For example:
    ``AcmeBlogBundle:Post``.::

    php app/console generate:doctrine:form AcmeBlogBundle:Post