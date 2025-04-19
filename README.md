# 📩 MessageAPI

**MessageAPI** is a modern RESTful messaging service built with Slim Framework and secured with JWT-based authentication. The project enables message exchange between users, read/unread tracking, role-based access control, soft deletion, and real-time admin statistics.

---

## 🚀 Features

- 🔐 JWT authentication for secure login/register
- 🧑‍💼 Role-based access (User / Admin)
- 💬 Sending messages, inbox & outbox
- 👁️‍🗨️ Mark as read/unread
- 🗑️ Soft delete (restorable deletion)
- 🔍 Filter by date, content, and pagination
- 📥 Mark all as read
- 🧾 Action logging (send, read, delete, restore, etc.)
- 📊 Real-time admin stats endpoint
- 🧪 Test-friendly database setup with seed data

---

## 🧠 What I Applied

> Through this project, I have gained hands-on experience in the following areas:

| Area                          | Key Topics                                                         |
|------------------------------|---------------------------------------------------------------------|
| ✅ **Slim Framework**        | Route groups, middleware, dependency injection (DI Container)       |
| ✅ **JWT Authentication**    | Secure token generation, middleware protection, user validation     |
| ✅ **RESTful API**           | HTTP methods, status codes, resource-based architecture             |
| ✅ **PDO (PHP Data Objects)**| Secure database access, prepared statements                         |
| ✅ **Role-Based Access**     | Role management for admin and users                                 |
| ✅ **Soft Delete Logic**     | Logical deletion using `is_deleted` field                           |
| ✅ **Logging & Monitoring**  | Tracking user actions via `message_logs` table                      |
| ✅ **Clean Architecture**    | Modular controller structure, environment config, .env usage        |

---


message-api/
│
├── public/                  
│   └── index.php             → Main entry point of the application (web-accessible)
│
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php       → Handles user registration and login
│   │   ├── MessageController.php    → Handles message sending, reading, deleting, etc.
│   │   └── AdminController.php      → Provides admin-only statistics
│   │
│   ├── Middleware/
│   │   └── JwtMiddleware.php        → Verifies JWT tokens for protected routes
│   │
│   ├── Database/
│   │   └── db.php                   → Initializes PDO database connection
│   │
│   └── Routes/
│       ├── auth.php                → Authentication-related route definitions
│       ├── message.php             → Message-related route definitions
│       ├── notification.php        → Notification routes
│       └── admin.php               → Admin route definitions
│
├── app/
│   ├── settings.php               → Application-level settings (e.g. environment)
│   ├── dependencies.php           → Registers dependencies into the container
│   ├── repositories.php           → (Optional) Repository bindings for data access
│   └── middleware.php             → Global middlewares (JWT, body parser, etc.)
│
├── uploads/                      → (Optional) Folder for uploaded files
├── logs/                         → (Optional) Application log files
│
├── .env                          → Environment-specific variables (not committed)
├── .env.example                  → Shared example file for environment setup
├── .gitignore                    → Files and folders to be ignored by Git
├── composer.json                 → Defines PHP dependencies and autoloading
├── composer.lock                 → Locks dependency versions
├── README.md                     → Project overview and API documentation
└── database/
    └── schema.sql                → (Optional) SQL schema for database initialization



---


## 📁 Setup

```bash
git clone https://github.com/yourusername/MessageAPI.git
cd MessageAPI
composer install
cp .env.example .env
Fill in the .env file:

env
Kopyala
Düzenle
DB_HOST=localhost
DB_NAME=message_app
DB_USER=root
DB_PASS=

JWT_SECRET=your_jwt_secret
🗃️ Database Structure
users → Stores user credentials and roles

messages → Stores user-to-user messages

message_logs → Stores action logs for auditing

You can import a prepared schema from database/schema.sql (if included).

🔑 API Endpoints
🧑 Auth
POST /auth/register

POST /auth/login

💬 Messages
POST /messages/send

GET /messages/inbox

GET /messages/sent

GET /messages/unread

GET /messages/{id}

PATCH /messages/read/{id}

PATCH /messages/read_all

DELETE /messages/{id}

🔔 Notifications
GET /notifications/status

🧠 Admin
GET /admin/stats

🔒 All requests must include a valid JWT token:

makefile
Kopyala
Düzenle
Authorization: Bearer <token>
👤 Admin Test User
json
Kopyala
Düzenle
{
  "email": "admin@example.com",
  "password": "123456",
  "role": "admin"
}
You may create this user manually in your database or via seed script.

📌 Requirements
PHP >= 8.0

Composer

MySQL or MariaDB

Apache / Nginx (or PHP built-in server for local testing)

📄 License
This project is open-source and licensed under the MIT License.

💡 Contributions & Feedback
Pull requests and issues are welcome!
Feel free to contact me on GitHub for questions or feedback.