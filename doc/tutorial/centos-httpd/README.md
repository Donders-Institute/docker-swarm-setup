# Container basic

Through building and spinning off the Apache HTTPd container with PHP support, you will learn:

- the docker workflow and basic UI commands,
- network port exporting,
- data persistency

## Build image

```bash
docker build -t httpd:centos .
```

## Check image layers, and aligh the Dockerfile with the layers

```bash
docker history httpd:centos
```

## Start a container using the image, note the port

```bash
docker run --rm -d -p 8080:80 --name myhttpd httpd:centos
docker ps
docker exec -it bash myhttpd bash
```

## Play around

- check the web page: open http://localhost:8080 in the browser.
- find from where the index.html is served, that's replace it within the container:

```bash
   cat > /var/www/html/index.html <<EOF
<html>
<head></head>
<body>
<h2>Welcome to my first HTML page served by Docker</h2>
<form action="hello.php" method="POST">
    Your name: <input type="text" name="name"></br>
    Your email: <input type="text" name="email"></br>
<input value="submit" name="submit" type="submit">
</form>
</body>
</html>
EOF
```

- find the location where the httpd log is stored

## Let's restart the container

```bash
docker stop myhttpd
docker run --rm -d -p 8080:80 --name myhttpd httpd:centos
```

Question: what will we see as the home page? The CentOS default, or the one we edited?

## How to make the HTML file persistent?

### make the file static in the container

### use volume (image)

```bash
docker volume create htmldoc
docker run --rm -d -p 8080:80 --name myhttpd -v htmldoc:/var/www/html httpd:centos
```

TODO: recreate the index.html file within the container, restart the container and see the index.html is persistent.

### use volume (mapping to host's filesystem)

```bash
docker run --rm -d -p 8080:80 --name myhttpd -v $PWD/html:/var/www/html httpd:centos
```

TODO: change the files on the host, and see if it's updated in the container by reloading the page

TODO: how about restarting the container with

```bash
docker run --rm -d -p 8080:80 --name myhttpd -v $PWD/htmldoc:/var/www/html:ro httpd:centos
```

### The httpd log is in /var/log/httpd within the container

Question: how to preserve the log on the host?

```bash
docker run --rm -d -p 8080:80 --name myhttpd -v $PWD/htmldoc:/var/www/html:ro -v $PWD/log:/var/log/httpd httpd:centos
```

## Update the Dockerfile to install php

```bash
docker build -t php:centos -f Dockerfile_php .
docker history php:centos
docker run --rm -d -p 8088:80 --name myhttpd-php -v $PWD/htmldoc:/var/www/html:ro -v $PWD/log:/var/log/httpd php:centos
```

## Stop the container

```bash
docker stop myhttpd
docker stop myhttpd-php
```

## Remove the image

```
docker rmi httpd:centos
docker rmi php:centos
```

## Let's make use the official MySQL container

- https://hub.docker.com/\_/mysql/

```bash
docker run --rm --name mysql \
-e MYSQL_ROOT_PASSWORD=admin123 \
-e MYSQL_DATABASE=registry \
-e MYSQL_USER=demo \
-e MYSQL_PASSWORD=demo123 \
-v $PWD/initdb.d:/docker-entrypoint-initdb.d \
-v $PWD/data:/var/lib/mysql
mysql
```

# docker-compose single-host container orchestration
Through building and spinning off the first Apache/Php/MySQL application stack with docker-compose, you will learn:

- the docker compose YAML file read through

graphical illustration of the application infrastructure setup, and a briefing on the user registration application.

- the docker compose file and the UI commands

```bash
docker-compose up

docker-compose up -d
docker-compose logs -f
```

# multi-host container orchestration (Docker Swarm)
The alternative is Kubernetes from google. Kubernetes is more targeting for Enterprises (very large scale deployment); while swarm is more focusing on ease-of-use for setup and management.

We will construct a swarm cluster from scratch.

We will modify the docker-compose file for docker swarm stack deployment. We will spin off multiple replicas for the web layer, and test the load-balancing feature and dynamic scaling feature.
