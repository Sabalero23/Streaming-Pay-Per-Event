<?php
// config/database.php

// Función para cargar variables de entorno desde .env
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear línea
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remover comillas si existen
            $value = trim($value, '"\'');
            
            // Establecer en $_ENV y $_SERVER
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Función para obtener variables de entorno
function getEnvVar($name, $default = null) {
    // Prioridad: $_ENV > $_SERVER > getenv() > default
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    if (function_exists('getenv')) {
        $value = getenv($name);
        if ($value !== false) {
            return $value;
        }
    }
    return $default;
}

// Cargar .env desde el directorio raíz del proyecto
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $envPath = __DIR__ . '/../../.env';
}
loadEnv($envPath);

class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    private $charset = 'utf8mb4';
    
    private function __construct() {
        // Cargar configuración desde variables de entorno
        $this->host = getEnvVar('DB_HOST', 'localhost');
        $this->dbname = getEnvVar('DB_NAME', 'streaming_platform');
        $this->username = getEnvVar('DB_USER', 'root');
        $this->password = getEnvVar('DB_PASS', '');
        $this->port = getEnvVar('DB_PORT', 3306);
        
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_TIMEOUT => 5
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log detallado del error
            $errorMsg = "Database connection failed: " . $e->getMessage();
            $errorDetails = "\nHost: {$this->host}:{$this->port}\nDatabase: {$this->dbname}\nUser: {$this->username}";
            error_log($errorMsg . $errorDetails);
            
            // Mensaje amigable para el usuario
            if (getEnvVar('APP_DEBUG') === 'true' || getEnvVar('APP_DEBUG') === true) {
                die("<h1>Error de Conexión a Base de Datos</h1>
                     <p><strong>Detalles:</strong> {$e->getMessage()}</p>
                     <p><strong>Host:</strong> {$this->host}:{$this->port}</p>
                     <p><strong>Base de datos:</strong> {$this->dbname}</p>
                     <p><strong>Usuario:</strong> {$this->username}</p>
                     <hr>
                     <h3>Soluciones:</h3>
                     <ol>
                         <li>Verificar que MySQL está corriendo: <code>sudo systemctl status mysql</code></li>
                         <li>Verificar credenciales en el archivo .env</li>
                         <li>Verificar que la base de datos existe</li>
                         <li>Ejecutar diagnóstico: <code>php debug_env.php</code></li>
                     </ol>");
            } else {
                die("<h1>Error de Conexión</h1>
                     <p>No se pudo conectar a la base de datos. Por favor contacte al administrador.</p>
                     <p><small>Error ID: " . date('YmdHis') . "</small></p>");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Verificar salud de la conexión
    public function healthCheck() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Clase base para modelos
abstract class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error in {$this->table}: " . $e->getMessage() . "\nSQL: {$sql}");
            throw new Exception("Error en la consulta a la base de datos");
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    public function findAll($limit = 100, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} LIMIT ? OFFSET ?";
        $stmt = $this->query($sql, [$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ($placeholders)";
        $this->query($sql, $values);
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $values[] = $id;
        
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE {$this->table} SET $setClause WHERE id = ?";
        
        return $this->query($sql, $values)->rowCount();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->query($sql, [$id])->rowCount();
    }
    
    public function exists($field, $value) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$field} = ?";
        $stmt = $this->query($sql, [$value]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}