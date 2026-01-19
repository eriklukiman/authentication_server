# OAuth 2.0 Authorization Server

A standards-compliant OAuth 2.0 authorization server built with PHP 8.4, using the `league/oauth2-server` library and the `eriklukiman/framework`.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running the Server](#running-the-server)
- [Usage Examples](#usage-examples)
- [Directory Structure](#directory-structure)
- [Testing the Implementation](#testing-the-implementation)
- [Security Notes](#security-notes)

## Overview

This OAuth 2.0 server implementation supports multiple grant types and provides secure token-based authentication and authorization for your applications. It follows the OAuth 2.0 specification (RFC 6749) and implements best practices for secure token management.

## Features

- ✅ **Client Credentials Grant** - Machine-to-machine authentication
- ✅ **Authorization Code Grant** - Traditional web application flow
- ✅ **Refresh Token Support** - Long-lived sessions without re-authentication
- ✅ **Scope Management** - Fine-grained access control
- ✅ **PSR-7 Compliant** - Modern PHP standards
- ✅ **Database-backed** - Persistent storage for clients, users, and tokens

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.4 or higher**
- **MySQL Database** (or compatible MariaDB)
- **Composer** - PHP dependency manager
- **OpenSSL** - For key generation

## Installation

### 1. Clone the Repository

```bash
git clone <repository_url>
cd authentication_server
```

### 2. Install Dependencies

```bash
composer install
```

This will install:
- `league/oauth2-server` - OAuth 2.0 server implementation
- `nyholm/psr7` - PSR-7 HTTP message implementation
- `eriklukiman/framework` - Custom routing and MVC framework

### 3. Generate Encryption Keys

The OAuth 2.0 server requires a private/public key pair for signing tokens:

```bash
openssl genrsa -out private.key 2048
openssl rsa -in private.key -pubout -out public.key
chmod 600 private.key
```

> **Note**: The `private.key` file should be kept secure and never committed to version control.

## Configuration

### Database Configuration

Edit the database credentials in [`config/app.php`](file:///opt/homebrew/var/www/steelytoe/authentication_server/config/app.php):

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'rx');          // Your MySQL username
define('DB_PASS', 'a');           // Your MySQL password
define('DB_NAME', 'auth_server'); // Your database name
define('DB_PORT', 3306);
```

## Database Setup

### 1. Create the Database

```bash
mysql -u rx -p -e "CREATE DATABASE auth_server;"
```

### 2. Run the Schema

The [`schema.sql`](file:///opt/homebrew/var/www/steelytoe/authentication_server/schema.sql) file contains all necessary table definitions and seed data:

```bash
mysql -u rx -p auth_server < schema.sql
```

This will create the following tables:
- `clients` - OAuth client applications
- `users` - End users who authorize access
- `scopes` - Available permission scopes
- `access_tokens` - Issued access tokens
- `refresh_tokens` - Issued refresh tokens
- `auth_codes` - Authorization codes (for auth code flow)

### 3. Default Test Data

The schema includes test data:

**Test Client:**
- Client ID: `testclient`
- Client Secret: `testsecret`
- Redirect URI: `http://localhost:8080/callback`

**Test User:**
- Username: `testuser`
- Password: `password`

**Test Scope:**
- Scope: `basic` - Basic Access

## Running the Server

Start the PHP built-in development server:

```bash
php -S localhost:8080 -t public
```

The server will be available at `http://localhost:8080`

> **Production Note**: For production deployments, use a proper web server like Nginx or Apache instead of the built-in PHP server.

## Usage Examples

### 1. Client Credentials Grant

Use this grant type for machine-to-machine authentication where no user is involved.

**Request:**

```bash
curl -X POST http://localhost:8080/access_token \
  -d "grant_type=client_credentials" \
  -d "client_id=testclient" \
  -d "client_secret=testsecret" \
  -d "scope=basic"
```

**Response:**

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 2. Authorization Code Grant

This grant type is for traditional web applications where a user needs to authorize the client.

#### Step 1: Get Authorization Code

Navigate to this URL in your browser:

```
http://localhost:8080/authorize?response_type=code&client_id=testclient&redirect_uri=http://localhost:8080/callback&scope=basic&state=xyz
```

- The user will see an approval form
- Upon approval, they'll be redirected to the callback URL
- The authorization code will be displayed on the callback page

#### Step 2: Exchange Code for Access Token

Replace `{AUTH_CODE}` with the code received from the callback:

```bash
curl -X POST http://localhost:8080/access_token \
  -d "grant_type=authorization_code" \
  -d "client_id=testclient" \
  -d "client_secret=testsecret" \
  -d "redirect_uri=http://localhost:8080/callback" \
  -d "code={AUTH_CODE}"
```

**Response:**

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

### 3. Refresh Token Grant

Use a refresh token to obtain a new access token without requiring user re-authentication:

```bash
curl -X POST http://localhost:8080/access_token \
  -d "grant_type=refresh_token" \
  -d "client_id=testclient" \
  -d "client_secret=testsecret" \
  -d "refresh_token={REFRESH_TOKEN}"
```

## Directory Structure

```
authentication_server/
├── config/
│   ├── app.php              # Application and database configuration
│   └── Database.php         # Database connection helper
├── Libraries/
│   ├── AuthServerFactory.php    # OAuth server factory
│   ├── Entities/            # OAuth2 entity implementations
│   │   ├── AccessTokenEntity.php
│   │   ├── AuthCodeEntity.php
│   │   ├── ClientEntity.php
│   │   ├── RefreshTokenEntity.php
│   │   ├── ScopeEntity.php
│   │   └── UserEntity.php
│   └── Repositories/        # OAuth2 repository implementations
│       ├── AccessTokenRepository.php
│       ├── AuthCodeRepository.php
│       ├── ClientRepository.php
│       ├── RefreshTokenRepository.php
│       ├── ScopeRepository.php
│       └── UserRepository.php
├── Models/                  # Database models
│   ├── Base.php            # Base model class
│   ├── AccessToken.php
│   ├── AuthCode.php
│   ├── Client.php
│   ├── RefreshToken.php
│   ├── Scope.php
│   └── User.php
├── Modules/                 # MVC Controllers
│   ├── AccessToken.php     # Handles /access_token endpoint
│   ├── Authorize.php       # Handles /authorize endpoint
│   └── Callback.php        # Handles OAuth callback
├── public/
│   └── index.php           # Application entry point
├── private.key             # Private key for JWT signing
├── public.key              # Public key for JWT verification
├── schema.sql              # Database schema and seed data
├── composer.json           # PHP dependencies
└── README.md               # This file
```

### Key Components

- **Modules**: MVC controllers that handle HTTP requests
  - `AccessToken.php` - Issues access tokens for all grant types
  - `Authorize.php` - Displays authorization form and issues auth codes
  - `Callback.php` - Receives OAuth callbacks and displays auth codes

- **Models**: Database models using Active Record pattern
  - Correspond to database tables
  - Provide data access layer

- **Libraries/Repositories**: Implement OAuth2 repository interfaces
  - Interface between OAuth server and database
  - Handle token/code persistence and retrieval

- **Libraries/Entities**: Implement OAuth2 entity interfaces
  - Represent OAuth2 concepts (clients, tokens, scopes)
  - Used by the OAuth server during grant processing

## Testing the Implementation

### Quick Verification Checklist

1. ✅ Server starts without errors
2. ✅ Client credentials grant returns access token
3. ✅ Authorization endpoint shows approval form
4. ✅ Authorization code can be exchanged for access token
5. ✅ Refresh token grant works

### Debugging Tips

If you encounter issues:

1. **Check PHP version**: `php -v` (must be 8.4+)
2. **Verify database connection**: Check credentials in `config/app.php`
3. **Check file permissions**: Ensure `private.key` has proper permissions (600)
4. **Review logs**: Check PHP error logs for detailed error messages
5. **Database tables**: Verify all tables were created: `SHOW TABLES;`

## Security Notes

⚠️ **Important Security Considerations:**

1. **Never commit `private.key`** - Add it to `.gitignore`
2. **Use HTTPS in production** - OAuth requires secure connections
3. **Strong client secrets** - Use cryptographically secure random strings
4. **Validate redirect URIs** - Always validate against registered URIs
5. **Token expiration** - Configure appropriate token lifetimes
6. **Password hashing** - User passwords are hashed with bcrypt
7. **Database security** - Use strong database credentials
8. **CORS configuration** - Configure CORS headers appropriately for your use case

## Next Steps

Now that your OAuth 2.0 server is running, you can:

1. **Register new clients** - Add entries to the `clients` table
2. **Define custom scopes** - Add entries to the `scopes` table
3. **Integrate with your API** - Validate tokens in your resource server
4. **Customize authorization UI** - Modify the `Authorize.php` module
5. **Add additional grant types** - Extend with password grant or PKCE

## Support

For issues or questions:
- Check the [league/oauth2-server documentation](https://oauth2.thephpleague.com/)
- Review OAuth 2.0 specification: [RFC 6749](https://tools.ietf.org/html/rfc6749)
- Contact: erik.lukiman@gmail.com

## License

This project is part of the Steelytoe framework suite.
