<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAEL POS - Professional Point of Sale System</title>
    <link rel="icon" type="image/svg+xml" 
    href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64' viewBox='0 0 60 60'%3E%3Cdefs%3E%3ClinearGradient id='lael-grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%230046B8'/%3E%3Cstop offset='100%25' stop-color='%23007BFF'/%3E%3C/linearGradient%3E%3C/defs%3E%3Ccircle cx='30' cy='30' r='26' fill='url(%23lael-grad)'/%3E%3Ctext x='15' y='40' font-family='Poppins, sans-serif' font-size='26' fill='%23FFFFFF' font-weight='700'%3E/= %3C/text%3E%3C/svg%3E" />

    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #1e3a8a;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --light-bg: #f8fafc;
            --dark-bg: #1e293b;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-primary-custom {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .btn-primary-custom:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .section-subtitle {
            font-size: 1.125rem;
            color: var(--text-light);
            text-align: center;
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon i {
            font-size: 1.75rem;
            color: var(--accent-color);
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .feature-description {
            color: var(--text-light);
        }
        
        .pricing-section {
            background-color: var(--light-bg);
            padding: 100px 0;
        }
        
        .pricing-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
        }
        
        .pricing-card.popular {
            border: 2px solid var(--accent-color);
            position: relative;
        }
        
        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-color);
            color: white;
            padding: 0.25rem 1.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .pricing-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .pricing-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
        }
        
        .pricing-period {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        .pricing-features {
            padding: 2rem;
        }
        
        .pricing-feature {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .pricing-feature i {
            color: var(--success-color);
            margin-right: 0.75rem;
        }
        
        .pricing-feature.disabled {
            color: var(--text-light);
        }
        
        .pricing-feature.disabled i {
            color: #cbd5e1;
        }
        
        .pricing-footer {
            padding: 0 2rem 2rem;
            text-align: center;
        }
        
        .clients-section {
            padding: 80px 0;
        }
        
        .client-logo {
            filter: grayscale(100%);
            opacity: 0.6;
            transition: all 0.3s ease;
        }
        
        .client-logo:hover {
            filter: grayscale(0%);
            opacity: 1;
        }
        
        .testimonial-section {
            background-color: var(--dark-bg);
            color: white;
            padding: 100px 0;
        }
        
        .testimonial-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            height: 100%;
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .testimonial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
            background-color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .faq-section {
            padding: 100px 0;
            background-color: var(--light-bg);
        }
        
        .accordion-button:not(.collapsed) {
            background-color: white;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .footer-section {
            background-color: var(--dark-bg);
            color: white;
            padding: 80px 0 40px;
        }
        
        .footer-links h5 {
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
        }
        
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin-bottom: 0.75rem;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 0.75rem;
            color: white;
            transition: background 0.3s ease;
        }
        
        .social-icons a:hover {
            background: var(--accent-color);
        }
        
        .copyright {
            border-top: 1px solid #334155;
            padding-top: 2rem;
            margin-top: 3rem;
            color: #94a3b8;
        }
        
        .nav-tabs .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            border: none;
            padding: 0.75rem 1.5rem;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
            background: transparent;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    @yield('content')
    <!-- Footer -->
    <footer class="footer-section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a href="/">
                        <svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" viewBox="0 0 200 60">
                            <defs>
                                <linearGradient id="lael-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#0046B8"/>
                                <stop offset="100%" stop-color="#007BFF"/>
                                </linearGradient>
                            </defs>
                            <!-- Blue gradient circle background -->
                            <circle cx="30" cy="30" r="20" fill="url(#lael-grad)" />
                            <!-- White minimal exchange symbol /= -->
                            <text x="20" y="38" font-family="Poppins, sans-serif" font-size="24" fill="#FFFFFF" font-weight="700">/=</text>
                            <!-- Product name -->
                            <text x="65" y="37" font-family="Poppins, sans-serif" font-size="26" fill="#FFFFFF" font-weight="600">LAEL</text>
                        </svg>
                    </a>
                    <p class="text-white-50 mb-4">The all-in-one point of sale solution designed for African businesses. Streamline your operations with our powerful, affordable system.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="footer-links">
                        <h5>Product</h5>
                        <a href="#features">Features</a>
                        <a href="#pricing">Pricing</a>
                        <a href="#">Updates</a>
                        <a href="#">Download</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="footer-links">
                        <h5>Company</h5>
                        <a href="#">About Us</a>
                        <a href="#">Careers</a>
                        <a href="#">Blog</a>
                        <a href="#">Contact</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="footer-links">
                        <h5>Support</h5>
                        <a href="#">Help Center</a>
                        <a href="#">Documentation</a>
                        <a href="#">Community</a>
                        <a href="#">Status</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <div class="footer-links">
                        <h5>Legal</h5>
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                        <a href="#">GDPR</a>
                    </div>
                </div>
            </div>
            <div class="copyright text-center">
                <p class="mb-0">&copy; 2023 LAEL POS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Simple script to handle smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
