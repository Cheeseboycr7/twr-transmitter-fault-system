
# 📡 TWR Transmitter Fault Management System

> A comprehensive fault management and knowledge base system for broadcast transmitters, designed for TWR (Trans World Radio) broadcast engineers and technicians.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-blue.svg)](https://mysql.com)
[![Bootstrap Version](https://img.shields.io/badge/Bootstrap-5.3-blue.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Screenshots](#-screenshots)
- [Technologies Used](#-technologies-used)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Configuration](#-configuration)
- [User Roles & Permissions](#-user-roles--permissions)
- [Usage Guide](#-usage-guide)
- [Directory Structure](#-directory-structure)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [Testing](#-testing)
- [Security](#-security)
- [Troubleshooting](#-troubleshooting)
- [FAQ](#-faq)
- [License](#-license)
- [Support](#-support)
- [Acknowledgments](#-acknowledgments)

---

## 📡 Overview

The **TWR Transmitter Fault Management System** is a web-based application designed to help broadcast engineers and technicians efficiently record, track, and resolve transmitter faults. It features a powerful searchable knowledge base that stores historical faults and their solutions, enabling quick resolution of similar issues and reducing downtime.

### 🎯 Key Benefits

- **Reduced Downtime**: Quick access to past solutions for similar faults
- **Knowledge Retention**: Preserve institutional knowledge about transmitter issues
- **Improved Efficiency**: Streamlined fault recording and tracking process
- **Better Decision Making**: Analytics and reports for informed maintenance decisions
- **Team Collaboration**: Multi-user system with role-based access control

---

## ✨ Features

### 🔧 Core Features

| Feature | Description |
|---------|-------------|
| **Fault Recording** | Record faults with transmitter, frequency, program, severity, and detailed description |
| **Troubleshooting Log** | Document troubleshooting steps, measurements, and actions taken |
| **Solution Database** | Store root causes, solutions, parts replaced, and repair time |
| **Knowledge Base** | Searchable database of past faults and their solutions |
| **Maintenance Management** | Schedule and track preventive maintenance tasks |
| **Analytics Dashboard** | View statistics, charts, and reports on fault trends |
| **User Management** | Role-based access control with 4 user levels |
| **Audit Trail** | Complete log of all user actions for accountability |

### 🎨 User Interface

- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **TWR Branding**: Custom color scheme based on TWR website
- **Dark Mode Ready**: CSS variables for easy theming
- **Interactive Charts**: Visual analytics with Chart.js
- **Intuitive Navigation**: Role-based menu items

### 🔒 Security Features

- **Password Hashing**: Secure password storage with PHP's password_hash()
- **CSRF Protection**: Cross-site request forgery protection
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Session Management**: Secure session handling with timeout
- **Audit Logging**: All actions are logged with IP addresses

---

## 📸 Screenshots

### Dashboard
![Dashboard](https://via.placeholder.com/800x400/0B2D4E/FFFFFF?text=TWR+Fault+System+Dashboard)

### Fault Recording
![Fault Recording](https://via.placeholder.com/800x400/008C8C/FFFFFF?text=Record+New+Fault)

### Knowledge Base Search
![Knowledge Base](https://via.placeholder.com/800x400/E35229/FFFFFF?text=Knowledge+Base+Search)

### Reports & Analytics
![Reports](https://via.placeholder.com/800x400/0B2D4E/FFFFFF?text=Reports+and+Analytics)

---

## 🛠️ Technologies Used

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| **PHP** | 7.4+ | Server-side scripting |
| **MySQL** | 5.7+ | Database management |
| **PDO** | - | Database abstraction layer |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| **Bootstrap** | 5.3 | UI framework |
| **Chart.js** | 4.4 | Data visualization |
| **Bootstrap Icons** | 1.10 | Icon library |
| **HTML5** | - | Structure |
| **CSS3** | - | Styling |
| **JavaScript** | ES6+ | Interactivity |

### Development Tools
| Tool | Purpose |
|------|---------|
| **Git** | Version control |
| **phpMyAdmin** | Database management |
| **XAMPP/WAMP** | Local development environment |

---

## 💻 System Requirements

### Minimum Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (MariaDB 10.2+)
- **Memory**: 256MB RAM
- **Storage**: 100MB free space
- **Browser**: Chrome 90+, Firefox 88+, Edge 90+, Safari 14+

### Recommended Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.20+
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher (MariaDB 10.5+)
- **Memory**: 512MB RAM
- **Storage**: 500MB free space

### PHP Extensions Required
```ini
extension=mysqli
extension=pdo_mysql
extension=json
extension=session
extension=date
extension=filter
extension=hash
extension=openssl
