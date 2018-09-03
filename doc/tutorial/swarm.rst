Tutorial: Docker swarm
**********************

In the previous tutorial, we have learnd about container orchestration for running a service stack with load-balancing feature.  However, the whole stack is running on a single Docker node, meaning that the service will be interrupted when the node is down, a single-point of failure.

In this tutorial, we are going to eliminate this single-point of failure by orchestrating containers on a cluster of Docker nodes, a Docker swarm cluster.  Apart from that, you will learn:

- how to create a swarm cluster from scratch,
- how to label nodes in a cluster,
- how to deploy a stack in a swarm cluster.

.. tip::
    Docker swarm is not the only solution for orchestrating containers on multiple computers.  A platform called `Kubenetes <https://kubernetes.io/>`_ was originally developed by Google and used in the many container infrastructure.

The architecture
================

The architecture of the Docker swarm cluster is relatively simple comparing to other distributed container orchestration platforms. As illustrated in :numref:`swarmarchitecture`, there are two types of nodes: *manager* and *worker*.

By design, managers are no difference to the workers in sharing the container's load except that they are also responsible for maintaining the status of the cluster on a distributed state store.  Managers exchange information with each other in order to maitain sufficient quorum of the `Raft consensus algorithm <https://en.wikipedia.org/wiki/Raft_(computer_science)>`_ for cluster fault tolerance.

.. figure:: ../figures/swarm-architecture.png
    :name: swarmarchitecture
    :alt: the swarm architecture.

    the swarm architecture, an illustration from `the docker blog <https://blog.docker.com/2016/06/docker-1-12-built-in-orchestration/>`_.

Creating a cluster
==================

Docker swarm is a **mode** supported natively by the Docker engine since version 1.12 in 2016. Given a group of independent Docker nodes, one can easily start create a cluster using the command:

.. code-block:: bash

    $ docker swarm init

After that you could check the cluster using

.. code-block:: bash

    $ docker node ls
    ID                            HOSTNAME            STATUS              AVAILABILITY        MANAGER STATUS      ENGINE VERSION
    pyiykevht7pc24s7wxvgkscrn *   pl-torque.dccn.nl   Ready               Active              Leader              18.03.1-ce

Et voilà! You have just created a swarm cluster, as simple as one command... As you have noticed, it is a one-node cluster.  In addition, you see that the node is by default a manager. Since it is the only manager, it is also the leading manager (**Leader**).

