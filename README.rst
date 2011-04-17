This bundle provides the feature to switch template file automatically depending on the user agent.

Currently under developing state.


Requirements
============

- Dua library (User Agent Detection abstraction library)
- Pear Net_UserAgent_Mobile


Install
=======

Gets the libraries from GitHub.

.. code-block: bash

    $ git submodule add -f git://github.com/hidenorigoto/MobileViewBundle.git vendor/bundles/Xnni/MobileViewBundle
    $ git submodule add -f git://github.com/hidenorigoto/Dua.git vendor/dua
    $ git submodule add -f git://github.com/iteman/net-useragent-mobile.git vendor/net-useragent-mobile

Setup autoload

.. code-block: php

    $loader->registerNamespaces(array(
        // add namespaces as follows
        'Xnni\\MobileViewBundle'    => __DIR__.'/../vendor/bundles',
        'Dua'                       => __DIR__.'/../vendor/dua/src',
    ));

    $loader->registerPrefixes(array(
        // add prefix as follows
        'Net_UserAgent_'            => __DIR__.'/../vendor/net-useragent-mobile/src',
    ));

    // and set the include path as follows
    set_include_path(get_include_path()
        .PATH_SEPARATOR.__DIR__.'/../vendor/net-useragent-mobile/src'
    );


