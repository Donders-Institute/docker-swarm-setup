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
    The ``--advertise-addr`` should be the IP address of the docker machine.  It may be different in different system.

.. note::
    The notation ``[vm1]`` on the command-line prompt indicates that the command should be executed on the specified docker machine.  All the commands in this tutorial follow the same notation.  If there is no such notation on the prompt, the command is performed on the host of the docker machines.

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

Adding nodes is done by executing the command provided by the ``docker swarm join-token`` commands above on the node that you are about to add.  For exampl, let's add our second docker machine (``vm2``) to the cluster as a manager:

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
    The ``docker node`` command is meant for managing nodes in the cluster, and therefore, it can only be executed on the manager nodes.  Since we just added ``vm2`` as a manager, we could do the ``docker node ls`` right away.

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

Manager node can demote manager node to become a worker or promote worker to become a manager. This dynamics allows administrator to ensure sufficient managers in the cluster while some manager nodes need to go down for maintenance.  Let's demote ``vm2`` from manager to worker:

.. code-block:: bash

    [vm1]$ docker node demote vm2
    Manager vm2 demoted in the swarm.

    [vm1]$ docker node ls
    ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    svdjh0i3k9ty5lsf4lc9d94mw *   vm1                 Ready               Active              Leader              18.06.1-ce
    m5r1j48nnl1u9n9mbr8ocwoa3     vm2                 Ready               Active                                  18.06.1-ce

Promote the ``vm2`` back to manager:

.. code-block:: bash

    [vm1]$ docker node promote vm2
    Node vm2 promoted to a manager in the swarm.

docker-compose file for stack
=============================

The following docker-compose file is modified from the one we used in the :ref:`tutorial-orchestration`.  Differences are highlighted.  Changes are:

* we stripped down the network part,
* we added container placement requirements via the ``deploy`` section,
* we persistented MySQL data in a docker volume (*due to the fact that I don't know how to make bind-mount working with MySQL container in a swarm of docker machines*),
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
                - ./app:/tmp/htmldoc:ro
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

Launching stack
===============

The docker-compose file above is already provided as part of the downloaded files in the preparation step.  The filename is ``docker-compose.swarm.yml``.

Follow the steps below to start the application stack, and make it accessible through the host on which the two docker-machine VMs are running:

#. Get into ``vm1`` and go to the directory in which you have downloaded the files for this tutorial.  It is a directory mounted under the ``/hosthome`` directory in the VM, e.g.

    .. code-block:: bash

        [vm1]$ cd /hosthome/tg/honlee/tmp/swarm

#. Login to the private registry with user *demo*:

    .. code-block:: bash

        [vm1]$ docker login docker-registry.dccn.nl:5000

#. Start the application stack:

    .. code-block:: bash

        [vm1]$ docker stack deploy -c docker-compose.swarm.yml --with-registry-auth webapp
        Creating network webapp_default
        Creating service webapp_db
        Creating service webapp_web

    .. note::
        The ``--with-registry-auth`` is very important for pulling the ``php:centos`` image from the private repository.

#. Check if the stack is started properly:

    .. code-block:: bash

        [vm1]$ docker stack ps webapp
        ID                  NAME                IMAGE                                     NODE                DESIRED STATE       CURRENT STATE            ERROR               PORTS
        7zez13p778rt        webapp_web.1        docker-registry.dccn.nl:5000/php:centos   vm2                 Running             Running 27 seconds ago                       
        dmdipd7vl7si        webapp_db.1         mysql:latest                              vm1                 Running             Running 28 seconds ago

#. Note that our web service (``webapp_web``) is running on ``vm2``.  So it is obvous that if we try to get the index page from ``vm2``, it should work.  Try the following commands on the host of the two VMs.

    .. code-block:: bash

        $ docker-machine ls
        NAME   ACTIVE   DRIVER       STATE     URL                         SWARM   DOCKER        ERRORS
        vm1    -        virtualbox   Running   tcp://192.168.99.100:2376           v18.06.1-ce   
        vm2    -        virtualbox   Running   tcp://192.168.99.101:2376           v18.06.1-ce   

        $ curl http://192.168.99.101:8080

    But you should note that getting the page from another VM ``vm1`` works as well even though the container is not running on it:

    .. code-block:: bash

        $ curl http://192.168.99.100:8080

    This is the magic of Docker Swarm's `routing mesh <https://docs.docker.com/engine/swarm/ingress/>`_ mechanism, which provides intrinsic feature of load balance and failover.

#. Since we are running this cluster on virtual machines, the web service is not accessible via the host's IP address.  The workaround we are doing below is to start a NGINX container on the host, and proxy the HTTP request to the web service running on the VMs.

    .. code-block:: bash

        $ cd /home/tg/honlee/tmp/swarm
        $ docker-compose -f docker-compose.proxy.yml up -d
        $ docker-compose -f docker-compose.proxy.yml ps
        Name              Command          State         Ports       
        -----------------------------------------------------------------
        swarm_proxy_1   nginx -g daemon off;   Up      0.0.0.0:80->80/tcp

    .. note::
        This workaround is very practicle for production.  Imaging you have a Swarm cluster running in a private network, and you want to expose the services to the Internet.  What you need is an gateway machine proxying requests from Internet to internal Swarm cluster. `NGINX <https://www.nginx.com/>`_ is a very powerful engine for proxying HTTP traffics, providing capability of load balancing and failover.

        You may want to have a look of the NGINX configuration in the ``proxy.conf.d`` directory (part of the downloaded files) to see how to ride on the Docker Swarm's routing mesh feature for load balance and failover.

Sharing Docker images
^^^^^^^^^^^^^^^^^^^^^

One benefit of using Docker swarm is that one can bring down a Docker node and the whole system will migrate all containers on it to other nodes.  This feature assumes that there is a central place where the Docker images can be pulled from.

In the example docker-compose file above, we make use of the official MySQL image from the DockerHub and the ``php:centos`` image from a private registry, ``docker-registry.dccn.nl``.  This private registry requires user authentication.  This is why we need to login to this registry before starting the application stack.

Overlay network
^^^^^^^^^^^^^^^

- "Deep dive in Docker Overlay Networks": https://www.youtube.com/watch?v=b3XDl0YsVsg https://www.youtube.com/watch?v=IgDLNcpmfqI

Container placement
^^^^^^^^^^^^^^^^^^^

Network routing mesh
^^^^^^^^^^^^^^^^^^^^

- figure illustration the routing mesh, using the example of the same docker-compose file

Service management
==================

Scaling
^^^^^^^

Once can also scale the service by updating the number of *replicas* of a service.  Let's scale the ``webapp_web`` service to 2 replicas.

.. code-block:: bash

    $ docker-machine ssh vm1

    [vm1]$ docker service ls
    ID                  NAME                MODE                REPLICAS            IMAGE                                     PORTS
    vod0xeqlrhn4        webapp_db           replicated          1/1                 mysql:latest                              
    lnpmd1ulg2tq        webapp_web          replicated          1/1                 docker-registry.dccn.nl:5000/php:centos   *:8080->80/tcp

    [vm1]$ docker service update --replicas 2 webapp_web
    [vm1]$ docker service ls
    ID                  NAME                MODE                REPLICAS            IMAGE                                     PORTS
    vod0xeqlrhn4        webapp_db           replicated          1/1                 mysql:latest                              
    lnpmd1ulg2tq        webapp_web          replicated          2/2                 docker-registry.dccn.nl:5000/php:centos   *:8080->80/tcp

Rotating update
^^^^^^^^^^^^^^^

Since we have two ``webapp_web`` replicas running in the cluster, we could now perform a rotating update without downtime.

Let's make some changes in our web interface codes, e.g.

.. code-block:: bash

    [vm1]$ cp app_new/search*.php app
    [vm1]$ cp app_new/navbar.php app/include/navbar.php

The new codes add search functionality to the web application.  Following to that, we will have to restart the service, using the ``docker service update`` command:

.. code-block:: bash

    [vm1]$ docker service update --force webapp_web

From the output of the command above, you may notice that the update on the two replicas is already a rotating update.  For instance, Docker only updates one replica at a time.  During the update, other replicas are still available for serving requests.

Node management
===============

Sometimes we need to perform maintenance on a Docker node.  In the Docker swarm cluster, one first drains the containers on the node to be maintained.  This is done by setting the node's availability to ``drain``.  For example, if we want to perform maintenance on ``vm2``:

    .. code-block:: bash

        [vm1]$ docker node update --availability drain vm2
        [vm1]$ docker node ls
        ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
        svdjh0i3k9ty5lsf4lc9d94mw *   vm1                 Ready               Active              Leader              18.06.1-ce
        m5r1j48nnl1u9n9mbr8ocwoa3     vm2                 Ready               Drain               Reachable           18.06.1-ce

Once you have done that, you will notice all containers running on ``vm2`` are automatically moved to ``vm1``.

    .. code-block:: bash
        :emphasize-lines: 3,4

        [vm1]$ docker stack ps webapp
        ID                  NAME                IMAGE                                     NODE                DESIRED STATE       CURRENT STATE             ERROR               PORTS
        9lmdd6cg2y74        webapp_web.1        docker-registry.dccn.nl:5000/php:centos   vm1                 Ready               Ready 3 seconds ago                           
        ptq7l3kw6suk         \_ webapp_web.1    docker-registry.dccn.nl:5000/php:centos   vm2                 Shutdown            Running 3 seconds ago                         
        zk9vcd5svqr2         \_ webapp_web.1    docker-registry.dccn.nl:5000/php:centos   vm2                 Shutdown            Shutdown 11 minutes ago                       
        qa7ixd3f0c2j        webapp_db.1         mysql:latest                              vm1                 Running             Running 16 minutes ago                        
        845mdx95dxho        webapp_web.2        docker-registry.dccn.nl:5000/php:centos   vm1                 Running             Running 11 minutes ago                        
        akjedulcbtt5         \_ webapp_web.2    docker-registry.dccn.nl:5000/php:centos   vm1                 Shutdown            Shutdown 11 minutes ago

After the maintenance work, just set the node's availability to ``active`` again:

    .. code-block:: bash

        [vm1]$ docker node update --availability activate vm2

