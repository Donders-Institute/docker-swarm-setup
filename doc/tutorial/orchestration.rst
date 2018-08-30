Tutorial: single-host orchestration
***********************************

This tutorial focuses on orchestrating multiple containers together on a single Docker host as an application stack. For doing so, we will be using the `docker-compose <https://docs.docker.com/compose/>`_ tool.

The application we are going to build is a user registration application.  The application has two interfaces, one is the user registration form; the other is an overview of all registered users.  Information of the registered users will be stored in the MySQL database; while the interfaces are built with PHP.

Throught the tutorial you will learn:

- the docker-compose file
- the usage of the docker-compose tool

Preparation
===========

The docker-compose tool is not immediately available after the Docker engine is installed on Linux.  Nevertheless, the installation is very straightforward as it's just a single binary file to be downloaded.  Follow the commands below to install it:

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

Container orchestration is to manage multiple containers in a controlled manner so that they work together as a set of integrated components.  The docker-compose file is to describe the containers and their relationship in the stack.  The docker-compose file is also written in `YAML <https://en.wikipedia.org/wiki/YAM>`_. Hereafter is the docker-compose file for our user registration application.

.. tip::
    The filename of the docker-compose file is usually ``docker-compose.yml`` as it is the default the ``docker-compose`` tool looks up in the directory.

.. code-block:: yaml
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

The docker-compose file above implements a service architecture shown in :numref:`apparchitecture` where we have two services (``web`` and ``db``) running in a internal network ``dbnet`` created on-demand.

.. tip::
    The docker-compose file starts with the keyword ``version``.  It is important to note that keywords of the docker-compose file are supported differently in different Docker versions. Thus, the keyword ``version`` is to tell the docker-compose tool which version it has to use for interpreting the entire docker-compose file.

    The compatibility table can be found `here <https://docs.docker.com/compose/compose-file/compose-versioning/>`_.

.. figure:: ../figures/app-service-architecture.png
    :name: apparchitecture
    :alt: illustration of the service architecture implemented by the docker-compose file in this tutorial.

    an illustration of the service architecture implemented by the docker-compose file used in this tutorial.

The service ``web`` uses the ``php:centos`` image we have built in :ref:`tutorial-basic`. It has two bind-mounts: one for the application codes (i.e. HTML and PHP files) and the other for making the HTTPd logs persistent on the host. The ``web`` service is attached to the ``dbnet`` network and has its network port 80 mapped to the port 8080 on the host.  Furthermore, it waits for the readiness of the ``db`` service before it can be started.

Another service ``db`` uses `the official MySQL image from the Docker Hub <https://hub.docker.com/_/mysql/>`_. According to the documentation of this official MySQL image, commands and environment variables are provided for initialising the database for our user registration application.

The ``db`` service has two bind-mounted volumes.  The ``./init.d`` directory on host is bind-mounted to the ``/docker-entrypoint-initdb.d`` directory in the container as we will make use the bootstrap mechanism provided by the container to create a database schema for the ``registry`` database; while the ``./data`` is bind-mounted to ``/var/lib/mysql`` for preserving the data in the MySQL database.  The ``db`` service is also joint into the ``dbnet`` network so that it becomes accessible to the ``web`` service.

Building services
=================

When the service stack has a container based on local image build (e.g. the ``web`` service in our example), it is necessary to build the container via the docker-compose tool.  For that, one can do:

.. code-block:: bash

    $ docker-compose build --force-rm

.. tip::

    The command above will loads the ``docker-compose.yml`` file in the current directory.  If you have a different filename/location for your docker-compose file, add the ``-f <filepath>`` option to the command.

Bringing services up
====================

Once the docker-compose file is reasy, bring the whole service stack up is very simple.  Just do:

.. code-block:: bash

    $ docker-compose up -d
    Creating network "orchestration_dbnet" with the default driver
    Creating orchestration_db_1 ...
    Creating orchestration_db_1 ... done
    Creating orchestration_web_1 ...
    Creating orchestration_web_1 ... done

Let's check our user registration application by connecting the browser to `http://localhost:8080 <http://localhost:8080>`_.

service status
--------------

.. code-block:: bash

    $ docker-compose ps
           Name                      Command               State          Ports
    -----------------------------------------------------------------------------------
    orchestration_db_1    docker-entrypoint.sh --def ...   Up      3306/tcp, 33060/tcp
    orchestration_web_1   /run-httpd.sh                    Up      0.0.0.0:8080->80/tcp

service logs
------------

The services may produce logs to its STDOUT/STDERR.  Those logs can be monitored using

.. code-block:: bash

    $ docker-compose logs -f

where the option ``-f`` follows the output on STDOUT/STDERR.

Bringing services down
======================

.. code-block:: bash

    $ docker-compose down
    Stopping orchestration_web_1 ...
    Stopping orchestration_db_1  ...
