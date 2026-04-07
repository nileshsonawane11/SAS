# Supervision Allocation System

An enterprise-ready web application to manage **exam supervision allocation**, invigilator scheduling, and exam slot management with a scalable deployment setup.

* Project Overview

The Supervision Allocation System automates the process of assigning faculty members to exam duties while ensuring:

* No scheduling conflicts
* Balanced workload distribution
* Efficient exam management

---

# 🛠 Tech Stack

* Backend: PHP
* Database: MySQL
* Server: Nginx / Apache (XAMPP)
* OS: Ubuntu / Windows

---

# System Requirements

## For XAMPP:

* XAMPP (PHP ≥ 8.0, MySQL)
* Windows / Linux

## For Production:

* Ubuntu Server (20.04+)
* Nginx
* PHP-FPM
* MySQL Server
* Static IP Address

---------------------------------------------------------------------------------------------------

# Installation Guide

🔹 Step 1: Clone Project (On command Prompt Or IDE)

git clone https://github.com/nileshsonawane11/SAS.git

cd SAS

---------------------------------------------------------------------------------------------------

# Setup on XAMPP (Local Development)

## Step 1: Install XAMPP

Download and install XAMPP from official site.

Start:

* Apache
* MySQL

---

## Step 2: Move Project


* Copy project folder to:
C:\xampp\htdocs\

---

## Step 3: Setup Database

* Open phpMyAdmin:

http://localhost/phpmyadmin

* Import:

Open database → Import → Select `database.sql`

---

## Step 4: Configure Database

* Edit:

/Backend/config.php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "supervision_system";

---

## Step 5: Run Project

* Open browser:

http://localhost/SAS

---

## ✅ XAMPP Setup Complete


---------------------------------------------------------------------------------------------------

# 🌐 Setup on Nginx Server with Static IP (Production)

## Step 1: Update System

sudo apt update && sudo apt upgrade -y

---

## Step 2: Install Required Packages

sudo apt install nginx mysql-server php php-fpm php-mysql -y


---

## Step 3: Set Static IP (WiFi / Ethernet)

* Edit Netplan config:

sudo nano /etc/netplan/01-netcfg.yaml


* Example configuration:


network:
  version: 2
  renderer: networkd
  ethernets:
    eth0:
      dhcp4: no
      addresses:
        - 192.168.1.100/24
      gateway4: 192.168.1.1
      nameservers:
        addresses: [8.8.8.8, 8.8.4.4]


* Apply:

sudo netplan apply

---

## Step 4: Setup MySQL Database


sudo mysql

CREATE DATABASE supervision_system;
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'strongpassword';
GRANT ALL PRIVILEGES ON supervision_system.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;


* Import database:


mysql -u appuser -p supervision_system < database.sql


---

## Step 5: Deploy Project

sudo mkdir -p /var/www/SAS
sudo cp -r * /var/www/SAS

---

## Step 6: Set Permissions

sudo chown -R www-data:www-data /var/www/SAS
sudo chmod -R 755 /var/www/SAS

---

## Step 7: Configure Nginx

sudo nano /etc/nginx/sites-available/SAS


server {
    listen 80;
    server_name 192.168.1.100;

    root /var/www/SAS;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
}

* Enable site:

sudo ln -s /etc/nginx/sites-available/SAS /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx


---

## Step 8: Update Project Config

$host = "localhost";
$user = "appuser";
$pass = "strongpassword";
$db   = "supervision_system";

---

## Step 9: Firewall Setup


sudo ufw allow 'Nginx Full'
sudo ufw enable


---

## Step 10: Access Project

http://192.168.1.100

---

## ✅ Production Setup Complete



---

# ❗ Troubleshooting

## Nginx not working:

sudo systemctl restart nginx


## PHP-FPM issue:

sudo systemctl restart php8.1-fpm


# 👨‍💻 Author

Supervision Allocation System

Government Polytechnic, Nashik - 422101
Third Year Students - Information Technology (2023-26)
* Sonawane Nilesh Kiran
* Patil Kastubh Sanjay
* Jadhav Prabhavati Prasad
* Sonawane Ketki Nandu
