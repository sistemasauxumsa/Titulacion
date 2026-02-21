<?php
require 'db_connection.php';

function buscarAlumnoPorCURP($curp) {
    try {
        $mysqli = getDatabaseConnection();
        
        // Buscar alumno por CURP
        $sql_alumno = "SELECT id, nombres, apaterno, amaterno, curp, niveles FROM alumnos WHERE curp = ?";
        $stmt_alumno = $mysqli->prepare($sql_alumno);
        $stmt_alumno->bind_param("s", $curp);
        $stmt_alumno->execute();
        $resultado_alumno = $stmt_alumno->get_result();
        
        if ($resultado_alumno->num_rows > 0) {
            $alumno = $resultado_alumno->fetch_assoc();
            
            // Verificar si estudió carrera (posición 5 para licenciatura o posición 6 para doctorado)
            $niveles = $alumno['niveles'];
            
            $estudio_licenciatura = (strlen($niveles) >= 5 && $niveles[4] == '1');
            $estudio_doctorado = (strlen($niveles) >= 7 && $niveles[6] == '1');
            $estudio_carrera = ($estudio_licenciatura || $estudio_doctorado);
            
            if (!$estudio_carrera) {
                return [
                    'success' => false,
                    'message' => 'El alumno no tiene registro de haber estudiado una carrera (nivel 5 o 6).'
                ];
            }
            
            return [
                'success' => true,
                'alumno' => $alumno,
                'estudio_licenciatura' => $estudio_licenciatura,
                'estudio_doctorado' => $estudio_doctorado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'CURP no encontrada en el sistema.'
            ];
        }
        
        $stmt_alumno->close();
        $mysqli->close();
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error en la base de datos: ' . $e->getMessage()
        ];
    }
}
?>
