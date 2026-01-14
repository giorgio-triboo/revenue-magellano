# Magellano.ai - Revenue Management Platform #

A Laravel-based platform for revenue management and statement processing. The platform enables publishers to manage their revenue data, upload billing information, and provides administrators with advanced tools for monitoring and processing financial information.

## Requirements
- PHP 8.4+
- MySQL 8.0+
- Node.js & NPM
- Redis 6.0+ (for queues and cache)
- PHP Extensions:
  - php8.4-mysql
  - php8.4-redis
  - php8.4-xml
  - php8.4-zip
  - php8.4-mbstring
  - php8.4-gd

## Tech Stack
- Laravel Framework
- MySQL/MariaDB
- Redis (queues and cache)
- Node.js and NPM (frontend assets)
- Alpine.js (frontend interactivity)
- Tailwind CSS (styling)

## Key Features
- Multi-role authentication system
- Publisher and sub-publisher management
- CSV file upload and processing
- AX export generation
- FTP integration
- Email notification system

## Core Components

### Authentication System
Located in `app/Http/Controllers/Auth/`
- Advanced login system with rate limiting and brute force protection
- User registration with VAT validation
- Email verification
- Password management with secure reset workflow
- Session security and monitoring

### Publisher Management
- Complete publisher lifecycle management
- VAT number validation with multi-country support
- Sub-publisher hierarchy management
- Financial data management
- AX system integration

### File Upload System
Located in `app/Services/UploadService.php`
- File validation and processing
- Progress monitoring
- Automated notifications
- Integration with SFTP servers
- Security checks and validations

### CSV Processing System
Located in `app/Jobs/ProcessCsvUpload.php`
- Asynchronous processing with queues
- Custom header mapping
- Batch processing
- Memory monitoring
- Progress tracking
- Error handling and reporting

### Export System
Located in `app/Services/AxExportService.php`
- Multiple export formats support
- AX format generation
- Automated SFTP upload
- Error handling and retry mechanisms

### Statement Management
- Complete statement lifecycle
- Revenue type management
- Publication status tracking
- Financial data validation
- Export capabilities

## Security Features

### Middleware Protection
- IP validation
- Role-based access control
- Session security
- HTTPS enforcement
- Rate limiting
- CSRF protection
- XSS protection

### Authorization System
- Role-based permissions
- Resource access control
- Publisher-level restrictions
- File access management
- Audit logging

## Frontend Architecture

### Dashboard Components
- Responsive layout
- Real-time interactive charts
- Dynamic filtering
- Real-time notifications

### File Management Interface
- Visual upload progress
- Drag-and-drop support
- Client-side validation
- Preview capabilities

## Development Setup

### Docker Setup (Raccomandato)

Per testare le modifiche in un ambiente locale isolato prima di metterle in produzione:

1. **Avvia i container:**
   ```bash
   docker-compose up -d
   ```

2. **Esegui lo script di setup:**
   ```bash
   ./docker/setup.sh
   ```

3. **Accedi all'applicazione:**
   - Web: http://localhost:8080
   - MySQL: localhost:3306
   - Redis: localhost:6379

**Comandi utili:**
```bash
# Usa il Makefile per comandi rapidi
make help          # Mostra tutti i comandi disponibili
make up            # Avvia i container
make down          # Ferma i container
make logs          # Mostra i log
make shell         # Accedi al container
make artisan CMD="migrate"  # Esegue comandi Artisan
make queue          # Avvia il queue worker
```

Per maggiori dettagli, consulta `docker/README.md`.

### Local Environment Setup (Senza Docker)
1. Clone the repository
2. Install PHP dependencies via Composer
3. Install Node.js dependencies
4. Configure environment variables
5. Set up database
6. Run migrations
7. Start development server

### Key Configuration Files
- `.env`: Environment configuration
- `config/`: Application configuration files
- `routes/`: Application routes
- `app/Http/Controllers/`: Core controllers
- `app/Services/`: Business logic services

## Core Controllers

### Authentication Controllers
Located in `app/Http/Controllers/Auth/`

#### RegisterController
- New user registration handling
- VAT validation with multi-country support (IT, FR, DE, ES, GB, CY, IE)
- Publisher account creation
- Verification email dispatch
- Privacy policy management

#### LoginController
- Credential validation
- Brute force protection
- Advanced rate limiting (5 max attempts)
- Secure session management
- Account status verification
- IP and User Agent tracking

#### EmailVerificationController
- Email verification process
- Token validation
- Account status updates
- User notifications

#### ForgotPasswordController & ResetPasswordController
- Password reset request handling
- Token management (60-minute validity)
- Secure password validation
- Reset confirmation

### Publisher Management Controllers

#### PublisherController
- Publisher listing with pagination
- Advanced filtering and dynamic sorting
- Sub-publisher management
- Publisher details and editing
- Data export to Excel
- API endpoints for publisher operations

#### ProfileController
- Profile data management
- Personal information updates
- Notification preferences
- Account deactivation handling

### Data Processing Controllers

#### UploadController
- File upload management
- Status monitoring
- Format validation
- Publication status control
- Template downloads
- SFTP integration

#### StatementController
- Statement listing and details
- Advanced filtering
- Monthly statistics calculation
- Export functionality
- Data validation
- Publisher data association

### Support System

#### SupportController
- Support request management
- Category-based routing
- Admin notifications
- Input validation and sanitization
- Activity logging

### Dashboard Controllers

#### DashboardController
- Role-specific dashboard views
- Data visualization
- Real-time updates
- Custom metrics display

Each controller implements:
- Input validation
- Authorization checks
- Error handling
- Event dispatching
- Logging
- Response formatting

## Queue Workers and Jobs

### Main Jobs
- CSV Processing
- AX Export Generation
- FTP Upload
- Notification Dispatch

### Queue Configuration
- Redis as queue driver
- Multiple queues for different job types
- Retry policies
- Error handling

## TODO
- Generate .env file after installation
- Remove .git folder after installation
- Implement distributed cache
- Optimize upload performance
- Enhance security measures
- Develop public APIs

## Best Practices and Standards
- PSR-12 compliance
- SOLID principles
- DRY principle
- Repository pattern
- Service pattern
- Comprehensive testing suite
- Performance optimization
- Security best practices