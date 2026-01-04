# OTP Service

A PHP-based OTP (One-Time Password) service with separate interfaces for administrators and customers.

## Features

- **Admin Interface**: Manage customers, view OTP requests, and monitor usage
- **Customer Interface**: Generate OTPs, view request history, manage message templates, and manage account settings
- **API Access**: RESTful API for OTP generation and verification
- **Message Templates**: Customizable message templates with placeholders for OTP codes
- **User Authentication**: Secure login system for both admin and customer users
- **Database Management**: MySQL-based data storage

## Project Structure

```
sms-gateway/
├── admin/                 # Admin interface
│   ├── dashboard.php      # Admin dashboard
│   ├── manage_customers.php # Customer management
│   ├── view_otp_requests.php # View all OTP requests
│   ├── view_message_templates.php # View all message templates
│   └── view_all_customers.php # View all customers
├── customers/             # Customer interface
│   ├── dashboard.php      # Customer dashboard
│   ├── generate_otp.php   # Generate new OTP
│   ├── manage_templates.php # Manage message templates
│   ├── view_otp_requests.php # View OTP history
│   └── account_settings.php # Account management
├── api/                   # API endpoints
│   ├── generate_otp.php   # Generate OTP via API
│   ├── verify_otp.php     # Verify OTP via API
│   ├── get_otp_requests.php # Get OTP requests via API
│   └── index.php          # API documentation
├── config/                # Configuration files
│   ├── config.php         # Main configuration
│   ├── database.php       # Database configuration
│   └── schema.php         # Database schema
├── models/                # Data models
│   ├── User.php           # User management
│   └── OTP.php            # OTP management (includes OTP message templates)
├── includes/              # Common includes
│   ├── auth.php           # Authentication functions
│   └── utils.php          # Utility functions
├── assets/                # CSS/JS files
├── init_db.php            # Database initialization
├── index.php              # Main entry point
├── login.php              # Login page
└── logout.php             # Logout functionality
```

## Setup Instructions

1. **Environment Variables Setup**:
   - Copy `.env.example` to `.env`
   - Update the values in `.env` with your actual Zong SMS API credentials:
     ```
     ZONG_LOGIN_ID=your_actual_login_id
     ZONG_LOGIN_PASSWORD=your_actual_password
     ```

2. **Database Setup**:
   - Make sure your XAMPP is running with MySQL
   - Update database credentials in `config/database.php` if needed
   - Run `init_db.php` to create the database tables and default admin user

3. **Default Admin Credentials**:
   - Username: `admin`
   - Password: `admin123`

4. **Access the Application**:
   - Visit `http://localhost/SMS%20Gateway/sms-gateway/` in your browser
   - Login with admin credentials to access admin features
   - Create customer accounts for end users

## OTP Message Templates

Each customer can create one custom message template with placeholders for OTP codes:
- Use `{OTP}` as the default placeholder (customizable)
- Each user has only one template that is used for all OTPs
- Templates are stored per user

## API Usage

The API requires authentication using an API key. You can find your API key in your account settings.

### Generate OTP
```
POST /api/generate_otp
Headers:
  Content-Type: application/json
  Authorization: Bearer YOUR_API_KEY
Body:
  {
    "phone_number": "+1234567890",
    "purpose": "Login verification"
  }
```

### Verify OTP
```
POST /api/verify_otp
Headers:
  Content-Type: application/json
  Authorization: Bearer YOUR_API_KEY
Body:
  {
    "otp_code": "123456"
  }
```

### Get OTP Requests
```
GET /api/get_otp_requests
Headers:
  Authorization: Bearer YOUR_API_KEY
```

## Security Notes

- Passwords are hashed using PHP's password_hash() function
- Input is sanitized to prevent XSS attacks
- SQL queries use prepared statements to prevent SQL injection
- API requests require valid authentication tokens

## Customization

- Update database credentials in `config/database.php`
- Modify the UI in the respective interface files
- Extend functionality by adding to the models in the `models/` directory
- Customize the SMS sending function in `includes/utils.php` to integrate with your SMS gateway
- Configure your Zong SMS API credentials in the `.env` file
- The mask parameter for SMS can be customized when calling the sendSMS function, otherwise it will be empty