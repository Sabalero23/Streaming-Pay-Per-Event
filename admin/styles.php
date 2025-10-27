<style>
/* Estilos compartidos para todo el panel admin */

.section {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 15px;
}

.section h2 {
    color: #2c3e50;
    font-size: 22px;
}

/* Botones */
.btn {
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    display: inline-block;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5568d3;
}

.btn-success {
    background: #4CAF50;
    color: white;
}

.btn-success:hover {
    background: #45a049;
}

.btn-danger {
    background: #f44336;
    color: white;
}

.btn-danger:hover {
    background: #da190b;
}

.btn-warning {
    background: #ffc107;
    color: #333;
}

/* Tablas */
table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    text-align: left;
    padding: 12px;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    font-size: 14px;
    color: #495057;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
    font-size: 14px;
}

table tr:hover {
    background: #f8f9fa;
}

/* Badges */
.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-live {
    background: #ff0000;
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Alertas */
.alert {
    padding: 15px 20px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 10px;
}

.stat-label {
    color: #666;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Filtros */
.filter-bar {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-bar a {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    border: 2px solid #e0e0e0;
    color: #333;
    transition: all 0.3s;
    font-size: 14px;
}

.filter-bar a.active,
.filter-bar a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Formularios */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 5px;
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 5px;
    font-size: 14px;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

/* Checkboxes */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-group input {
    width: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .section {
        padding: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-value {
        font-size: 28px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-bar {
        padding: 15px;
    }
    
    table {
        font-size: 13px;
    }
    
    table th,
    table td {
        padding: 8px;
    }
}

@media (max-width: 480px) {
    .section {
        padding: 15px;
    }
    
    .section h2 {
        font-size: 18px;
    }
    
    .stat-value {
        font-size: 24px;
    }
    
    .btn {
        padding: 8px 15px;
        font-size: 13px;
    }
    
    table {
        font-size: 12px;
    }
    
    table th,
    table td {
        padding: 6px;
    }
}

/* Tabla responsive con scroll horizontal */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -20px;
        padding: 0 20px;
    }
}
</style>
