<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحول الرقمي بمحافظة كفر الشيخ | عرض تفاعلي</title>
    <meta name="theme-color" content="#26302D">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts: KFS identity: Tajawal (headings) + IBM Plex Sans Arabic (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- GSAP & ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --bg-primary: #1D2422;
            --bg-secondary: #26302D;
            --bg-card: rgba(38, 48, 45, 0.94);
            --border-glass: rgba(0, 191, 216, 0.22);
            --accent-cyan: #00BFD8;
            --accent-emerald: #D8B03E;
            --accent-gold: #F0D878;
            --accent-blue: #39D9E8;
            --text-primary: #ECE8D6;
            --text-secondary: #B9B4A0;
            --font-display: 'Tajawal', sans-serif;
            --font-body: 'IBM Plex Sans Arabic', sans-serif;
            --transition-smooth: cubic-bezier(0.22, 1, 0.36, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--font-body);
            overflow: hidden;
            width: 100vw;
            height: 100vh;
            user-select: none;
        }

        /* Ambient Animated Background Canvas */
        #bg-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        /* 16:9 Stage Container */
        #presentation-wrapper {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        #deck-container {
            position: relative;
            width: 100%;
            height: 100%;
            max-width: 177.78vh; /* 16:9 Aspect Ratio Lock on Ultra-Wide */
            max-height: 56.25vw; /* 16:9 Aspect Ratio Lock on Tall Screens */
            background: radial-gradient(circle at 80% 20%, rgba(216, 176, 62, 0.08), transparent 45%),
                        radial-gradient(circle at 20% 80%, rgba(0, 191, 216, 0.09), transparent 50%),
                        rgba(29, 36, 34, 0.82);
            border: 1px solid rgba(0, 191, 216, 0.15);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.85), inset 0 0 60px rgba(0, 191, 216, 0.04);
            overflow: hidden;
            border-radius: 12px;
        }

        @media (max-width: 1024px) {
            #deck-container {
                max-width: 100vw;
                max-height: 100vh;
                border-radius: 0;
                border: none;
            }
        }

        /* Top Header Bar */
        .top-hud {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 54px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 50;
            background: linear-gradient(180deg, rgba(29, 36, 34, 0.9) 0%, transparent 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .brand-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .brand-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent-cyan);
            box-shadow: 0 0 12px var(--accent-cyan);
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.6; }
        }

        .hud-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hud-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-secondary);
            padding: 6px 12px;
            border-radius: 8px;
            font-family: var(--font-body);
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hud-btn:hover {
            background: rgba(0, 191, 216, 0.15);
            border-color: var(--accent-cyan);
            color: #fff;
        }

        .hud-btn.active {
            background: rgba(216, 176, 62, 0.2);
            border-color: var(--accent-emerald);
            color: var(--accent-emerald);
        }

        /* Slide Base */
        .slide {
            position: absolute;
            inset: 0;
            padding: 68px 48px 64px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            z-index: 1;
        }

        .slide.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            z-index: 10;
        }

        /* Typography */
        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 50px;
            background: rgba(0, 191, 216, 0.1);
            border: 1px solid rgba(0, 191, 216, 0.3);
            color: var(--accent-cyan);
            font-family: var(--font-display);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 14px;
            width: fit-content;
        }

        .slide-title {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 3.1vw, 2.8rem);
            font-weight: 900;
            line-height: 1.25;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #ffffff 30%, #B9B4A0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .slide-subtitle {
            font-size: clamp(0.95rem, 1.3vw, 1.15rem);
            color: var(--text-secondary);
            max-width: 780px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        /* Glass Cards */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 20px;
            transition: transform 0.3s var(--transition-smooth), border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            border-color: rgba(0, 191, 216, 0.5);
            box-shadow: 0 12px 35px rgba(0, 191, 216, 0.12);
        }

        /* Bilingual Toggle Support */
        .en-sub {
            display: none;
            font-size: 0.78em;
            color: var(--accent-cyan);
            font-weight: 500;
            margin-top: 2px;
            direction: ltr;
            text-align: right;
        }

        body.show-en .en-sub {
            display: block;
        }

        /* SLIDE 1: Hero */
        .hero-grid {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 40px;
            align-items: center;
            height: 100%;
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(2.4rem, 4.2vw, 3.8rem);
            font-weight: 900;
            line-height: 1.18;
            margin-bottom: 18px;
        }

        .hero-title span {
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-emerald));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 28px;
        }

        .meta-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px;
        }

        .meta-box i {
            color: var(--accent-emerald);
            font-size: 1.2rem;
            margin-bottom: 6px;
        }

        .meta-box h4 {
            font-family: var(--font-display);
            font-size: 0.9rem;
            color: #fff;
        }

        .meta-box p {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .hero-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* SLIDE 2: Agenda */
        .agenda-track {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-top: 16px;
        }

        .agenda-node {
            position: relative;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 270px;
        }

        .agenda-node:hover {
            transform: translateY(-8px);
        }

        .node-num {
            font-family: var(--font-display);
            font-size: 2.4rem;
            font-weight: 900;
            color: rgba(0, 191, 216, 0.25);
            line-height: 1;
        }

        .agenda-node:hover .node-num {
            color: var(--accent-cyan);
        }

        .node-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(0, 191, 216, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-cyan);
            font-size: 1.3rem;
            margin: 14px 0;
        }

        /* SLIDE 3: Concept & InstaPay Analogy */
        .concept-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: stretch;
        }

        .toggle-switch-bar {
            display: inline-flex;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 4px;
            margin-bottom: 16px;
        }

        .sim-btn {
            padding: 8px 18px;
            border-radius: 50px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sim-btn.active {
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-blue));
            color: #0F1C1B;
        }

        .evolution-banner {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(90deg, rgba(216, 176, 62, 0.12), rgba(0, 191, 216, 0.08));
            border: 1px solid rgba(216, 176, 62, 0.35);
            border-radius: 14px;
            padding: 16px 24px;
        }

        /* SLIDE 4: Pillars & Transformation */
        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .pillar-card {
            text-align: center;
            padding: 18px 12px;
        }

        .pillar-card i {
            font-size: 1.6rem;
            color: var(--accent-cyan);
            margin-bottom: 10px;
        }

        .pillar-card h4 {
            font-family: var(--font-display);
            font-size: 0.95rem;
            margin-bottom: 6px;
        }

        .pillar-card p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.45;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .comparison-table th {
            background: rgba(0, 191, 216, 0.12);
            color: var(--accent-cyan);
            font-family: var(--font-display);
            padding: 10px 16px;
            text-align: right;
        }

        .comparison-table td {
            padding: 10px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .badge-old {
            color: #E8968D;
            background: rgba(166, 58, 50, 0.1);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
        }

        .badge-new {
            color: #8FCB9F;
            background: rgba(61, 122, 80, 0.12);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
        }

        /* SLIDE 5: Milestones Timeline */
        .timeline-wrapper {
            position: relative;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 20px;
        }

        .timeline-line {
            position: absolute;
            top: 34px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-emerald), var(--accent-cyan));
            z-index: 0;
        }

        .milestone-card {
            position: relative;
            z-index: 1;
            padding-top: 12px;
        }

        .date-pill {
            display: inline-block;
            background: var(--bg-primary);
            border: 2px solid var(--accent-cyan);
            color: var(--accent-cyan);
            font-family: var(--font-display);
            font-weight: 800;
            padding: 6px 18px;
            border-radius: 50px;
            margin-bottom: 16px;
            box-shadow: 0 0 20px rgba(0, 191, 216, 0.25);
        }

        .apps-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 22px;
        }

        .app-chip {
            background: rgba(0, 143, 165, 0.1);
            border: 1px solid rgba(0, 143, 165, 0.3);
            border-radius: 10px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .app-chip i {
            color: var(--accent-cyan);
        }

        /* SLIDE 6: Tech Centers & Infrastructure */
        .infra-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 12px;
        }

        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .feature-list li i {
            color: var(--accent-emerald);
            margin-top: 4px;
        }

        /* SLIDE 7: 6 Departments Interactive Wheel/Grid */
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .dept-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .dept-card .dept-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .dept-num {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            color: #0F1C1B;
            font-family: var(--font-display);
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* SLIDE 8: Data-to-Decision Pipeline */
        .pipeline-flow {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin: 18px 0;
        }

        .pipe-step {
            position: relative;
            text-align: center;
            border-top: 3px solid var(--accent-cyan);
        }

        .pipe-step:nth-child(2) { border-color: var(--accent-blue); }
        .pipe-step:nth-child(3) { border-color: var(--accent-gold); }
        .pipe-step:nth-child(4) { border-color: var(--accent-emerald); }

        .governance-ring {
            background: rgba(216, 176, 62, 0.08);
            border: 1px dashed rgba(216, 176, 62, 0.5);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: #F0D878;
            font-size: 0.92rem;
            font-weight: 600;
        }

        /* SLIDE 9: GIS Services Catalog */
        .gis-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .gis-item {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .gis-item i {
            font-size: 1.4rem;
            color: var(--accent-emerald);
        }

        .gis-item h4 {
            font-family: var(--font-display);
            font-size: 0.95rem;
        }

        .gis-item p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .portal-banner {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: rgba(0, 191, 216, 0.08);
            border: 1px solid rgba(0, 191, 216, 0.3);
            border-radius: 12px;
        }

        /* SLIDE 10: Spatial Registration & Fees Chart */
        .fees-layout {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 24px;
            align-items: center;
        }

        .chart-box {
            position: relative;
            height: 240px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* SLIDE 11: Interactive Citizen Journey (8 Steps) */
        .journey-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .journey-step {
            padding: 14px;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .journey-step.active-step {
            border-color: var(--accent-cyan);
            background: rgba(0, 191, 216, 0.12);
            box-shadow: 0 0 25px rgba(0, 191, 216, 0.18);
        }

        .step-badge {
            font-family: var(--font-display);
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--accent-cyan);
            margin-bottom: 4px;
        }

        .sms-simulator {
            margin-top: 16px;
            background: rgba(216, 176, 62, 0.1);
            border: 1px solid rgba(216, 176, 62, 0.35);
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        /* SLIDE 12: Conclusion */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin: 20px 0;
        }

        .summary-item {
            text-align: center;
            padding: 18px 12px;
        }

        .summary-num {
            width: 36px;
            height: 36px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: rgba(0, 191, 216, 0.15);
            border: 1px solid var(--accent-cyan);
            color: var(--accent-cyan);
            font-family: var(--font-display);
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Bottom Navigation Bar */
        .bottom-nav {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 54px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 50;
            background: linear-gradient(0deg, rgba(29, 36, 34, 0.95) 0%, transparent 100%);
        }

        .progress-track {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            margin: 0 24px;
            overflow: hidden;
            cursor: pointer;
        }

        .progress-fill {
            height: 100%;
            width: 8.33%;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-emerald));
            transition: width 0.4s var(--transition-smooth);
        }

        .nav-arrows {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .nav-btn:hover:not(:disabled) {
            background: var(--accent-cyan);
            color: #0F1C1B;
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .slide-counter {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-secondary);
            min-width: 65px;
            text-align: center;
        }

        /* Slide Overview Modal */
        #overview-modal {
            position: fixed;
            inset: 0;
            background: rgba(29, 36, 34, 0.94);
            backdrop-filter: blur(20px);
            z-index: 200;
            display: none;
            padding: 40px;
            overflow-y: auto;
        }

        #overview-modal.open {
            display: block;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            max-width: 1200px;
            margin: 24px auto 0;
        }

        .overview-thumb {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .overview-thumb:hover, .overview-thumb.current {
            border-color: var(--accent-cyan);
            transform: translateY(-4px);
        }

        /* ============================================================
           KFS Identity layer — aligns the deck with docs/kfs-design-system.md
           (dark government surface + cyan water + gold heritage accent,
           Tajawal / IBM Plex, radius 8–12, no glass / neon / gradient text)
           ============================================================ */
        :root {
            --kfs-dark: #26302D;
            --kfs-dark-2: #1D2422;
            --kfs-dark-soft: #34413D;
            --kfs-cyan: #00BFD8;
            --kfs-cyan-light: #39D9E8;
            --kfs-gold: #D8B03E;
            --kfs-gold-light: #F0D878;
            --kfs-ivory: #ECE8D6;
            --kfs-ink-on-accent: #0F1C1B;
            --line-gold: rgba(216, 176, 62, .34);
            --line-cyan: rgba(0, 191, 216, .25);
            --line-soft: rgba(236, 232, 214, .10);
        }

        body {
            background-color: var(--kfs-dark-2);
            background-image: url("{{ asset('images/kfs/kfs-pattern-faint.svg') }}");
            background-size: 420px;
        }

        #deck-container {
            background:
                linear-gradient(120deg, rgba(38, 48, 45, .98) 0%, rgba(38, 48, 45, .94) 60%, rgba(0, 143, 165, .30) 100%);
            border: 1px solid var(--line-gold);
            box-shadow: 0 25px 70px rgba(0, 0, 0, .45);
        }
        #deck-container::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url("{{ asset('images/kfs/kfs-pattern.svg') }}") center / 520px repeat;
            opacity: .035;
            pointer-events: none;
        }

        /* Header bar: dark surface + heritage gold line */
        .top-hud {
            background: var(--kfs-dark-2);
            border-bottom: 1px solid var(--line-gold);
        }
        .top-hud::after {
            content: "";
            position: absolute;
            inset-inline: 0;
            bottom: -4px;
            height: 1px;
            background: var(--line-gold);
            opacity: .5;
        }
        .brand-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
            background: #FCFBF7;
            border-radius: 8px;
            padding: 3px;
        }
        .brand-badge .brand-sep { color: var(--kfs-gold); }
        a.hud-btn { text-decoration: none; }
        .hud-btn {
            background: transparent;
            border: 1px solid var(--line-cyan);
            color: var(--kfs-ivory);
            border-radius: 8px;
            font-family: var(--font-display);
            font-weight: 700;
        }
        .hud-btn:hover { background: rgba(0, 191, 216, .12); border-color: var(--kfs-cyan); color: #fff; }
        .hud-btn.active { background: rgba(216, 176, 62, .16); border-color: var(--kfs-gold); color: var(--kfs-gold-light); }
        .hud-btn:focus-visible, .nav-btn:focus-visible, .glass-card:focus-visible, .sim-btn:focus-visible {
            outline: 3px solid var(--kfs-cyan);
            outline-offset: 2px;
        }

        /* Kicker / titles — solid colours, gold 36×3 bar like .kfs-stitle */
        .tag-pill {
            background: rgba(0, 191, 216, .10);
            border-color: var(--line-cyan);
            color: var(--kfs-cyan-light);
        }
        .slide-title {
            background: none;
            -webkit-text-fill-color: currentColor;
            color: var(--kfs-ivory);
            font-weight: 800;
            position: relative;
            padding-top: 14px;
        }
        .slide-title::before {
            content: "";
            position: absolute;
            top: 0;
            inset-inline-start: 0;
            width: 36px;
            height: 3px;
            border-radius: 2px;
            background: var(--kfs-gold);
        }
        .hero-title { font-weight: 800; }
        .hero-title span {
            background: none;
            -webkit-text-fill-color: currentColor;
            color: var(--kfs-gold);
        }
        .hero-title .en-sub { color: var(--kfs-cyan-light); }
        .en-sub { color: var(--kfs-cyan-light); }

        /* Cards: solid surface, 1px line, radius 12, calm hover */
        .glass-card {
            background: var(--kfs-dark-soft);
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            border: 1px solid var(--line-soft);
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .18);
            transition: transform .28s var(--transition-smooth), border-color .28s ease, box-shadow .28s ease;
        }
        .glass-card:hover {
            border-color: var(--line-cyan);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .28);
        }
        .agenda-node:hover { transform: translateY(-3px); }
        .meta-box { background: rgba(236, 232, 214, .04); border-color: var(--line-soft); border-radius: 12px; }
        .node-num { color: rgba(216, 176, 62, .45); font-weight: 800; }
        .agenda-node:hover .node-num { color: var(--kfs-gold); }
        .node-icon { background: rgba(0, 191, 216, .12); color: var(--kfs-cyan-light); border: 1px solid var(--line-gold); }
        .dept-num, .sim-btn.active {
            background: var(--kfs-cyan);
            color: var(--kfs-ink-on-accent);
        }
        .summary-num { background: rgba(216, 176, 62, .14); border-color: var(--kfs-gold); color: var(--kfs-gold-light); }
        .date-pill { background: var(--kfs-dark-2); box-shadow: none; border-color: var(--kfs-gold); color: var(--kfs-gold-light); }
        .timeline-line { background: linear-gradient(90deg, var(--kfs-gold), var(--kfs-cyan)); }
        .journey-step.active-step { box-shadow: none; background: rgba(0, 191, 216, .10); }
        .comparison-table th { background: var(--kfs-dark-2); color: var(--kfs-gold-light); border-bottom: 1px solid var(--line-gold); }
        .comparison-table tbody tr:hover td { background: rgba(236, 232, 214, .03); }
        .badge-new { background: rgba(61, 122, 80, .22); }
        .badge-old { background: rgba(166, 58, 50, .22); }

        /* Bottom navigation: dark bar, cyan→gold progress (scroll-progress rule) */
        .bottom-nav {
            background: var(--kfs-dark-2);
            border-top: 1px solid var(--line-gold);
        }
        .progress-track { background: rgba(236, 232, 214, .10); }
        .progress-fill { background: linear-gradient(90deg, var(--kfs-cyan), var(--kfs-gold)); }
        .nav-btn { border-radius: 8px; border-color: var(--line-cyan); background: transparent; }
        .nav-btn:hover:not(:disabled) { background: var(--kfs-cyan); color: var(--kfs-ink-on-accent); }

        #overview-modal { background: rgba(29, 36, 34, .97); backdrop-filter: none; }
        .overview-thumb { background: var(--kfs-dark-soft); border-color: var(--line-soft); }
        .overview-thumb:hover, .overview-thumb.current { border-color: var(--kfs-gold); transform: translateY(-3px); }

        .deck-credit {
            font-size: .75rem;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        .deck-credit i { color: #E8968D; }

        @media (max-width: 760px) {
            .top-hud { padding: 0 12px; }
            .brand-badge .brand-text { display: none; }
            .hud-btn span { display: none; }
            .deck-credit { display: none; }
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>

    <canvas id="bg-canvas"></canvas>

    <div id="presentation-wrapper">
        <div id="deck-container">
            
            <!-- Top HUD -->
            <header class="top-hud">
                <div class="brand-badge">
                    <img src="{{ asset('logo.png') }}" alt="شعار محافظة كفر الشيخ" class="brand-logo" width="34" height="34">
                    <span class="brand-text">محافظة كفر الشيخ <span class="brand-sep">|</span> الإدارة العامة لنظم المعلومات والتحول الرقمي</span>
                </div>
                <div class="hud-controls">
                    <a class="hud-btn" href="{{ route('home') }}" title="العودة إلى البوابة الإلكترونية لمحافظة كفر الشيخ">
                        <i class="fa-solid fa-house" aria-hidden="true"></i>
                        <span>الموقع الرسمي</span>
                    </a>
                    <button class="hud-btn" id="btn-lang" title="تبديل المصطلحات الإنجليزية">
                        <i class="fa-solid fa-language"></i>
                        <span>AR / EN</span>
                    </button>
                    <button class="hud-btn" id="btn-replay" title="إعادة تشغيل حركات الشريحة">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>إعادة الحركة</span>
                    </button>
                    <button class="hud-btn" id="btn-overview" title="نظرة عامة على الشرائح (G)">
                        <i class="fa-solid fa-grip"></i>
                        <span>الفهرس</span>
                    </button>
                    <button class="hud-btn" id="btn-fullscreen" title="ملء الشاشة (F)">
                        <i class="fa-solid fa-expand"></i>
                    </button>
                </div>
            </header>

            <!-- SLIDE 1: HERO OPENING -->
            <section class="slide active" data-title="البداية: التحول الرقمي بكفر الشيخ">
                <div class="hero-grid">
                    <div>
                        <div class="tag-pill stagger-el">
                            <i class="fa-solid fa-microchip"></i>
                            <span>مركز استدامة للتدريب والتطوير • عرض تعليمي تفاعلي</span>
                        </div>
                        <h1 class="hero-title stagger-el">
                            التحول الرقمي في<br>
                            <span>محافظة كفر الشيخ</span>
                            <span class="en-sub">Digital Transformation in Kafr El-Sheikh Governorate</span>
                        </h1>
                        <p class="slide-subtitle stagger-el">
                            رحلة متكاملة من الإجراءات الورقية التقليدية إلى الإدارة الذكية المترابطة: كيف تخدم التكنولوجيا والخرائط الجيومكانية المواطن وتدعم صناعة القرار.
                            <span class="en-sub">From paper-based workflows to interconnected smart local administration & geospatial services.</span>
                        </p>
                        <div class="hero-meta-grid stagger-el">
                            <div class="meta-box">
                                <i class="fa-solid fa-building-columns"></i>
                                <h4>الجهة المنفذة</h4>
                                <p>ديوان عام محافظة كفر الشيخ</p>
                            </div>
                            <div class="meta-box">
                                <i class="fa-solid fa-earth-africa"></i>
                                <h4>النطاق التقني</h4>
                                <p>نظم المعلومات والخدمات الجيومكانية</p>
                            </div>
                            <div class="meta-box">
                                <i class="fa-solid fa-user-graduate"></i>
                                <h4>الفئة المستهدفة</h4>
                                <p>الطلاب والمتدربون (مسار تعليمي)</p>
                            </div>
                        </div>
                    </div>
                    <div class="hero-visual stagger-el">
                        <!-- Custom Animated Futuristic SVG Hub -->
                        <svg width="360" height="360" viewBox="0 0 360 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="180" cy="180" r="155" stroke="rgba(0,191,216,0.18)" stroke-width="1.5" stroke-dasharray="8 8" class="spin-slow"/>
                            <circle cx="180" cy="180" r="120" stroke="rgba(216,176,62,0.28)" stroke-width="2" stroke-dasharray="24 12" class="spin-reverse"/>
                            <circle cx="180" cy="180" r="82" fill="rgba(0,191,216,0.06)" stroke="#00BFD8" stroke-width="2"/>
                            <!-- Orbiting Nodes -->
                            <g class="pulse-node">
                                <circle cx="180" cy="25" r="14" fill="#26302D" stroke="#00BFD8" stroke-width="2"/>
                                <text x="180" y="30" fill="#00BFD8" font-size="11" font-family="Tajawal" font-weight="bold" text-anchor="middle">GIS</text>
                            </g>
                            <g class="pulse-node">
                                <circle cx="335" cy="180" r="14" fill="#26302D" stroke="#D8B03E" stroke-width="2"/>
                                <text x="335" y="184" fill="#D8B03E" font-size="10" font-family="Tajawal" font-weight="bold" text-anchor="middle">DATA</text>
                            </g>
                            <g class="pulse-node">
                                <circle cx="180" cy="335" r="14" fill="#26302D" stroke="#39D9E8" stroke-width="2"/>
                                <text x="180" y="339" fill="#39D9E8" font-size="10" font-family="Tajawal" font-weight="bold" text-anchor="middle">WEB</text>
                            </g>
                            <g class="pulse-node">
                                <circle cx="25" cy="180" r="14" fill="#26302D" stroke="#F0D878" stroke-width="2"/>
                                <text x="25" y="184" fill="#F0D878" font-size="10" font-family="Tajawal" font-weight="bold" text-anchor="middle">SMS</text>
                            </g>
                            <!-- Center Emblem -->
                            <circle cx="180" cy="180" r="72" fill="#FCFBF7" stroke="#D8B03E" stroke-width="1.5"/>
                            <image href="{{ asset('logo.png') }}" x="124" y="124" width="112" height="112" preserveAspectRatio="xMidYMid meet">
                                <title>شعار محافظة كفر الشيخ</title>
                            </image>
                            <text x="180" y="288" fill="#00BFD8" font-size="12" font-family="IBM Plex Sans Arabic" font-weight="600" text-anchor="middle">منظومة رقمية متكاملة</text>
                        </svg>
                    </div>
                </div>
            </section>

            <!-- SLIDE 2: 5-AXIS ROADMAP -->
            <section class="slide" data-title="خارطة الطريق: المحاور الخمسة">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-map-signs"></i><span>الأجندة التعليمية</span></div>
                <h2 class="slide-title stagger-el">خمسة محاور لفهم التحول الرقمي بالمحافظة</h2>
                <p class="slide-subtitle stagger-el">انقر على أي محور للانتقال المباشر واستكشاف تفاصيله التفاعلية.</p>
                
                <div class="agenda-track">
                    <div class="glass-card agenda-node stagger-el" onclick="goToSlide(2)">
                        <div>
                            <div class="node-num">01</div>
                            <div class="node-icon"><i class="fa-solid fa-lightbulb"></i></div>
                            <h3>مفهوم التحول الرقمي</h3>
                            <span class="en-sub">Digital Transformation Concept</span>
                        </div>
                        <p style="font-size:0.83rem; color:var(--text-secondary);">ما المقصود به؟ وكيف يتغير أسلوب العمل الإداري؟</p>
                    </div>
                    <div class="glass-card agenda-node stagger-el" onclick="goToSlide(4)">
                        <div>
                            <div class="node-num">02</div>
                            <div class="node-icon"><i class="fa-solid fa-file-signature"></i></div>
                            <h3>رقمنة الخدمات</h3>
                            <span class="en-sub">Digitizing Gov Services</span>
                        </div>
                        <p style="font-size:0.83rem; color:var(--text-secondary);">الانتقال من الورق إلى الإجراء الإلكتروني ومحطات 2025–2026.</p>
                    </div>
                    <div class="glass-card agenda-node stagger-el" onclick="goToSlide(5)">
                        <div>
                            <div class="node-num">03</div>
                            <div class="node-icon"><i class="fa-solid fa-server"></i></div>
                            <h3>البنية التحتية والمراكز</h3>
                            <span class="en-sub">Tech Centers & Infrastructure</span>
                        </div>
                        <p style="font-size:0.83rem; color:var(--text-secondary);">المراكز التكنولوجية، الألياف الضوئية، والهيكل التنظيمي.</p>
                    </div>
                    <div class="glass-card agenda-node stagger-el" onclick="goToSlide(7)">
                        <div>
                            <div class="node-num">04</div>
                            <div class="node-icon"><i class="fa-solid fa-diagram-project"></i></div>
                            <h3>البيانات ودعم القرار</h3>
                            <span class="en-sub">Data & Decision Support</span>
                        </div>
                        <p style="font-size:0.83rem; color:var(--text-secondary);">من الرصد الميداني إلى الخرائط الرقمية (GIS) والقرار الدقيق.</p>
                    </div>
                    <div class="glass-card agenda-node stagger-el" onclick="goToSlide(8)">
                        <div>
                            <div class="node-num">05</div>
                            <div class="node-icon"><i class="fa-solid fa-location-crosshairs"></i></div>
                            <h3>الخدمات الجيومكانية</h3>
                            <span class="en-sub">Geospatial & Citizen Journey</span>
                        </div>
                        <p style="font-size:0.83rem; color:var(--text-secondary);">شهادة التسجيل المكاني ورحلة المواطن من التقديم حتى الإخطار.</p>
                    </div>
                </div>
            </section>

            <!-- SLIDE 3: CONCEPT & INSTAPAY ANALOGY -->
            <section class="slide" data-title="01 | مفهوم التحول الرقمي">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-lightbulb"></i><span>المحور الأول: المفهوم والتطور المؤسسي</span></div>
                <h2 class="slide-title stagger-el">ما هو التحول الرقمي؟ (مثال إنستاباي vs المحافظة)</h2>
                
                <div class="concept-layout stagger-el">
                    <div class="glass-card">
                        <h3 style="color:var(--accent-cyan); font-family:var(--font-display); margin-bottom:10px;">
                            <i class="fa-solid fa-network-wired"></i> التعريف الجوهري
                        </h3>
                        <p style="line-height:1.7; font-size:0.95rem; margin-bottom:16px;">
                            استخدام التكنولوجيا والبرامج وقواعد البيانات لتحويل العمل من <strong>الإجراءات التقليدية والورقية</strong> إلى <strong>إجراءات إلكترونية مترابطة</strong>، بما يضمن إنجاز الأعمال بسرعة ودقة وحوكمة أعلى.
                        </p>
                        <div style="background:rgba(0,0,0,0.3); padding:14px; border-radius:12px; border-right:3px solid var(--accent-emerald);">
                            <p style="font-size:0.88rem; color:#ECE8D6;">
                                <strong>القاعدة الذهبية:</strong> التحول الرقمي ليس مجرد استبدال الورق بالشاشات؛ بل هو ارتقاء بالهيكل التنظيمي ليصبح <strong>جهازًا عصبيًا مركزيًا</strong> يربط البيانات ويضع المواطن في القلب.
                            </p>
                        </div>
                    </div>

                    <div class="glass-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">محاكاة المقارنة التفاعلية</h3>
                            <div class="toggle-switch-bar">
                                <button class="sim-btn active" onclick="toggleAnalogy('instapay', this)">مثال: إنستاباي</button>
                                <button class="sim-btn" onclick="toggleAnalogy('kfs', this)">محافظة كفر الشيخ</button>
                            </div>
                        </div>
                        <div id="analogy-content" style="margin-top:8px;">
                            <!-- Populated via JS -->
                        </div>
                    </div>
                </div>

                <div class="evolution-banner stagger-el">
                    <div>
                        <span style="font-size:0.8rem; color:var(--text-secondary);">المسمى السابق</span>
                        <h4 style="color:#E8968D; font-family:var(--font-display);">مركز المعلومات ودعم اتخاذ القرار</h4>
                    </div>
                    <div style="font-size:1.5rem; color:var(--accent-cyan);"><i class="fa-solid fa-arrow-left-long"></i></div>
                    <div>
                        <span style="font-size:0.8rem; color:var(--text-secondary);">المسمى الحالي المطور</span>
                        <h4 style="color:var(--accent-emerald); font-family:var(--font-display);">الإدارة العامة لنظم المعلومات والتحول الرقمي</h4>
                    </div>
                    <p style="max-width:340px; font-size:0.82rem; color:var(--text-secondary);">
                        تطوير للهيكل التنظيمي وتوسيع للاختصاصات لمواكبة رقمنة الجهاز الإداري للدولة.
                    </p>
                </div>
            </section>

            <!-- SLIDE 4: 5 BENEFITS & PAPER VS DIGITAL -->
            <section class="slide" data-title="01 | مكاسب التحول الرقمي">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-layer-group"></i><span>الأثر المباشر على الإدارة والمواطن</span></div>
                <h2 class="slide-title stagger-el">ماذا يحقق التحول الرقمي لمحافظة كفر الشيخ؟</h2>

                <div class="pillars-grid stagger-el">
                    <div class="glass-card pillar-card">
                        <i class="fa-solid fa-gears"></i>
                        <h4>ميكنة الإجراءات</h4>
                        <p>تحويل دورات العمل الورقية إلى إجراءات إلكترونية منظمة.</p>
                    </div>
                    <div class="glass-card pillar-card">
                        <i class="fa-solid fa-database"></i>
                        <h4>قواعد البيانات</h4>
                        <p>تجميع البيانات وتحديثها وربطها مركزيًا لدعم العمل الإداري.</p>
                    </div>
                    <div class="glass-card pillar-card">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                        <h4>تطوير الخدمات</h4>
                        <p>تحسين متابعة الطلبات والبلاغات وتيسير الخدمة للمواطنين.</p>
                    </div>
                    <div class="glass-card pillar-card">
                        <i class="fa-solid fa-chart-pie"></i>
                        <h4>دعم القرار</h4>
                        <p>إحصاءات وتقارير دقيقة لتحديد المشكلات ومتابعة الأداء.</p>
                    </div>
                    <div class="glass-card pillar-card">
                        <i class="fa-solid fa-shield-halved"></i>
                        <h4>الحوكمة والرقابة</h4>
                        <p>توثيق الإجراءات وتوحيد مسارات العمل ومتابعة التنفيذ.</p>
                    </div>
                </div>

                <div class="glass-card stagger-el" style="padding:14px 20px;">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>وجه المقارنة</th>
                                <th>العصر الورقي (قبل التحول)</th>
                                <th>العصر الرقمي (واقع كفر الشيخ اليوم)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>آلية العمل</strong></td>
                                <td><span class="badge-old">تداول يدوي بطيء بين الإدارات</span></td>
                                <td><span class="badge-new">تسجيل ومتابعة إلكترونية للطلب وفق الصلاحيات</span></td>
                            </tr>
                            <tr>
                                <td><strong>استرجاع البيانات</strong></td>
                                <td><span class="badge-old">استغراق وقت طويل للوصول للمعلومة</span></td>
                                <td><span class="badge-new">استدعاء فوري وموثوق للسجلات والتقارير</span></td>
                            </tr>
                            <tr>
                                <td><strong>واجهة المواطن</strong></td>
                                <td><span class="badge-old">طوابير ومراجعات متعددة للشبابيك</span></td>
                                <td><span class="badge-new">مراكز تكنولوجية وبوابة إلكترونية أسرع</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- SLIDE 5: MILESTONES & SYSTEMS -->
            <section class="slide" data-title="02 | محطات التحول الرقمي والأنظمة">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-file-signature"></i><span>المحور الثاني: رقمنة الخدمات الحكومية</span></div>
                <h2 class="slide-title stagger-el">أبرز محطات التحول الرقمي وتطبيقات العمل</h2>

                <div class="timeline-wrapper stagger-el">
                    <div class="timeline-line"></div>
                    <div class="glass-card milestone-card">
                        <span class="date-pill">أكتوبر 2025</span>
                        <h3 style="font-family:var(--font-display); margin-bottom:8px; color:#fff;">المنظومة الموحدة</h3>
                        <p style="font-size:0.86rem; color:var(--text-secondary); line-height:1.55;">
                            إطلاق منظومة إلكترونية موحدة لدورات العمل وقواعد بيانات مركزية مترابطة، وتطوير تطبيقات الويب والبلاغات.
                        </p>
                    </div>
                    <div class="glass-card milestone-card">
                        <span class="date-pill" style="border-color:var(--accent-emerald); color:var(--accent-emerald);">ديسمبر 2025</span>
                        <h3 style="font-family:var(--font-display); margin-bottom:8px; color:#fff;">التمكين الجيومكاني</h3>
                        <p style="font-size:0.86rem; color:var(--text-secondary); line-height:1.55;">
                            تطوير مركز نظم المعلومات الجغرافية (GIS) وميكنة قواعد البيانات المكانية وربطها بخدمات المواطنين.
                        </p>
                    </div>
                    <div class="glass-card milestone-card">
                        <span class="date-pill" style="border-color:var(--accent-gold); color:var(--accent-gold);">أبريل 2026</span>
                        <h3 style="font-family:var(--font-display); margin-bottom:8px; color:#fff;">مركز التحول الرقمي</h3>
                        <p style="font-size:0.86rem; color:var(--text-secondary); line-height:1.55;">
                            تجهيز البنية التحتية النهائية لمركز التحول الرقمي بالديوان العام لدعم ميكنة الخدمات وتسريع المعاملات.
                        </p>
                    </div>
                </div>

                <div class="stagger-el" style="margin-top:18px;">
                    <h4 style="font-family:var(--font-display); font-size:0.95rem; color:var(--accent-cyan); margin-bottom:8px;">
                        <i class="fa-solid fa-code"></i> أبرز إنجازات إدارة النظم والتطبيقات:
                    </h4>
                    <div class="apps-strip">
                        <div class="app-chip"><i class="fa-solid fa-globe"></i><span>تطبيقات الويب الخدمية</span></div>
                        <div class="app-chip"><i class="fa-solid fa-headset"></i><span>منظومات البلاغات والتواصل</span></div>
                        <div class="app-chip"><i class="fa-solid fa-graduation-cap"></i><span>تطبيقات حوكمة التدريب</span></div>
                        <div class="app-chip"><i class="fa-solid fa-diagram-project"></i><span>ميكنة دورات عمل الديوان</span></div>
                    </div>
                </div>
            </section>

            <!-- SLIDE 6: TECH CENTERS & INFRASTRUCTURE -->
            <section class="slide" data-title="03 | المراكز التكنولوجية والبنية التحتية">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-server"></i><span>المحور الثالث: التكنولوجيا لخدمة المواطن</span></div>
                <h2 class="slide-title stagger-el">المراكز التكنولوجية والبنية التحتية الرقمية</h2>

                <div class="infra-grid stagger-el">
                    <div class="glass-card">
                        <h3 style="font-family:var(--font-display); color:var(--accent-cyan); margin-bottom:6px;">
                            <i class="fa-solid fa-store"></i> المراكز التكنولوجية: الخدمة أقرب للمواطن
                        </h3>
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-truck-fast"></i><div><strong>مراكز ثابتة ومركز متنقل:</strong> انتشار على مستوى مدن المحافظة ومركز متنقل يصل بالخدمة إلى القرى.</div></li>
                            <li><i class="fa-solid fa-window-restore"></i><div><strong>خدمات إلكترونية موحدة:</strong> تقديم الإجراءات والمعاملات للمواطنين من شباك واحد مميكن.</div></li>
                            <li><i class="fa-solid fa-credit-card"></i><div><strong>تحصيل ودفع إلكتروني:</strong> سداد الرسوم إلكترونيًا بدلًا من التعامل النقدي التقليدي.</div></li>
                            <li><i class="fa-solid fa-seedling"></i><div><strong>مجمعات «حياة كريمة»:</strong> التوسع في مراكز تكنولوجية جديدة داخل القرى لتحقيق الشمول الخدمي.</div></li>
                        </ul>
                    </div>

                    <div class="glass-card">
                        <h3 style="font-family:var(--font-display); color:var(--accent-emerald); margin-bottom:6px;">
                            <i class="fa-solid fa-network-wired"></i> البنية الأساسية وغرفة الحاسب المركزية
                        </h3>
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-bolt"></i><div><strong>شبكة ألياف ضوئية (Fiber Optics):</strong> ربط عالي السرعة يضمن استقرار تبادل البيانات بين الإدارات.</div></li>
                            <li><i class="fa-solid fa-server"></i><div><strong>غرفة الحاسب الآلي المركزية:</strong> تشغيل وإدارة الخوادم وقواعد البيانات وضمان استمرارية الأنظمة.</div></li>
                            <li><i class="fa-solid fa-shield-virus"></i><div><strong>مركز بيانات وأمن سيبراني:</strong> استضافة مؤمنة للأنظمة الحكومية وحماية بيانات المواطنين.</div></li>
                            <li><i class="fa-solid fa-gauge-high"></i><div><strong>متابعة الأداء والجودة:</strong> قياس معدلات إنجاز الطلبات في المراكز التكنولوجية لحظيًا.</div></li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- SLIDE 7: ORGANIZATIONAL STRUCTURE (6 SPECIALTIES) -->
            <section class="slide" data-title="03 | هيكل الإدارة العامة">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-sitemap"></i><span>التكامل المؤسسي</span></div>
                <h2 class="slide-title stagger-el">هيكل الإدارة العامة: ستة تخصصات في منظومة واحدة</h2>
                <p class="slide-subtitle stagger-el">تعمل الإدارات الست بتكامل تام لضمان تدفق البيانات من الميدان حتى متخذ القرار.</p>

                <div class="dept-grid stagger-el">
                    <div class="glass-card dept-card">
                        <div class="dept-header">
                            <span class="dept-num">1</span>
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">إدارة النظم والتطبيقات</h3>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary);">تطوير البرامج وتطبيقات الويب وأتمتة دورات العمل وتحديث قواعد البيانات.</p>
                    </div>
                    <div class="glass-card dept-card">
                        <div class="dept-header">
                            <span class="dept-num">2</span>
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">إدارة البنية الأساسية</h3>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary);">إدارة شبكات الألياف الضوئية، مراكز البيانات، وتأمين الاتصال والأمن السيبراني.</p>
                    </div>
                    <div class="glass-card dept-card">
                        <div class="dept-header">
                            <span class="dept-num">3</span>
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">غرفة الحاسب الآلي المركزية</h3>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary);">تشغيل الخوادم المركزية وتوفير الدعم التقني لضمان توافر الأنظمة دون انقطاع.</p>
                    </div>
                    <div class="glass-card dept-card">
                        <div class="dept-header">
                            <span class="dept-num">4</span>
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">الإشراف على المراكز التكنولوجية</h3>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary);">متابعة جودة الخدمات المقدمة للمواطنين في المراكز الثابتة والمتنقلة.</p>
                    </div>
                    <div class="glass-card dept-card">
                        <div class="dept-header">
                            <span class="dept-num">5</span>
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">إدارة الإحصاء</h3>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary);">إعداد التقارير الإحصائية وتحليل المؤشرات الرقمية لدعم اتخاذ القرار.</p>
                    </div>
                    <div class="glass-card dept-card">
                        <div class="dept-header">
                            <span class="dept-num">6</span>
                            <h3 style="font-family:var(--font-display); font-size:1.05rem;">إدارة الرصد الميداني</h3>
                        </div>
                        <p style="font-size:0.84rem; color:var(--text-secondary);">متابعة الأوضاع والخدمات والأسواق على أرض الواقع وتسجيل الملاحظات فوريًا.</p>
                    </div>
                </div>
            </section>

            <!-- SLIDE 8: DATA TO DECISION & GIS -->
            <section class="slide" data-title="04 | البيانات ودعم القرار والخرائط الرقمية">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-chart-pie"></i><span>المحور الرابع: من الميدان إلى القرار</span></div>
                <h2 class="slide-title stagger-el">دورة البيانات والخرائط الرقمية (GIS) في دعم القرار</h2>

                <div class="pipeline-flow stagger-el">
                    <div class="glass-card pipe-step">
                        <i class="fa-solid fa-binoculars" style="font-size:1.8rem; color:var(--accent-cyan); margin-bottom:10px;"></i>
                        <h3 style="font-family:var(--font-display); font-size:1.05rem;">1. رصد ميداني</h3>
                        <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:6px;">تسجيل الأوضاع والأسواق والمشكلات على أرض الواقع لحظيًا.</p>
                    </div>
                    <div class="glass-card pipe-step">
                        <i class="fa-solid fa-database" style="font-size:1.8rem; color:var(--accent-blue); margin-bottom:10px;"></i>
                        <h3 style="font-family:var(--font-display); font-size:1.05rem;">2. قواعد بيانات</h3>
                        <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:6px;">تجميع وتحديث وربط البيانات المكانية والوصفية مركزيًا.</p>
                    </div>
                    <div class="glass-card pipe-step">
                        <i class="fa-solid fa-chart-line" style="font-size:1.8rem; color:var(--accent-gold); margin-bottom:10px;"></i>
                        <h3 style="font-family:var(--font-display); font-size:1.05rem;">3. إحصاء وتحليل</h3>
                        <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:6px;">إنتاج خرائط GIS وتقارير تحليلية عبر المنصات الإحصائية (2026).</p>
                    </div>
                    <div class="glass-card pipe-step">
                        <i class="fa-solid fa-chess-king" style="font-size:1.8rem; color:var(--accent-emerald); margin-bottom:10px;"></i>
                        <h3 style="font-family:var(--font-display); font-size:1.05rem;">4. قرار دقيق</h3>
                        <p style="font-size:0.82rem; color:var(--text-secondary); margin-top:6px;">تحديد الأولويات والتخطيط العمراني وإدارة الأصول والمرافق.</p>
                    </div>
                </div>

                <div class="governance-ring stagger-el">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>الحوكمة والرقابة تحيط بالدورة كلها: توثيق الإجراءات، توحيد مسارات العمل، وكل خدمة (تراخيص، تصالح، إشغالات) لها موقع دقيق على الخريطة.</span>
                </div>
            </section>

            <!-- SLIDE 9: GEOSPATIAL PORTAL SERVICES -->
            <section class="slide" data-title="05 | خدمات مركز نظم المعلومات الجيومكانية">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-map-location-dot"></i><span>المحور الخامس: البوابة الجيومكانية</span></div>
                <h2 class="slide-title stagger-el">خدمات مركز نظم المعلومات الجيومكانية (8 خدمات رئيسية)</h2>

                <div class="gis-grid stagger-el">
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-building"></i>
                        <h4>رخص المباني بأنواعها</h4>
                        <p>إنشاء، هدم، تعلية، ترميم، تعديل، أو إضافة.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-file-contract"></i>
                        <h4>ملفات التصالح</h4>
                        <p>تسجيل مكاني لطلبات التصالح في مخالفات البناء.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-faucet-drip"></i>
                        <h4>توصيل المرافق</h4>
                        <p>شهادات تسجيل طلبات توصيل الكهرباء والمياه والغاز.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-hammer"></i>
                        <h4>تنفيذ الإزالات</h4>
                        <p>شهادات تسجيل وتوثيق تنفيذ قرارات الإزالة.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-shop"></i>
                        <h4>رخص الإشغالات والأكشاك</h4>
                        <p>إصدار وتجديد شهادات التسجيل المكاني للإشغالات.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-compass-drafting"></i>
                        <h4>الأعمال المساحية</h4>
                        <p>رفع مساحي وتوقيع الإحداثيات على الخرائط الرقمية.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-tower-cell"></i>
                        <h4>محطات تقوية المحمول</h4>
                        <p>شهادات تسجيل ومطابقة رخص أبراج المحمول.</p>
                    </div>
                    <div class="glass-card gis-item">
                        <i class="fa-solid fa-route"></i>
                        <h4>الكشف عن المرافق</h4>
                        <p>تحديد مسارات وشبكات المرافق الأرضية بالموقع.</p>
                    </div>
                </div>

                <div class="portal-banner stagger-el">
                    <span><i class="fa-solid fa-globe" style="color:var(--accent-cyan); margin-left:8px;"></i> البوابة الإلكترونية لخدمات المحليات بمحافظة كفر الشيخ:</span>
                    <code style="color:var(--accent-cyan); font-weight:700; direction:ltr;">kfs.gov.eg/gis-portal</code>
                </div>
            </section>

            <!-- SLIDE 10: SPATIAL REGISTRATION CERTIFICATE & FEES CHART -->
            <section class="slide" data-title="05 | شهادة التسجيل المكاني والرسوم">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-certificate"></i><span>دراسة حالة تطبيقية</span></div>
                <h2 class="slide-title stagger-el">شهادة التسجيل المكاني لرخص البناء: المتطلبات والرسوم</h2>

                <div class="fees-layout stagger-el">
                    <div class="glass-card">
                        <h3 style="font-family:var(--font-display); color:var(--accent-cyan); margin-bottom:10px;">
                            أهمية الشهادة والمستندات المطلوبة
                        </h3>
                        <p style="font-size:0.88rem; color:var(--text-secondary); margin-bottom:14px; line-height:1.55;">
                            وثيقة رقمية مؤمنة وشرط أساسي لاستخراج تراخيص البناء، تحدد الموقف من <strong>الحيز العمراني</strong> ومناطق الحظر دون الحاجة لزيارة المركز التكنولوجي.
                        </p>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                            <div style="background:rgba(255,255,255,0.04); padding:10px; border-radius:10px;">
                                <strong style="color:#fff; font-size:0.85rem;"><i class="fa-solid fa-id-card" style="color:var(--accent-emerald);"></i> بطاقة الرقم القومي</strong>
                                <p style="font-size:0.78rem; color:var(--text-secondary);">صورة سارية للمالك الأصيل.</p>
                            </div>
                            <div style="background:rgba(255,255,255,0.04); padding:10px; border-radius:10px;">
                                <strong style="color:#fff; font-size:0.85rem;"><i class="fa-solid fa-scroll" style="color:var(--accent-emerald);"></i> سند الملكية الرسمي</strong>
                                <p style="font-size:0.78rem; color:var(--text-secondary);">عقد ملكية العقار أو قطعة الأرض.</p>
                            </div>
                        </div>
                        <div style="background:rgba(0,191,216,0.08); padding:10px 14px; border-radius:10px; font-size:0.82rem;">
                            <i class="fa-solid fa-user-tie" style="color:var(--accent-cyan);"></i> <strong>ملاحظة:</strong> يُرفق التوكيل الرسمي في حال التقديم بواسطة وكيل، ويتم السداد إلكترونيًا عبر بوابة <strong>e-Finance</strong>.
                        </div>
                    </div>

                    <div class="glass-card" style="text-align:center;">
                        <h3 style="font-family:var(--font-display); font-size:1.05rem; margin-bottom:4px;">تفصيل إجمالي الرسوم: <span style="color:var(--accent-emerald);">415.00 ج.م</span></h3>
                        <p style="font-size:0.78rem; color:var(--text-secondary); margin-bottom:8px;">موزعة إلكترونيًا عبر بوابة الدفع الحكومي e-Finance</p>
                        <div class="chart-box">
                            <canvas id="feesChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SLIDE 11: INTERACTIVE 8-STEP CITIZEN JOURNEY -->
            <section class="slide" data-title="05 | رحلة عمل الخدمة وإخطارات SMS">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-route"></i><span>تجربة المواطن الرقمية</span></div>
                <h2 class="slide-title stagger-el">رحلة عمل الخدمة: 8 خطوات من التقديم حتى الإخطار</h2>

                <div class="journey-container stagger-el" id="journey-grid">
                    <div class="glass-card journey-step active-step" onclick="selectStep(0)">
                        <div class="step-badge">الخطوة 01 • المواطن</div>
                        <h4 style="font-size:0.9rem;">تقديم الطلب إلكترونيًا</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">تحديد نوع الرخصة والموقع الجغرافي وإرفاق المستندات.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(1)">
                        <div class="step-badge">الخطوة 02 • البوابة</div>
                        <h4 style="font-size:0.9rem;">السداد عبر e-Finance</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">دفع الرسوم إلكترونيًا واستلام رقم المعاملة فورًا.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(2)">
                        <div class="step-badge">الخطوة 03 • المركز</div>
                        <h4 style="font-size:0.9rem;">قيد الطلب بالمنظومة</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">وصول الطلب إلكترونيًا لمركز GIS وبدء دورة العمل.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(3)">
                        <div class="step-badge">الخطوة 04 • المراجعة</div>
                        <h4 style="font-size:0.9rem;">المطابقة الفنية والمكانية</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">معاينة ميدانية وتوقيع الإحداثيات على الخرائط الرقمية.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(4)">
                        <div class="step-badge">الخطوة 05 • التحقق</div>
                        <h4 style="font-size:0.9rem;">هل الطلب مستوفى؟</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">فحص اكتمال البيانات والمستندات الهندسية والقانونية.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(5)">
                        <div class="step-badge">الخطوة 06 • الاستيفاء</div>
                        <h4 style="font-size:0.9rem;">استكمال أو فروق رسوم</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">إرسال إشعار SMS عند وجود بيانات ناقصة وإعادة المراجعة.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(6)">
                        <div class="step-badge">الخطوة 07 • الاعتماد</div>
                        <h4 style="font-size:0.9rem;">إصدار الشهادة المؤمنة</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">اعتماد شهادة التسجيل المكاني رقميًا وتأمينها.</p>
                    </div>
                    <div class="glass-card journey-step" onclick="selectStep(7)">
                        <div class="step-badge">الخطوة 08 • التسليم</div>
                        <h4 style="font-size:0.9rem;">إخطار المواطن والاستلام</h4>
                        <p style="font-size:0.78rem; color:var(--text-secondary);">رسالة نصية SMS بانتهاء الخدمة واستلام الشهادة إلكترونيًا.</p>
                    </div>
                </div>

                <div class="sms-simulator stagger-el">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <i class="fa-solid fa-comment-sms" style="font-size:1.6rem; color:var(--accent-emerald);"></i>
                        <div>
                            <strong style="font-size:0.88rem; color:#fff;">محاكي رسائل المواطن (SMS Live Feed):</strong>
                            <p id="sms-preview-text" style="font-size:0.84rem; color:#ECE8D6;">
                                «تم تسجيل طلبكم بنجاح على البوابة الجيومكانية لمحافظة كفر الشيخ برقم معاملة #KFS-2026-849.»
                            </p>
                        </div>
                    </div>
                    <span style="font-size:0.75rem; color:var(--text-secondary); white-space:nowrap;">انقر على أي خطوة أعلاه</span>
                </div>
            </section>

            <!-- SLIDE 12: CONCLUSION -->
            <section class="slide" data-title="الخلاصة والختام">
                <div class="tag-pill stagger-el"><i class="fa-solid fa-flag-checkered"></i><span>الخلاصة التنفيذية</span></div>
                <h2 class="slide-title stagger-el">خمس حقائق تلخص التحول الرقمي بكفر الشيخ</h2>

                <div class="summary-cards stagger-el">
                    <div class="glass-card summary-item">
                        <div class="summary-num">1</div>
                        <h4 style="font-size:0.92rem; margin-bottom:6px;">ثقافة عمل جديدة</h4>
                        <p style="font-size:0.8rem; color:var(--text-secondary);">التحول الرقمي تغيير في طريقة أداء العمل، وليس مجرد أجهزة.</p>
                    </div>
                    <div class="glass-card summary-item">
                        <div class="summary-num">2</div>
                        <h4 style="font-size:0.92rem; margin-bottom:6px;">قواعد بيانات موحدة</h4>
                        <p style="font-size:0.8rem; color:var(--text-secondary);">رقمنة الخدمات بدأت بميكنة دورات العمل والربط المركزي.</p>
                    </div>
                    <div class="glass-card summary-item">
                        <div class="summary-num">3</div>
                        <h4 style="font-size:0.92rem; margin-bottom:6px;">الخدمة أقرب إليك</h4>
                        <p style="font-size:0.8rem; color:var(--text-secondary);">المراكز التكنولوجية الثابتة والمتنقلة تقرّب الخدمة للمواطن.</p>
                    </div>
                    <div class="glass-card summary-item">
                        <div class="summary-num">4</div>
                        <h4 style="font-size:0.92rem; margin-bottom:6px;">القرار بالبيانات</h4>
                        <p style="font-size:0.8rem; color:var(--text-secondary);">الإحصاءات الدقيقة والخرائط الرقمية (GIS) أساس القرار الأفضل.</p>
                    </div>
                    <div class="glass-card summary-item">
                        <div class="summary-num">5</div>
                        <h4 style="font-size:0.92rem; margin-bottom:6px;">رحلة شفافة</h4>
                        <p style="font-size:0.8rem; color:var(--text-secondary);">الخدمات الجيومكانية تضمن مسارًا واضحًا من التقديم حتى الإخطار.</p>
                    </div>
                </div>

                <div class="glass-card stagger-el" style="text-align:center; padding:22px; background:var(--kfs-dark-2); border-color:var(--line-gold);">
                    <h3 style="font-family:var(--font-display); font-size:1.5rem; margin-bottom:6px;">شكرًا لطيب المتابعة</h3>
                    <p style="color:var(--text-secondary); font-size:0.9rem;">
                        محافظة كفر الشيخ • الإدارة العامة لنظم المعلومات والتحول الرقمي • مركز استدامة للتدريب والتطوير (Estidama - We Train to Last)
                    </p>
                </div>
            </section>

            <!-- Bottom Controls -->
            <footer class="bottom-nav">
                <div class="slide-counter" id="slide-counter">01 / 12</div>
                <span class="deck-credit">صُنع بكل <i class="fa-solid fa-heart" aria-label="حب"></i> بواسطة إدارة التحول الرقمي</span>
                <div class="progress-track" id="progress-track" title="انقر للانتقال">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="nav-arrows">
                    <button class="nav-btn" id="btn-prev" title="السابق (سهم يمين)">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button class="nav-btn" id="btn-next" title="التالي (سهم يسار أو مسافة)">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
            </footer>

        </div>
    </div>

    <!-- Overview Modal -->
    <div id="overview-modal">
        <div style="max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-family:var(--font-display);">فهرس شرائح العرض التفاعلي</h2>
            <button class="hud-btn" onclick="toggleOverview()"><i class="fa-solid fa-xmark"></i> إغلاق</button>
        </div>
        <div class="overview-grid" id="overview-grid"></div>
    </div>

    <script>
        // State
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        let currentSlide = 0;
        let feesChartInstance = null;

        // Analogy Data (Slide 3)
        const analogyData = {
            instapay: {
                badge: 'تطبيق إنستاباي (التحويلات المالية)',
                before: 'الذهاب إلى البنك أو ماكينة الصراف، والالتزام بمواعيد العمل الرسمية، والانتظار لوصول التحويل.',
                after: 'تحويل لحظي من الهاتف على مدار 24 ساعة حتى في الإجازات الرسمية مع سجل إلكتروني كامل.'
            },
            kfs: {
                badge: 'محافظة كفر الشيخ (خدمات الإدارة المحلية)',
                before: 'تداول الأوراق يدويًا بين الإدارات، طوابير الشبابيك، واستغراق وقت طويل للوصول إلى معلومة.',
                after: 'منظومة إلكترونية موحدة لدورات العمل، مراكز تكنولوجية، وبوابة جيومكانية تتيح التقديم والدفع عن بُعد.'
            }
        };

        // SMS Journey Messages (Slide 11)
        const smsMessages = [
            '«الخطوة 1: المواطن يحدد نوع الرخصة وموقع العقار جغرافيًا ويرفق المستندات عبر البوابة kfs.gov.eg/gis-portal.»',
            '«الخطوة 2: تم سداد الرسوم (415 ج.م) إلكترونيًا عبر e-Finance وإصدار رقم المعاملة لمتابعة الطلب.»',
            '«الخطوة 3: تم قيد طلبكم إلكترونيًا بمنظومة مركز نظم المعلومات الجيومكانية وبدء دورة العمل.»',
            '«الخطوة 4: رسالة SMS بموعد المعاينة الميدانية والرفع المساحي والتوقيع على الخرائط الرقمية.»',
            '«الخطوة 5: يجري التحقق الفني والقانوني من استيفاء جميع اشتراطات الحيز العمراني والمستندات.»',
            '«الخطوة 6: إشعار SMS في حال وجود مستندات ناقصة أو فروق رسوم لاستكمالها إلكترونيًا دون عناء.»',
            '«الخطوة 7: تم اعتماد الطلب وإصدار شهادة التسجيل المكاني المؤمنة إلكترونيًا.»',
            '«الخطوة 8: مبروك! انتهت الخدمة بنجاح ويمكنكم استلام الشهادة إلكترونيًا لتقديمها بملف رخصة البناء.»'
        ];

        function toggleAnalogy(key, btn) {
            document.querySelectorAll('.sim-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const data = analogyData[key];
            const container = document.getElementById('analogy-content');
            container.innerHTML = `
                <div style="font-size:0.82rem; color:var(--accent-cyan); font-weight:700; margin-bottom:10px;">${data.badge}</div>
                <div style="background:rgba(166,58,50,0.08); border:1px solid rgba(166,58,50,0.25); padding:10px 14px; border-radius:10px; margin-bottom:10px;">
                    <strong style="color:#E8968D; font-size:0.82rem;"><i class="fa-solid fa-clock-rotate-left"></i> قبل التحول الرقمي:</strong>
                    <p style="font-size:0.84rem; margin-top:4px;">${data.before}</p>
                </div>
                <div style="background:rgba(216,176,62,0.1); border:1px solid rgba(216,176,62,0.3); padding:10px 14px; border-radius:10px;">
                    <strong style="color:#8FCB9F; font-size:0.82rem;"><i class="fa-solid fa-bolt"></i> بعد التحول الرقمي:</strong>
                    <p style="font-size:0.84rem; margin-top:4px;">${data.after}</p>
                </div>
            `;
            gsap.fromTo(container, { opacity: 0, y: 8 }, { opacity: 1, y: 0, duration: 0.35 });
        }

        function selectStep(index) {
            const steps = document.querySelectorAll('.journey-step');
            steps.forEach((s, i) => s.classList.toggle('active-step', i === index));
            const preview = document.getElementById('sms-preview-text');
            preview.textContent = smsMessages[index];
            gsap.fromTo(preview, { opacity: 0, x: -10 }, { opacity: 1, x: 0, duration: 0.3 });
        }

        function initFeesChart() {
            const ctx = document.getElementById('feesChart');
            if (!ctx) return;
            if (feesChartInstance) feesChartInstance.destroy();

            feesChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        'المقابل الفني (400.00 ج.م)',
                        'إشعارات SMS (10.00 ج.م)',
                        'دمغة الشهادة (5.00 ج.م)'
                    ],
                    datasets: [{
                        data: [400, 10, 5],
                        backgroundColor: ['#00BFD8', '#D8B03E', '#A9793C'],
                        borderColor: '#26302D',
                        borderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
                    animation: { animateScale: true, duration: 1100 },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#ECE8D6',
                                font: { family: 'IBM Plex Sans Arabic', size: 12 },
                                padding: 14
                            }
                        },
                        tooltip: {
                            rtl: true,
                            bodyFont: { family: 'IBM Plex Sans Arabic' }
                        }
                    }
                }
            });
        }

        function animateSlide(index) {
            const activeSlide = slides[index];
            const elements = activeSlide.querySelectorAll('.stagger-el');
            
            gsap.killTweensOf(elements);
            gsap.fromTo(elements, 
                { opacity: 0, y: 24, scale: 0.98 },
                { 
                    opacity: 1, 
                    y: 0, 
                    scale: 1, 
                    duration: 0.65, 
                    stagger: 0.09, 
                    ease: 'power3.out',
                    clearProps: 'transform'
                }
            );

            // Trigger Chart on Slide 10 (index 9)
            if (index === 9) {
                initFeesChart();
            }
        }

        function goToSlide(index) {
            if (index < 0 || index >= totalSlides) return;
            slides[currentSlide].classList.remove('active');
            currentSlide = index;
            slides[currentSlide].classList.add('active');

            // Update UI
            document.getElementById('slide-counter').textContent = 
                `${String(currentSlide + 1).padStart(2, '0')} / ${String(totalSlides).padStart(2, '0')}`;
            document.getElementById('progress-fill').style.width = `${((currentSlide + 1) / totalSlides) * 100}%`;
            document.getElementById('btn-prev').disabled = currentSlide === 0;
            document.getElementById('btn-next').disabled = currentSlide === totalSlides - 1;

            animateSlide(currentSlide);
        }

        // Navigation Controls
        document.getElementById('btn-next').addEventListener('click', () => goToSlide(currentSlide + 1));
        document.getElementById('btn-prev').addEventListener('click', () => goToSlide(currentSlide - 1));
        document.getElementById('btn-replay').addEventListener('click', () => animateSlide(currentSlide));

        document.getElementById('btn-lang').addEventListener('click', function() {
            document.body.classList.toggle('show-en');
            this.classList.toggle('active');
        });

        document.getElementById('btn-fullscreen').addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen();
            }
        });

        // Progress Bar Scrubbing
        document.getElementById('progress-track').addEventListener('click', (e) => {
            const rect = e.currentTarget.getBoundingClientRect();
            // RTL calculation: right is 0, left is 100%
            const clickX = rect.right - e.clientX;
            const ratio = Math.max(0, Math.min(1, clickX / rect.width));
            const target = Math.floor(ratio * totalSlides);
            goToSlide(Math.min(target, totalSlides - 1));
        });

        // Overview Modal
        function toggleOverview() {
            const modal = document.getElementById('overview-modal');
            modal.classList.toggle('open');
            if (modal.classList.contains('open')) {
                const grid = document.getElementById('overview-grid');
                grid.innerHTML = '';
                slides.forEach((s, idx) => {
                    const thumb = document.createElement('div');
                    thumb.className = `overview-thumb ${idx === currentSlide ? 'current' : ''}`;
                    thumb.innerHTML = `
                        <div style="color:var(--accent-cyan); font-family:var(--font-display); font-weight:800; font-size:0.85rem;">شريحة ${String(idx + 1).padStart(2, '0')}</div>
                        <div style="font-weight:700; margin-top:6px; font-size:0.95rem;">${s.dataset.title || 'شريحة'}</div>
                    `;
                    thumb.onclick = () => {
                        goToSlide(idx);
                        toggleOverview();
                    };
                    grid.appendChild(thumb);
                });
            }
        }
        document.getElementById('btn-overview').addEventListener('click', toggleOverview);

        // Keyboard Shortcuts (RTL-Aware: Left Arrow = Next, Right Arrow = Prev)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' || e.key === ' ' || e.key === 'PageDown') {
                e.preventDefault();
                goToSlide(currentSlide + 1);
            } else if (e.key === 'ArrowRight' || e.key === 'PageUp') {
                e.preventDefault();
                goToSlide(currentSlide - 1);
            } else if (e.key.toLowerCase() === 'g') {
                toggleOverview();
            } else if (e.key.toLowerCase() === 'f') {
                document.getElementById('btn-fullscreen').click();
            } else if (e.key === 'Escape') {
                document.getElementById('overview-modal').classList.remove('open');
            }
        });

        // Ambient Network Particle Background
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        for (let i = 0; i < (reduceMotion ? 0 : 28); i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.18,
                vy: (Math.random() - 0.5) * 0.18,
                r: Math.random() * 2 + 1
            });
        }

        function drawAmbient() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(0, 191, 216, 0.22)';
            ctx.strokeStyle = 'rgba(216, 176, 62, 0.05)';

            for (let i = 0; i < particles.length; i++) {
                const p = particles[i];
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fill();

                for (let j = i + 1; j < particles.length; j++) {
                    const p2 = particles[j];
                    const dist = Math.hypot(p.x - p2.x, p.y - p2.y);
                    if (dist < 140) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(drawAmbient);
        }

        // SVG Continuous Rotation
        gsap.to('.spin-slow', { rotation: 360, transformOrigin: '180px 180px', duration: 60, repeat: -1, ease: 'none' });
        gsap.to('.spin-reverse', { rotation: -360, transformOrigin: '180px 180px', duration: 48, repeat: -1, ease: 'none' });

        // Initialize First View
        toggleAnalogy('instapay', document.querySelector('.sim-btn'));
        goToSlide(0);
        drawAmbient();
    </script>
</body>
</html>