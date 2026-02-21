<?php
require_once 'db_connection.php';

function verificarServicioSocial($id_alumno) {
    try {
        $mysqli = getDatabaseConnection();
        $servicios = [];
        
        // Buscar servicios sociales (completados y en proceso)
        $sql = "SELECT s.idcarr, c.nom as nombre_carrera, s.tiporeg, s.estatus, s.concluido 
                FROM servsoc s 
                JOIN carreras c ON s.idcarr = c.id 
                WHERE s.idal = ? AND s.tiporeg = 'S'";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_alumno);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($servicio = $result->fetch_assoc()) {
            $servicios[] = [
                'id_carrera' => $servicio['idcarr'],
                'nombre_carrera' => $servicio['nombre_carrera'],
                'tipo_registro' => $servicio['tiporeg'],
                'estatus' => $servicio['estatus'],
                'concluido' => $servicio['concluido'],
                'estado_descripcion' => $servicio['concluido'] == 1 ? 'Completado' : 
                                      ($servicio['estatus'] == 1 ? 'En proceso' : 'Inactivo')
            ];
        }
        
        $completados = array_filter($servicios, function($s) { return $s['concluido'] == 1; });
        $en_proceso = array_filter($servicios, function($s) { return $s['concluido'] != 1 && $s['estatus'] == 1; });
        
        return [
            'tiene_servicio_social' => !empty($servicios),
            'total_servicios' => count($servicios),
            'completados' => count($completados),
            'en_proceso' => count($en_proceso),
            'detalle' => $servicios
        ];
        
    } catch (Exception $e) {
        return [
            'tiene_servicio_social' => false,
            'total_servicios' => 0,
            'completados' => 0,
            'en_proceso' => 0,
            'detalle' => []
        ];
    }
}
?>
