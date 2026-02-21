<?php
require_once 'EnvLoader.php';
require_once 'verificar_adeudos.php';
require_once 'verificar_servicio_social.php';

// Configuración estricta para JSON limpio
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// Función simple de conexión usando .env
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $host = EnvLoader::get('DB_HOST');
        $username = EnvLoader::get('DB_USERNAME');
        $password = EnvLoader::get('DB_PASSWORD');
        $database = EnvLoader::get('DB_DATABASE');
        
        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            return null;
        }
    }
    return $conn;
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curp'])) {
    $curp = strtoupper($_POST['curp']);
    
    // Buscar alumno
    $mysqli = getDB();
    if (!$mysqli) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    $stmt = $mysqli->prepare("SELECT id, nombres, apaterno, amaterno, curp, niveles FROM alumnos WHERE curp = ?");
    $stmt->bind_param("s", $curp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'CURP no encontrada en el sistema']);
        exit;
    }
    
    $alumno = $result->fetch_assoc();
    $id_alumno = $alumno['id'];
    
    // Buscar carreras (simplificado)
    $carreras = [];
    
    // Licenciatura
    $stmt = $mysqli->prepare("SELECT e.idcarr, c.nom, c.niv FROM egresados5 e 
        JOIN carreras c ON e.idcarr = c.id 
        WHERE e.idal = ? AND e.certrec = 1 AND e.certent = 1 
        AND EXISTS (
            SELECT 1 FROM servsoc s 
            WHERE s.idal = e.idal AND s.idcarr = e.idcarr 
            AND s.tiporeg = 'S' AND s.estatus = 1 AND s.concluido = 1
        )");
    $stmt->bind_param("i", $id_alumno);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $nivel = ($row['niv'] == 5) ? 'Licenciatura' : 'Maestría';
        $carreras[] = [
            'id' => $row['idcarr'],
            'nombre' => $row['nom'],
            'nivel' => $nivel,
            'niv' => (int)$row['niv']
        ];
    }
    
    // Doctorado
    $stmt = $mysqli->prepare("SELECT e.idcarr, c.nom, c.niv FROM egresados6 e 
        JOIN carreras c ON e.idcarr = c.id 
        WHERE e.idal = ? AND e.certrec = 1 AND e.certent = 1 
        AND EXISTS (
            SELECT 1 FROM servsoc s 
            WHERE s.idal = e.idal AND s.idcarr = e.idcarr 
            AND s.tiporeg = 'S' AND s.estatus = 1 AND s.concluido = 1
        )");
    $stmt->bind_param("i", $id_alumno);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $nivel = ($row['niv'] == 6) ? 'Maestría' : 'Licenciatura';
        $carreras[] = [
            'id' => $row['idcarr'],
            'nombre' => $row['nom'],
            'nivel' => $nivel,
            'niv' => (int)$row['niv']
        ];
    }
    
    if (empty($carreras)) {
        // Verificar si es por falta de servicio social
        $resultado_servicio = verificarServicioSocial($id_alumno);
        
        if ($resultado_servicio['tiene_servicio_social']) {
            if ($resultado_servicio['completados'] === 0) {
                $mensaje = "No puedes tramitar tu titulación porque no has completado tu servicio social.";
                if ($resultado_servicio['en_proceso'] > 0) {
                    $mensaje .= " Tienes {$resultado_servicio['en_proceso']} servicio(s) social(es) en proceso.";
                }
                echo json_encode([
                    'success' => false,
                    'message' => $mensaje,
                    'es_servicio_social' => true,
                    'servicio_social' => $resultado_servicio
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No tienes carreras disponibles para titularse. Asegúrate de haber completado todos tus requisitos académicos.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes carreras disponibles para titularse. No se encontraron registros de servicio social.'
            ]);
        }
        exit;
    }
    
    // Verificar adeudos del alumno
    $resultado_adeudos = verificarAdeudosAlumno($id_alumno);
    
    if ($resultado_adeudos['tiene_adeudos']) {
        echo json_encode([
            'success' => false,
            'message' => 'No puedes tramitar tu titulación porque tienes adeudos pendientes.',
            'adeudos' => $resultado_adeudos['adeudos'],
            'importe_total' => $resultado_adeudos['importe_total'],
            'cantidad_adeudos' => $resultado_adeudos['cantidad_adeudos'],
            'es_adeudo' => true
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'alumno' => $alumno,
        'carreras' => $carreras,
        'adeudos' => $resultado_adeudos
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
