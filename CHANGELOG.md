### 1.2.35 (2022-08-11)
  1. New Endpoints with POST request support
  2. Support PHP 8

### 1.2.34 (2022-07-20)
  1. Clean up code

### 1.2.33 (2022-07-20)
  1. Added endpoint to check health status of indexer and cache

### 1.2.32 (2022-07-15)
  1. Product Bundle - Added attribute "bundle_option_id" and "bundle_option_title" to variants

### 1.2.31 (2022-05-13)
  1. CSP whitelist for trucking code

### 1.2.30 (2022-04-07)
  1. Reset current store after request

### 1.2.29 (2022-02-04)
  1. Added check if MIS is enabled

### 1.2.28 (2022-01-25)
  1. Added endpoint for List of Source - MIS
  2. Read available source for product

### 1.2.27 (2021-12-08)
  1. Add support for POST request

### 1.2.26 (2021-11-23)
  1. Add support for tier price in final_price

### 1.2.25 (2021-10-22)
  1. Fix total product count

### 1.2.24 (2021-10-21)
  1. Added parameter: since_id

### 1.2.23 (2021-10-21)
  1. Display module version in config

### 1.2.22 (2021-10-14)
  1. Added optional parameter: load_from_cache with options:
     1. automatic - DEFAULT | first will try to load product from cache if results are empty then will try to reload without cache.
     2. off - always load fresh data without cache - (this can be slow on performance)
     3. on - load only from cache

### 1.2.21 (2021-10-14)
  1. Fix issue with cache 

### 1.2.20 (2021-10-11)
  1. Fix download bundle product options

### 1.2.19 (2021-09-07)
  1. Fix image url

### 1.2.18 (2021-09-02)
  1. load out of stock variants

### 1.2.17 (2021-08-31)
  1. Load out of stock product

### 1.2.16 (2021-03-16)
  1. Build version fix

### 1.2.15 (2021-03-16)
  1. Build version fix

### 1.2.14 (2021-03-15)
  1. Added support for exporting bundle products

### 1.2.13 (2020-11-19)
  1. Added `&no_total=1`

### 1.2.12 (2020-11-19)
  1. Improved `&visibility_all=1` and `&status_all=1` params

### 1.2.11 (2020-10-08)
  1. Added `&visibility_all=1` and `&status_all=1` params

### 1.2.10 (2020-09-28)
  1. Added another way to get categories forced by adding param `?category_ver=2`

### 1.2.9 (2020-08-17)
  1. Category try, catch()
  
### 1.2.8 (2020-08-17)
  1. Build version fix
  
### 1.2.7 (2020-08-11)
  1. Added more debug functions
  2. Added memory limit set

### 1.2.5 (2020-06-01)
  1. Added 'debug' param to see noticed
  2. Added catch errors

### 1.2.4 (2020-05-21)
  1. Version increase
  
### 1.2.3 (2020-05-21)
  1. Fixed `setCurrentStore` not being triggered
  
### 1.2.2 (2020-04-15)
  1. Fixed an issue with product options

### 1.2.1 (2020-04-03)
  1. Fixed an issue with product attribute: "quantity_and_stock_status"

### 1.2.0 (2020-04-03)
  1. **New features**
        1. Now supports multiple currencies per store
        2. Product custom options are now extracted
  2. **Improvements**
        1. Product category output (now returned as a category tree)
        2. Reduced database queries
        3. Reduced memory usage
  
### 1.1.8 (2020-04-01)
  1. Fixed an issue related to variant product options.
