# PayU Payment Gateway MEA for Magento v2.4+ extension #

This guide details how to install the PayU Payment Gateway for Magento v2.4+ extension. Plugin tested on Magento v2.4 and above

## Prerequisites
* Magento 2.4+ application
* SSH access to server hosting Magento application

## Dependencies

In addition to Magento system requirements, this extension requires the following PHP extensions in order to work properly:

- [`soap`](https://php.net/manual/en/book.soap.php)
- [`xml`](https://php.net/manual/en/book.xml.php)

## Installation

### Via Composer

You can install the extension via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require payu-mea/gateway-magento
```
or add
```bash
payu-mea/gateway-magento: "*"
```
to the **require** section of your composer.json and run `composer update`. To enable extension after installation you need to execute the following command in the root directory of your magento application.

```bash
bin/magento module:enable --clear-static-content PayU_Gateway
bin/magento setup:upgrade
bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
bin/magento cache:clean
```

### from GitHub repository

1) Download latest tagged version of plugin from GitHub repository
2) Unpack the downloaded archive
3) Copy the files from *"payu-mea-gateway-magento-master"* directory to your Magento 2.4 application inside the directory *app\code\PayU\Gateway*. If the directory doesn't exist you will need to create it before copying extension files.

After copying the files you need to enable the extension by executing the following command from the root directory of your magento application:
```bash
php bin/magento module:enable --clear-static-content PayU_Gateway
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:clean
```

## Configuration
To configure the extension, you have to navigate to **Stores > Configuration > Sales > Payment Methods** and find PayU Gateway
extension listed among other payment methods

For Kenyan payment methods (Mpesa, Equitel, Airtel Money, Mobile Banking) - configuration in **Stores > Configuration > Customers > Customer Configuration > Name and Address Options > Show Telephone** must be set to "Required"
