<style>
/* ===================================
   EVENTIX ADMIN - Estilos Modernos
   =================================== */

/* Sections */
.section {
    background: white;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f3f5;
    flex-wrap: wrap;
    gap: 15px;
}

.section h2 {
    color: #2c3e50;
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.3px;
}

/* Botones */
.btn {
    padding: 12px 28px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 65, 108, 0.3);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 65, 108, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
}

.btn-secondary {
    background: #f8f9fa;
    color: #2c3e50;
    border: 2px solid #e9ecef;
    box-shadow: none;
}

.btn-secondary:hover {
    background: #e9ecef;
    border-color: #dee2e6;
}

.btn-sm {
    padding: 8px 20px;
    font-size: 12px;
}

.btn-lg {
    padding: 16px 36px;
    font-size: 15px;
}

/* Tablas */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

table th {
    text-align: left;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

table th:first-child {
    border-top-left-radius: 12px;
}

table th:last-child {
    border-top-right-radius: 12px;
}

table td {
    padding: 16px;
    border-bottom: 1px solid #f1f3f5;
    font-size: 14px;
    color: #2c3e50;
    background: white;
}

table tr:last-child td:first-child {
    border-bottom-left-radius: 12px;
}

table tr:last-child td:last-child {
    border-bottom-right-radius: 12px;
}

table tr:hover td {
    background: #f8f9fa;
}

/* Badges */
.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.badge-danger {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    color: white;
}

.badge-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.badge-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.badge-live {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Alertas */
.alert {
    padding: 18px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    border: none;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-error {
    background: linear-gradient(135deg, rgba(255, 65, 108, 0.1) 0%, rgba(255, 75, 43, 0.1) 100%);
    color: #c92a4a;
    border-left: 4px solid #ff416c;
}

.alert-success {
    background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%);
    color: #0f8478;
    border-left: 4px solid #11998e;
}

.alert-warning {
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
    color: #b54764;
    border-left: 4px solid #f5576c;
}

.alert-info {
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.1) 0%, rgba(0, 242, 254, 0.1) 100%);
    color: #3a8ec4;
    border-left: 4px solid #4facfe;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 32px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.15);
    border-color: #667eea;
}

.stat-icon {
    font-size: 40px;
    margin-bottom: 16px;
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-value {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    color: #7f8c8d;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
}

/* Filtros */
.filter-bar {
    background: white;
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.filter-bar a {
    padding: 10px 20px;
    border-radius: 20px;
    text-decoration: none;
    border: 2px solid #e9ecef;
    color: #2c3e50;
    transition: all 0.3s ease;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-bar a.active,
.filter-bar a:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Formularios */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    color: #2c3e50;
    background: white;
    transition: all 0.3s ease;
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 12px 18px;
    border: 2px solid #e9ecef;
    border-radius: 30px;
    font-size: 14px;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 24px;
    color: #7f8c8d;
    background: #f8f9fa;
    border-radius: 16px;
    margin: 20px 0;
}

.empty-state-icon {
    font-size: 72px;
    margin-bottom: 24px;
    opacity: 0.3;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.empty-state h3 {
    color: #2c3e50;
    margin-bottom: 12px;
    font-size: 20px;
    font-weight: 700;
}

.empty-state p {
    color: #7f8c8d;
    font-size: 15px;
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

/* Cards Adicionales */
.info-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.info-card h4 {
    color: #2c3e50;
    font-size: 16px;
    margin-bottom: 12px;
    font-weight: 700;
}

.info-card p {
    color: #7f8c8d;
    font-size: 14px;
    line-height: 1.6;
}

/* Pagination */
.pagination {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 30px;
}

.pagination a,
.pagination span {
    padding: 10px 16px;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    text-decoration: none;
    color: #2c3e50;
    font-weight: 600;
    transition: all 0.3s ease;
}

.pagination a:hover,
.pagination span.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

/* Action Buttons Group */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Modal Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: white;
    padding: 40px;
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f3f5;
}

.modal-header h3 {
    color: #2c3e50;
    font-size: 22px;
    font-weight: 700;
}

/* Progress Bar */
.progress-bar {
    width: 100%;
    height: 8px;
    background: #f1f3f5;
    border-radius: 10px;
    overflow: hidden;
    margin: 16px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

/* Loading Spinner */
.spinner {
    border: 3px solid #f1f3f5;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Tooltip */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip .tooltip-text {
    visibility: hidden;
    background: #2c3e50;
    color: white;
    text-align: center;
    padding: 8px 12px;
    border-radius: 8px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 12px;
    white-space: nowrap;
}

.tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .section {
        padding: 24px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
    }
    
    .stat-card {
        padding: 24px;
    }
    
    .stat-value {
        font-size: 28px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-bar {
        padding: 16px;
    }
    
    table {
        font-size: 13px;
    }
    
    table th,
    table td {
        padding: 12px 8px;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .section {
        padding: 20px;
    }
    
    .section h2 {
        font-size: 20px;
    }
    
    .stat-value {
        font-size: 24px;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 13px;
    }
    
    table {
        font-size: 12px;
    }
    
    table th,
    table td {
        padding: 10px 6px;
    }
}

/* Tabla responsive con scroll horizontal */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .table-responsive {
        margin: 0 -24px;
        padding: 0 24px;
    }
}
</style>