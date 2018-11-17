Tutorial: Docker swarm
**********************

In the previous tutorial, we have learnd about container orchestration for running a service stack with load-balancing feature.  However, the whole stack is running on a single Docker node, meaning that the service will be interrupted when the node is down, a single-point of failure.

In this tutorial, we are going to eliminate this single-point of failure by orchestrating containers on a cluster of Docker nodes, a Docker swarm cluster.  We will revisit our web application developed in the :ref:`tutorial-orchestration` session, and make our web-tier redundent for eventual node failure.

You will learn:

- how to create a swarm cluster from scratch,
- how to deploy a stack in a swarm cluster,
- how to manage the cluster.

.. tip::
    Docker swarm is not the only solution for orchestrating containers on multiple computers.  A platform called `Kubenetes <https://kubernetes.io/>`_ was originally developed by Google and used in the many container infrastructure.

Preparation
===========

In this tutorial, we are going to create a cluster using `docker machine <https://docs.docker.com/machine/>`_.  For that, we will need to install `VirtualBox <https://virtualbox.org>`_ on the computer.  Follow the commands below to install the VirtualBox RPM.

.. code-block:: bash

    $ wget https://download.virtualbox.org/virtualbox/5.2.22/VirtualBox-5.2-5.2.22_126460_el7-1.x86_64.rpm
    $ sudo yum install VirtualBox-5.2-5.2.22_126460_el7-1.x86_64.rpm

Next step is to download the files we are going to use in this exercise:

.. code-block:: bash

    $ mkdir -p ~/tmp
    $ cd ~/tmp
    $ https://github.com/Donders-Institute/docker-swarm-setup/raw/master/doc/tutorial/centos-httpd/swarm.tar.gz
    $ tar xvzf swarm.tar.gz
    $ cd swarm

Bootstrap two docker machines with the prepared script:

.. code-block:: bash

    $ ./docker-machine-bootstrap.sh vm1 vm2

Open two new terminals, each logs into one of the two virtual machines. For example, on terminal one, do

.. code-block:: bash

    $ docker-machine ssh vm1

On the second terminal, do

.. code-block:: bash

    $ docker-machine ssh vm2

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

Docker swarm is a "mode" supported natively by the Docker engine since version 1.12 in 2016.  On the first docker machine, `vm1` in this case, we can simply initiate the cluster by:

.. code-block:: bash

    [vm1]$ docker swarm init --advertise-addr 192.168.99.100

.. note::
    The `advertise-addr` should be the IP address of the docker machine.  It may be different in different system.

.. note::
    The notation `[vm1]` on the command-line prompt indicates that the command should be executed on the specified docker machine.  All the commands in this tutorial follow the same notation.  If there is no such notation on the prompt, the command is performed on the host of the docker machines.

After that you could check the cluster using

.. code-block:: bash

    [vm1]$ docker node ls
    ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    svdjh0i3k9ty5lsf4lc9d94mw *   vm1                 Ready               Active              Leader              18.06.1-ce

Et voilà! You have just created a swarm cluster, as simple as one command... As you have noticed, it is a one-node cluster.  In addition, you see that the node is by default a manager. Since it is the only manager, it is also the leading manager (*Leader*).

Join tokens
===========

Managers also hold tokens (a.k.a. join token) for nodes to join the cluster. There are two join tokens; one for joining the cluster as a mansger, the other for being a worker.  To retrieve the token for manager, use the following command on the first manager.

.. code-block:: bash

    [vm1]$ docker swarm join-token manager
    To add a manager to this swarm, run the following command:

        docker swarm join --token SWMTKN-1-2i60ycz95dbpblm0bewz0fyypwkk5jminbzpyheh7yzf5mvrla-1q74k0ngm0br70ur93h7pzdg4 192.168.99.100:2377

For worker, one does

.. code-block:: bash

    [vm1]$ docker swarm join-token worker
    To add a worker to this swarm, run the following command:

        docker swarm join --token SWMTKN-1-2i60ycz95dbpblm0bewz0fyypwkk5jminbzpyheh7yzf5mvrla-9br20buxcon364sgmdbcfobco 192.168.99.100:2377

The output of these two commands simply tells you what to run on the nodes that are about to join the cluster.

Adding nodes
============

Adding nodes is done by executing the command provided by the ``docker swarm join-token`` commands above on the node that you are about to add.  For exampl, let's add our second docker machine (`vm2`) to the cluster as a manager:

.. code-block:: bash

    [vm2]$ docker swarm join --token \
    SWMTKN-1-2i60ycz95dbpblm0bewz0fyypwkk5jminbzpyheh7yzf5mvrla-1q74k0ngm0br70ur93h7pzdg4 \
    192.168.99.100:2377

After that, you can see the cluster has more nodes available.

.. code-block:: bash

    [vm2]$ docker node ls
    ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    svdjh0i3k9ty5lsf4lc9d94mw     vm1                 Ready               Active              Leader              18.06.1-ce
    m5r1j48nnl1u9n9mbr8ocwoa3 *   vm2                 Ready               Active              Reachable           18.06.1-ce

.. note::
    The `docker node` command is meant for managing nodes in the cluster, and therefore, it can only be executed on the manager nodes.  Since we just added `vm2` as a manager, we could do the `docker node ls` right away.

Labeling nodes
^^^^^^^^^^^^^^

It is sometimes useful to lable the nodes.  Node lables are useful for container placement on nodes.  Let's now lable the two nodes with *os=linux*.

.. code-block:: bash

    [vm1]$ docker node update --label-add os=linux vm1
    [vm1]$ docker node update --label-add os=linux vm2

.. tip::
    There are more than node lables that can help us locating nodes for specific containers.

Promoting and demoting nodes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Manager node can demote manager node to become a worker or promote worker to become a manager. This dynamics allows administrator to ensure sufficient managers in the cluster while some manager nodes need to go down for maintenance.  Let's demote `vm2` from manager to worker:

.. code-block:: bash

    [vm1]$ docker node demote vm2
    Manager vm2 demoted in the swarm.

    [vm1]$ docker node ls
    ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    svdjh0i3k9ty5lsf4lc9d94mw *   vm1                 Ready               Active              Leader              18.06.1-ce
    m5r1j48nnl1u9n9mbr8ocwoa3     vm2                 Ready               Active                                  18.06.1-ce

Promote the `vm2` back to manager:

.. code-block:: bash

    [vm1]$ docker node promote vm2
    Node vm2 promoted to a manager in the swarm.

The docker-compose file
=======================

The following docker-compose file is modified from the one we used in the :ref:`tutorial-orchestration`.  Differences are highlighted.  Changes are:

* we stripped down the network part,
* we added container placement requirements via the `deploy`,
* we stored persistent data in docker volumes,
* we made use of a private docker image registry.

.. code-block:: yaml
    :linenos:
    :emphasize-lines: 3,4,6,7,8,22,25,26,27,28,29,30,32,35,42,43,44,45,46,47

    version: '3.1'

    networks:
        default:

    volumes:
        dbdata:
        weblog:

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
                - dbdata:/var/lib/mysql
            networks:
                - default
            deploy:
                mode: replicated
                replicas: 1
                placement:
                    constraints:
                        - node.hostname == vm1
        web:
            image: docker-registry.dccn.nl:5000/php:centos
            volumes:
                - ./app:/var/www/html:ro
                - weblog:/var/log/httpd
            networks:
                - default
            ports:
                - 8080:80
            depends_on:
                - db
            deploy:
                mode: replicated
                replicas: 1
                placement:
                    constraints:
                        - node.labels.os == linux

Sharing volumn and image
^^^^^^^^^^^^^^^^^^^^^^^^

- bind-mount to shared storage
- docker registry for image

Overlay network
^^^^^^^^^^^^^^^

- "Deep dive in Docker Overlay Networks": https://www.youtube.com/watch?v=b3XDl0YsVsg https://www.youtube.com/watch?v=IgDLNcpmfqI

Container placement
^^^^^^^^^^^^^^^^^^^

Launching stack
===============

.. code-block:: bash

    $ docker stack deploy -c docker-compose.yml myapp

Network routing mesh
^^^^^^^^^^^^^^^^^^^^

- figure illustration the routing mesh, using the example of the same docker-compose file
