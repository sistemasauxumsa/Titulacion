<?php
/**
 * Clase para leer variables de entorno desde archivo .env
 */
class EnvLoader {
    private static $loaded = false;
    
    /**
     * Cargar variables desde archivo .env
     */
    public static function load($path = null) {
        if (self::$loaded) return;
        
        $path = $path ?: __DIR__ . '/../.env';
        
        if (!file_exists($path)) {
            throw new Exception("Archivo .env no encontrado en: " . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) continue;
            
            // Parsear línea
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                $value = trim($value, '"\'');
                
                // Establecer como variable de entorno
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtener variable de entorno
     */
    public static function get($key, $default = null) {
        self::load();
        
        return $_ENV[$key] ?? $default;
    }
}

// Cargar automáticamente al incluir el archivo
EnvLoader::load();
?>
