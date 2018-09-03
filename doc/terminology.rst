Terminology
***********

**Docker engine** is the software providing the libraries, services and toolsets of Docker. It enables computer to build Docker images and lauch Docker containers. Docker engine has two different editions: the community edition (**Docker CE**) and the enterprise edition (**Docker EE**).

**Docker node/host** is a physical or virtual computer on which the Docker engine is enabled.

**Docker swarm cluster** is a group of Docker nodes.  Each node has either a **manager** or **worker** role in the cluster. At least one master node is required for a docker swarm cluster to function.

**Manager** refers to the node maintaining the state of a docker swarm cluster. There can be one or more managers in a cluster. The more managers in the cluster, the higher level of the cluster fault-tolerance.

**Worker** refers to the node sharing the container workload in a docker swarm cluster.

**Docker image** is an executable package that includes everything needed to run an application--the code, a runtime, libraries, environment variables, and configuration files.

**Docker container** is a runtime instance of an image. A container is launched by running an Docker image.

**Docker service** is a logical representation of multiple replicas of the same container.  Replicas are used for service load-balancing and/or failover.

**Docker stack** is a set of linked **Docker services**.
