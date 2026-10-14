<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samburu Health Platform</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap">

    <style>
        :root {
            --green-dark: #1a3d2b;
            --green-mid: #2d6a4f;
            --green-light: #52b788;
            --cream: #f5f0e8;
            --cream-dark: #ede5d5;
            --gold: #c9913d;
            --gold-light: #e8bb78;
            --white: #ffffff;
            --ink: #1c2b22;
            --ink-mid: #3d5246;
            --ink-soft: #6b7f72;
            --border: #d8cfc0;
            --bg: var(--cream);
            --bg-section: var(--white);
            --bg-card: var(--white);
            --fg: var(--ink);
            --font-display: 'Lora', Georgia, serif;
            --font-body: 'Poppins', system-ui, sans-serif;
            color-scheme: light;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #141f18;
                --bg-section: #1a2a1f;
                --bg-card: #1f3027;
                --fg: #e8ede9;
                --cream: #141f18;
                --cream-dark: #1a2a1f;
                --ink: #e8ede9;
                --ink-mid: #b8cbbf;
                --ink-soft: #7a9982;
                --border: #2d4035;
                --gold: #d4a24a;
                color-scheme: dark;
            }
        }

        :root[data-theme="dark"] {
            --bg: #141f18;
            --bg-section: #1a2a1f;
            --bg-card: #1f3027;
            --fg: #e8ede9;
            --cream: #141f18;
            --cream-dark: #1a2a1f;
            --ink: #e8ede9;
            --ink-mid: #b8cbbf;
            --ink-soft: #7a9982;
            --border: #2d4035;
            --gold: #d4a24a;
            color-scheme: dark;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--fg);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(245, 240, 232, 0.92);
            border-bottom: 1px solid var(--border);
            padding: 16px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .navbar {
                background: rgba(20, 31, 24, 0.92);
            }
        }

        :root[data-theme="dark"] .navbar {
            background: rgba(20, 31, 24, 0.92);
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: var(--green-dark);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 20px;
            height: 20px;
        }

        .logo-text {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--green-dark);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .logo-text {
                color: var(--green-light);
            }
        }

        :root[data-theme="dark"] .logo-text {
            color: var(--green-light);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }

        .nav-links a {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--ink-mid);
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--green-mid);
        }

        .nav-cta {
            background: var(--green-dark) !important;
            color: var(--white) !important;
            padding: 9px 22px;
            border-radius: 24px;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            transition: background 0.2s, transform 0.15s !important;
        }

        .nav-cta:hover {
            background: var(--green-mid) !important;
            transform: translateY(-1px);
        }

        .hero {
            background: var(--green-dark);
            overflow: hidden;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 500px;
            height: 500px;
            background: radial-gradient(ellipse, rgba(82,183,136,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 7%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            min-height: 560px;
        }

        .hero-text {
            position: relative;
            z-index: 2;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(82,183,136,0.15);
            border: 1px solid rgba(82,183,136,0.3);
            color: var(--green-light);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 24px;
        }

        .hero-eyebrow::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--green-light);
            border-radius: 50%;
        }

        .hero h1 {
            font-family: var(--font-display);
            font-size: clamp(2.4rem, 5vw, 3.5rem);
            line-height: 1.15;
            color: var(--white);
            margin-bottom: 20px;
        }

        .hero h1 em {
            font-style: italic;
            color: var(--gold-light);
        }

        .hero-desc {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.75);
            max-width: 480px;
            margin-bottom: 36px;
            line-height: 1.7;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--gold);
            color: var(--white);
            padding: 13px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s, transform 0.15s;
            display: inline-block;
        }

        .btn-primary:hover {
            background: var(--gold-light);
            color: var(--ink);
            transform: translateY(-2px);
        }

        .btn-outline {
            border: 1.5px solid rgba(255,255,255,0.4);
            color: var(--white);
            padding: 13px 28px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: border-color 0.2s, background 0.2s;
            display: inline-block;
        }

        .btn-outline:hover {
            border-color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,0.07);
        }

        .hero-visual {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 220px 160px;
            gap: 10px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
        }

        .hero-img {
            background-size: cover;
            background-position: center;
            border-radius: 10px;
        }

        .hero-img:first-child {
            grid-column: 1 / -1;
            grid-row: 1;
            border-radius: 12px 12px 6px 6px;
        }

        .hi-1 {
            background-image: url('https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=800&q=80');
        }

        .hi-2 {
            background-image: url('https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=400&q=80');
        }

        .hi-3 {
            background-image: url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&q=80');
        }

        .hero-badge {
            position: absolute;
            bottom: 16px;
            left: 16px;
            background: var(--green-dark);
            border: 1px solid rgba(82,183,136,0.3);
            color: var(--white);
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hero-badge-num {
            font-size: 1.4rem;
            font-weight: 700;
            font-family: var(--font-display);
            color: var(--gold-light);
            line-height: 1;
        }

        .hero-badge-label {
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
        }

                .stats-bar {
            background: var(--cream-dark);
            border-bottom: 1px solid var(--border);
            padding: 24px 7%;
        }

        .stats-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-num {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--green-dark);
            line-height: 1;
            display: block;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .stat-num {
                color: var(--green-light);
            }
        }

        :root[data-theme="dark"] .stat-num {
            color: var(--green-light);
        }

        .stat-label {
            font-size: 0.78rem;
            color: var(--ink-soft);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .section {
            padding: 80px 7%;
        }

        .section-inner {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 52px;
        }

        .eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .section h2 {
            font-family: var(--font-display);
            font-size: clamp(1.7rem, 3.5vw, 2.4rem);
            color: var(--ink);
            line-height: 1.25;
            margin-bottom: 14px;
        }

        .section-sub {
            font-size: 1rem;
            color: var(--ink-soft);
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .services-bg {
            background: var(--bg-section);
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .service-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(26,61,43,0.12);
        }

        /* Icon banner at the top of each service card */
        .card-banner {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .card-banner svg {
            width: 72px;
            height: 72px;
            position: relative;
            z-index: 2;
        }

        /* Subtle radial glow behind each icon */
        .card-banner::before {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            opacity: 0.18;
        }

        .cb-education { background: linear-gradient(135deg, #1a3d2b 0%, #2d6a4f 100%); }
        .cb-education::before { background: radial-gradient(circle, #52b788, transparent); }

        .cb-myth { background: linear-gradient(135deg, #2d4a1e 0%, #3d6b2a 100%); }
        .cb-myth::before { background: radial-gradient(circle, #c9913d, transparent); }

        .cb-symptom { background: linear-gradient(135deg, #1a2f3d 0%, #1f4d5c 100%); }
        .cb-symptom::before { background: radial-gradient(circle, #52b788, transparent); }

        .card-tag {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 3;
            background: rgba(255,255,255,0.15);
            color: var(--white);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.25);
        }

        .card-body {
            padding: 24px;
        }

        .card-icon-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .card-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--cream-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .card-icon-circle {
                background: var(--bg-section);
            }
        }

        :root[data-theme="dark"] .card-icon-circle {
            background: var(--bg-section);
        }

        .card-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--ink);
        }

        .card-desc {
            font-size: 0.88rem;
            color: var(--ink-soft);
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--green-mid);
            transition: gap 0.2s;
        }

        .card-link:hover {
            gap: 10px;
        }

        .card-link::after {
            content: '→';
        }

        .languages-bg {
            background: var(--bg);
        }

        .lang-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .lang-card {
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            text-align: center;
            transition: border-color 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }

        .lang-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gold);
            transform: scaleX(0);
            transition: transform 0.25s;
            transform-origin: left;
        }

        .lang-card:hover::before {
            transform: scaleX(1);
        }

        .lang-card:hover {
            border-color: var(--gold);
            transform: translateY(-3px);
        }

        .lang-flag {
            font-size: 2.8rem;
            margin-bottom: 14px;
            line-height: 1;
        }

        .lang-name {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--green-dark);
            margin-bottom: 6px;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .lang-name {
                color: var(--green-light);
            }
        }

        :root[data-theme="dark"] .lang-name {
            color: var(--green-light);
        }

        .lang-desc {
            font-size: 0.85rem;
            color: var(--ink-soft);
            margin-bottom: 14px;
        }

        .lang-phrase {
            display: inline-block;
            background: var(--cream-dark);
            color: var(--green-mid);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) .lang-phrase {
                background: var(--bg-section);
                color: var(--green-light);
            }
        }

        :root[data-theme="dark"] .lang-phrase {
            background: var(--bg-section);
            color: var(--green-light);
        }

        .cta-band {
            background: var(--green-dark);
            position: relative;
            overflow: hidden;
        }

        .cta-band::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1400&q=60') center/cover no-repeat;
            opacity: 0.12;
        }

        .cta-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 40px;
            align-items: center;
            padding: 70px 7%;
        }

        .cta-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--gold-light);
            margin-bottom: 12px;
        }

        .cta-band h2 {
            font-family: var(--font-display);
            font-size: clamp(1.7rem, 3.5vw, 2.4rem);
            color: var(--white);
            line-height: 1.25;
            margin-bottom: 14px;
        }

        .cta-body {
            font-size: 1rem;
            color: rgba(255,255,255,0.72);
            max-width: 500px;
            line-height: 1.7;
        }

        .cta-buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: center;
        }

        footer {
            background: #0e1a13;
            color: var(--white);
            padding: 44px 7%;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-logo {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 4px;
        }

        .footer-copy {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.75);
        }

        .footer-links {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .footer-links a {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.85);
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--gold-light);
        }

        @media (max-width: 900px) {
            .hero-inner {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .hero-visual {
                display: none;
            }

            .service-grid {
                grid-template-columns: 1fr;
            }

            .lang-grid {
                grid-template-columns: 1fr;
            }

            .cta-inner {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .nav-links li:not(:last-child):not(:nth-last-child(2)) {
                display: none;
            }

            .hero-inner {
                padding: 56px 7%;
            }

            .section {
                padding: 56px 7%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition: none !important;
                animation: none !important;
            }
        }

    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo-wrap">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <span class="logo-text">Samburu Health</span>
        </div>

        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="auth/login.php" class="nav-cta">Login</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-inner">
            <div class="hero-text">
                <span class="hero-eyebrow">Samburu County, Kenya</span>

                <h1>Your Health,<br><em>Your Language.</em></h1>

                <p class="hero-desc">
                    Access trusted health education, bust common health myths, and get
                    symptom guidance — in Maa, Kiswahili, or English — built for
                    rural communities in Samburu County.
                </p>

                <div class="hero-buttons">
                    <a href="auth/register.php" class="btn-primary">Create Free Account</a>
                    <a href="auth/login.php" class="btn-outline">Sign In</a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-img hi-1"></div>
                <div class="hero-img hi-2"></div>
                <div class="hero-img hi-3"></div>

                <div class="hero-badge">
                    <div>
                        <div class="hero-badge-num">3</div>
                        <div class="hero-badge-label">Languages</div>
                    </div>

                    <div style="width:1px;height:36px;background:rgba(255,255,255,0.15)"></div>

                    <div>
                        <div class="hero-badge-num">WHO</div>
                        <div class="hero-badge-label">Verified content</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="stats-bar">
        <div class="stats-inner">
            <div class="stat-item">
                <span class="stat-num">15+</span>
                <span class="stat-label">Health Articles</span>
            </div>

            <div class="stat-item">
                <span class="stat-num">12</span>
                <span class="stat-label">Myths Debunked</span>
            </div>

            <div class="stat-item">
                <span class="stat-num">AI</span>
                <span class="stat-label">Powered Guidance</span>
            </div>

            <div class="stat-item">
                <span class="stat-num">3</span>
                <span class="stat-label">Languages</span>
            </div>

            <div class="stat-item">
                <span class="stat-num">CHW</span>
                <span class="stat-label">Community Workers</span>
            </div>
        </div>
    </div>

    <section class="section services-bg" id="services">
        <div class="section-inner">
            <div class="section-header">
                <p class="eyebrow">What You Can Access</p>
                <h2>Health Support at Your Fingertips</h2>
                <p class="section-sub">
                    Three core tools designed for rural communities — offline-friendly,
                    multilingual, and backed by WHO and Kenya Ministry of Health guidelines.
                </p>
            </div>

            <div class="service-grid">
                <div class="service-card">
                    <div class="card-banner cb-education">
                        <span class="card-tag">Education</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="card-icon-circle">📚</div>
                            <h3 class="card-title">Health Education</h3>
                        </div>
                        <p class="card-desc">
                            Learn about maternal health, child nutrition, immunization,
                            malaria, diarrhoea and other common illnesses through
                            clear, community-tested articles.
                        </p>
                        <a href="auth/login.php" class="card-link">Read articles</a>
                    </div>
                </div>

                <div class="service-card">
                    <div class="card-banner cb-myth">
                        <span class="card-tag">Myth vs Fact</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>
                    </div>
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="card-icon-circle">⚖️</div>
                            <h3 class="card-title">Myth vs Fact</h3>
                        </div>
                        <p class="card-desc">
                            Our AI myth detector identifies health misconceptions common
                            in Samburu County and gives you the evidence-based facts
                            from trusted sources.
                        </p>
                        <a href="auth/login.php" class="card-link">Check a myth</a>
                    </div>
                </div>

                <div class="service-card">
                    <div class="card-banner cb-symptom">
                        <span class="card-tag">AI Guidance</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="card-icon-circle">🩺</div>
                            <h3 class="card-title">Symptom Guidance</h3>
                        </div>
                        <p class="card-desc">
                            Describe what you are feeling and our ML classifier plus
                            AI assistant will help you understand possible causes and
                            when to seek a health worker.
                        </p>
                        <a href="auth/login.php" class="card-link">Get guidance</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section languages-bg" id="about">
        <div class="section-inner">
            <div class="section-header">
                <p class="eyebrow">Available Languages</p>
                <h2>Choose the Language You Understand Best</h2>
                <p class="section-sub">
                    Samburu Health is built to reach everyone — the platform supports
                    all three languages spoken in the region.
                </p>
            </div>

            <div class="lang-grid">
                <div class="lang-card">
                    <div class="lang-flag">🇰🇪</div>
                    <div class="lang-name">Maa</div>
                    <p class="lang-desc">
                        Indigenous language of the Samburu and Maasai people.
                        The first choice for local communities.
                    </p>
                    <span class="lang-phrase">Nkishu enkiama</span>
                </div>

                <div class="lang-card">
                    <div class="lang-flag">🇰🇪</div>
                    <div class="lang-name">Kiswahili</div>
                    <p class="lang-desc">
                        Kenya's national language — widely understood across
                        all communities and age groups.
                    </p>
                    <span class="lang-phrase">Afya yako kwanza</span>
                </div>

                <div class="lang-card">
                    <div class="lang-flag">🌍</div>
                    <div class="lang-name">English</div>
                    <p class="lang-desc">
                        Full English resources for health workers, educators,
                        and community members who prefer it.
                    </p>
                    <span class="lang-phrase">Your health matters</span>
                </div>
            </div>
        </div>
    </section>

    <div class="cta-band">
        <div class="cta-inner">
            <div>
                <p class="cta-label">Join the Community</p>
                <h2>Ready to Take Control of Your Health?</h2>
                <p class="cta-body">
                    Create a free account in under two minutes. Access health education,
                    myth busting tools, and AI-powered symptom guidance — in the
                    language you understand best.
                </p>
            </div>

            <div class="cta-buttons">
                <a href="auth/register.php" class="btn-primary">Create Free Account</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-inner">
            <div>
                <div class="footer-logo">Samburu Health Platform</div>
                <div class="footer-copy">
                    © 2026 · Health education &amp; telemedicine guidance for rural Kenya
                </div>
            </div>

            <nav class="footer-links">
                <a href="index.php">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
            </nav>
        </div>
    </footer>

</body>
</html>