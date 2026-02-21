<?php
require_once 'db_connection.php';

function verificarAdeudosAlumno($id_alumno) {
    try {
        $mysqli = getDatabaseConnection();
        $adeudos = [];
        $importe_total = 0;
        $hoy = date('Y-m-d');
        
        // Buscar adeudos en xcargos5 (licenciatura)
        $sql_cargos5 = "SELECT idconcepto, tsaldos, fvence FROM xcargos5 WHERE idal = ? AND tsaldos > 0 AND estatus = 1 AND fvence < ?";
        $stmt_cargos5 = $mysqli->prepare($sql_cargos5);
        $stmt_cargos5->bind_param("is", $id_alumno, $hoy);
        $stmt_cargos5->execute();
        $resultado_cargos5 = $stmt_cargos5->get_result();
        
        while ($saldo = $resultado_cargos5->fetch_assoc()) {
            $id_concepto = $saldo['idconcepto'];
            
            // Obtener detalles del concepto desde xconceptos
            $sql_concepto = "SELECT id, nom, importe FROM xconceptos WHERE id = ?";
            $stmt_concepto = $mysqli->prepare($sql_concepto);
            $stmt_concepto->bind_param("i", $id_concepto);
            $stmt_concepto->execute();
            $resultado_concepto = $stmt_concepto->get_result();
            
            if ($resultado_concepto->num_rows > 0) {
                $concepto = $resultado_concepto->fetch_assoc();
                $adeudo = [
                    'id_concepto' => $id_concepto,
                    'nombre' => $concepto['nom'],
                    'importe' => $saldo['tsaldos'],
                    'importe_original' => $concepto['importe'],
                    'fecha_vencimiento' => $saldo['fvence'],
                    'nivel' => 'Licenciatura'
                ];
                $adeudos[] = $adeudo;
                $importe_total += $saldo['tsaldos'];
            }
            $stmt_concepto->close();
        }
        $stmt_cargos5->close();
        
        // Buscar adeudos en xcargos6 (doctorado)
        $sql_cargos6 = "SELECT idconcepto, tsaldos, fvence FROM xcargos6 WHERE idal = ? AND tsaldos > 0 AND estatus = 1 AND fvence < ?";
        $stmt_cargos6 = $mysqli->prepare($sql_cargos6);
        $stmt_cargos6->bind_param("is", $id_alumno, $hoy);
        $stmt_cargos6->execute();
        $resultado_cargos6 = $stmt_cargos6->get_result();
        
        while ($saldo = $resultado_cargos6->fetch_assoc()) {
            $id_concepto = $saldo['idconcepto'];
            
            // Obtener detalles del concepto desde xconceptos
            $sql_concepto = "SELECT id, nom, importe FROM xconceptos WHERE id = ?";
            $stmt_concepto = $mysqli->prepare($sql_concepto);
            $stmt_concepto->bind_param("i", $id_concepto);
            $stmt_concepto->execute();
            $resultado_concepto = $stmt_concepto->get_result();
            
            if ($resultado_concepto->num_rows > 0) {
                $concepto = $resultado_concepto->fetch_assoc();
                $adeudo = [
                    'id_concepto' => $id_concepto,
                    'nombre' => $concepto['nom'],
                    'importe' => $saldo['tsaldos'],
                    'importe_original' => $concepto['importe'],
                    'fecha_vencimiento' => $saldo['fvence'],
                    'nivel' => 'Maestría'
                ];
                $adeudos[] = $adeudo;
                $importe_total += $saldo['tsaldos'];
            }
            $stmt_concepto->close();
        }
        $stmt_cargos6->close();
        
        return [
            'tiene_adeudos' => !empty($adeudos),
            'adeudos' => $adeudos,
            'importe_total' => $importe_total,
            'cantidad_adeudos' => count($adeudos)
        ];
        
    } catch (Exception $e) {
        return [
            'tiene_adeudos' => false,
            'adeudos' => [],
            'importe_total' => 0,
            'cantidad_adeudos' => 0
        ];
    }
}
?>
