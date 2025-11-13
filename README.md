# Document Tracking System (DTS4C-F)

A comprehensive document tracking and management system built with Laravel 12, designed for organizations to efficiently manage document workflows, routing, and tracking across departments.

## Features

### 📄 Document Management
- **Document Registration**: Upload and register documents with metadata (title, type, description, purpose)
- **QR Code Tracking**: Generate and scan QR codes for quick document identification and tracking
- **File Storage**: Secure file storage with support for various file types
- **Document Status Tracking**: Real-time status updates (Registered, In Transit, Received, On Hold, Completed, Archived, etc.)
- **Document Routing**: Route documents between departments and users
- **Document History**: Complete audit trail of all document movements and actions

### 👥 User Roles & Permissions
- **Owner**: Upload documents, track their documents, receive completed documents, archive/delete documents
- **Handler**: Process incoming documents, send documents to other handlers, forward to owners, reject documents, hold/resume processing
- **Admin**: Manage users, departments, view all system activities, handle bug reports
- **Auditor**: View audit logs and document activity logs

### 🔔 Notifications
- Email notifications for:
  - Document assignment
  - Document received
  - Document sent
  - Document forwarded
  - Document rejected
  - Document updated
  - Document archived
  - Document deleted

### 📊 Dashboard & Analytics
- Role-specific dashboards with relevant statistics
- Document status overview
- Activity logs and audit trails
- Export functionality for logs

### 🏢 Department Management
- Create and manage departments
- Assign users to departments
- Route documents between departments

### 🔍 Tracking & Logging
- **Document Logs**: Detailed logs of all document actions
- **Audit Logs**: System-wide audit trail
- **Activity Logs**: Complete activity history
- **QR Code Tracking**: Public QR code scanning for document tracking

### 🐛 Bug Reporting
- Submit and manage bug reports
- Admin can view and update bug report status

### 👤 User Profile
- Profile picture upload
- User information management
- Account activation/deactivation

## Technology Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL/PostgreSQL/SQLite
- **Authentication**: Laravel Sanctum (API) + Session (Web)
- **Frontend**: Blade Templates, Tailwind CSS 4, Vite
- **QR Code**: html5-qrcode, qrcode.js
- **Email**: Laravel Mail (SMTP)

## Requirements

- PHP >= 8.2
- Composer
- Node.js & npm
- Database (MySQL, PostgreSQL, or SQLite)
- Web server (Apache/Nginx) or PHP built-in server

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd DTS4C-F-main
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node Dependencies
```bash
npm install
```

### 4. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your configuration:
- Database credentials
- Mail settings (SMTP)
- App URL
- Other environment variables

### 5. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 6. Storage Link
```bash
php artisan storage:link
```

### 7. Build Assets
```bash
npm run build
```

For development:
```bash
npm run dev
```

## Quick Start

### Using Composer Scripts

The project includes convenient composer scripts:

**Setup (first time):**
```bash
composer setup
```

**Development (with hot reload):**
```bash
composer dev
```

This will start:
- Laravel development server
- Queue worker
- Log viewer (Pail)
- Vite dev server

**Testing:**
```bash
composer test
```

### Manual Start

**Start Laravel Server:**
```bash
php artisan serve
```

**Start Queue Worker (for emails):**
```bash
php artisan queue:work
```

**Start Vite Dev Server (for frontend):**
```bash
npm run dev
```

## Project Structure

```
DTS4C-F-main/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin controllers
│   │   │   ├── Api/            # API controllers
│   │   │   ├── Auditor/        # Auditor controllers
│   │   │   ├── Handler/        # Handler controllers
│   │   │   └── Owner/         # Owner controllers
│   │   └── Middleware/         # Custom middleware
│   ├── Mail/                   # Email notification classes
│   ├── Models/                 # Eloquent models
│   ├── Providers/              # Service providers
│   └── Services/               # Business logic services
├── config/                     # Configuration files
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── public/                     # Public assets
├── resources/
│   ├── views/                  # Blade templates
│   ├── css/                    # Stylesheets
│   └── js/                     # JavaScript files
├── routes/
│   ├── api.php                 # API routes
│   └── web.php                 # Web routes
└── storage/                    # Storage directory
```

## API Documentation

### Authentication

**Login:**
```
POST /api/login
Body: { "email": "user@example.com", "password": "password" }
```

**Get Current User:**
```
GET /api/user
Headers: Authorization: Bearer {token}
```

**Logout:**
```
POST /api/logout
Headers: Authorization: Bearer {token}
```

### Document Endpoints

**Register Document (Owner):**
```
POST /api/documents
Headers: Authorization: Bearer {token}
Body: FormData with file, title, description, etc.
```

**Get My Documents (Owner):**
```
GET /api/documents/my-documents
Headers: Authorization: Bearer {token}
```

**Get Assigned Documents (Handler):**
```
GET /api/documents/assigned
Headers: Authorization: Bearer {token}
```

**Send Document (Handler):**
```
POST /api/documents/{id}/send
Headers: Authorization: Bearer {token}
Body: { "target_handler_id": 1, "remarks": "..." }
```

**Receive Document (Handler):**
```
POST /api/routes/{routeId}/receive
Headers: Authorization: Bearer {token}
```

**Track Document by QR Code:**
```
GET /api/documents/track/{qr_code}
Headers: Authorization: Bearer {token}
```

**Get Document File:**
```
GET /api/documents/{id}/file
Headers: Authorization: Bearer {token}
```

### Admin Endpoints

**User Management:**
```
GET    /api/admin/users
POST   /api/admin/users
PUT    /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST   /api/admin/users/{id}/activate
POST   /api/admin/users/{id}/deactivate
```

**Department Management:**
```
GET    /api/admin/departments
POST   /api/admin/departments
PUT    /api/admin/departments/{id}
DELETE /api/admin/departments/{id}
```

### Notification Endpoints

```
GET  /api/notifications
POST /api/notifications/read/{id}
```

## Database Schema

### Key Tables

- **users**: User accounts with roles (owner, handler, admin, auditor)
- **departments**: Organizational departments
- **documents**: Document records with metadata
- **document_routes**: Document routing history
- **document_logs**: Detailed document action logs
- **audit_logs**: System audit trail
- **activity_logs**: User activity logs
- **notifications**: User notifications
- **bug_reports**: Bug report submissions

## Configuration

### Mail Configuration

Configure SMTP settings in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Storage Configuration

Documents are stored in `storage/app/documents/` and accessed via the storage link.

Profile pictures are stored in `storage/app/public/profile-pictures/`.

## Security Features

- Role-based access control (RBAC)
- Laravel Sanctum for API authentication
- Session-based authentication for web
- Password hashing
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS protection
- Soft deletes for data retention

## Development

### Code Style

The project uses Laravel Pint for code formatting:
```bash
./vendor/bin/pint
```

### Testing

Run tests with:
```bash
php artisan test
```

### Queue Processing

For production, use a proper queue worker:
```bash
php artisan queue:work --tries=3
```

Or configure supervisor/systemd for automatic queue processing.

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Run migrations: `php artisan migrate --force`
4. Optimize: `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`
5. Build assets: `npm run build`
6. Set up queue worker (supervisor/systemd)
7. Configure web server (Apache/Nginx)

See `prepare-production.ps1` for a PowerShell deployment script.

## Troubleshooting

### Storage Link Issues
```bash
php artisan storage:link
```

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Permission Issues (Linux)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues and bug reports, use the built-in bug reporting feature or contact the system administrator.

## Contributing

Contributions are welcome! Please ensure your code follows Laravel best practices and includes appropriate tests.

---

**Built with ❤️ using Laravel**
