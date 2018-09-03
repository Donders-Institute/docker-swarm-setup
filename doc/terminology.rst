Terminology
***********

**Docker node/host** is a physical or virtual computer that is docker-engine-enabled.

**Docker swarm cluster** is a group of docker-engine-enabled nodes.  Each node has either a **manager** or **worker** role in the cluster. At least one master node is required for a docker swarm cluster to function.

**Manager** refers to the node maintaining the state of a docker swarm cluster. There can be one or more managers in a cluster. The more managers in the cluster, the higher level of the cluster fault-tolerance.

**Worker** refers to the node sharing the container workload in a docker swarm cluster.

**Docker service** is a logical representation of multiple replicas of the same container.  Replicas are used for service load-balancing and/or failover.

**Docker stack** is a set of linked **services**.