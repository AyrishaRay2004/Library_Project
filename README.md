# Library Management System

A PHP and Oracle-based Library Management System.

## Project Screenshots

### Login Page
![Login Page](Screenshots/login_page/index_Page1.jpg)
![Login Page](Screenshots/login_page/index_Page2.jpg)

### Admin Dashboard
![Admin Dashboard](Screenshots/AdminDashboard.jpg)

### Form (On filling the form the data get stored in the database)
![Admin Dashboard](Screenshots/adddetailstodatabase.jpg)

### Reports
![Reports](Screenshots/Reports/report1.jpg)
![Reports](Screenshots/Reports/report2.jpg)
![Reports](Screenshots/Reports/report3.jpg)

### Visual Report
![Visual Report](Screenshots/charts.jpg)

### Fine Receipt
![Fine Receipt](Screenshots/FineReceipt.jpg)

### Database
![Database](Screenshots/database.jpg)


## Features
- Author, Book, Member Management
- Borrow & Return Records
- Overdue Fine Calculation
- Data Visualization using Highcharts

## Technologies Used
- PHP
- Oracle Database
- OCI8
- Bootstrap 5
- Highcharts

## Security
- Database credentials are stored in a separate config file
- config.php is excluded using .gitignore

## Setup Instructions
1. Install Oracle Database
2. Enable OCI8 in PHP
3. Import database.sql
4. Create config.php with DB credentials
5. Run using XAMPP / Apache
