# Adobe Code Challenge

- Build a containerized web app (fictions CRUD operations) and deploy on cloud (AWS/Azure/GCP)
- Design and implement an API server with authentication and rate limiting capabilities 

## ▶️ Live Demo (AWS Lightsail)
 - UI: [http://54.191.5.188/](http://54.191.5.188/)
 - API: [http://54.191.5.188:8080](http://54.191.5.188:8080)

Containerized PHP Slim 4 API with JWT authentication and Redis rate limiting. 
Deployed on AWS Lightsail via Docker Compose

---

## 📦 Example Setup

```bash
git clone https://github.com/vas2025/adobe-code-challenge.git
cd adobe-code-challenge
sudo docker compose build
sudo docker compose up -d
```

---

## 🗺️ Roadmap
- Phase 1: Repository, base structure, initial commit
- Phase 2: Backend server API (PHP, JWT, rate limiting)
- Phase 3: Frontend (React + TS), authentication and CRUD UI
- Phase 4: Docker + docker-compose
- Phase 5: AWS Deploy (Lightsail)

## 🧩 API Manual
All responses are returned in JSON format.  
Authentication uses **JWT Bearer tokens** returned on login.

---

## 🔹 Authentication

### `POST /api/auth/register`
Registers a new user.

**Request**
```json
{
  "email": "user@example.com",
  "password": "secret123"
}
```

**Response**
```json
{
  "message": "User registered successfully"
}
```

---

### `POST /api/auth/login`
Authenticates an existing user and returns a JWT token.

**Request**
```json
{
  "email": "user@example.com",
  "password": "secret123"
}
```

**Response**
```json
{
  "token": "<JWT_TOKEN>"
}
```

Use this token in subsequent requests:
```
Authorization: Bearer <JWT_TOKEN>
```

---

## 🔹 Books CRUD

All book operations require authentication.

### `GET /api/books`
Returns a list of all books.

**Headers**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response**
```json
[
  {
    "id": 1,
    "title": "Neuromancer",
    "author": "William Gibson",
    "description": "Classic cyberpunk novel."
  }
]
```

**CURL**
```bash
curl -X GET http://54.191.5.188:8080/api/books /
	-H "Content-Type: application/json" /
	-H "Authorization: Bearer <JWT_TOKEN>"
```

---

### `GET /api/books/{id}`
Returns a single book by ID.

**Example**
```
GET /api/books/1
Authorization: Bearer <JWT_TOKEN>
```

**Response**
```json
{
  "id": 1,
  "title": "Neuromancer",
  "author": "William Gibson",
  "description": "Classic cyberpunk novel."
}
```

---

### `POST /api/books`
Creates a new book record.

**Request**
```json
{
  "title": "Snow Crash",
  "author": "Neal Stephenson",
  "description": "A post-cyberpunk classic."
}
```

**Response**
```json
{
  "message": "Book created successfully",
  "id": 2
}
```

**CURL**
```bash
curl -X POST http://54.191.5.188:8080/api/books /
	-H "Content-Type: application/json" /
	-H "Authorization: Bearer <JWT_TOKEN>" -d '{
		"title": "Book Title",
		"author": "Book Author",
		"description": "Book Desc"
	}'
```

---

### `PUT /api/books/{id}`
Updates an existing book record.

**Request**
```json
{
  "title": "Neuromancer (Updated Edition)",
  "author": "William Gibson",
  "description": "Cyberpunk re-release, 2025 edition."
}
```

**Response**
```json
{
  "message": "Book updated successfully"
}
```

---

### `DELETE /api/books/{id}`
Deletes a book record.

**Example**
```
DELETE /api/books/2
Authorization: Bearer <JWT_TOKEN>
```

**Response**
```json
{
  "message": "Book deleted successfully"
}
```

---

## 🔹 Health Check

### `GET /api/ping`
Checks API availability.

**Response**
```json
{"pong": true}
```

---

## 🧱 Database Schema

### users
| Field | Type | Constraints | Description |
|-------|------|--------------|--------------|
| id | SERIAL | PK | Unique user ID |
| email | VARCHAR(255) | UNIQUE, NOT NULL | User login email |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation time |

### books
| Field | Type | Constraints | Description |
|-------|------|--------------|--------------|
| id | SERIAL | PK | Unique book ID |
| title | VARCHAR(255) | NOT NULL | Book title |
| author | VARCHAR(255) |  | Book author |
| description | TEXT |  | Book description |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Created date |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update date |

---

## 🚦 Rate Limiting

Rate limiting is applied globally using Redis.  
Each client (by IP) can make:

- **100 requests per 60 seconds**

If the limit is exceeded:
```json
{
  "error": "Too many requests",
  "limit": 100,
  "window_seconds": 60
}
```

HTTP Status: `429 Too Many Requests`

---

## 🧠 Technology Stack

| Layer | Technology |
|--------|-------------|
| Backend | PHP 8.2 (Slim Framework 4) |
| Database | PostgreSQL 15 |
| Cache / Rate Limiter | Redis 7 |
| Auth | JWT (Firebase PHP-JWT) |
| Frontend | React (TypeScript + Ant Design) |
| Containerization | Docker Compose (Nginx, PHP-FPM, Postgres, Redis, Frontend) |
| Cloud | AWS Lightsail (Ubuntu 22.04) |