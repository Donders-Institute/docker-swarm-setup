Exercise: the basic
*******************

In this exercise, you will build and spin off an Apache HTTPd container with PHP support.  Through it you will learn:

- the docker workflow and basic UI commands,
- network port exporting,
- data persistency

The ``Dockerfile``
==================

Before starting a container with Docker, we need a docker container image that is either provided in a public repository (a.k.a. docker registry), such as `Docker Hub <https://hub.docker.com>`_, or built by ourselves.  For building our own docker image, one should firstly write an instruction document known as the `Dockerfile <https://docs.docker.com/engine/reference/builder/>`_.

Dockerfile is a `YAML <https://en.wikipedia.org/wiki/YAML>`_ document describing how a docker container should be built.  Hereafter is an example of building a container for Apache HTTPd:

.. _dockerfile-httpd:

.. code-block:: dockerfile
    :linenos:

    FROM centos:7
    MAINTAINER The CentOS Project <cloud-ops@centos.org>
    LABEL Vendor="CentOS" \
        License=GPLv2 \
        Version=2.4.6-40


    RUN yum -y --setopt=tsflags=nodocs update && \
        yum -y --setopt=tsflags=nodocs install httpd && \
        yum clean all

    EXPOSE 80

    # Simple startup script to avoid some issues observed with container restart
    ADD run-httpd.sh /run-httpd.sh
    RUN chmod -v +x /run-httpd.sh

    CMD ["/run-httpd.sh"]

The Dockerfile above is explained below:

#. Each line of the Dockerfile is started with a **keyword** followed by **argument(s)**.

#. **Line 1:** All container images are built from a basis image.  This is indicated by the ``FROM`` keyword. In this example, the basis image is the official CentOS 7 image from the public docker repository.
#. **Line 2-3:** a container image can be created with metadata.  For instance, the ``MAINTAINER`` and ``LABEL`` attributes are provided in the example.
#. **Line 8-10:** since we want to build a image for running the HTTPd server, we uses the YUM package manager to install the ``httpd`` package within the container; and it is done by using the ``RUN`` keyword.
#. **Line 12:** we know that the HTTPd service will run on port number 80, we asked the container to expose that port.
#. **Line 14:** comments in Dockerfile are started with the ``#``.
#. **Line 15:** the `run-httpd.sh <https://raw.githubusercontent.com/Donders-Institute/docker-swarm-setup/master/doc/tutorial/centos-httpd/basic/run-httpd.sh>`_ file is something we want to add from the host machine into the container image, as it is a script for bootstraping the HTTPd service after the container is started.  Thus, we make use of the ``ADD`` keyword here for *copying the file "run-httpd.sh" on the host into the root directory (i.e. /run-httpd.sh) of the container image*.
#. **Line 16:** here we make the HTTPd bootstrap script executable so that it can be run within the container.  It is done using the ``RUN`` keyword again.
#. **Line 18:** the keyword ``CMD`` specifies the command to run when the container is started.  Here we want to run the bootstrap script we have just copied into the container.
