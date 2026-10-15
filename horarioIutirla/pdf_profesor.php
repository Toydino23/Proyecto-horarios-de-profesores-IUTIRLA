<?php
require_once '..\horarioIutirla\includes\tcpdf.php';
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'])) {
    $cedula = trim($_POST['cedula']);

    // Buscar todos los detalles de horario donde el profesor tenga esta cédula
    $sql = "SELECT hd.dia, hd.intervalo, hd.salon, h.nombre AS nombre_horario, h.periodo,
                   p.Nombre, p.Apellido, p.Cedula
            FROM horario_detalle hd
            INNER JOIN horario h ON hd.idhorario = h.idhorario
            INNER JOIN profesor p ON hd.idprofesor = p.id
            WHERE p.Cedula = ?
            ORDER BY hd.iddetalle ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $cedula);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $filas = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    if (empty($filas)) {
            echo "<script>
            alert('El profesor no se encuentra en ninguno de los horarios disponibles');
            window.location.href = 'horarioprof.php';
        </script>";
        exit();
    }

    // Datos del profesor para el encabezado
    $nombreProfesor = $filas[0]['Nombre'] . ' ' . $filas[0]['Apellido'];

    $diasOrden = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

    $intervalos = [
        '7:30 - 8:15' => '7:30 - 8:15<br>8:15 - 9:00',
        '9:05 - 9:50' => '9:05 - 9:50<br>9:50 - 10:35',
        '10:40 - 11:25' => '10:40 - 11:25<br>11:25 - 12:10'
    ];

    $tabla = [];
    foreach ($intervalos as $clave => $texto) {
        $tabla[$clave] = [
            'texto' => $texto,
            'dias' => []
        ];
    }

    // Organizar las clases encontradas en la matriz de la tabla
    foreach ($filas as $f) {
        $intervaloDB = trim((string)$f['intervalo']);

        $clave = null;
        if (strpos($intervaloDB, '7:30 - 8:15') !== false) {
            $clave = '7:30 - 8:15';
        } elseif (strpos($intervaloDB, '9:05 - 9:50') !== false) {
            $clave = '9:05 - 9:50';
        } elseif (strpos($intervaloDB, '10:40 - 11:25') !== false) {
            $clave = '10:40 - 11:25';
        }

        if ($clave !== null) {
            // Si el profesor da múltiples clases a la misma hora en distintos horarios, los concatenamos
            if (!isset($tabla[$clave]['dias'][$f['dia']])) {
                $tabla[$clave]['dias'][$f['dia']] = [];
            }
            $tabla[$clave]['dias'][$f['dia']][] = $f;
        }
    }

    $html = '
    <style>
        table { width: 100%; border-collapse: collapse; }
        th {
            background-color: #217346;
            color: #ffffff;
            font-size: 10pt;
            text-align: center;
            padding: 6px;
            border: 1px solid #000000;
        }
        td {
            font-size: 9pt;
            text-align: center;
            vertical-align: middle;
            padding: 6px;
            border: 1px solid #000000;
        }
        .time {
            background-color: #e8f5e9;
            font-weight: bold;
            line-height: 1.35;
        }
        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 12px;
        }
        .info-clase {
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ccc;
        }
    </style>';

    $html .= '<div class="title">Horario Profesor: ' . htmlspecialchars($nombreProfesor, ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '<br>';
    $html .= '<div class="subtitle">C.I: ' . htmlspecialchars($cedula, ENT_QUOTES, 'UTF-8') . '</div>';
    $html .= '<br>';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<th width="18%">Intervalo</th>';
    foreach ($diasOrden as $dia) {
        $html .= '<th width="16.4%">' . $dia . '</th>';
    }
    $html .= '</tr>';

    foreach ($intervalos as $clave => $textoIntervalo) {
        $html .= '<tr>';
        $html .= '<td class="time">' . $textoIntervalo . '</td>';

        foreach ($diasOrden as $dia) {
            if (isset($tabla[$clave]['dias'][$dia])) {
                $clases = $tabla[$clave]['dias'][$dia];
                $contenido = '';
                
                foreach ($clases as $clase) {
                    $nombreHorario = $clase['nombre_horario'];
                    $periodo = $clase['periodo'];
                    $salon = $clase['salon'];
                    
                    $textoSalon = ($salon == 0) ? 'N/A' : htmlspecialchars((string)$salon, ENT_QUOTES, 'UTF-8');

                    $contenido .= '<div class="info-clase">';
                    $contenido .= '<b>' . htmlspecialchars($nombreHorario, ENT_QUOTES, 'UTF-8') . '</b><br>';
                    $contenido .= 'Período: ' . htmlspecialchars($periodo, ENT_QUOTES, 'UTF-8') . '<br>';
                    $contenido .= 'Aula: ' . $textoSalon;
                    $contenido .= '</div>';
                }
                
                $html .= '<td>' . $contenido . '</td>';
            } else {
                $html .= '<td></td>';
            }
        }

        $html .= '</tr>';
    }

    $html .= '</table>';

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Horario IUTIRLA');
    $pdf->SetTitle('Horario - ' . $nombreProfesor);
    $pdf->SetSubject('Horario académico del Profesor');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage('L');

    $imgFile = 'C:\\xampp\\htdocs\\horarioIutirla\\includes\\images\\logoiutirla.png';
    if(file_exists($imgFile)) {
        $pdf->Image($imgFile, 10, 8, 22, 0, 'PNG');
    }

    $pdf->Ln(18);

    $pdf->writeHTML($html, true, false, true, false, '');

    $filename = 'Horario_Profesor_' . $cedula . '.pdf';
    $pdf->Output($filename, 'D'); 
    exit;
} else {
    http_response_code(400);
        echo "<script>
            alert('Error de cedula profesor');
            window.location.href = 'horarioprof.php';
        </script>";
        exit();;
}
?>