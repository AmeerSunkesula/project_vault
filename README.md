# Project Vault

A comprehensive project management platform for Dr. YC James Yen Government Polytechnic, Kuppam. This system allows students and staff to showcase, collaborate, and explore innovative projects across all engineering branches.

## Features

### Core Functionality
- **User Management**: Student and staff registration with role-based access
- **Project Showcase**: Create, edit, and display projects with detailed descriptions
- **Collaboration System**: Request and manage project collaborations
- **Voting System**: Upvote/downvote projects with one vote per user
- **Starring System**: Star favorite projects like GitHub repositories
- **Comments & Replies**: Interactive discussion system for projects
- **Search & Filter**: Advanced project discovery with branch and type filtering
- **Notifications**: Real-time notifications for collaborations and updates

### User Roles
- **Students**: Can create projects, collaborate, vote, and comment
- **Staff**: Can approve projects, manage users, and access all features
- **Admin**: Full system access including user management and system settings

### Engineering Branches
- **DCME**: Diploma in Computer Engineering
- **DEEE**: Diploma in Electrical and Electronics Engineering  
- **DME**: Diploma in Mechanical Engineering
- **DECE**: Diploma in Electronics and Communication Engineering

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Icons**: Font Awesome 6.0
- **Server**: Apache (XAMPP compatible)

## Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser (Chrome, Firefox, Safari, Edge)

### Setup Instructions

1. **Clone/Download the Project**
   ```bash
   # Place the project in your XAMPP htdocs directory
   C:\xampp\htdocs\project_vault\
   ```

2. **Database Setup**
   - Start XAMPP and ensure Apache and MySQL are running
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema:
     

3. **Configuration**
   - Update database credentials in `config/database.php` if needed:
     ```php
     private $host = 'localhost';
     private $db_name = 'project_vault';
     private $username = 'root';
     private $password = '';
     ```

4. **College Logo**
   - Place your college logo as `assets/images/polytechnic_logo.jpg`
   - Recommended size: 200x200 pixels or similar aspect ratio

5. **Access the Application**
   - Open your browser and navigate to: `http://localhost/project_vault/`

### Default Admin Account
- **Username**: admin
- **Password**: admin123
- **Email**: admin@polytechnic.edu

**Important**: Change the admin password after first login!

## File Structure

```
project_vault/
├── api/                    # API endpoints
│   ├── branches.php
│   ├── comments.php
│   ├── collaborations.php
│   ├── dashboard.php
│   ├── notifications.php
│   └── projects.php
├── assets/                 # Static assets
│   ├── css/
│   │   ├── dashboard.css
│   │   ├── explore.css
│   │   ├── project.css
│   │   └── style.css
│   ├── js/
│   │   ├── dashboard.js
│   │   ├── explore.js
│   │   ├── main.js
│   │   └── project.js
│   └── images/
│       └── polytechnic_logo.jpg
├── auth/                   # Authentication pages
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── config/                 # Configuration files
│   ├── config.php
│   └── database.php
├── dashboard/              # User/Admin dashboard
│   ├── index.php
│   ├── collaborations.php
│   ├── settings.php
│   ├── notifications.php
│   ├── profile.php
│   ├── admin/
│   │   ├── collaborations.php
│   │   ├── index.php
│   │   ├── notifications.php
│   │   ├── projects.php
│   │   ├── reports.php
│   │   └── users.php
│   └── projects/
│       ├── add.php
│       ├── edit.php
│       └── index.php
├── database/               # Database files
│   └── schema.sql
├── projects/                # Project exploration
│   ├── index.php
│   ├── user.php
│   ├── index.php
│   └── .htaccess
├── index.php              # Homepage
├── project.php            # Project detail page
└── README.md
```

## Usage Guide

### For Students

1. **Registration**
   - Visit the registration page
   - Select "Student" role
   - Fill in roll number (12 characters), branch, and other details
   - Account is activated immediately

2. **Creating Projects**
   - Login and go to Dashboard
   - Click "Add New Project"
   - Fill in project details including title, descriptions, branch, and type
   - Add GitHub repository link if available

3. **Collaboration**
   - Browse projects in the Explore section
   - Click "Request Collaboration" on projects you want to join
   - Wait for project creator's approval

4. **Interacting with Projects**
   - Vote (upvote/downvote) on projects
   - Star favorite projects
   - Comment and reply to discussions

### For Staff

1. **Registration**
   - Register with "Staff" role
   - Account requires admin approval
   - Admin will approve or reject the request

2. **Project Management**
   - View all projects in the system
   - Approve or manage project content
   - Access user management features

### For Administrators

1. **User Management**
   - Approve/reject staff registration requests
   - Manage user accounts and permissions
   - Handle password reset requests

2. **System Management**
   - Monitor system activity
   - Manage projects and content
   - System configuration and maintenance

## Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's password_hash()
- **SQL Injection Protection**: All database queries use prepared statements
- **XSS Prevention**: All user input is sanitized and escaped
- **CSRF Protection**: Forms include CSRF tokens
- **Session Management**: Secure session handling with timeout
- **Input Validation**: Comprehensive server-side validation

## Customization

### Adding New Branches
Edit `config/config.php` and add new branches to the `$branches` array:

```php
$branches = [
    'NEW_BRANCH' => [
        'name' => 'New Branch Name',
        'types' => [
            'Project Type 1',
            'Project Type 2',
            // Add more types
        ]
    ],
    // Existing branches...
];
```

### Styling
- Main styles: `assets/css/style.css`
- Dashboard styles: `assets/css/dashboard.css`
- Explore page styles: `assets/css/explore.css`
- Project page styles: `assets/css/project.css`
- Admin page styles: `assets/css/admin.css`

### JavaScript Functionality
- Main functions: `assets/js/main.js`
- Dashboard features: `assets/js/dashboard.js`
- Explore features: `assets/js/explore.js`
- Project features: `assets/js/project.js`

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check if MySQL is running in XAMPP
   - Verify database credentials in `config/database.php`
   - Ensure database `project_vault` exists

2. **Page Not Loading**
   - Check if Apache is running in XAMPP
   - Verify file permissions
   - Check for PHP errors in XAMPP logs

3. **Images Not Displaying**
   - Ensure `assets/images/polytechnic_logo.jpg` exists
   - Check file permissions
   - Verify image file format (JPG, PNG, etc.)

4. **JavaScript Not Working**
   - Check browser console for errors
   - Ensure all JS files are loading correctly
   - Verify file paths are correct

### Support

For technical support or questions:
- Check the browser console for JavaScript errors
- Review XAMPP error logs
- Ensure all prerequisites are installed correctly

## License

This project is developed for Dr. YC James Yen Government Polytechnic, Kuppam. All rights reserved.


---

**Note**: This system is designed for local deployment and educational use. For production deployment, additional security measures and server configuration may be required.
# project_vault
