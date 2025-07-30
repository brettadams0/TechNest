# TechNest Admin User Guide

## Welcome, Administrator!

This guide provides comprehensive instructions for managing the TechNest e-commerce platform. As an admin, you have access to all system functions including product management, user administration, order processing, and theme customization.

## Table of Contents
1. [Admin Access](#admin-access)
2. [Admin Dashboard](#admin-dashboard)
3. [Product Management](#product-management)
4. [User Management](#user-management)
5. [Order Management](#order-management)
6. [Theme Management](#theme-management)
7. [Troubleshooting](#troubleshooting)

## Admin Access

### Logging into Admin Panel
1. **Navigate to the website** and click **"Login"**
2. **Enter your admin credentials**:
   - Username or email address
   - Password
3. **Click "Login"** to access your account
4. **Access admin panel** by clicking **"Admin"** in the navigation or visiting `/admin/`

### Admin Authentication
- **Admin accounts** are separate from regular user accounts
- **Admin privileges** are granted through the database admin table
- **Session management** keeps you logged in during admin tasks
- **Logout** when finished to maintain security

## Admin Dashboard

### Dashboard Overview
The admin dashboard provides quick access to all management functions:

- **Manage Products**: Add, edit, and remove products
- **Manage Users**: View and manage user accounts
- **Manage Orders**: Process and track customer orders
- **Manage Themes**: Customize site appearance

### Dashboard Features
- **Quick statistics**: View recent orders, users, and products
- **Navigation shortcuts**: Direct access to all admin functions
- **System status**: Monitor site health and performance

## Product Management

### Accessing Product Management
1. **From admin dashboard**: Click **"Manage Products"**
2. **Direct URL**: Navigate to `/admin/manage_products.php`

### Adding New Products
1. **Click "Add New Product"** button
2. **Fill out product information**:
   - **Product Name**: Clear, descriptive name
   - **Description**: Detailed product information
   - **Price**: Set accurate pricing (decimal format)
   - **Image URL**: Path to product image
3. **Click "Save Product"** to add to catalog

### Product Information Fields
- **Product ID**: Auto-generated unique identifier
- **Name**: Product title (required)
- **Description**: Detailed product description
- **Image URL**: Path to product image file
- **Price**: Product cost in decimal format
- **Created Date**: Automatic timestamp

### Editing Products
1. **Find the product** in the product list
2. **Click "Edit"** next to the product
3. **Modify information** as needed
4. **Click "Update Product"** to save changes

### Deleting Products
1. **Find the product** in the product list
2. **Click "Delete"** next to the product
3. **Confirm deletion** in the popup dialog
4. **Product is permanently removed** from catalog

### Product Options Management
Many products have customizable options:

#### Adding Product Options
1. **Edit the product** you want to add options to
2. **Scroll to "Product Options"** section
3. **Add option details**:
   - **Option Name**: Type of option (e.g., "Color", "Storage")
   - **Option Value**: Specific value (e.g., "Red", "256GB")
   - **Extra Price**: Additional cost for this option
4. **Click "Add Option"** to save

#### Managing Existing Options
- **Edit options**: Modify values and pricing
- **Remove options**: Delete unwanted options
- **Reorder options**: Change display order

## User Management

### Accessing User Management
1. **From admin dashboard**: Click **"Manage Users"**
2. **Direct URL**: Navigate to `/admin/manage_users.php`

### Viewing User List
The user management page displays:
- **User ID**: Unique identifier
- **Username**: User's login name
- **Email**: User's email address
- **Status**: Active/Inactive status
- **Registration Date**: When account was created
- **Actions**: Edit/Delete options

### User Account Actions

#### Viewing User Details
1. **Click on username** to view detailed information
2. **Review user data**:
   - Account information
   - Order history
   - Account status

#### Editing User Information
1. **Click "Edit"** next to the user
2. **Modify user data**:
   - Username
   - Email address
   - Account status
3. **Click "Update User"** to save changes

#### Deactivating Users
1. **Click "Edit"** next to the user
2. **Change status** to "Inactive"
3. **Save changes** to deactivate account

#### Deleting Users
1. **Click "Delete"** next to the user
2. **Confirm deletion** in popup dialog
3. **User account is permanently removed**

### Admin User Management
- **Creating admin accounts**: Add users to admin table
- **Removing admin privileges**: Remove from admin table
- **Admin security**: Limit admin access to trusted users

## Order Management

### Accessing Order Management
1. **From admin dashboard**: Click **"Manage Orders"**
2. **Direct URL**: Navigate to `/admin/manage_orders.php`

### Viewing Orders
The order management page shows:
- **Order ID**: Unique order identifier
- **Customer**: Username of ordering customer
- **Order Date**: When order was placed
- **Total Amount**: Order total
- **Status**: Current order status
- **Actions**: View/Update options

### Order Processing Workflow

#### New Orders (Pending)
1. **Review order details** by clicking on order
2. **Verify customer information** and order items
3. **Check inventory** for ordered products
4. **Update status** to "Processing" when ready

### Order Details View
Clicking on an order shows:
- **Customer information**: Name, email, contact details
- **Order items**: Products, quantities, options, prices
- **Order total**: Complete cost breakdown
- **Order history**: Status change timeline

### Order Actions
- **Update status**: Change order processing status
- **Add notes**: Internal notes about order
- **Cancel order**: Cancel with reason
- **Export order**: Download order details

## Theme Management

### Accessing Theme Management
1. **From admin dashboard**: Click **"Manage Themes"**
2. **Direct URL**: Navigate to `/admin/manage_themes.php`

### Available Themes
TechNest includes three built-in themes:
- **Regular**: Standard TechNest design
- **Minimal**: Clean, simple interface
- **Holiday**: Festive seasonal theme

### Theme Configuration
1. **Select active theme** from dropdown
2. **Preview theme** before applying
3. **Apply theme** to make it live
4. **Theme changes** are immediate

### Theme Features
- **Responsive design**: Works on all devices
- **Consistent navigation**: Maintains site structure
- **Product compatibility**: All themes display products properly

### Custom Theme Development
To add custom themes:
1. **Create CSS file** in `/assets/` directory
2. **Add theme record** to database
3. **Test theme** thoroughly before activation
4. **Document theme** for future reference

## Troubleshooting

### Common Admin Issues

#### Can't Access Admin Panel
- **Check login credentials**: Verify username/password
- **Confirm admin privileges**: Check database admin table
- **Clear browser cache**: Try different browser
- **Check file permissions**: Ensure admin files are accessible

#### Product Management Issues
- **Images not displaying**: Check file paths and permissions
- **Products not saving**: Verify database connection
- **Options not working**: Check product_options table structure

#### User Management Problems
- **Can't edit users**: Verify database permissions
- **User data not updating**: Check form validation
- **Admin privileges not working**: Verify admin table entries

#### Order Processing Issues
- **Orders not displaying**: Check database queries
- **Status not updating**: Verify order table structure
- **Email notifications**: Check email configuration

### Database Issues
- **Connection errors**: Verify database credentials
- **Query failures**: Check SQL syntax and table structure
- **Performance problems**: Optimize database queries

### File System Issues
- **Upload problems**: Check directory permissions
- **Image display issues**: Verify file paths
- **Theme loading**: Check CSS file locations

### Performance Optimization
- **Database optimization**: Index frequently queried fields
- **Image optimization**: Compress product images
- **Caching**: Implement page caching if needed
- **CDN usage**: Use content delivery networks for assets

---

**Need Admin Support?** Contact me at adamsbrett00@gmail.com.
