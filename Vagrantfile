# -*- mode: ruby -*-
# vi: set ft=ruby :
Vagrant.configure("2") do |config|
    config.vm.box = "ubuntu/trusty64"
    config.vm.define :lamp do |lamp_config|
        lamp_config.vm.hostname = 'lamp'
        lamp_config.vm.network "private_network", ip: "192.168.50.11"
        lamp_config.vm.provision :shell, path: "bootstrap.sh"
    end
    config.vm.provider :virtualbox do |vb_config|
        vb_config.name = "Vagrant - Ubuntu LAMP"
    end
    config.vm.synced_folder "./", "/var/www/html/ADT" , owner: "www-data", group: "www-data", mount_options: ["dmode=775,fmode=666"] , create:true
end
