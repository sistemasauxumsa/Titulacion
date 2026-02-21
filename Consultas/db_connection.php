<?php
require_once 'EnvLoader.php';

/**
 * Archivo de conexión centralizado a la base de datos
 * Usado por todos los archivos de consultas para evitar duplicación de credenciales
 */

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    // Credenciales desde archivo .env
    private $host;
    private $username;
    private $password;
    private $database;
    
    private function __construct() {
        // Obtener credenciales desde .env
        $this->host = EnvLoader::get('DB_HOST');
        $this->username = EnvLoader::get('DB_USERNAME');
        $this->password = EnvLoader::get('DB_PASSWORD');
        $this->database = EnvLoader::get('DB_DATABASE');
        
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->connection->connect_error) {
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    /**
     * Obtener instancia única de la conexión (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener la conexión activa
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Cerrar la conexión
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    /**
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Función helper para obtener conexión rápidamente
 */
function getDatabaseConnection() {
    return DatabaseConnection::getInstance()->getConnection();
}

/**
 * Función helper para cerrar conexión
 */
function closeDatabaseConnection() {
    DatabaseConnection::getInstance()->close();
}
?>
