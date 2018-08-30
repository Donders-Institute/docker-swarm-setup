Tutorial: single-host orchestration
***********************************

This tutorial focuses on orchestrating multiple containers together on a single Docker host as an application stack. For doing so, we will be using the `docker-compose <https://docs.docker.com/compose/>`_ tool.

The application we are going to build is a user registration application.  The application has two interfaces, one is the user registration form; the other is an overview of all registered users.  Information of the registered users will be stored in the MySQL database; while the interfaces are built with PHP.

Throught the tutorial you will learn:

- the docker-compose file
- the basic usage of the docker-compose command

Preparation
===========

The ``docker-compose`` tool is not immediately available after the Docker engine is installed on Linux.  Thus, we will have to install it maunally.  But installation is very straightforward as it's just a single binary file to be downloaded.  Follow the commands below to install it:

.. code-block:: bash

    $ sudo curl -L https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$(uname -s)-$(uname -m) \
    -o /usr/local/bin/docker-compose
    $ chmod +x /usr/local/bin/docker-compose
    $ docker-compose --version

Files used in this tutorial are available on GitHub. Preparing those files within the ``~/tmp`` using the commands below:

.. code-block:: bash

    $ mkdir -p ~/tmp
    $ cd ~/tmp
    $ wget https://github.com/Donders-Institute/docker-swarm-setup/raw/master/doc/tutorial/centos-httpd/orchestration.tar.gz
    $ tar xvzf orchestration.tar.gz
    $ cd orchestration
    $ ls
    app  cleanup.sh  docker-compose.yml  initdb.d

.. important::

    In order to make the following commands in this tutorial work, you also need to prepare the files we used in the :ref:`tutorial-basic` section.

The docker-compose file
=======================

Container orchestration is to manage multiple containers in a controlled manner so that they work together as a set of integrated components.  The docker-compose file is to describe the containers and their relationship in the stack.  The docker-compose file is also written in `YAML <https://en.wikipedia.org/wiki/YAM>`_. Hereafter is the docker-compose file for our user registration application.  The service architecture it represented is shown in :numref:`apparchitecture`.

.. figure:: ../figures/app-service-architecture.png
    :name: apparchitecture
    :alt: illustration of the service architecture implemented by the docker-compose file in this tutorial.

    an illustration of the service architecture implemented by the docker-compose file used in this tutorial.

.. code-block:: none
    :linenos:

    version: '3.1'

    networks:
        dbnet:

    services:
        db:
            image: mysql:latest
            hostname: db
            command: --default-authentication-plugin=mysql_native_password
            environment:
                - MYSQL_ROOT_PASSWORD=admin123
                - MYSQL_DATABASE=registry
                - MYSQL_USER=demo
                - MYSQL_PASSWORD=demo123
            volumes:
                - ./initdb.d:/docker-entrypoint-initdb.d
                - ./data:/var/lib/mysql
            networks:
                - dbnet
        web:
            build:
                context: ../basic
                dockerfile: Dockerfile_php
            image: php:centos
            volumes:
                - ./app:/var/www/html
                - ./log:/var/log/httpd
            networks:
                - dbnet
            ports:
                - 8080:80
            depends_on:
                - db
