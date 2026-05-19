# Setup & Installation Steps

Follow these steps to deploy the Insurance API POC locally.

## Prerequisites
- PHP 8.2 or higher
- Composer
- MySql or PostgreSQL
- Redis Server (for caching and background queues)
- Postman (for API testing)

## Installation Guide

**1. Clone the repository**
```bash
git clone <repository_url>
cd insurance-api
```

**2. Install Dependencies**
```bash
composer install
```

**3. Environment Setup**
Copy the example environment file and generate your application encryption key:
```bash
cp .env.example .env
php artisan key:generate
```

**4. Database Configuration**
Open the `.env` file and configure your database credentials. Make sure your MySQL or PostgreSQL server is running and the database exists.
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=insurance_api
DB_USERNAME=root
DB_PASSWORD=
```

**5. Run Migrations & Seeders**
This will create the necessary tables and populate initial roles and user accounts (Admin, Agent, Customer).
```bash
php artisan migrate --seed
```

**6. Link Storage for Documents**
Since claim documents are uploaded using the `public` disk, you must create a symbolic link to make them accessible:
```bash
php artisan storage:link
```

**7. Configure Caching and Queues**
Ensure Redis is running locally and configure the cache and queue connections in your `.env` file:
```env
CACHE_STORE=redis
QUEUE_CONNECTION=database
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

**8. Start the Queue Worker**
To process background email notifications for quotes and claims, run the queue worker:
```bash
php artisan queue:work
```

**9. Start the Development Server**
```bash
php artisan serve
```
The API will now be accessible at `http://localhost:8000`.

## Testing the API
1. Import the provided Postman collection.
2. Run the `Login (Admin)` request to receive a Bearer token.
3. Set the Bearer token in Postman's authorization tab for subsequent requests.
