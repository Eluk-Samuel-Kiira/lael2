@extends('home.layout')

@section('title', 'Terms of Service - STARDENA SUITE')
@section('description', 'STARDENA SUITE terms of service. Read our legal agreement for using our point-of-sale system.')

@section('content')

<!-- Page Header -->
<section class="page-header section accent-background" style="padding: 120px 0 60px;">
    <div class="container" data-aos="fade-up">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 mb-4">
                    <span class="badge" style="background: rgba(251, 115, 57, 0.15); color: #fb7339; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.5px;">
                        <i class="bi bi-file-earmark-text me-1"></i> LEGAL
                    </span>
                </div>
                <h1 class="display-4 fw-bold" style="color: #E2EAF4;">
                    Terms of <span style="color: #fb7339;">Service</span>
                </h1>
                <p class="lead" style="color: #8899AA; max-width: 600px; margin: 0 auto;">
                    Effective Date: January 1, 2025 | Last Updated: May 12, 2026
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Terms of Service Content -->
<section id="terms" class="terms section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="terms-content-wrapper" style="background: var(--brand-card); border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); padding: 2.5rem; transition: all 0.3s ease;">
                    <div class="terms-content" style="color: #B0C4D8; line-height: 1.8;">

                        <h2 class="buy-btn">1. Agreement to Terms</h2>
                        <p>By accessing or using STARDENA SUITE ("the Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you may not access or use the Service. These Terms apply to all users, including business owners, staff members, and administrators.</p>

                        <h2 class="buy-btn">2. Description of Service</h2>
                        <p>STARDENA SUITE provides a cloud-based point-of-sale system for businesses, including features such as sales processing, inventory management, customer management, reporting, analytics, multi-currency support, and offline mode functionality. We reserve the right to modify, suspend, or discontinue any part of the Service at any time.</p>

                        <h2 class="buy-btn">3. Account Registration and Security</h2>
                        <p>To use the Service, you must create an account. You agree to:</p>
                        <ul style="padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Provide accurate, current, and complete information during registration</li>
                            <li style="margin-bottom: 0.5rem;">Maintain the security and confidentiality of your login credentials</li>
                            <li style="margin-bottom: 0.5rem;">Notify us immediately of any unauthorized access to your account</li>
                            <li style="margin-bottom: 0.5rem;">Accept responsibility for all activities that occur under your account</li>
                            <li style="margin-bottom: 0.5rem;">Not share your account credentials with unauthorized individuals</li>
                        </ul>
                        <p>We reserve the right to suspend or terminate accounts that violate these Terms.</p>

                        <h2 class="buy-btn">4. Subscriptions and Payments</h2>
                        <h3 class="h5 fw-bold mb-2" style="color: #06D6A0;">4.1 Pricing Plans</h3>
                        <p>We offer Free Trial, Starter, Business, and Enterprise plans, as well as one-time license options. Pricing and features are as described on our website and may be updated from time to time.</p>
                        
                        <h3 class="h5 fw-bold mb-2 mt-3" style="color: #06D6A0;">4.2 Billing and Renewals</h3>
                        <p>Monthly and yearly subscriptions auto-renew at the end of each billing period until cancelled. You may cancel at any time from your account settings. Cancellation takes effect at the end of your current billing period; no refunds are provided for partial periods.</p>
                        
                        <h3 class="h5 fw-bold mb-2 mt-3" style="color: #06D6A0;">4.3 One-Time Licenses</h3>
                        <p>One-time licenses grant perpetual access to the version purchased, including updates for one year. After one year, continued updates require a maintenance renewal.</p>
                        
                        <h3 class="h5 fw-bold mb-2 mt-3" style="color: #06D6A0;">4.4 Payment Methods</h3>
                        <p>We accept credit/debit cards, mobile money, and bank transfers. All fees are exclusive of applicable taxes, which you are responsible for paying.</p>

                        <h2 class="buy-btn">5. Free Trial</h2>
                        <p>New users may be eligible for a free trial. Free trial periods are as specified on our website. We reserve the right to modify or cancel free trials at any time. At the end of the free trial, you must upgrade to a paid plan or your account will be suspended.</p>

                        <h2 class="buy-btn">6. User Responsibilities and Acceptable Use</h2>
                        <p>You agree not to use the Service for any unlawful purpose or in any way that could damage, disable, overburden, or impair the Service. Prohibited activities include:</p>
                        <ul style="padding-left: 1.2rem;">
                            <li style="margin-bottom: 0.5rem;">Violating any applicable laws or regulations</li>
                            <li style="margin-bottom: 0.5rem;">Infringing on intellectual property rights of others</li>
                            <li style="margin-bottom: 0.5rem;">Distributing malware, viruses, or other harmful code</li>
                            <li style="margin-bottom: 0.5rem;">Attempting to gain unauthorized access to the Service or its systems</li>
                            <li style="margin-bottom: 0.5rem;">Using the Service to process fraudulent transactions</li>
                            <li style="margin-bottom: 0.5rem;">Reverse engineering, decompiling, or disassembling the Service</li>
                            <li style="margin-bottom: 0.5rem;">Reselling or redistributing the Service without authorization</li>
                        </ul>

                        <h2 class="buy-btn">7. Offline Mode</h2>
                        <p>The Service includes offline functionality. You acknowledge that offline transactions are stored locally on your device and become your responsibility until they are successfully synced to our servers. We are not liable for data loss due to device theft, damage, or failure. We recommend regular backups and secure storage of devices used with the Service.</p>

                        <h2 class="buy-btn">8. Data Ownership and Intellectual Property</h2>
                        <p><strong>Your Data:</strong> You retain all ownership rights to the business data you input into the Service (products, sales, customer information). We do not claim ownership of your data.</p>
                        <p><strong>Our IP:</strong> The Service, including its code, design, features, and trademarks, is owned by Stardena and protected by copyright and other intellectual property laws. You may not copy, modify, or create derivative works without our permission.</p>

                        <h2 class="buy-btn">9. Service Availability and Support</h2>
                        <p>We strive to maintain 99.9% uptime for the Service. However, we do not guarantee uninterrupted or error-free operation. Scheduled maintenance will be communicated in advance when possible. Support is provided via email and chat during business hours (Standard) and 24/7 for Enterprise customers.</p>

                        <h2 class="buy-btn">10. Limitation of Liability</h2>
                        <p>To the maximum extent permitted by law, STARDENA SUITE and its affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of profits, data, or goodwill, arising from your use of or inability to use the Service. Our total liability shall not exceed the amount you paid us in the 12 months preceding the claim.</p>

                        <h2 class="buy-btn">11. Disclaimer of Warranties</h2>
                        <p>The Service is provided "as is" and "as available" without warranties of any kind, either express or implied. We do not warrant that the Service will meet your specific requirements, be uninterrupted, secure, or error-free, or that defects will be corrected.</p>

                        <h2 class="buy-btn">12. Indemnification</h2>
                        <p>You agree to indemnify and hold harmless STARDENA SUITE, its officers, employees, and agents from any claims, damages, losses, or expenses arising from your use of the Service, violation of these Terms, or infringement of any third-party rights.</p>

                        <h2 class="buy-btn">13. Termination</h2>
                        <p>We may terminate or suspend your account immediately, without prior notice, for conduct that violates these Terms or is harmful to other users or our business. Upon termination, you must cease using the Service, and we may delete your data after a reasonable period (typically 30 days) unless legal retention is required.</p>

                        <h2 class="buy-btn">14. Governing Law</h2>
                        <p>These Terms shall be governed by and construed in accordance with the laws of the Republic of Uganda, without regard to its conflict of law provisions. Any disputes arising under these Terms shall be resolved exclusively in the courts of Kampala, Uganda.</p>

                        <h2 class="buy-btn">15. Changes to Terms</h2>
                        <p>We may modify these Terms at any time. We will notify you of material changes via email or through the Service. Your continued use of the Service after such changes constitutes acceptance of the new Terms.</p>

                        <h2 class="buy-btn">16. Entire Agreement</h2>
                        <p>These Terms constitute the entire agreement between you and STARDENA SUITE regarding the Service and supersede any prior agreements.</p>

                        <h2 class="buy-btn">17. Contact Us</h2>
                        <p>If you have any questions about these Terms, please contact us at:</p>
                        <p style="background: rgba(251, 115, 57, 0.05); border: 1px solid rgba(251, 115, 57, 0.1); border-radius: 12px; padding: 1.5rem; margin-top: 0.5rem;">
                            <strong>STARDENA SUITE by Stardena</strong><br>
                            Kampala, Uganda<br>
                            Email: <a href="mailto:pos@stardena.org" style="color: #fb7339; text-decoration: none; font-weight: 500;">pos@stardena.org</a><br>
                            Phone: +256 (0) 754 428 612
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection