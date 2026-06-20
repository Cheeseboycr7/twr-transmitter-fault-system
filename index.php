<?php
// index.php - Landing Page
require_once 'config/db.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TWR Transmitter Fault Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/twr-theme.css">
    <style>
        /* Additional landing page specific styles */
        .hero-section {
            background: linear-gradient(135deg, var(--twr-navy) 0%, var(--twr-navy-light) 100%);
            color: var(--twr-white);
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(0, 140, 140, 0.08);
            pointer-events: none;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(227, 82, 41, 0.05);
            pointer-events: none;
        }

        .hero-section h1 {
            color: var(--twr-white);
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .hero-section .highlight {
            color: var(--twr-teal);
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .hero-section .btn-primary {
            background-color: var(--twr-teal);
            border-color: var(--twr-teal);
            padding: 0.8rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 50px;
            position: relative;
            z-index: 1;
        }

        .hero-section .btn-primary:hover {
            background-color: var(--twr-teal-dark);
            border-color: var(--twr-teal-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 140, 140, 0.3);
        }

        .hero-section .btn-outline-light {
            border-radius: 50px;
            padding: 0.8rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .hero-image {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .hero-image i {
            font-size: 12rem;
            color: rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }

        .hero-image .badge-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.1);
            z-index: 2;
        }

        .hero-image .badge-container i {
            font-size: 5rem;
            color: var(--twr-teal);
            opacity: 0.8;
        }

        .feature-section {
            padding: 80px 0;
            background-color: var(--twr-white);
        }

        .feature-section .feature-card {
            text-align: center;
            padding: 2rem 1.5rem;
            border-radius: var(--twr-radius-md);
            transition: var(--twr-transition);
            height: 100%;
            border: 1px solid transparent;
        }

        .feature-section .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--twr-shadow-md);
            border-color: var(--twr-light-bg);
        }

        .feature-section .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--twr-navy) 0%, var(--twr-navy-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .feature-section .feature-icon i {
            font-size: 2.5rem;
            color: var(--twr-white);
        }

        .feature-section h5 {
            color: var(--twr-navy);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-section p {
            color: var(--twr-gray-600);
            font-size: 0.95rem;
        }

        .stats-section {
            background-color: var(--twr-light-bg);
            padding: 60px 0;
        }

        .stats-section .stat-item {
            text-align: center;
            padding: 1.5rem;
        }

        .stats-section .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--twr-navy);
            display: block;
        }

        .stats-section .stat-label {
            color: var(--twr-gray-600);
            font-weight: 500;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--twr-teal) 0%, var(--twr-teal-dark) 100%);
            color: var(--twr-white);
            padding: 60px 0;
            text-align: center;
        }

        .cta-section h2 {
            color: var(--twr-white);
            margin-bottom: 1rem;
        }

        .cta-section p {
            opacity: 0.9;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .cta-section .btn-light {
            padding: 0.8rem 2.5rem;
            font-weight: 600;
            border-radius: 50px;
            color: var(--twr-navy);
        }

        .cta-section .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .footer {
            background-color: var(--twr-navy-dark);
            color: rgba(255, 255, 255, 0.7);
            padding: 40px 0 20px;
        }

        .footer h6 {
            color: var(--twr-white);
            font-weight: 600;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--twr-transition);
        }

        .footer a:hover {
            color: var(--twr-teal);
        }

        .footer .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            text-align: center;
            line-height: 40px;
            margin-right: 0.5rem;
            transition: var(--twr-transition);
            color: var(--twr-white);
        }

        .footer .social-icons a:hover {
            background: var(--twr-teal);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0 40px;
                text-align: center;
            }

            .hero-section h1 {
                font-size: 2.2rem;
            }

            .hero-section p {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-image i {
                font-size: 6rem;
            }

            .hero-image .badge-container i {
                font-size: 3rem;
            }

            .hero-image .badge-container {
                padding: 1.5rem;
            }

            .feature-section {
                padding: 40px 0;
            }

            .stats-section .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-broadcast"></i> TWR Fault System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary text-white px-3 ms-2" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary px-3 ms-2" href="register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>
                        Transmitter Fault<br>
                        <span class="highlight">Management System</span>
                    </h1>
                    <p>
                        A comprehensive knowledge base system for recording, tracking, and resolving
                        transmitter faults. Built for broadcast engineers by broadcast engineers.
                    </p>
                    <div>
                        <a href="register.php" class="btn btn-primary me-3">
                            <i class="bi bi-person-plus"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-outline-light">
                            <i class="bi bi-chevron-down"></i> Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <i class="bi bi-broadcast"></i>
                        <div class="badge-container">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="feature-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2>System Features</h2>
                <p class="text-muted">Everything you need to manage transmitter faults efficiently</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-clipboard-plus"></i>
                        </div>
                        <h5>Fault Recording</h5>
                        <p>Quickly record faults with details like transmitter, frequency, severity, and description.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5>Knowledge Base</h5>
                        <p>Search through past faults and their solutions to quickly resolve similar issues.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-tools"></i>
                        </div>
                        <h5>Troubleshooting Log</h5>
                        <p>Document troubleshooting steps, measurements, and actions taken during fault resolution.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h5>Solution Tracking</h5>
                        <p>Record root causes, solutions, parts replaced, and repair time for each fault.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h5>Analytics Dashboard</h5>
                        <p>View statistics, charts, and reports on fault trends, common issues, and performance.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h5>Maintenance Management</h5>
                        <p>Schedule and track preventive maintenance tasks for all transmitters.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number" data-count="156">0</span>
                        <span class="stat-label">Total Faults Recorded</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number" data-count="149">0</span>
                        <span class="stat-label">Faults Resolved</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number" data-count="7">0</span>
                        <span class="stat-label">Open Faults</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number" data-count="95">0</span>
                        <span class="stat-label">% Resolution Rate</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="feature-section" style="background-color: var(--twr-white);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2>Built for Broadcast Professionals</h2>
                    <p class="text-muted" style="font-size: 1.1rem;">
                        The Transmitter Fault Management System is designed specifically for broadcast engineers
                        and technicians who need a reliable way to track faults and build a knowledge base.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-twr-teal me-2"></i>
                            Real-time fault tracking
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-twr-teal me-2"></i>
                            Collaborative troubleshooting
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-twr-teal me-2"></i>
                            Historical data analysis
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-twr-teal me-2"></i>
                            Report generation
                        </li>
                    </ul>
                    <a href="register.php" class="btn btn-primary mt-3">
                        <i class="bi bi-person-plus"></i> Join Now
                    </a>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-quote" style="font-size: 3rem; color: var(--twr-teal); opacity: 0.3;"></i>
                            <p class="fs-5 fst-italic" style="color: var(--twr-navy);">
                                "This system has revolutionized how we handle transmitter faults.
                                The knowledge base has saved us countless hours of troubleshooting."
                            </p>
                            <p class="fw-bold mb-0">— Broadcast Engineer</p>
                            <small class="text-muted">TWR Africa</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join other broadcast professionals in building a comprehensive fault management system.</p>
            <a href="register.php" class="btn btn-light">
                <i class="bi bi-person-plus"></i> Create Your Account
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h6><i class="bi bi-broadcast text-twr-teal"></i> TWR Fault System</h6>
                    <p style="font-size: 0.9rem;">
                        A comprehensive fault management and knowledge base system for broadcast transmitters.
                    </p>
                    <div class="social-icons">
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                        <a href="#"><i class="bi bi-github"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4 mb-md-0">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled" style="font-size: 0.9rem;">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h6>System Modules</h6>
                    <ul class="list-unstyled" style="font-size: 0.9rem;">
                        <li><a href="#">Fault Recording</a></li>
                        <li><a href="#">Knowledge Base</a></li>
                        <li><a href="#">Troubleshooting</a></li>
                        <li><a href="#">Analytics</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contact</h6>
                    <ul class="list-unstyled" style="font-size: 0.9rem;">
                        <li><i class="bi bi-envelope me-2"></i> support@twrfaultsystem.com</li>
                        <li><i class="bi bi-phone me-2"></i> +27 11 234 5678</li>
                        <li><i class="bi bi-geo-alt me-2"></i> Johannesburg, South Africa</li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center" style="font-size: 0.85rem;">
                &copy; <?= date('Y') ?> TWR Fault Management System. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animated counter for stats
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-number');

            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const duration = 2000; // 2 seconds
                const step = Math.ceil(target / (duration / 16)); // ~60fps
                let current = 0;

                const updateCounter = () => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target + '+';
                        return;
                    }
                    counter.textContent = current;
                    requestAnimationFrame(updateCounter);
                };

                // Start counter when element is visible
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCounter();
                            observer.unobserve(entry.target);
                        }
                    });
                });

                observer.observe(counter);
            });
        });
    </script>
</body>

</html>