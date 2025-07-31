# Magento v1.0

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)
![License](https://img.shields.io/badge/license-MIT-blue.svg)

This Laravel application serves as an intermediary API service, acting as a robust bridge between a data-gathering service (e.g., a Python web crawler) and a Magento 2 e-commerce platform.

The core purpose of this service is to create a controlled, manual approval workflow. Instead of sending data directly to Magento, products are first staged in a local database. An operator can then review, edit, and approve these products before manually triggering a synchronization to create or update them in Magento.

## ✨ Core Features

*   **Product Staging:** Receives product data from a crawler and stores it locally.
*   **Manual Approval Workflow:** Products are held in a `pending_review` state until an operator takes action.
*   **Operator CRUD Interface:** Provides RESTful endpoints for an operator's front-end application to List, View, and Update the staged products.
*   **Controlled Magento Sync:** A dedicated endpoint allows an operator to manually push an approved product to Magento.
*   **Dual-Layer Security:**
    *   **API Key Authentication** for trusted machine-to-machine communication (the crawler).
    *   **Laravel Sanctum** for authenticating user-facing applications (the operator's dashboard).
*   **Error Handling & Logging:** If a sync to Magento fails, the product status is updated to `error` with a descriptive message, allowing for easy diagnosis and retries.

## 🚀 Architecture Flow

The data flow is designed for control and observability.

```
+----------------+      +---------------------------+      +----------------+
| Python Crawler | --1->|    Laravel Bridge API     | --2->|    Database    |
+----------------+      | (POST /crawler/products)  |      |  (MySQL/PgSQL) |
                        +-------------^-------------+      +-------^--------+
                                      |                            | 4. Update
                                      | 3. CRUD & Sync             |    Product
                                      v                            |    Status
                        +-------------+-------------+      +-------+--------+
                        |      Operator's App       | --5->|   Magento 2    |
                        |     (SPA / Frontend)      |      | (via REST API) |
                        +---------------------------+      +----------------+
```
1.  The **Crawler** sends new product data to a secure endpoint.
2.  The **Laravel API** validates the data and stores it in the local **Database** with a `pending_review` status.
3.  The **Operator's App** fetches pending products, allowing an operator to review and edit them.
4.  After editing, the operator can trigger a **Sync**, which calls a dedicated endpoint on the Laravel API.
5.  The **Laravel API** then makes a request to the **Magento 2 API** to create or update the product. The local product's status is updated to `synced` or `error`.

## 🔧 Technology Stack

*   **Backend:** Laravel 12.x, PHP 8.2+
*   **Database:** MySQL / PostgreSQL
*   **Authentication:** Laravel Sanctum, Custom API Key Middleware
*   **API:** RESTful Principles

## ⚙️ Installation & Setup

Follow these steps to get the project up and running on your local development machine.

### 1. Prerequisites
*   PHP >= 8.2
*   Composer
*   Node.js & npm
*   A database server (e.g., MySQL)

### 2. Clone the Repository
```bash
git clone https://github.com/behzadiziy/magento.git
cd magento
```

### 3. Install Dependencies
```bash
composer install
npm install
npm run build
```

### 4. Environment Configuration
Create a `.env` file by copying the example file.
```bash
cp .env.example .env
```
Now, open `.env` and configure the following variables:

```dotenv

# API Key for the Python Web Crawler
CRAWLER_API_KEY=YourSecureRandomCrawlerKey

# Magento 2 Instance Details
MAGENTO_BASE_URL=https://your-magento-instance.com
MAGENTO_ACCESS_TOKEN=your_magento_access_token

# Sanctum configuration (for the operator's SPA front-end)
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

### 5. Final Setup Commands
```bash
# Generate a new application key
php artisan key:generate

# Run the database migrations
php artisan migrate
```

### 6. Start the Server
```bash
php artisan serve
```
Your API will now be available at `http://127.0.0.1:8000`.

## 🔐 API Endpoints Documentation

All endpoints are prefixed with `/api/v1`.

### Crawler Endpoint

Authentication is handled via an API Key passed in the `X-API-KEY` header.

#### `POST /crawler/products`
Submits a new product from the crawler for staging.

*   **Headers:**
    *   `Accept: application/json`
    *   `X-API-KEY: {{crawler_api_key}}`
*   **Request Body:**
    ```json
    {
        "sku": "CRAWLER-SKU-001",
        "name": "Product from Crawler",
        "description": "A detailed product description.",
        "price": 199.99,
        "stock_quantity": 50
    }
    ```
*   **Success Response (201 Created):**
    ```json
    {
        "id": 1,
        "sku": "CRAWLER-SKU-001",
        "name": "Product from Crawler",
        // ... other fields
        "status": "pending_review",
        "created_at": "...",
        "updated_at": "..."
    }
    ```

---

### Operator Endpoints

Authentication is handled via Laravel Sanctum. All requests must include a Bearer Token in the `Authorization` header.

#### `GET /products`
Lists all staged products. Can be filtered by status.

*   **URL Parameters:** `?status=pending_review` (optional)
*   **Success Response (200 OK):** A paginated list of product objects.

#### `PUT /products/{product}`
Updates a staged product's details after operator review.

*   **Request Body:**
    ```json
    {
        "name": "Product Name (Updated by Operator)",
        "price": 195.50,
        "status": "approved"
    }
    ```
*   **Success Response (200 OK):** The complete, updated product object.

#### `POST /products/{product}/sync`
Triggers the synchronization of a single, approved product to Magento.

*   **Request Body:** (Empty)
*   **Success Response (200 OK):**
    ```json
    {
        "message": "Product synced to Magento successfully.",
        "product": {
            // ... product object with "status": "synced"
        }
    }
    ```
*   **Error Response (502 Bad Gateway):**
    ```json
    {
        "message": "Failed to sync product to Magento.",
        "error": "Magento API Error: Invalid login credentials."
    }
    ```

## 📜 License

This project is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
