Swarm cluster operation procedures
**********************************

Cluster initialisation
======================

.. note::
    In most of cases, there is no need to initialse another cluster.

Before there is anything, a cluster should be initialised.  Simply run the command below on a docker node to initialise a new cluster:

.. code-block:: bash

    $ docker swarm init

Force a new cluster
^^^^^^^^^^^^^^^^^^^

In case the quorum of the cluster is lost (and you are not able to bring other manager nodes online again), you need to reinitiate a new cluster forcefully.  This can be done on one of the remaining manager node using the following command:

.. code-block:: bash

    $ docker swarm init --force-new-cluster

After this command is issued, a new cluster is created with only one manager (i.e. the one on which you issued the command). All remaining nodes become workers.  You will have to add additional manager nodes manually.

.. Tip::
    Depending on the number of managers in the cluster, the required quorum (and thus the level of fail tolerance) is different.  Check `this page <https://docs.docker.com/engine/swarm/admin_guide/#operate-manager-nodes-in-a-swarm>`_ for more information.

Node operation
==============

System provisioning
^^^^^^^^^^^^^^^^^^^

The operating system and the docker engine on the node is provisioned using the DCCN linux-server kickstart.  The following kickstart files are used:

* ``/mnt/install/kickstart-*/ks-*-dccn-dk.cfg``: the main kickstart configuration file
* ``/mnt/install/kickstart-*/postkit-dccn-dk/script-selection``: main script to trigger post-kickstart scripts
* ``/mnt/install/kickstart-*/setup-docker-*``: the docker-specific post-kickstart scripts

**Configure devicemapper to direct-lvm mode**

    By default, the `devicemapper storage drive <https://docs.docker.com/engine/userguide/storagedriver/device-mapper-driver/>`_ of docker is running the loop-lvm mode which is known to be suboptimal for performance.  In a production environment, the direct-lvm mode is recommended.  How to configure the devicemapper to use direct-lvm mode is described `here <https://docs.docker.com/engine/userguide/storagedriver/device-mapper-driver/#configure-direct-lvm-mode-for-production>`_.
    
    Hereafter is a script summarizing the all steps.  The script is also available at ``/mnt/docker/scripts/node-management/docker-thinpool.sh``.
    
    .. code-block:: bash
        :linenos:
        
        #!/bin/bash

        if [ $# -ne 1 ]; then
            echo "USAGE: $0 <device>" 
            exit 1
        fi

        # get raw device path (e.g. /dev/sdb) from the command-line argument 
        device=$1

        # check if the device is available
        file -s ${device} | grep 'cannot open'
        if [ $? -eq 0 ]; then
            echo "device not found: ${device}"
            exit 1
        fi

        # install/update the LVM package
        yum install -y lvm2

        # create a physical volume on device
        pvcreate ${device}

        # create a volume group called 'docker'
        vgcreate docker ${device}

        # create logical volumes within the 'docker' volume group: one for data, one for metadate
        # assign volume size with respect to the size of the volume group
        lvcreate --wipesignatures y -n thinpool docker -l 95%VG
        lvcreate --wipesignatures y -n thinpoolmeta docker -l 1%VG
        lvconvert -y --zero n -c 512K --thinpool docker/thinpool --poolmetadata docker/thinpoolmeta

        # update the lvm profile for volume autoextend
        cat >/etc/lvm/profile/docker-thinpool.profile <<EOL
        activation {
            thin_pool_autoextend_threshold=80
            thin_pool_autoextend_percent=20
        }
        EOL

        # apply lvm profile
        lvchange --metadataprofile docker-thinpool docker/thinpool

        lvs -o+seg_monitor

        # create daemon.json file to instruct docker using the created logical volumes
        cat >/etc/docker/daemon.json <<EOL
        {
            "insecure-registries": ["docker-registry.dccn.nl:5000"],
            "storage-driver": "devicemapper",
            "storage-opts": [
                 "dm.thinpooldev=/dev/mapper/docker-thinpool",
                 "dm.use_deferred_removal=true",
                 "dm.use_deferred_deletion=true"
            ]
        }
        EOL

        # remove legacy deamon configuration through docker.service.d to avoid confliction with daemon.json
        if [ -f /etc/systemd/system/docker.service.d/swarm.conf ]; then
            mv /etc/systemd/system/docker.service.d/swarm.conf /etc/systemd/system/docker.service.d/swarm.conf.bk
        fi 

        # reload daemon configuration
        systemctl daemon-reload

Join the cluster
^^^^^^^^^^^^^^^^

After the docker daemon is started, the node should be joined to the cluster.  The command used to join the cluster can be retrieved from one of the manager node, using the command:

.. code-block:: bash

    $ docker swarm join-token manager

.. note::
    The example command above obtains the command for joining the cluster as a manager node.  For joining the cluster as a worker, replace the ``manager`` on the command with ``worker``.

After the command is retrieved, it should be run on the node that is about to join to the cluster.

Leave the cluster
^^^^^^^^^^^^^^^^^

Run the following command on the node that is about to leave the cluster.

.. code-block:: bash

    $ docker swarm leave

If the node is a manager, the option ``-f`` (or ``--force``) should also be used in the command.

.. note::
    The node leaves the cluster is **NOT** removed automatically from the node table.  Instead, the node is marked as ``Down``.  If you want the node to be removed from the table, you should run the command ``docker node rm``.

.. tip::
    An alternative way to remove a node from the cluster directly is to run the ``docker node rm`` command on a manager node.

.. _promote_demote_node:

Promote and demote node
^^^^^^^^^^^^^^^^^^^^^^^

Node in the cluster can be demoted (from manager to worker) or promoted (from worker to manager).  This is done by using the command:

.. code-block:: bash

    $ docker node promote <WorkerNodeName>
    $ docker node demote <ManagerNodeName>
    
Monitor nodes
^^^^^^^^^^^^^

To list all nodes in the cluster, do

.. code-block:: bash

    $ docker node ls
    
To inspect a node, do

.. code-block:: bash

    $ docker node inspect <NodeName>
    
To list tasks running on a node, do

.. code-block:: bash

    $ docker node ps <NodeName>

Service operation
=================

In swarm cluster, a service is created by deploying a container in the cluster.  The container can be deployed as a singel instance (i.e. task) or multiple instances to achieve service failover and load-balancing.

start a service
^^^^^^^^^^^^^^^

To start a service in the cluster, one uses the ``docker service create`` command.  Hereafter is an example for starting a ``nginx`` web service in the cluster using the container image ``docker-registry.dccn.nl:5000/nginx:1.0.0``:

.. code-block:: bash
    :linenos:

    $ docker service create \
    --name webapp-proxy \
    --replicas 2 \
    --publish 8080:80/tcp \
    --constaint "node.labels.function == production" \
    --mount "type=bind,source=/mnt/docker/webapp-proxy/conf,target=/etc/nginx/conf.d" \
    docker-registry.dccn.nl:5000/nginx:1.0.0

Options used above is explained in the following table:

===============  ========
   option        function
===============  ========
``--name``       set the service name to ``webapp-proxy``
``--replicas``   deploy ``2`` tasks in the cluster for failover and loadbalance
``--publish``    map internal ``tcp`` port ``80`` to ``8080``, and expose it to the world
``--constaint``  restrict the tasks to run on nodes labled with ``function = production``
``--mount``      mount host's ``/mnt/docker/webapp-proxy/conf`` to container's ``/etc/nginx/conf.d``
===============  ========

More options can be found `here <https://docs.docker.com/engine/reference/commandline/service_create/>`_.

.. _remove_service:

remove a service
^^^^^^^^^^^^^^^^

Simply use the ``docker service rm <ServiceName>`` to remove a running service in the cluster.  It is not normal to remove a productional service.

.. Tip::
    In most of cases, you should consider **updating the service** rather than removing it.

update a service
^^^^^^^^^^^^^^^^

It is very common to update a productional service.  Think about the following conditions that you will need to update the service:

* a new node is being added to the cluster, and you want to move an running service on it, or
* a new container image is being provided (e.g. software update or configuration changes) and you want to update the service to this new version, or
* you want to create more tasks of the service in the cluster to distribute the load.

To update a service, one uses the command ``docker service update``.  The following example update the ``webapp-proxy`` service to use a new version of nginx image ``docker-registry.dccn.nl:5000/nginx:1.2.0``:

.. code-block:: bash

    $ docker service update \
    --image docker-registry.dccn.nl:5000/nginx:1.2.0 \
    webapp-proxy

More options can be found `here <https://docs.docker.com/engine/reference/commandline/service_update/>`_.

monitor services
^^^^^^^^^^^^^^^^

To list all running services:

.. code-block:: bash

    $ docker service ls

To list tasks of a service:

.. code-block:: bash

    $ docker service ps <ServieName>

To inspect a service:

.. code-block:: bash

    $ docker service inspect <ServiceName>

Stack operation
===============

A stack is usually defined as a group of related services. The defintion is described using the `docker-compose version 3 specification <https://docs.docker.com/compose/compose-file/>`_.

Here is :ref:`an example <docker-compose-data-stager>` of defining the three services of `the DCCN data-stager <https://github.com/Donders-Institute/data-stager>`_.

Using the ``docker stack`` command you can manage multiple services in one consistent manner.

deploy (update) a stack
^^^^^^^^^^^^^^^^^^^^^^^

Assuming the docker-compose file is called ``docker-compose.yml``, to launch the services defined in it in the swarm cluster is:

.. code-block:: bash

    $ docker stack deploy -c docker-compose.yml <StackName>

When there is an update in the stack description file (e.g. ``docker-compose.yml``), one can use the same command to apply changes on the running stack.

.. note::
    Every stack will be created with an overlay network in swarm, and organise services within the network.  The name of the network is ``<StackName>_default``.

.. _remove_stack:

remove a stack
^^^^^^^^^^^^^^

Use the following command to remove a stack from the cluster:

.. code-block:: bash

    $ docker stack rm <StackName>

Monitor stacks
^^^^^^^^^^^^^^

To list all running stacks:

.. code-block:: bash

    $ docker stack ls

To list all services in a stack:

.. code-block:: bash

    $ docker stack services <StackName>

To list all tasks of the services in a stack:

.. code-block:: bash

    $ docker stack ps <StackName>

Emergancy shutdown
==================

.. note::
    The emergency shutdown should take place **before** the network and the central storage are down.

#. login to one manager
#. :ref:`demote <promote_demote_node>` other managers
#. remove running :ref:`stacks <remove_stack>` and :ref:`services <remove_service>`
#. shutdown all workers
#. shutdown the manager

Reboot from shutdown
^^^^^^^^^^^^^^^^^^^^

#. boot on the manager node (the last one being shutted down)
#. boot on other nodes
#. :ref:`promote nodes <promote_demote_node>` until a desired number of managers is reached
#. deploy firstly the docker-registry stack

   .. code-block:: bash

       $ cd /mnt/docker/scripts/microservices/registry/
       $ sudo ./start.sh
       
   .. note::
       The docker-registry stack should be firstly made available as other services/stacks will need to pull container images from it.

#. deploy other stacks and services

Disaster recovery
=================

Hopefully there is no need to go though it!!

For the moment, we are not `backing up the state of the swarm cluster <https://docs.docker.com/engine/swarm/admin_guide/#back-up-the-swarm>`_.  Given that the container data has been stored (and backedup) on the central storage, the impact of lossing a cluster is not dramatic (as long as the container data is available, it is already possible to restart all services on a fresh new cluster).

Nevertheless, `here <https://docs.docker.com/engine/swarm/admin_guide/#recover-from-disaster>`_ is the official instruction of disaster recovery.
