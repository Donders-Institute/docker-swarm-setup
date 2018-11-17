#!/bin/bash

function usage {
    echo "$0 <vm_name>"
}

function bootvm {
    if [ $# -ne 1 ]; then
        echo "machine name not specified."
        return 1
    fi

    # latest version of boot2docker is broken, force the useage to earlier version.
    # https://github.com/docker/machine/issues/4608
    docker-machine create --driver virtualbox --virtualbox-boot2docker-url https://github.com/boot2docker/boot2docker/releases/download/v18.06.1-ce/boot2docker.iso $1
    return $?
}

function add_registry_crt {
    if [ $# -ne 2 ]; then
        return 1
    fi
    crt_host=$1
    vm=$2
    echo
    echo "adding $crt_host to $vm ..."
    docker-machine ssh $vm "sudo mkdir -p /var/lib/boot2docker/certs/"
    docker-machine scp $crt_host $vm:
    docker-machine ssh $vm "sudo mv $(basename $crt_host) /var/lib/boot2docker/certs/"
    echo
    echo "rebooting $vm ..."
    docker-machine restart $vm
    return $?
}

if [ $# -lt 1 ]; then
    usage
    exit 1
fi

for vm in $@; do
    # step 1. start a vm with the given machine name
    bootvm $vm
    if [ $? -ne 0 ]; then
        echo "fail creating vm: $vm"
        exit 2
    fi

    # step 2. update vm with registry certificate
    add_registry_crt /mnt/install/kickstart-7/docker/docker-registry.crt $vm
done

docker-machine ls
