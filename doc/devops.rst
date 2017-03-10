Service development
*******************

This document will walk you though few steps to build and run a WordPress application in the docker swarm cluster.  The WordPress application consists of two service components:

* The WordExpress web application hosted in a Apache HTTP server
* MySQL database

For each of the two services, we will build a corresponding Docker container.

Write Dockerfile
================

Every docker container is built on top a basic container image, the OS container. Almost all Linux distributions have their mainstream systems published as container images on `Docker Hub <https://hub.docker.com/explore/>`_.


Build image
===========

Upload image to registry
========================

Deploy service
==============
