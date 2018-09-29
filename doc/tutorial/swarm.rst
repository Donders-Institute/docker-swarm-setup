Tutorial: Docker swarm
**********************

In the previous tutorial, we have learnd about container orchestration for running a service stack with load-balancing feature.  However, the whole stack is running on a single Docker node, meaning that the service will be interrupted when the node is down, a single-point of failure.

In this tutorial, we are going to eliminate this single-point of failure by orchestrating containers on a cluster of Docker nodes, a Docker swarm cluster.  We will revisit our web application developed in the :ref:`tutorial-orchestration` session, and make our web-tier redundent for eventual node failure.

You will learn:

- how to create a swarm cluster from scratch,
- how to label nodes in a cluster,
- how to deploy a stack in a swarm cluster.

.. tip::
    Docker swarm is not the only solution for orchestrating containers on multiple computers.  A platform called `Kubenetes <https://kubernetes.io/>`_ was originally developed by Google and used in the many container infrastructure.

Architecture
============

The architecture of the Docker swarm cluster is relatively simple comparing to other distributed container orchestration platforms. As illustrated in :numref:`swarmarchitecture`, there are two types of nodes: *manager* and *worker*.

By design, managers are no difference to the workers in sharing container load except that they are also responsible for maintaining the status of the cluster on a distributed state store.  Managers exchange information with each other in order to maitain sufficient quorum of the `Raft consensus algorithm <https://en.wikipedia.org/wiki/Raft_(computer_science)>`_ for cluster fault tolerance.

.. figure:: ../figures/swarm-architecture.png
    :name: swarmarchitecture
    :alt: the swarm architecture.

    the swarm architecture, an illustration from `the docker blog <https://blog.docker.com/2016/06/docker-1-12-built-in-orchestration/>`_.

Service and stack
^^^^^^^^^^^^^^^^^

Two terms used frequently in Docker swarm should be pointed out here: *service* and *stack*.

In the swarm cluster, a container can be started with multiple instances (i.e. replicas). The term *service* is used to refer to the replicas of the same container.

A *stack* is referred to a group of connected *services*.  Similar to the single-node orchestration, a stack is also described by a *docker-compose* file with extra attributes specific to Docker swarm.

Creating a cluster
==================

Docker swarm is a "mode" supported natively by the Docker engine since version 1.12 in 2016. Given a group of independent Docker nodes, one can easily start create a cluster using the command:

.. code-block:: bash

    $ docker swarm init

After that you could check the cluster using

.. code-block:: bash

    $ docker node ls
    ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    pyiykevht7pc24s7wxvgkscrn *   pl-torque.dccn.nl   Ready               Active              Leader              18.03.1-ce

Et voilà! You have just created a swarm cluster, as simple as one command... As you have noticed, it is a one-node cluster.  In addition, you see that the node is by default a manager. Since it is the only manager, it is also the leading manager (*Leader*).

Join tokens
===========

Managers also hold tokens (a.k.a. join token) for nodes to join the cluster. There are two join tokens; one for joining the cluster as a mansger, the other for being a worker.  To retrieve the token for manager, use the following command on the first manager.

.. code-block:: bash

    $ docker swarm join-token manager
    To add a manager to this swarm, run the following command:

        docker swarm join --token SWMTKN-1-4tznpl3vlnpgyp4f8papm2e5my9o27p6v2ewk41m1xfk654fun-e5lv67kc05o3wcquywe0hujya 131.174.44.95:2377

For worker, one does

.. code-block:: bash

    $ docker swarm join-token worker
    To add a worker to this swarm, run the following command:

        docker swarm join --token SWMTKN-1-4tznpl3vlnpgyp4f8papm2e5my9o27p6v2ewk41m1xfk654fun-2k9eap8y5vzgj7yzxminkxor7 131.174.44.95:2377

The output of these two commands simply tells you what to run on the nodes that are about to join the cluster.

Adding nodes
============

Adding nodes is done by executing the command provided by the ``docker swarm join-token`` commands above.  After that, you can see the cluster has more nodes available.

.. code-block:: bash

    ID                            HOSTNAME              STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    zqmkhtcq2bx6wvb1hg1h8psww     pl-cvmfs-s1.dccn.nl   Ready               Active              Reachable           18.03.1-ce
    mmssbtdqb66rym7ac7yqwq2ib     pl-squid.dccn.nl      Ready               Active              Reachable           18.03.1-ce
    pyiykevht7pc24s7wxvgkscrn *   pl-torque.dccn.nl     Ready               Active              Leader              18.03.1-ce

Labeling nodes
^^^^^^^^^^^^^^

It is sometimes useful to lable the node so that they can be distinguished by, e.g. the operation system of the host, for deploying containers.  Assuming we just added a Windows node to the cluster, we could assigne a lable *os=windows* to the node so that we can use the label to deploy containers that require to run on Windows.  For that, we do:

.. code-block:: bash

    $ docker node update --label-add os=windows <hostname>

.. tip::
    There are more than node lables that can help us locating nodes for specific containers.

Promoting and demoting nodes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Manager node can demote manager node to become a worker or promote worker to become a manager. This dynamics allows administrator to ensure sufficient managers in the cluster while some manager nodes need to go down for maintenance. For promoting or demoting a node, one does:

.. code-block:: bash

    $ docker promote <hostname>

or

.. code-block:: bash

    $ docker demote <hostname>

Exercise: join the cluster
^^^^^^^^^^^^^^^^^^^^^^^^^^

Hereafter is the interactive exercise:

- The tutor prepared docker-engine nodes, and created a one-node cluster in advance.  The tutor distributes join token for manager node to student, and ask student to add node to the cluster.
- The tutor asks students to label the node with label *os=linux* and *owner=student*.
- The tutor asks students to demote node in a sequencial order.  For example, student 1 demotes the node prepared by the tutor followed by student 2 demotes the node student 1 has been working on followed by student 3 demote the node student 2 has been working on, etc.  At the end, the cluster should still have one manager node that is operated by the last student.
- Reverse the sequence of the previous step to promote nodes back to managers.

All student nodes should be in manager role in the cluster.

The docker-compose file
=======================

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
                - /home/tg/honlee/tmp/orchestration/initdb.d:/docker-entrypoint-initdb.d
                - /home/tg/honlee/tmp/orchestration/data:/var/lib/mysql
            networks:
                - dbnet
            deploy:
                mode: replicated
                replicas: 1
                placement:
                    constraints:
                        - node.labels.os != windows
        web:
            image: docker-registry.dccn.nl:5000/php:centos
            volumes:
                - /home/tg/honlee/tmp/orchestration/app:/var/www/html
                - /home/tg/honlee/tmp/orchestration/log:/var/log/httpd
            networks:
                - dbnet
            ports:
                - 8080:80
            depends_on:
                - db
            deploy:
                mode: replicated
                replicas: 2
                placement:
                    constraints:
                        - node.labels.os != windows

Sharing volumn and image
^^^^^^^^^^^^^^^^^^^^^^^^

- bind-mount to shared storage
- docker registry for image

Overlay network
^^^^^^^^^^^^^^^

- "Deep dive in Docker Overlay Networks": https://www.youtube.com/watch?v=IgDLNcpmfqI

Container placement
^^^^^^^^^^^^^^^^^^^

Launching stack
===============

.. code-block:: bash

    $ docker stack deploy -c docker-compose.yml myapp

Network routing mesh
^^^^^^^^^^^^^^^^^^^^

- figure illustration the routing mesh, using the example of the same docker-compose file
