================
bootstrap 2 VMs
================
docker-machine-bootstrap.sh vm1 vm2
docker-machine ssh vm1
docker-machine ssh vm2

======================
on VM1 as manager node
======================
docker swarm init --advertise-addr 192.168.99.100
docker node ls
docker swarm join-token worker

======================
on VM2 as worker node
======================
docker swarm join --token ... 

======================
on VM1 as manager node
======================
docker node ls

docker node promote vm2 
docker node ls

docker node demote vm2
docker node ls

docker node update --label-add os=linux vm1
docker node update --label-add os=linux vm2

docker stack deploy -c docker-compose.swarm.yml --with-registry-auth webapp

docker stack ps webapp

docker service scale webapp_web=2

=============
proxy on host
=============
curl http://192.168.99.100:8080
curl http://192.168.99.101:8080

docker-compose -f docker-compose.proxy.yml up -d

docker service update --constraint-rm node.hostname=vm1 webapp_db

docker node update --availability drain vm1
