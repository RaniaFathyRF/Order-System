# Order Management System

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-DC382D?style=for-the-badge&logo=redis&logoColor=white)

A robust order processing system with inventory management built with Laravel and Redis.

## Features

- ✅ Real-time stock management  
- 🔒 Race condition prevention for low-stock items  
- ♻️ Automatic transaction rollback on failures  
- 🧪 Comprehensive test coverage  
- 🔐 Secure API endpoints  

## Installation

### Requirements

- PHP 8.1+
- Composer 2.0+
- Redis 6.0+
- MySQL 8.0+

### Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/your-repo/order-system.git
   cd order-system
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Set up database and Redis in `.env`:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=order_system
   DB_USERNAME=root
   DB_PASSWORD=

   REDIS_CLIENT=phpredis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   CACHE_STORE=redis

   ```

5. Run migrations:
   ```bash
   php artisan migrate --seed
   ```

6. Install Redis extension:
   ```bash
   sudo apt install php8.*-redis 
   sudo systemctl restart redis
   sudo systemctl restart nginx or sudo systemctl restart apcahe2
   
   ```

## API Documentation

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | `/api/orders` | Place a new order |
| GET    | `/api/products` | List available products |

### Example Request

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 2}'
```

## Running Tests

```bash
php artisan test
```

Test coverage includes:
- Stock validation
- Order processing
- Transaction safety
- Concurrency control

## Deployment

### Production Setup
1.Optimize Laravel:
   ```bash
   php artisan optimize:clear
   ```
2.Postman Collection Documentation:
   ```bash
     https://documenter.getpostman.com/view/20126221/2sB2cX91ui
   ```
## Troubleshooting

### Common Issues

**Redis connection errors**
- Verify Redis server is running: `redis-cli ping`
- Check firewall settings

**Test failures**
- Clear caches: `php artisan optimize:clear`
- Reset test database: `php artisan migrate:fresh --seed`

**403 Forbidden errors**
- Ensure proper authentication headers
- Verify Sanctum tokens are valid

## License

Distributed under the MIT License. See `LICENSE` for more information.

📧 **Contact**: raniafathyhowig@gmail.com.com  
🌐 **Project Link**: [https://github.com/RaniaFathyRF/order-system](https://github.com/your-repo/order-system)

