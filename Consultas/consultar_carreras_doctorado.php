<?php
require 'db_connection.php';

function consultarCarrerasDoctorado($id_alumno) {
    try {
        $mysqli = getDatabaseConnection();
        
        $carreras = [];
        
        // Buscar en egresados6 (maestría - nivel 6) donde certrec=1 Y certent=1
        $sql_egresados = "SELECT idcarr FROM egresados6 WHERE idal = ? AND certrec = 1 AND certent = 1";
        $stmt_egresados = $mysqli->prepare($sql_egresados);
        $stmt_egresados->bind_param("i", $id_alumno);
        $stmt_egresados->execute();
        $resultado_egresados = $stmt_egresados->get_result();
        
        while ($egresado = $resultado_egresados->fetch_assoc()) {
            $id_carrera = $egresado['idcarr'];
            
            // Verificar servicio social en servsoc
            $sql_servsoc = "SELECT idcarr FROM servsoc WHERE idal = ? AND idcarr = ? AND tiporeg = 'S' AND estatus = 1 AND concluido = 1";
            $stmt_servsoc = $mysqli->prepare($sql_servsoc);
            $stmt_servsoc->bind_param("ii", $id_alumno, $id_carrera);
            $stmt_servsoc->execute();
            $resultado_servsoc = $stmt_servsoc->get_result();
            
            if ($resultado_servsoc->num_rows > 0) {
                // Obtener nombre y nivel de la carrera
                $sql_carrera = "SELECT id, nom, niv FROM carreras WHERE id = ?";
                $stmt_carrera = $mysqli->prepare($sql_carrera);
                $stmt_carrera->bind_param("i", $id_carrera);
                $stmt_carrera->execute();
                $resultado_carrera = $stmt_carrera->get_result();
                
                if ($resultado_carrera->num_rows > 0) {
                    $carrera = $resultado_carrera->fetch_assoc();
                    $carrera['id'] = $id_carrera;
                    $carreras[] = $carrera;
                }
                $stmt_carrera->close();
            }
            $stmt_servsoc->close();
        }
        
        $stmt_egresados->close();
        $mysqli->close();
        
        return $carreras;
        
    } catch (Exception $e) {
        error_log("Error consultando carreras de doctorado: " . $e->getMessage());
        return [];
    }
}
?>
