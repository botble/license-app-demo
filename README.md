# License Manager API Demo

Interactive web application for testing License Manager API operations.

## Live Demo

**URL**: https://license-app-demo.botble.com

## Features

### External API Demo (index.php)
- Test API connection
- Activate licenses
- Verify license validity
- Deactivate licenses

### Internal API Demo (internal.php)
- List and view products
- List and view licenses
- Create new licenses
- Block/unblock licenses

## Quick Start

### Requirements
- PHP 7.4+ with cURL extension
- Web server (Apache/Nginx)
- HTTPS recommended

### Installation

1. Copy files to your web server:
   ```bash
   cp -r demo-app/ /var/www/license-demo/
   ```

2. Set proper permissions:
   ```bash
   chmod 644 /var/www/license-demo/*.php
   chmod 644 /var/www/license-demo/.htaccess
   ```

3. Configure your web server to point to the directory

4. Access via browser: `https://your-domain.com/`

## Configuration

### External API
1. Enter your License Manager API URL (e.g., `https://license.botble.com`)
2. Enter your **External** API Key
3. Enter your Product Reference ID
4. Enter your License Code
5. Enter your Client Name

### Internal API
1. Enter your License Manager API URL
2. Enter your **Internal** API Key (with elevated permissions)

## API Endpoints

### External API
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/external/connection-check` | GET | Test connectivity |
| `/api/external/license/activate` | POST | Activate license |
| `/api/external/license/verify` | POST | Verify license |
| `/api/external/license/deactivate` | POST | Deactivate license |

### Internal API
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/internal/connection-check` | GET | Test connectivity |
| `/api/internal/products` | GET | List products |
| `/api/internal/products/{id}` | GET | Get product |
| `/api/internal/product-licenses` | GET | List licenses |
| `/api/internal/product-licenses` | POST | Create license |
| `/api/internal/blocked-product-licenses/{id}` | POST/DELETE | Block/unblock |

### Required Headers
```
Content-Type: application/json
X-API-KEY: {your-api-key}
X-API-URL: {your-app-url}
X-API-IP: {your-server-ip}
```

## Security Notes

- All configuration is stored in PHP session (server-side)
- Credentials are never exposed in URLs
- HTTPS is enforced via .htaccess
- API keys are masked in request display

## Files

```
demo-app/
├── index.php      # External API demo
├── internal.php   # Internal API demo
├── api.php        # Shared API functions
├── .htaccess      # Apache security config
└── README.md      # This file
```

## Documentation

- Full API Documentation: https://docs.botble.com/license-manager
- Support: https://botble.com/contact

## License

Part of License Manager plugin by Botble Technologies.
