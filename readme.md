This guide provides the steps to set up the Hotel Management System, configure dependencies, and run necessary commands for syncing bookings with the PMS API.
## 1. Install Dependencies
### Step 1: Install PHP Dependencies
Use Composer to install the required PHP dependencies:
``` bash
composer install
```
## 2. Configure Environment Variables
Update the `.env` configuration file with the appropriate settings for your SQL database and Redis cache. Below is what you need to focus on:
### MySQL Database Configuration:
``` env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotel_management_system
DB_USERNAME=root
DB_PASSWORD=
```
### Redis Cache Configuration:
``` env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```
## 3. Run Migrations
Migrate the database schema to set up required tables:
``` bash
php artisan migrate
```
## 4. Sync Bookings Using Artisan Command
Run the sync command to retrieve bookings and related data from the PMS API:
``` bash
php artisan sync:pms-bookings
```
## 5. Start the Queue Worker
To process queued jobs (e.g., background syncing), start the queue worker:
``` bash
php artisan queue:work
```
