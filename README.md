# Feedoptimise_CatalogExport

This module is used to extract catalog data from Magento2 stores.

## Getting Started

### Prerequisites

There are a few things needed prior to installing this module:
```
- Magento2 Installed
- Magento2 Store Setup
- PHP 7+ installed
```
### !! BACKUP !!

**Make sure to backup your Magento store before running the steps below.** 

*Our plugin has been tested across lots of installations but there could be some custom/bespoke changes in your Magento code or other unexpected scenarios which can cause your Magento to crash during or after the installation thus it's strongly advised to have a backup in place so you can easily restore should some issues arise.*

## Installing via Composer (recommended)
The first step is to add our repo to your composer.json, by running the following (from the root of your project in command line):
```
composer require feedoptimise/magento2-catalog-export
```
Now you can enable our module by running the following (from the root of your project in command line):
```
bin/magento module:enable Feedoptimise_CatalogExport
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```
Then check the module has been enabled correctly, by running the following (from the root of your project in command line):
```
bin/magento module:status
```
Once the module is installed, you should then login to the admin panel of your store and visit the following page:
```
Stores > Configuration > Feedoptimise > Catalog Export
```
Once on the extension config page, please ensure the settings are as follows. The security token will be provided by Feedoptimise, prior or after the install.
```
Module Enable = Yes
Security Token = <SECURITY_TOKEN_HERE>
```

Once installed, we (Feedoptimise) will confirm it is working correctly and our system will begin extracting data for your product feed.

## Installing (manually)

The first step to install this module, is to create the following directory path on your server **app/code/Feedoptimise/CatalogExport**. Then you can copy all of the contents inside the **src** folder into the newly created directory.

The directory structure should look like:
```
app/
    code/
        Feedoptimise/
            CatalogExport/
                Controller/
                    ...
                etc/
                    ...
                Helper/
                    ...
                composer.json
                README.md
                registration.php

```

Once the folder has been placed inside the **/app/code** directory, you can then run the following (from the root of your project in command line):
```
bin/magento module:enable Feedoptimise_CatalogExport
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Then check the module has been enabled correctly, by running the following (from the root of your project in command line):
```
bin/magento module:status
```

Once the module is installed, you should then login to the admin panel of your store and visit the following page:
```
Stores > Configuration > Feedoptimise > Catalog Export
```
Once on the extension config page, please ensure the settings are as follows. The security token will be provided by Feedoptimise, prior or after the install.
```
Module Enable = Yes
Security Token = <SECURITY_TOKEN_HERE>
```

Once installed, we (Feedoptimise) will confirm it is working correctly and our system will begin extracting data for your product feed.

## Updating (installed via composer)
To update the module, run the following (from the root of your project in command line).
```
composer update feedoptimise/magento2-catalog-export
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Uninstalling (installed via composer)
To uninstall our extension, run the following (from the root of your project in command line):
```
bin/magento module:disable Feedoptimise_CatalogExport
bin/magento setup:upgrade
composer remove feedoptimise/magento2-catalog-export
```

## Uninstalling (installed manually)
To uninstall our extension, run the following (from the root of your project in command line):
```
bin/magento module:disable Feedoptimise_CatalogExport
composer remove Feedoptimise/CatalogExport
bin/magento setup:upgrade
```

## Author

* **Joe Yates (Feedoptimise) ** - https://www.feedoptimise.com

## License

Usage is subject to permission.
Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
