# School Chat System

A web-based chat system for schools enabling communication between students, teachers, and administrators.

## Features

- **User Authentication**: Secure login/registration with role-based access
- **Real-time Messaging**: Private and group chats with live updates
- **File Sharing**: Upload and share images, PDFs, and documents
- **User Management**: Admin dashboard for managing users and monitoring chats
- **Role-Based Access**: Different permissions for students, teachers, and admins
- **Responsive Design**: Works on desktop and mobile devices

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

### Setup Steps

1. **Clone or extract the project files** to your web server directory

2. **Create the database**:
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure database connection**:
   Edit `config.php` and update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'school_chat');
   ```

4. **Set permissions** for the uploads directory:
   ```bash
   chmod 755 uploads/
   ```

5. **Access the application**:
   Open your browser and navigate to: `http://localhost/school-chat/`

## Default Admin Account

- **Email**: admin@school.com
- **Password**: admin123

**Important**: Change the admin password after first login!

## User Roles

### Student
- Join group chats
- Send private messages to teachers
- Share files and images
- View message history

### Teacher
- All student permissions
- Create and manage group chats
- Communicate with all users
- Access basic admin features

### Administrator
- Full system access
- User management (add, edit, delete users)
- Chat monitoring and moderation
- View system statistics

## File Structure

```
school-chat/
├── index.php           # Login page
├── register.php        # Registration page
├── chat.php           # Main chat interface
├── admin.php          # Admin dashboard
├── config.php         # Database configuration
├── database.sql       # Database schema
├── api/               # Backend API endpoints
│   ├── auth.php       # Authentication
│   ├── chats.php      # Chat management
│   ├── messages.php   # Message handling
│   └── users.php      # User management
├── css/
│   └── style.css      # Styles
├── js/
│   └── app.js         # Frontend logic
└── uploads/           # File uploads directory
```

## Security Features

- Password hashing using bcrypt
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- File upload validation and restrictions
- Session management
- Role-based access control

## Usage

### Creating a New Chat
1. Click "New Chat" button
2. Enter the username of the person you want to chat with
3. Start messaging

### Creating a Group Chat (Teachers/Admins only)
1. Click "New Group" button
2. Enter group name
3. Add participant user IDs
4. Start group conversation

### Sending Files
1. Click the attachment button (📎)
2. Select a file (max 5MB)
3. Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX

### Admin Functions
1. Navigate to Admin Panel from the chat interface
2. View system statistics
3. Manage users (edit, delete)
4. Monitor chat activity

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config.php`
- Ensure MySQL service is running
- Check if database exists

### File Upload Issues
- Check `uploads/` directory permissions (755)
- Verify PHP `upload_max_filesize` setting
- Ensure `.htaccess` is present in uploads directory

### Messages Not Updating
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Clear browser cache

## Future Enhancements

- WebSocket integration for true real-time messaging
- Push notifications
- Video/audio calling
- Message reactions and emojis
- Advanced search and filters
- Message encryption
- Mobile app versions

## License

This project is for educational purposes.

## Support

For issues or questions, contact your system administrator.
