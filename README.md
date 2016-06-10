Apache2 setup with php and curl. I also have htaccess running so that any url past api/ will redirect to the api.

The route I took to set up a quick local server. Run at ip `localhost`
sudo apt install apache2
sudo nano /etc/apache2/sites-available/000-default.conf 

Add these lines:
```
<Directory /var/www/html>
        AllowOverride All
</Directory>
```

sudo a2enmod rewrite
sudo apt-get install php5-cli
sudo apt-get install php5 libapache2-mod-php5 php5-mcrypt
sudo apt-get install php5-curl

sudo /etc/init.d/apache2 restart

#Api commands

The api commands will accept any url past `api/` but with the Interview test I only allowed `GET localhost/api/product/search?name={:productName:}`. 
Product name is a variable. Please use any product name you would like. I used "Ipod" and had wonderful, quick results. 
I decided that if "name" in the search was left blank, I would leave it up to the storefront's api to decide if it wanted to return a search or not.

# BlueSpurs-Php

BlueSpurs Interview Test
Task 1

Your task is to create a RESTful web service endpoint that allows a client to input a product name as a GET query parameter and returns the cheapest product.

Provided below are API keys for the BestBuy and Walmart APIs. Your result should return the best (minimum) price for the product and which store to buy it from. If there are multiple products, always return the lowest priced product. For example:

Request

GET /product/search?name=ipad

Example Response

200 OK

{
    "productName": "iPad Mini",
    "bestPrice": "150.00",
    "currency": "CAD",
    "location": "Walmart"
}


Required API Keys

BestBuy: pfe9fpy68yg28hvvma49sc89

Walmart: rm25tyum3p9jm9x9x7zxshfa