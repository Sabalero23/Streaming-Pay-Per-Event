<style>
/* ===================================
   EVENTIX - Estilo Estudios Max
   Diseño Corporativo Limpio
   =================================== */

/* Reset y Base */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: #ffffff;
    color: #333333;
    font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
    line-height: 1.8;
    font-size: 16px;
}

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 30px;
}

/* ===================================
   HERO SECTION - Estilo Estudios Max
   =================================== */
.hero {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    padding: 80px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.1);
}

.hero .container {
    position: relative;
    z-index: 1;
    max-width: 900px;
}

.hero h1 {
    font-size: 42px;
    margin-bottom: 20px;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.3;
}

.hero p {
    font-size: 20px;
    color: #ffffff;
    font-weight: 300;
    margin-bottom: 30px;
    line-height: 1.6;
}

/* ===================================
   SECTIONS
   =================================== */
.section {
    padding: 70px 30px;
    background: #ffffff;
}

.section:nth-child(even) {
    background: #f7f7f7;
}

.section-title {
    font-size: 36px;
    margin-bottom: 15px;
    color: #1e3c72;
    font-weight: 700;
    text-align: center;
    line-height: 1.3;
}

.section-subtitle {
    text-align: center;
    color: #666666;
    font-size: 18px;
    margin-bottom: 50px;
    font-weight: 300;
    line-height: 1.6;
}

/* ===================================
   LIVE BADGE
   =================================== */
.live-badge {
    background: #e74c3c;
    color: white;
    padding: 12px 30px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    animation: pulse-simple 2s infinite;
    margin-bottom: 40px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.live-badge::before {
    content: "●";
    margin-right: 10px;
    font-size: 16px;
    animation: blink 1.5s infinite;
}

@keyframes pulse-simple {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.9; }
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* ===================================
   BADGES
   =================================== */
.badge {
    padding: 5px 12px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 700;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-live {
    background: #e74c3c;
    color: white;
}

.badge-success {
    background: #27ae60;
    color: white;
}

.badge-warning {
    background: #f39c12;
    color: white;
}

.badge-info {
    background: #3498db;
    color: white;
}

.free-badge {
    background: #27ae60;
    color: white;
    padding: 6px 14px;
    border-radius: 3px;
    font-weight: 700;
    display: inline-block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ===================================
   CARDS - Estilo Limpio
   =================================== */
.card {
    background: #ffffff;
    border-radius: 4px;
    padding: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
}

/* ===================================
   EVENTS GRID
   =================================== */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.event-card {
    background: #ffffff;
    border-radius: 4px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.event-thumbnail {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    overflow: hidden;
}

.event-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-info {
    padding: 25px;
    background: #ffffff;
}

.event-category {
    color: #3498db;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.event-title {
    font-size: 20px;
    margin: 0 0 15px 0;
    color: #333333;
    font-weight: 700;
    line-height: 1.4;
    min-height: 56px;
}

.event-date {
    color: #666666;
    font-size: 14px;
    margin-bottom: 20px;
    font-weight: 400;
}

.event-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.price {
    font-size: 28px;
    font-weight: 700;
    color: #1e3c72;
}

/* ===================================
   BUTTONS - Estilo Corporativo
   =================================== */
.btn {
    background: #3498db;
    color: white;
    padding: 12px 30px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    font-size: 14px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn:hover {
    background: #2980b9;
    box-shadow: 0 3px 8px rgba(52,152,219,0.3);
}

.btn-primary {
    background: #3498db;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-success {
    background: #27ae60;
}

.btn-success:hover {
    background: #229954;
}

.btn-danger {
    background: #e74c3c;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-lg {
    padding: 15px 40px;
    font-size: 16px;
}

/* ===================================
   STATS CARDS - Estilo Estudios Max
   =================================== */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

.stat-card {
    background: #ffffff;
    padding: 40px 30px;
    border-radius: 4px;
    text-align: center;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.06);
}

.stat-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
    transform: translateY(-3px);
}

.stat-icon {
    font-size: 48px;
    margin-bottom: 15px;
    color: #3498db;
}

.stat-number {
    font-size: 48px;
    font-weight: 700;
    color: #1e3c72;
    margin-bottom: 10px;
}

.stat-label {
    color: #666666;
    margin-top: 10px;
    font-size: 14px;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ===================================
   FEATURES GRID - Iconos Grandes
   =================================== */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

.feature-card {
    background: #ffffff;
    padding: 40px 30px;
    border-radius: 4px;
    text-align: center;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.06);
}

.feature-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
    transform: translateY(-3px);
}

.feature-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon img {
    max-width: 100%;
    max-height: 100%;
    filter: grayscale(0);
    opacity: 0.9;
}

.feature-card h3 {
    font-size: 18px;
    color: #333333;
    margin-bottom: 15px;
    font-weight: 700;
}

.feature-card p {
    color: #666666;
    font-size: 14px;
    line-height: 1.7;
}

/* ===================================
   ALERTS
   =================================== */
.alert {
    padding: 15px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 14px;
    border-left: 4px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #27ae60;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-color: #e74c3c;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border-color: #3498db;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-color: #f39c12;
}

/* ===================================
   FORMS
   =================================== */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333333;
    font-weight: 600;
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #d0d0d0;
    border-radius: 4px;
    background: #ffffff;
    color: #333333;
    font-size: 14px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52,152,219,0.1);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

/* ===================================
   EMPTY STATES
   =================================== */
.no-events {
    text-align: center;
    padding: 80px 30px;
    color: #999999;
    background: #f7f7f7;
    border-radius: 4px;
}

.no-events svg {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
    opacity: 0.3;
    color: #3498db;
}

.no-events h3 {
    color: #666666;
    margin-bottom: 10px;
    font-size: 22px;
    font-weight: 700;
}

.no-events p {
    color: #999999;
    font-size: 15px;
}

/* ===================================
   EVENT DETAIL PAGE
   =================================== */
.event-detail {
    max-width: 1200px;
    margin: 0 auto;
}

.event-hero {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 4px;
    padding: 50px;
    margin-bottom: 30px;
    color: #ffffff;
}

.event-main {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.event-content {
    background: #ffffff;
    border-radius: 4px;
    padding: 40px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.06);
}

.event-sidebar {
    background: #ffffff;
    border-radius: 4px;
    padding: 30px;
    height: fit-content;
    border: 1px solid #e0e0e0;
    position: sticky;
    top: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.06);
}

.event-thumbnail-large {
    width: 100%;
    height: 400px;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 100px;
    margin-bottom: 30px;
    overflow: hidden;
}

.event-thumbnail-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item strong {
    display: block;
    color: #333333;
    font-weight: 700;
    margin-bottom: 5px;
    font-size: 14px;
}

.info-item span {
    color: #666666;
    font-size: 14px;
}

/* ===================================
   PLAYER
   =================================== */
.player-container {
    background: #000;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}

.video-player {
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
}

/* ===================================
   PROFILE
   =================================== */
.profile-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    padding: 50px;
    border-radius: 4px;
    margin-bottom: 30px;
    text-align: center;
    color: #ffffff;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    margin: 0 auto 20px;
    border: 4px solid rgba(255,255,255,0.3);
}

/* ===================================
   FILTER SECTION
   =================================== */
.filter-section {
    background: #f7f7f7;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 30px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    border: 1px solid #e0e0e0;
}

/* ===================================
   CTA SECTION - Estilo Estudios Max
   =================================== */
.cta-section {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    padding: 60px 30px;
    text-align: center;
    border-radius: 4px;
    margin: 40px auto;
}

.cta-section h2 {
    color: #ffffff;
    font-size: 32px;
    margin-bottom: 15px;
    font-weight: 700;
}

.cta-section p {
    color: #ffffff;
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.95;
}

.cta-section .btn {
    background: #ffffff;
    color: #1e3c72;
    font-weight: 700;
    padding: 15px 40px;
}

.cta-section .btn:hover {
    background: #f0f0f0;
}

/* ===================================
   RESPONSIVE
   =================================== */
@media (max-width: 768px) {
    .hero {
        padding: 60px 20px;
    }
    
    .hero h1 {
        font-size: 32px;
    }
    
    .hero p {
        font-size: 16px;
    }
    
    .section {
        padding: 50px 20px;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .stat-number {
        font-size: 36px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .event-main {
        grid-template-columns: 1fr;
    }
    
    .event-hero,
    .event-content,
    .event-sidebar,
    .profile-header {
        padding: 30px 20px;
    }
    
    .event-thumbnail-large {
        height: 250px;
        font-size: 60px;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0 15px;
    }
    
    .hero h1 {
        font-size: 26px;
    }
    
    .hero p {
        font-size: 14px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .event-title {
        font-size: 18px;
        min-height: auto;
    }
    
    .price {
        font-size: 24px;
    }
    
    .stats {
        grid-template-columns: 1fr;
    }
    
    .stat-card,
    .feature-card {
        padding: 30px 20px;
    }
}

/* ===================================
   UTILITIES
   =================================== */
.text-center {
    text-align: center;
}

.mt-4 { margin-top: 2rem; }
.mb-4 { margin-bottom: 2rem; }
.py-5 { padding-top: 3rem; padding-bottom: 3rem; }
</style>