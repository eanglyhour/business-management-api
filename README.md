# PHP API

A PHP REST API project built for development and learning, using Docker, MySQL, JWT authentication, and Bakong payment integration.

> 🚧 **Status: In Development**
>
> This project is currently under active development. Features, security, testing, and infrastructure are still being improved. It is **not production-ready**.

---

## 🚀 Tech Stack

* PHP
* MySQL 8.4
* Composer
* Docker
* Docker Compose
* phpMyAdmin
* JWT Authentication
* Bakong KHQR Payment
* REST API

---

## 📁 Project Structure

```text
php_api/
│
├── app/
│   ├── controllers/
│   ├── models/
│   ├── services/
│   └── ...
│
├── DB/
│
├── docker/
│   └── php/
│       └── Dockerfile
│
├── public/
│   └── index.php
│
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
├── docker-compose.yml
├── note.txt
└── README.md
```

---

## ⚙️ Requirements

Install the following before running the project:

* Docker Desktop
* Git
* PHP (optional when using Docker)
* Composer (optional when using Docker)

---

## 🔧 Environment Setup

Clone the repository:

```bash
git clone <your-repository-url>
cd php_api
```

Copy `.env.example` to `.env`.

### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

### Linux / macOS

```bash
cp .env.example .env
```

Then configure your environment variables:

```env
JWT_SECRET=your_jwt_secret_here

ACCESS_TOKEN_EXPIRE=15m
REFRESH_TOKEN_EXPIRE=7d

REFRESH_COOKIE_NAME=refresh_token

COOKIE_SECURE=false
COOKIE_HTTP_ONLY=true
COOKIE_SAME_SITE=Strict

BAKONG_API_URL=your_bakong_api_url
BAKONG_TOKEN=your_bakong_token_here
BAKONG_ACCOUNT_ID=your_bakong_account_id

BAKONG_MERCHANT_NAME=Your Merchant Name
BAKONG_CITY=Phnom Penh
```

> ⚠️ Never commit `.env` or real API tokens/secrets to GitHub.

---

## 🐳 Run with Docker

Build and start the containers:

```bash
docker compose up -d --build
```

Check running containers:

```bash
docker compose ps
```

View PHP API logs:

```bash
docker compose logs -f php
```

View MySQL logs:

```bash
docker compose logs -f mysql
```

Stop the containers:

```bash
docker compose down
```

Stop containers and remove database volume:

```bash
docker compose down -v
```

> ⚠️ `docker compose down -v` deletes the MySQL Docker volume and therefore removes the local database data.

---

## 🌐 Development Services

| Service    | URL                     | Purpose             |
| ---------- | ----------------------- | ------------------- |
| PHP API    | `http://localhost:9000` | REST API            |
| MySQL      | `localhost:9001`        | Database            |
| phpMyAdmin | `http://localhost:9002` | Database management |

Inside Docker, PHP connects to MySQL using:

```env
DB_HOST=mysql
DB_PORT=3306
```

Do **not** use `localhost` for the MySQL host from inside the PHP container.

---

## 🔐 Authentication

The API uses JWT authentication with:

* Access Token
* Refresh Token
* HTTP-only Cookie
* SameSite Cookie protection
* Configurable token expiration

Example configuration:

```env
ACCESS_TOKEN_EXPIRE=15m
REFRESH_TOKEN_EXPIRE=7d

COOKIE_HTTP_ONLY=true
COOKIE_SECURE=false
COOKIE_SAME_SITE=Strict
```

For production with HTTPS:

```env
COOKIE_SECURE=true
```

---

## 💳 Bakong Payment

The project includes Bakong payment integration for KHQR-based payments.

Required environment variables:

```env
BAKONG_API_URL=
BAKONG_TOKEN=
BAKONG_ACCOUNT_ID=
BAKONG_MERCHANT_NAME=
BAKONG_CITY=
```

Never commit the real Bakong token or account credentials.

---

## 🧪 Development

Install PHP dependencies:

```bash
composer install
```

Update dependencies:

```bash
composer update
```

Run the development environment:

```bash
docker compose up -d
```

---

## 🔄 Git Workflow

Create a feature branch:

```bash
git checkout -b feature/your-feature
```

After making changes:

```bash
git add .
git commit -m "Add your feature"
git push origin feature/your-feature
```

---

## 🚧 Development Roadmap

* [ ] Improve API architecture
* [ ] Improve authentication security
* [ ] Add request validation
* [ ] Add API tests
* [ ] Add Swagger / OpenAPI documentation
* [ ] Improve Docker configuration
* [ ] Add health checks
* [ ] Add CI with GitHub Actions
* [ ] Add automated testing
* [ ] Add Docker image build pipeline
* [ ] Deploy to Render
* [ ] Add production environment configuration
* [ ] Add monitoring and logging

---

## 🔒 Security

This project is currently for development and learning purposes.

Before production deployment, the following should be reviewed:

* Secret management
* JWT security
* Cookie security
* CORS configuration
* Rate limiting
* Input validation
* SQL injection protection
* Authentication and authorization
* HTTPS
* Database security
* API logging
* Error handling

---

## 📌 Project Status

```text
🚧 IN DEVELOPMENT
```

The project is actively being improved and may contain incomplete features or breaking changes.

---

## 👨‍💻 Author

**Eang Lyhour**

Computer Science Student
Cambodia

---

## 📄 License

This project is currently for educational and development purposes.
