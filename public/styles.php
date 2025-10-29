<style>
/* Estilos compartidos para el sitio público */

/* Sections */
.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 10px 20px;
    text-align: center;
}

.hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
}

.hero p {
    font-size: 20px;
    opacity: 0.9;
}

.section {
    padding: 60px 20px;
}

.section-title {
    font-size: 32px;
    margin-bottom: 40px;
}

/* Cards */
.card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

/* Buttons */
.btn {
    background: #4CAF50;
    color: white;
    padding: 12px 24px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
    font-size: 16px;
    border: none;
    cursor: pointer;
    font-weight: 500;
}

.btn:hover {
    background: #45a049;
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.btn-primary:hover {
    opacity: 0.9;
}

.btn-danger {
    background: #f44336;
}

.btn-danger:hover {
    background: #da190b;
}

.btn-secondary {
    background: #666;
}

.btn-secondary:hover {
    background: #555;
}

/* Events Grid */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.event-card {
    background: #1a1a1a;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.event-thumbnail {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
}

.event-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-info {
    padding: 20px;
}

.event-category {
    color: #667eea;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.event-title {
    font-size: 20px;
    margin: 10px 0;
    color: #fff;
}

.event-date {
    color: #999;
    font-size: 14px;
    margin-bottom: 15px;
}

.event-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 24px;
    font-weight: bold;
    color: #4CAF50;
}

/* Badges */
.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.badge-live {
    background: #ff0000;
    color: white;
    animation: pulse 2s infinite;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.live-badge {
    background: #ff0000;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    animation: pulse 2s infinite;
    margin-bottom: 30px;
}

.live-badge::before {
    content: "●";
    margin-right: 8px;
    font-size: 18px;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Alerts */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

/* Forms */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #fff;
    font-weight: 500;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #333;
    border-radius: 5px;
    background: #1a1a1a;
    color: #fff;
    font-size: 16px;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
}

/* Stats */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.stat-card {
    background: #1a1a1a;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
}

.stat-number {
    font-size: 48px;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    color: #999;
    margin-top: 10px;
}

/* Empty States */
.no-events {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-events svg {
    width: 100px;
    height: 100px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.no-events h3 {
    color: #999;
    margin-bottom: 10px;
}

/* Player */
.player-container {
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 30px;
}

.video-player {
    width: 100%;
    aspect-ratio: 16/9;
    background: #000;
}

/* Profile */
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
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
    font-weight: bold;
    margin: 0 auto 20px;
    border: 4px solid rgba(255,255,255,0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .hero h1 {
        font-size: 32px;
    }
    
    .hero p {
        font-size: 16px;
    }
    
    .hero {
        padding: 40px 20px;
    }
    
    .section {
        padding: 40px 20px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .events-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .stats {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
    }
    
    .stat-number {
        font-size: 36px;
    }
}

@media (max-width: 480px) {
    .hero h1 {
        font-size: 24px;
    }
    
    .hero p {
        font-size: 14px;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .event-title {
        font-size: 18px;
    }
    
    .price {
        font-size: 20px;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 14px;
    }
}
</style>
