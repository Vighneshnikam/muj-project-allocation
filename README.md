# Project Management Website

A web-based platform for managing student and faculty projects, built with PHP and Bootstrap.

## Features
- Secure login for students, faculty, and admins
- Project allocation and management
- Responsive, accessible design
- CSRF, XSS, and SQL injection protection
- SEO meta tags, sitemap, robots.txt
- Privacy Policy, Terms of Service, Contact page
- Cookie consent banner
- Error logging

## Setup
1. Clone/download the project to your web server (XAMPP recommended)
2. Import the provided SQL file into your MySQL database
3. Update database credentials in `connection/connection.php`
4. Set up mail settings in `contact.php` if needed
5. Ensure `error.log` is writable by the web server

## Security
- Enforces strong password policy
- All forms protected by CSRF tokens
- HTTPS enforced
- Error logging to file

## Accessibility & SEO
- All images have alt text
- Color contrast meets WCAG AA
- Breadcrumbs, sticky nav, and search on all main pages
- Sitemap and robots.txt included

## Contributing
- Fork the repo and submit pull requests
- Follow consistent code style and comment your code
- Report issues or suggest features via GitHub

## License
MIT (or your chosen license) 