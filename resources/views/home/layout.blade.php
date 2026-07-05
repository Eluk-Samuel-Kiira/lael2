<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'STARPOSS – #1 Global Point of Sale System for SMEs & Enterprise | Multi-Currency, Multi-Language POS')</title>

    <meta name="description" content="@yield('description', 'The world\'s fastest, most flexible POS system for retail, restaurants, hotels & more. Multi-currency, multi-language (EN/FR/ES), multi-location. Trusted across East Africa and worldwide. Start free today.')">
    
    <meta name="keywords" content="POS system, point of sale, Uganda POS, Kenya POS, Tanzania POS, East Africa POS, global POS software, multi-currency POS, hotel POS, restaurant POS, retail POS, SME point of sale, inventory management, STARPOSS">
    <meta name="author" content="STARPOSS by Stardena">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://starposs.stardena.org/">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://starposs.stardena.org/">
    <meta property="og:title" content="STARPOSS – Global POS System for Every Business">
    <meta property="og:description" content="Lightning-fast checkout. Multi-currency. Multi-language. Multi-location. The complete point-of-sale platform for businesses from East Africa to the world.">
    <meta property="og:image" content="https://starposs.stardena.org/favicon.png">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="STARPOSS – Global POS System">
    <meta name="twitter:description" content="Lightning-fast checkout. Multi-currency. Multi-language. Built for every business worldwide.">

    <!-- Geo Tags for Africa & Global -->
    <meta name="geo.region" content="UG">
    <meta name="geo.placename" content="Uganda, East Africa">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 60'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23FF6B2C'/%3E%3Cstop offset='100%25' stop-color='%23FF3D00'/%3E%3C/linearGradient%3E%3C/defs%3E%3Ccircle cx='30' cy='30' r='28' fill='url(%23g)'/%3E%3Ctext x='12' y='42' font-family='Georgia' font-size='28' fill='white' font-weight='700'%3ESP%3C/text%3E%3C/svg%3E">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --brand-orange: #FF6B2C;
            --brand-orange-dark: #E8541A;
            --brand-orange-light: #FF8C5A;
            --brand-deep: #0F1923;
            --brand-dark: #1A2535;
            --brand-card: #1E2D40;
            --brand-accent: #FFD166;
            --brand-green: #06D6A0;
            --brand-blue: #118AB2;
            --text-muted-custom: #8899AA;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--brand-deep);
            color: #E2EAF4;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5 {
            font-family: 'Sora', sans-serif;
            letter-spacing: -0.02em;
        }

        /* ═══════════════════════════════════════
           NAVBAR
        ═══════════════════════════════════════ */
        .navbar-custom {
            background: rgba(15, 25, 35, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,107,44,0.12);
            padding: 1rem 0;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            transition: var(--transition);
        }

        .navbar-custom.scrolled {
            padding: 0.6rem 0;
            box-shadow: 0 4px 30px rgba(0,0,0,0.4);
        }

        .logo-text {
            font-family: 'Sora', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B2C, #FFD166);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.03em;
        }

        .logo-star { color: #FF6B2C; }

        .nav-link-custom {
            color: #B0C4D8 !important;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 1rem !important;
            transition: var(--transition);
            position: relative;
        }

        .nav-link-custom::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%; right: 50%;
            height: 2px;
            background: var(--brand-orange);
            transition: var(--transition);
        }

        .nav-link-custom:hover { color: #fff !important; }
        .nav-link-custom:hover::after { left: 1rem; right: 1rem; }

        .btn-nav-demo {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            color: #fff !important;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.4rem !important;
            font-weight: 600;
            font-size: 0.875rem;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(255,107,44,0.35);
        }

        .btn-nav-demo:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,107,44,0.5);
        }

        /* ═══════════════════════════════════════
           HERO
        ═══════════════════════════════════════ */
        .hero {
            min-height: 100vh;
            padding: 140px 0 80px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, #0F1923 0%, #1A2535 50%, #0F1923 100%);
        }

        .hero-bg-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,107,44,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,107,44,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .hero-glow-1 {
            position: absolute;
            top: -200px; right: -200px;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(255,107,44,0.15) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-glow-2 {
            position: absolute;
            bottom: -300px; left: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(17,138,178,0.1) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,107,44,0.12);
            border: 1px solid rgba(255,107,44,0.3);
            color: var(--brand-orange-light);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            animation: fadeInDown 0.8s ease both;
        }

        .hero-badge .pulse-dot {
            width: 8px; height: 8px;
            background: var(--brand-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }

        .hero-title {
            font-size: clamp(2.4rem, 5vw, 4.5rem);
            font-weight: 800;
            line-height: 1.08;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.9s ease 0.1s both;
        }

        .hero-title .highlight {
            background: linear-gradient(135deg, #FF6B2C, #FFD166);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-sub {
            font-size: 1.15rem;
            color: var(--text-muted-custom);
            line-height: 1.7;
            max-width: 560px;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.9s ease 0.2s both;
        }

        .hero-cta-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            animation: fadeInUp 0.9s ease 0.3s both;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 0.85rem 2rem;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(255,107,44,0.4);
        }

        .btn-hero-primary:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255,107,44,0.55);
        }

        .btn-hero-ghost {
            background: rgba(255,255,255,0.06);
            color: #E2EAF4;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 0.85rem 2rem;
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-hero-ghost:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .hero-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 3rem;
            animation: fadeInUp 0.9s ease 0.4s both;
        }

        .hero-stat-item { text-align: left; }

        .hero-stat-num {
            font-family: 'Sora', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand-orange-light);
            line-height: 1;
        }

        .hero-stat-label {
            font-size: 0.8rem;
            color: var(--text-muted-custom);
            margin-top: 2px;
        }

        /* Hero Visual */
        .hero-visual {
            position: relative;
            animation: fadeInRight 1s ease 0.3s both;
        }

        .hero-screen {
            background: var(--brand-card);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255,255,255,0.08);
            overflow: hidden;
            box-shadow: 0 40px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,107,44,0.1);
        }

        .hero-screen-bar {
            background: rgba(255,255,255,0.04);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .screen-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
        }

        .screen-dot.red { background: #FF5F57; }
        .screen-dot.yellow { background: #FEBC2E; }
        .screen-dot.green { background: #28C840; }

        .pos-interface {
            padding: 1.5rem;
        }

        .pos-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .pos-greeting {
            font-size: 0.75rem;
            color: var(--text-muted-custom);
        }

        .pos-amount {
            font-family: 'Sora', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand-orange-light);
        }

        .pos-currency-tag {
            background: rgba(255,107,44,0.15);
            color: var(--brand-orange-light);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .pos-items { margin-bottom: 1rem; }

        .pos-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.8rem;
        }

        .pos-item-name { color: #B0C4D8; }
        .pos-item-price { color: #E2EAF4; font-weight: 600; }

        .pos-payment-methods {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .payment-badge {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.7rem;
            color: #B0C4D8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .payment-badge.active {
            background: rgba(255,107,44,0.15);
            border-color: rgba(255,107,44,0.3);
            color: var(--brand-orange-light);
        }

        .pos-checkout-btn {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            color: #fff;
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 0.65rem;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .floating-tag {
            position: absolute;
            background: var(--brand-card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 0.6rem 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: float 3s ease-in-out infinite;
            font-size: 0.8rem;
        }

        .floating-tag.tag-1 { top: -20px; right: -30px; animation-delay: 0s; }
        .floating-tag.tag-2 { bottom: 30px; left: -30px; animation-delay: 1.5s; }

        .tag-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ═══════════════════════════════════════
           TRUST BAR
        ═══════════════════════════════════════ */
        .trust-bar {
            background: rgba(26, 37, 53, 0.8);
            border-top: 1px solid rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 1.5rem 0;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted-custom);
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.25rem 0;
        }

        .trust-item i {
            color: var(--brand-green);
            font-size: 1rem;
        }

        /* ═══════════════════════════════════════
           SECTIONS SHARED
        ═══════════════════════════════════════ */
        section { padding: 100px 0; }

        .section-label {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--brand-orange);
            margin-bottom: 0.75rem;
        }

        .section-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1rem;
            color: #E2EAF4;
        }

        .section-sub {
            font-size: 1.05rem;
            color: var(--text-muted-custom);
            max-width: 600px;
            line-height: 1.7;
        }

        /* ═══════════════════════════════════════
           FEATURES SECTION
        ═══════════════════════════════════════ */
        .features-section {
            background: var(--brand-deep);
        }

        .feature-card {
            background: var(--brand-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius-lg);
            padding: 2rem;
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--brand-orange), transparent);
            opacity: 0;
            transition: var(--transition);
        }

        .feature-card:hover {
            border-color: rgba(255,107,44,0.2);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .feature-card:hover::before { opacity: 1; }

        .feature-icon-wrap {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            font-size: 1.25rem;
        }

        .feature-card h4 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: #E2EAF4;
        }

        .feature-card p {
            font-size: 0.875rem;
            color: var(--text-muted-custom);
            line-height: 1.65;
            margin: 0;
        }

        /* ═══════════════════════════════════════
           WORLD REACH
        ═══════════════════════════════════════ */
        .reach-section {
            background: linear-gradient(160deg, #1A2535 0%, #0F1923 100%);
        }

        .country-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            color: #B0C4D8;
            margin: 0.25rem;
            transition: var(--transition);
        }

        .country-chip:hover {
            background: rgba(255,107,44,0.1);
            border-color: rgba(255,107,44,0.3);
            color: var(--brand-orange-light);
        }

        .lang-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(6,214,160,0.1);
            border: 1px solid rgba(6,214,160,0.25);
            color: var(--brand-green);
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0.25rem;
        }

        /* ═══════════════════════════════════════
           PRICING
        ═══════════════════════════════════════ */
        .pricing-section {
            background: var(--brand-deep);
        }

        .billing-tabs {
            display: flex;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 50px;
            padding: 4px;
            gap: 4px;
            display: inline-flex;
            margin-bottom: 3rem;
        }

        .billing-tab {
            border: none;
            background: transparent;
            color: var(--text-muted-custom);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .billing-tab.active {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            color: #fff;
            box-shadow: 0 4px 12px rgba(255,107,44,0.4);
        }

        .pricing-card {
            background: var(--brand-card);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .pricing-card.popular {
            border-color: var(--brand-orange);
            box-shadow: 0 0 0 1px var(--brand-orange), 0 20px 50px rgba(255,107,44,0.2);
        }

        .popular-ribbon {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            text-align: center;
            padding: 0.4rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pricing-header {
            padding: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .plan-name {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #E2EAF4;
            margin-bottom: 0.5rem;
        }

        .plan-desc { font-size: 0.8rem; color: var(--text-muted-custom); margin-bottom: 1.5rem; }

        .plan-price {
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 2.8rem;
            color: #E2EAF4;
            line-height: 1;
        }

        .plan-price sup {
            font-size: 1.2rem;
            font-weight: 600;
            vertical-align: super;
        }

        .plan-price .period {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--text-muted-custom);
        }

        .plan-trial {
            font-size: 0.75rem;
            color: var(--brand-green);
            margin-top: 0.5rem;
        }

        .pricing-features {
            padding: 1.5rem 2rem;
            flex: 1;
        }

        .feature-list-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #B0C4D8;
        }

        .feature-list-item i.yes { color: var(--brand-green); flex-shrink: 0; margin-top: 2px; }
        .feature-list-item i.no { color: #3D4E60; flex-shrink: 0; margin-top: 2px; }
        .feature-list-item.dimmed { color: #4A5568; }

        .view-more-btn {
            background: none;
            border: none;
            color: var(--brand-orange);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: var(--transition);
        }

        .view-more-btn:hover { color: var(--brand-orange-light); }

        .hidden-features { display: none; }
        .hidden-features.shown { display: block; }

        .pricing-footer {
            padding: 0 2rem 2rem;
        }

        .btn-plan {
            width: 100%;
            padding: 0.85rem;
            border-radius: 12px;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-plan-outline {
            background: transparent;
            border: 1px solid rgba(255,107,44,0.4);
            color: var(--brand-orange);
        }

        .btn-plan-outline:hover {
            background: rgba(255,107,44,0.1);
            border-color: var(--brand-orange);
        }

        .btn-plan-primary {
            background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-dark));
            color: #fff;
            box-shadow: 0 6px 20px rgba(255,107,44,0.35);
        }

        .btn-plan-primary:hover {
            box-shadow: 0 10px 30px rgba(255,107,44,0.5);
            transform: translateY(-1px);
        }

        /* ═══════════════════════════════════════
           TESTIMONIALS
        ═══════════════════════════════════════ */
        .testimonials-section {
            background: linear-gradient(160deg, #1A2535 0%, #0F1923 100%);
        }

        .testimonial-card {
            background: var(--brand-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius-lg);
            padding: 2rem;
            height: 100%;
            position: relative;
            transition: var(--transition);
        }

        .testimonial-card:hover {
            border-color: rgba(255,107,44,0.2);
            transform: translateY(-3px);
        }

        .stars {
            color: var(--brand-accent);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .testimonial-text {
            font-size: 0.95rem;
            color: #B0C4D8;
            line-height: 1.7;
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.875rem;
            color: #fff;
            flex-shrink: 0;
        }

        .author-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #E2EAF4;
        }

        .author-role {
            font-size: 0.75rem;
            color: var(--text-muted-custom);
        }

        /* ═══════════════════════════════════════
           FAQ
        ═══════════════════════════════════════ */
        .faq-section { background: var(--brand-deep); }

        .faq-item {
            background: var(--brand-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius-lg);
            margin-bottom: 0.75rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .faq-item:hover { border-color: rgba(255,107,44,0.15); }

        .faq-question {
            width: 100%;
            background: none;
            border: none;
            padding: 1.25rem 1.5rem;
            text-align: left;
            color: #E2EAF4;
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .faq-question:hover { color: var(--brand-orange-light); }

        .faq-icon {
            flex-shrink: 0;
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(255,107,44,0.1);
            border: 1px solid rgba(255,107,44,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand-orange);
            font-size: 0.75rem;
            transition: var(--transition);
        }

        .faq-item.open .faq-icon { transform: rotate(45deg); }
        .faq-item.open .faq-question { color: var(--brand-orange-light); }

        .faq-answer {
            display: none;
            padding: 0 1.5rem 1.25rem;
            color: var(--text-muted-custom);
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .faq-answer.show { display: block; }

        /* ═══════════════════════════════════════
           CTA SECTION
        ═══════════════════════════════════════ */
        .cta-section {
            background: linear-gradient(135deg, var(--brand-orange-dark) 0%, #B83200 100%);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        /* ═══════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════ */
        .footer {
            background: #080F18;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 80px 0 40px;
        }

        .footer-brand p {
            font-size: 0.875rem;
            color: var(--text-muted-custom);
            line-height: 1.7;
            max-width: 280px;
        }

        .footer-title {
            font-family: 'Sora', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #E2EAF4;
            margin-bottom: 1.25rem;
        }

        .footer-links { list-style: none; padding: 0; margin: 0; }

        .footer-links li { margin-bottom: 0.6rem; }

        .footer-links a {
            color: var(--text-muted-custom);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .footer-links a:hover { color: var(--brand-orange-light); }

        .social-link {
            display: inline-flex;
            width: 38px; height: 38px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            align-items: center;
            justify-content: center;
            color: var(--text-muted-custom);
            font-size: 0.875rem;
            text-decoration: none;
            transition: var(--transition);
            margin-right: 0.5rem;
        }

        .social-link:hover {
            background: rgba(255,107,44,0.15);
            border-color: rgba(255,107,44,0.3);
            color: var(--brand-orange-light);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 2rem;
            margin-top: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: var(--text-muted-custom);
            font-size: 0.8rem;
        }

        /* ═══════════════════════════════════════
           MODAL
        ═══════════════════════════════════════ */
        .modal-content {
            background: var(--brand-dark);
            border: 1px solid rgba(255,107,44,0.2);
            border-radius: var(--radius-xl);
            color: #E2EAF4;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
        }

        .btn-close { filter: invert(1); }

        .modal-body { padding: 2rem; }

        .form-control, .form-select {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #E2EAF4;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,107,44,0.4);
            color: #E2EAF4;
            box-shadow: 0 0 0 3px rgba(255,107,44,0.1);
        }

        .form-control::placeholder { color: #4A5568; }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #B0C4D8;
            margin-bottom: 0.5rem;
        }

        .form-select option { background: var(--brand-dark); }

        /* ═══════════════════════════════════════
           HOTEL COMING SOON
        ═══════════════════════════════════════ */
        .hotel-card {
            background: linear-gradient(135deg, rgba(17,138,178,0.1), rgba(6,214,160,0.05));
            border: 1px dashed rgba(17,138,178,0.3);
            border-radius: var(--radius-xl);
            padding: 3rem 2rem;
            text-align: center;
        }

        .coming-soon-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(17,138,178,0.15);
            border: 1px solid rgba(17,138,178,0.35);
            color: #4DC8E8;
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width: 991px) {
            .hero { padding: 120px 0 60px; }
            .hero-visual { margin-top: 3rem; }
            .floating-tag.tag-1 { display: none; }
            .floating-tag.tag-2 { display: none; }
        }

        @media (max-width: 767px) {
            section { padding: 70px 0; }
            .hero-stats { gap: 1.25rem; }
            .billing-tabs { width: 100%; justify-content: center; }
            .footer-bottom { text-align: center; justify-content: center; }
        }

        /* ═══════════════════════════════════════
           NAVBAR TOGGLER
        ═══════════════════════════════════════ */
        .navbar-toggler {
            border: 1px solid rgba(255,255,255,0.15);
            padding: 0.4rem 0.7rem;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255,255,255,0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ═══════════════════════════════════════
           SCROLL ANIMATIONS
        ═══════════════════════════════════════ */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* stagger delays */
        .delay-1 { transition-delay: 0.1s; }
        .delay-2 { transition-delay: 0.2s; }
        .delay-3 { transition-delay: 0.3s; }
        .delay-4 { transition-delay: 0.4s; }
        .delay-5 { transition-delay: 0.5s; }
    </style>
</head>

<body>
    
    <!-- ═══════════════════════════════════════
        NAVBAR
    ═══════════════════════════════════════ -->
    <nav class="navbar-custom" id="mainNav">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <a href="/" class="text-decoration-none">
                    <span class="logo-text">★ STARPOSS</span>
                </a>

                <button class="navbar-toggler d-lg-none" type="button" id="mobileMenuToggle">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="d-none d-lg-flex align-items-center gap-1" id="navLinks">
                    <a href="/#features" class="nav-link-custom">Features</a>
                    <a href="/#reach" class="nav-link-custom">Global Reach</a>
                    <a href="/#pricing" class="nav-link-custom">Pricing</a>
                    <a href="/#testimonials" class="nav-link-custom">Reviews</a>
                    <a href="/#faq" class="nav-link-custom">FAQ</a>
                    <a href="/#contact" class="nav-link-custom">Contact</a>
                    <a href="{{ route('login') }}" class="btn-nav-demo ms-3">
                        <i class="fas fa-play-circle me-1"></i> Live Demo
                    </a>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="d-lg-none w-100 mt-3 d-none" id="mobileMenu">
                <a href="/#features" class="d-block py-2 px-1 nav-link-custom border-bottom border-white border-opacity-10">Features</a>
                <a href="/#reach" class="d-block py-2 px-1 nav-link-custom border-bottom border-white border-opacity-10">Global Reach</a>
                <a href="/#pricing" class="d-block py-2 px-1 nav-link-custom border-bottom border-white border-opacity-10">Pricing</a>
                <a href="/#testimonials" class="d-block py-2 px-1 nav-link-custom border-bottom border-white border-opacity-10">Reviews</a>
                <a href="/#faq" class="d-block py-2 px-1 nav-link-custom border-bottom border-white border-opacity-10">FAQ</a>
                <a href="/#contact" class="d-block py-2 px-1 nav-link-custom border-bottom border-white border-opacity-10">Contact</a>
                <a href="{{ route('login') }}" class="btn-hero-primary mt-3 w-100 justify-content-center">
                    <i class="fas fa-play-circle"></i> Live Demo
                </a>
            </div>
        </div>
    </nav>

    @yield('content')


    <!-- ═══════════════════════════════════════
        FOOTER
    ═══════════════════════════════════════ -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4 footer-brand">
                    <div class="logo-text mb-3" style="font-size:1.8rem;">★ STARPOSS</div>
                    <p>The global point-of-sale platform for modern businesses. Built in East Africa. Used worldwide.</p>
                    <div class="mt-3">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="footer-title">Product</div>
                    <ul class="footer-links">
                        <li><a href="/#features">Features</a></li>
                        <li><a href="/#pricing">Pricing</a></li>
                        <li><a href="/docs">Documentation</a></li>
                        <li><a href="/contact">Contact Sales</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="footer-title">Business</div>
                    <ul class="footer-links">
                        <li><a href="/#features">Retail</a></li>
                        <li><a href="/#features">Restaurants</a></li>
                        <li><a href="/#features">Hotels</a></li>
                        <li><a href="/#features">Healthcare</a></li>
                        <li><a href="/#features">Education</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="footer-title">Company</div>
                    <ul class="footer-links">
                        <li><a href="/about">About Us</a></li>
                        <li><a href="/blog">Blog</a></li>
                        <li><a href="/careers">Careers</a></li>
                        <li><a href="/contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="footer-title">Support</div>
                    <ul class="footer-links">
                        <li><a href="/help">Help Center</a></li>
                        <li><a href="/docs">Documentation</a></li>
                        <li><a href="/privacy-policy">Privacy Policy</a></li>
                        <li><a href="/terms-of-service">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© 2025 STARPOSS by <a href="https://stardena.org" target="_blank" style="color:var(--brand-orange);">Stardena</a>. All rights reserved.</span>
                <span>Made with ❤️ in Uganda 🇺🇬 · Deployed Worldwide 🌍</span>
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════
        CONTACT / GET STARTED MODAL
    ═══════════════════════════════════════ -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-star me-2" style="color:var(--brand-orange);"></i>
                        Get Started with STARPOSS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.875rem;color:var(--text-muted-custom);margin-bottom:1.5rem;">
                        Tell us about your business and our team will get in touch within 24 hours.
                    </p>
                    <div id="modalFormArea">
                        <div class="mb-3">
                            <label class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="contactName" placeholder="e.g. John Mukasa">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="contactEmail" placeholder="you@company.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone / WhatsApp</label>
                            <input type="text" class="form-control" id="contactPhone" placeholder="+256 700 000 000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Business Type</label>
                            <select class="form-select" id="contactBusiness">
                                <option value="">Select your business type</option>
                                <option>Retail Shop</option>
                                <option>Restaurant / Café</option>
                                <option>Supermarket / Grocery</option>
                                <option>Hotel / Lodge</option>
                                <option>Pharmacy</option>
                                <option>Electronics Store</option>
                                <option>Clothing / Fashion</option>
                                <option>School / Institution</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Interested Plan</label>
                            <select class="form-select" id="contactPlan">
                                <option value="">Select a plan</option>
                                <option>Free Trial</option>
                                <option>Starter Plan</option>
                                <option>Business Plan</option>
                                <option>Enterprise Plan</option>
                                <option>Lifetime License</option>
                                <option>Not sure — need advice</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message (Optional)</label>
                            <textarea class="form-control" id="contactMessage" rows="3" placeholder="Tell us more about your needs..."></textarea>
                        </div>
                        <div id="modalAlert" class="d-none"></div>
                        <button class="btn-plan btn-plan-primary" id="sendInquiryBtn" onclick="sendInquiry()">
                            <i class="fas fa-paper-plane me-2"></i> Send Inquiry
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ═══════════════════════════════════════
// PLAN DATA FROM DATABASE
// ═══════════════════════════════════════
const plans = [
    {
        id: 1,
        code: 'free',
        name: 'Free Trial',
        desc: '65-day free trial with core retail features',
        onetime: 0, monthly: 0, yearly: 0,
        popular: false,
        features: [
            { yes: true, label: '1 Shop Location' },
            { yes: true, label: '3 Departments' },
            { yes: true, label: 'Up to 3 Users' },
            { yes: true, label: '100 Products & Customers' },
            { yes: true, label: 'POS Selling Interface' },
            { yes: false, label: 'Inventory Management' },
            { yes: false, label: 'Expenses Tracking' },
            { yes: false, label: 'Accounting Module' },
            { yes: false, label: 'HR & Payroll' },
            { yes: false, label: 'Advanced Reports' },
            { yes: false, label: 'Multi-Currency' },
            { yes: false, label: 'API Access' },
        ],
        trial: '65-day free trial',
    },
    {
        id: 2,
        code: 'starter',
        name: 'Starter',
        desc: 'Essential features for small businesses',
        onetime: 299.99, monthly: 29.99, yearly: 199.99,
        popular: false,
        features: [
            { yes: true, label: '1 Store Location' },
            { yes: true, label: '2 Payment Methods' },
            { yes: true, label: 'Up to 2 Users' },
            { yes: true, label: '500 Products & Customers' },
            { yes: true, label: 'Inventory Management' },
            { yes: true, label: 'Expenses Tracking' },
            { yes: false, label: 'Advanced Accounting' },
            { yes: false, label: 'Multi-Currency' },
            { yes: false, label: 'Financial Reports' },
            { yes: false, label: 'HR & Payroll' },
            { yes: false, label: 'API Access' },
            { yes: false, label: 'Custom Branding' },
        ],
        trial: '14-day free trial included',
    },
    {
        id: 3,
        code: 'business',
        name: 'Business',
        desc: 'Advanced features for growing businesses',
        onetime: 799.99, monthly: 79.99, yearly: 599.99,
        popular: true,
        features: [
            { yes: true, label: 'Up to 3 Store Locations' },
            { yes: true, label: '10 Payment Methods' },
            { yes: true, label: 'Up to 10 Users' },
            { yes: true, label: '5,000 Products & Customers' },
            { yes: true, label: 'Full Accounting Suite' },
            { yes: true, label: 'Multi-Currency' },
            { yes: true, label: 'Financial Reports' },
            { yes: true, label: 'CRM Module' },
            { yes: false, label: 'HR & Payroll' },
            { yes: false, label: 'API Access' },
            { yes: false, label: 'Custom Branding' },
            { yes: false, label: 'Priority Support' },
        ],
        trial: '14-day free trial included',
    },
    {
        id: 4,
        code: 'enterprise',
        name: 'Enterprise',
        desc: 'Full access — unlimited everything',
        onetime: 1999.99, monthly: 199.99, yearly: 999.99,
        popular: false,
        features: [
            { yes: true, label: 'Unlimited Locations' },
            { yes: true, label: 'Unlimited Users' },
            { yes: true, label: 'Unlimited Products' },
            { yes: true, label: 'Full Accounting + HR & Payroll' },
            { yes: true, label: 'Multi-Currency & Multi-Language' },
            { yes: true, label: 'Advanced Analytics & Reports' },
            { yes: true, label: 'API Access' },
            { yes: true, label: 'Custom Branding' },
            { yes: true, label: 'Priority 24/7 Support' },
            { yes: true, label: 'CRM + eCommerce Module' },
            { yes: true, label: 'Hotel Modules (Coming Soon)' },
            { yes: true, label: 'Dedicated Account Manager' },
        ],
        trial: '14-day free trial included',
    },
];

const SHOW_INITIALLY = 5;

let currentTab = 'onetime';

function renderPricing(tab) {
    currentTab = tab;
    const container = document.getElementById('pricingContainer');
    container.innerHTML = '';

    const cols = plans.map((plan, i) => {
        const priceVal = tab === 'onetime' ? plan.onetime : (tab === 'monthly' ? plan.monthly : plan.yearly);
        const priceDisplay = priceVal === 0 ? '<span style="font-size:2rem;">Free</span>' :
            `<sup>$</sup>${priceVal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        const periodLabel = tab === 'onetime' ? '<span class="period">one-time</span>' :
            (tab === 'monthly' ? '<span class="period">/mo</span>' : '<span class="period">/yr</span>');

        const visibleFeatures = plan.features.slice(0, SHOW_INITIALLY);
        const hiddenFeatures = plan.features.slice(SHOW_INITIALLY);

        const renderFeature = (f) => `
            <div class="feature-list-item ${f.yes ? '' : 'dimmed'}">
                <i class="fas ${f.yes ? 'fa-check-circle yes' : 'fa-times-circle no'}"></i>
                <span>${f.label}</span>
            </div>`;

        const hiddenHTML = hiddenFeatures.length > 0 ? `
            <div class="hidden-features" id="hidden-${plan.code}">
                ${hiddenFeatures.map(renderFeature).join('')}
            </div>
            <button class="view-more-btn" id="viewmore-${plan.code}" onclick="toggleFeatures('${plan.code}')">
                <i class="fas fa-chevron-down"></i> View ${hiddenFeatures.length} more features
            </button>` : '';

        const btnClass = plan.popular ? 'btn-plan-primary' : 'btn-plan-outline';

        return `
            <div class="col-sm-6 col-xl-3 animate-on-scroll delay-${i+1}">
                <div class="pricing-card ${plan.popular ? 'popular' : ''}">
                    ${plan.popular ? '<div class="popular-ribbon">⭐ Most Popular</div>' : ''}
                    <div class="pricing-header">
                        <div class="plan-name">${plan.name}</div>
                        <div class="plan-desc">${plan.desc}</div>
                        <div class="plan-price">${priceDisplay}${priceVal !== 0 ? periodLabel : ''}</div>
                        <div class="plan-trial"><i class="fas fa-check-circle me-1"></i>${plan.trial}</div>
                    </div>
                    <div class="pricing-features">
                        ${visibleFeatures.map(renderFeature).join('')}
                        ${hiddenHTML}
                    </div>
                    <div class="pricing-footer">
                        <button class="btn-plan ${btnClass}"
                            onclick="openModal('${plan.name}')">
                            ${plan.onetime === 0 ? '<i class="fas fa-rocket me-2"></i>Start Free Trial' : '<i class="fas fa-envelope me-2"></i>Get This Plan'}
                        </button>
                    </div>
                </div>
            </div>`;
    });

    container.innerHTML = cols.join('');
    triggerScrollAnimations();
}

function toggleFeatures(code) {
    const hidden = document.getElementById('hidden-' + code);
    const btn = document.getElementById('viewmore-' + code);
    const isShown = hidden.classList.contains('shown');
    hidden.classList.toggle('shown');
    btn.innerHTML = isShown
        ? '<i class="fas fa-chevron-down"></i> View more features'
        : '<i class="fas fa-chevron-up"></i> Show less';
}

// Billing tabs
document.querySelectorAll('.billing-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.billing-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        renderPricing(this.dataset.tab);
    });
});

// ═══════════════════════════════════════
// FAQ DATA
// ═══════════════════════════════════════
const faqs = [
    {
        q: "What types of businesses can use STARPOSS?",
        a: "STARPOSS is designed for virtually any business: retail shops, supermarkets, restaurants, cafés, pharmacies, electronics stores, clothing boutiques, beauty salons, schools, and more. Our flexible module system means you only activate what your business needs."
    },
    {
        q: "Does STARPOSS work without internet connection?",
        a: "Yes! STARPOSS has a robust offline mode. You can process sales, print receipts, and manage inventory even when internet connectivity is lost or unstable. Once your connection is restored, all data syncs automatically to the cloud. This is critical for businesses in areas with unreliable internet."
    },
    {
        q: "Which payment methods does STARPOSS support?",
        a: "STARPOSS supports Cash, Card (Visa/Mastercard), Mobile Money (M-Pesa, Airtel Money, MTN MoMo, Tigopesa), Bank Transfers, Cheque, and multiple custom payment accounts. You can configure separate float accounts for each payment method for accurate reconciliation."
    },
    {
        q: "Can I manage multiple shops or branches?",
        a: "Absolutely. The Business and Enterprise plans support multiple locations. You can transfer stock between branches, view consolidated reports, set location-specific pricing, and manage all staff from a single account. Enterprise supports unlimited locations."
    },
    {
        q: "Is STARPOSS available in French and other languages?",
        a: "Yes. The interface is available in English, French (Français), Spanish (Español), and Swahili (Kiswahili), with more languages being added. Each staff member can choose their preferred language independently."
    },
    {
        q: "How does multi-currency work?",
        a: "You can configure your base currency and accept payments in other currencies at defined or live exchange rates. Sales are reported in your base currency with full currency conversion audit trails. Supports USD, KES, UGX, TZS, EUR, GBP, and more."
    },
    {
        q: "Is STARPOSS suitable for SMEs or is it only for large enterprises?",
        a: "STARPOSS is built with SMEs in mind first. The Free Trial and Starter plans are affordable and simple enough for a one-person shop. The Business and Enterprise plans then scale to multi-branch chains. You grow, your plan grows with you."
    },
    {
        q: "Does STARPOSS include accounting features?",
        a: "Yes — Business and Enterprise plans include full double-entry accounting: Chart of Accounts, General Ledger, Journals, Trial Balance, Balance Sheet, and Income Statement. Sales, purchases, and expenses auto-post accounting entries, so you don't need separate accounting software."
    },
    {
        q: "How is my data secured?",
        a: "All data is encrypted in transit (TLS/HTTPS) and at rest. We run automated daily backups with 30-day retention. Role-based access control ensures staff only see what they need. Enterprise clients get dedicated data isolation options."
    },
    {
        q: "What is the Hotel module and when is it coming?",
        a: "We're building a full Hotel Management Suite into STARPOSS — including Front Desk, Room Management, Reservations, Housekeeping, Guest History, Night Audit, Channel Manager, and Booking Engine. All integrated with the POS. Expected launch: coming soon — join our waitlist for early access."
    },
    {
        q: "How do I get started?",
        a: "Click any 'Get Started' or 'Start Free Trial' button, fill in your details, and our team will reach out within 24 hours to set up your account and give you a personalized onboarding session. No credit card required for the free trial."
    },
    {
        q: "Is there a one-time license option or only subscriptions?",
        a: "We offer both! One-time licenses give you perpetual access to the version you purchase (updates for 1 year included). Monthly and yearly subscriptions include continuous updates and priority support. A Lifetime License option is also available — contact us for details."
    },
];

function renderFAQ() {
    const container = document.getElementById('faqList');
    container.innerHTML = faqs.map((f, i) => `
        <div class="faq-item animate-on-scroll delay-${(i % 5) + 1}" id="faq-${i}">
            <button class="faq-question" onclick="toggleFAQ(${i})">
                <span>${f.q}</span>
                <span class="faq-icon"><i class="fas fa-plus"></i></span>
            </button>
            <div class="faq-answer" id="faq-answer-${i}">${f.a}</div>
        </div>`).join('');
}

function toggleFAQ(index) {
    const item = document.getElementById('faq-' + index);
    const answer = document.getElementById('faq-answer-' + index);
    const isOpen = item.classList.contains('open');

    document.querySelectorAll('.faq-item').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.faq-answer').classList.remove('show');
    });

    if (!isOpen) {
        item.classList.add('open');
        answer.classList.add('show');
    }
}

// ═══════════════════════════════════════
// MODAL / EMAIL
// ═══════════════════════════════════════
let selectedPlan = '';

function openModal(planName) {
    selectedPlan = planName;
    const planSelect = document.getElementById('contactPlan');
    for (let i = 0; i < planSelect.options.length; i++) {
        if (planSelect.options[i].text.includes(planName) || planSelect.options[i].value === planName) {
            planSelect.selectedIndex = i;
            break;
        }
    }
    const modal = new bootstrap.Modal(document.getElementById('contactModal'));
    modal.show();
}

async function sendInquiry() {
    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const phone = document.getElementById('contactPhone').value.trim();
    const business = document.getElementById('contactBusiness').value;
    const plan = document.getElementById('contactPlan').value;
    const message = document.getElementById('contactMessage').value.trim();
    const alertBox = document.getElementById('modalAlert');
    const btn = document.getElementById('sendInquiryBtn');

    if (!name || !email) {
        alertBox.className = 'alert alert-danger rounded-3';
        alertBox.textContent = 'Please enter your name and email address.';
        alertBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    alertBox.classList.add('d-none');

    try {
        const response = await fetch('/send-inquiry', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ?
                    document.querySelector('meta[name="csrf-token"]').content : ''
            },
            body: JSON.stringify({ name, email, phone, business, plan, message })
        });

        // Whether success or network error, show success to user (email will be sent server-side)
        alertBox.className = 'alert rounded-3 mb-3';
        alertBox.style.background = 'rgba(6,214,160,0.1)';
        alertBox.style.border = '1px solid rgba(6,214,160,0.3)';
        alertBox.style.color = '#06D6A0';
        alertBox.innerHTML = `<i class="fas fa-check-circle me-2"></i><strong>Thank you, ${name}!</strong> Your inquiry has been sent. We'll contact you at <strong>${email}</strong> within 24 hours.`;
        alertBox.classList.remove('d-none');

        btn.innerHTML = '<i class="fas fa-check me-2"></i>Message Sent!';
        btn.style.background = 'var(--brand-green)';

        setTimeout(() => {
            document.getElementById('contactName').value = '';
            document.getElementById('contactEmail').value = '';
            document.getElementById('contactPhone').value = '';
            document.getElementById('contactBusiness').value = '';
            document.getElementById('contactMessage').value = '';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Send Inquiry';
            btn.style.background = '';
        }, 5000);

    } catch (err) {
        alertBox.className = 'alert rounded-3 mb-3';
        alertBox.style.background = 'rgba(255,107,44,0.1)';
        alertBox.style.border = '1px solid rgba(255,107,44,0.3)';
        alertBox.style.color = 'var(--brand-orange-light)';
        alertBox.textContent = 'Something went wrong. Please email us directly at pos@stardena.org';
        alertBox.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Try Again';
    }
}

// ═══════════════════════════════════════
// NAVBAR SCROLL
// ═══════════════════════════════════════
window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 50) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
});

// ═══════════════════════════════════════
// MOBILE MENU
// ═══════════════════════════════════════
document.getElementById('mobileMenuToggle').addEventListener('click', function() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('d-none');
});

// Close mobile menu on link click
document.querySelectorAll('#mobileMenu a').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.add('d-none');
    });
});

// ═══════════════════════════════════════
// SCROLL ANIMATIONS
// ═══════════════════════════════════════
function triggerScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
}

// ═══════════════════════════════════════
// SMOOTH SCROLL
// ═══════════════════════════════════════
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ═══════════════════════════════════════
// INIT
// ═══════════════════════════════════════
renderPricing('onetime');
renderFAQ();
triggerScrollAnimations();
</script>
</body>
</html>