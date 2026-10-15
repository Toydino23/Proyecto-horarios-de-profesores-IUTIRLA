<?php
session_start();

include("connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_schedule"])) {
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

include("sidebar.php");

$usuario = $_SESSION['usuario'] ?? '';

function limpio($valor) {
    return htmlspecialchars(trim((string)$valor), ENT_QUOTES, 'UTF-8');
}

function puedeDictarDia(array $profesor, int $dayIndex): bool {
    $map = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
    $dia = $map[$dayIndex] ?? null;
    if ($dia === null) return false;
    return isset($profesor[$dia]) && intval($profesor[$dia]) === 1;
}

function separarPeriodo($periodo) {
    $partes = explode('-', (string)$periodo);
    return [
        'period' => $partes[0] ?? '',
        'individual' => $partes[1] ?? ''
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_schedule"])) {
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

$mensaje = '';
$tipoMensaje = 'success';

$editando = false;
$idhorarioEditar = 0;
$horarioEditar = [
    'nombre' => '',
    'periodo' => ''
];
$detalleHorario = [];

$name_val = isset($_POST['name']) ? limpio($_POST['name']) : '';
$period_val = isset($_POST['period']) ? intval($_POST['period']) : '';
$individual_val = isset($_POST['individual']) ? intval($_POST['individual']) : '';

$timeSlots = [
    '7:30 - 8:15      8:15 - 9:00',
    '9:05 - 9:50      9:50 - 10:35',
    '10:40 - 11:25    11:25 - 12:10'
];

$days = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

$profesores = [];
$sqlProfesores = "SELECT profesor.id, profesor.cedula, profesor.Nombre, profesor.Apellido, profesor.Clase, profesor.Carrera, semana.Lunes, semana.Martes, semana.Miercoles, semana.Jueves, semana.Viernes FROM profesor INNER JOIN semana ON profesor.idsemana = semana.idsemana ORDER BY profesor.Nombre";
$resultProfesores = mysqli_query($conn, $sqlProfesores);
if ($resultProfesores) {
    while ($row = mysqli_fetch_assoc($resultProfesores)) {
        $profesores[$row['id']] = $row;
    }
    mysqli_free_result($resultProfesores);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_horario"])) {
    $name = limpio($_POST['name'] ?? '');
    $period = isset($_POST['period']) ? intval($_POST['period']) : 0;
    $individual = isset($_POST['individual']) ? intval($_POST['individual']) : 0;
    $periodo_formateado = $period . '-' . $individual;
    
    $idhorarioEditar = isset($_POST['idhorario']) ? intval($_POST['idhorario']) : 0;
    
    $salonesOcupados = [];

    if ($name === '' || $period < 1900 || $period > 9999 || $individual < 1) {
        $mensaje = "Debes completar correctamente nombre, año y número del periodo.";
        $tipoMensaje = 'error';
    } else {
        mysqli_begin_transaction($conn);
        try {
            $profesoresOcupados = [];

            $sqlOcupados = "
                SELECT hd.dia, hd.intervalo, hd.salon
                FROM horario_detalle hd
                INNER JOIN horario h ON hd.idhorario = h.idhorario
                WHERE h.periodo = ? AND hd.salon <> 0 AND h.idhorario <> ?
            ";
            if ($stmtOcupados = mysqli_prepare($conn, $sqlOcupados)) {
                mysqli_stmt_bind_param($stmtOcupados, "si", $periodo_formateado, $idhorarioEditar);
                mysqli_stmt_execute($stmtOcupados);
                $resOcupados = mysqli_stmt_get_result($stmtOcupados);
                while ($row = mysqli_fetch_assoc($resOcupados)) {
                    $salonesOcupados[$row['dia']][$row['intervalo']][$row['salon']] = true;
                }
                mysqli_stmt_close($stmtOcupados);
            }

            $sqlOcupadosProf = "
                SELECT hd.dia, hd.intervalo, hd.idprofesor, p.cedula
                FROM horario_detalle hd
                INNER JOIN horario h ON hd.idhorario = h.idhorario
                INNER JOIN profesor p ON hd.idprofesor = p.id
                WHERE h.periodo = ? AND hd.idprofesor IS NOT NULL AND hd.idprofesor <> 0 AND h.idhorario <> ?
            ";
            if ($stmtOcupadosProf = mysqli_prepare($conn, $sqlOcupadosProf)) {
                mysqli_stmt_bind_param($stmtOcupadosProf, "si", $periodo_formateado, $idhorarioEditar);
                mysqli_stmt_execute($stmtOcupadosProf);
                $resOcupadosProf = mysqli_stmt_get_result($stmtOcupadosProf);
                while ($row = mysqli_fetch_assoc($resOcupadosProf)) {
                    $profesoresOcupados[$row['dia']][$row['intervalo']][$row['idprofesor']] = [
                        'cedula' => $row['cedula']
                    ];
                }
                mysqli_stmt_close($stmtOcupadosProf);
            }

            if ($idhorarioEditar > 0) {
                $stmtUpd = mysqli_prepare($conn, "UPDATE horario SET nombre = ?, periodo = ? WHERE idhorario = ?");
                mysqli_stmt_bind_param($stmtUpd, "ssi", $name, $periodo_formateado, $idhorarioEditar);
                mysqli_stmt_execute($stmtUpd);
                mysqli_stmt_close($stmtUpd);

                $stmtDel = mysqli_prepare($conn, "DELETE FROM horario_detalle WHERE idhorario = ?");
                mysqli_stmt_bind_param($stmtDel, "i", $idhorarioEditar);
                mysqli_stmt_execute($stmtDel);
                mysqli_stmt_close($stmtDel);

                $idhorario = $idhorarioEditar;
            } else {
                $stmtHorario = mysqli_prepare($conn, "INSERT INTO horario (nombre, periodo) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmtHorario, "ss", $name, $periodo_formateado);
                mysqli_stmt_execute($stmtHorario);
                $idhorario = mysqli_insert_id($conn);
                mysqli_stmt_close($stmtHorario);
            }

            $stmtDetalle = mysqli_prepare($conn, "INSERT INTO horario_detalle (idhorario, dia, intervalo, idprofesor, salon) VALUES (?, ?, ?, ?, ?)");

            for ($i = 0; $i < count($timeSlots); $i++) {
                for ($d = 0; $d < count($days); $d++) {
                    $campoProfesor = 'nombre_' . $i . '_' . $d;
                    $campoSalon = 'slot_' . $i . '_' . $d;

                    $idprofesor = (isset($_POST[$campoProfesor]) && $_POST[$campoProfesor] !== '') ? intval($_POST[$campoProfesor]) : null;
                    $salon = isset($_POST[$campoSalon]) ? intval($_POST[$campoSalon]) : 0;

                    $intervalo = $timeSlots[$i];
                    $dia = $days[$d];

                    if ($idprofesor !== null && isset($profesores[$idprofesor])) {
                        if (!puedeDictarDia($profesores[$idprofesor], $d)) {
                            throw new Exception("El profesor seleccionado no está disponible en " . $dia . ".");
                        }

                        if (isset($profesoresOcupados[$dia][$intervalo][$idprofesor])) {
                            $profData = $profesores[$idprofesor];
                            $nombreProf = $profData['Nombre'] . " " . $profData['Apellido'];
                            $cedulaProf = $profData['cedula'] ?? 'N/D';
                            throw new Exception("El profesor $nombreProf (cédula $cedulaProf) ya está asignado en $dia para el intervalo $intervalo.");
                        }
                    }

                    if ($salon !== 0 && isset($salonesOcupados[$dia][$intervalo][$salon])) {
                        throw new Exception("El salón " . $salon . " ya está ocupado en " . $dia . " para el periodo " . $periodo_formateado . ".");
                    }

                    mysqli_stmt_bind_param($stmtDetalle, "issii", $idhorario, $dia, $intervalo, $idprofesor, $salon);
                    mysqli_stmt_execute($stmtDetalle);

                    if ($salon !== 0) {
                        $salonesOcupados[$dia][$intervalo][$salon] = true;
                    }
                    if ($idprofesor !== null) {
                        $profesoresOcupados[$dia][$intervalo][$idprofesor] = [
                            'cedula' => $profesores[$idprofesor]['cedula'] ?? null
                        ];
                    }
                }
            }

            mysqli_stmt_close($stmtDetalle);
            mysqli_commit($conn);

            $mensaje = ($idhorarioEditar > 0) ? "Horario actualizado correctamente." : "Horario guardado correctamente.";
            $tipoMensaje = 'success';

            if ($idhorarioEditar <= 0) {
                $name_val = '';
                $period_val = '';
                $individual_val = '';
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $mensaje = $e->getMessage();
            $tipoMensaje = 'error';
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["editar_horario"])) {
    $idhorarioEditar = isset($_POST["idhorario"]) ? intval($_POST["idhorario"]) : 0;

    if ($idhorarioEditar > 0) {
        $stmtEdit = mysqli_prepare($conn, "SELECT nombre, periodo FROM horario WHERE idhorario = ?");
        mysqli_stmt_bind_param($stmtEdit, "i", $idhorarioEditar);
        mysqli_stmt_execute($stmtEdit);
        $resEdit = mysqli_stmt_get_result($stmtEdit);

        if ($rowEdit = mysqli_fetch_assoc($resEdit)) {
            $horarioEditar['nombre'] = $rowEdit['nombre'];
            $horarioEditar['periodo'] = $rowEdit['periodo'];
            $editando = true;

            $stmtDet = mysqli_prepare($conn, "SELECT dia, intervalo, idprofesor, salon FROM horario_detalle WHERE idhorario = ?");
            mysqli_stmt_bind_param($stmtDet, "i", $idhorarioEditar);
            mysqli_stmt_execute($stmtDet);
            $resDet = mysqli_stmt_get_result($stmtDet);

            while ($r = mysqli_fetch_assoc($resDet)) {
                $detalleHorario[$r['intervalo']][$r['dia']] = [
                    'idprofesor' => $r['idprofesor'],
                    'salon' => $r['salon']
                ];
            }

            mysqli_stmt_close($stmtDet);

            $parts = separarPeriodo($horarioEditar['periodo']);
            $period_val = $parts['period'];
            $individual_val = $parts['individual'];
            $name_val = $horarioEditar['nombre'];
        }

        mysqli_stmt_close($stmtEdit);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_horario"])) {
    $idhorario = isset($_POST["idhorario"]) ? intval($_POST["idhorario"]) : 0;

    if ($idhorario > 0) {
        mysqli_begin_transaction($conn);

        try {
            $stmtDelDet = mysqli_prepare($conn, "DELETE FROM horario_detalle WHERE idhorario = ?");
            if (!$stmtDelDet) {
                throw new Exception("Error preparando DELETE detalle: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmtDelDet, "i", $idhorario);
            if (!mysqli_stmt_execute($stmtDelDet)) {
                throw new Exception("Error ejecutando DELETE detalle: " . mysqli_stmt_error($stmtDelDet));
            }
            mysqli_stmt_close($stmtDelDet);

            $stmtDelHor = mysqli_prepare($conn, "DELETE FROM horario WHERE idhorario = ?");
            if (!$stmtDelHor) {
                throw new Exception("Error preparando DELETE horario: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmtDelHor, "i", $idhorario);
            if (!mysqli_stmt_execute($stmtDelHor)) {
                throw new Exception("Error ejecutando DELETE horario: " . mysqli_stmt_error($stmtDelHor));
            }
            mysqli_stmt_close($stmtDelHor);

            mysqli_commit($conn);
            $mensaje = "Horario eliminado correctamente.";
            $tipoMensaje = "success";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $mensaje = $e->getMessage();
            $tipoMensaje = "error";
        }
    } else {
        $mensaje = "ID de horario inválido.";
        $tipoMensaje = "error";
    }
}

// --- CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA DE HORARIOS ---
$registros_por_pagina = 5; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$busqueda = isset($_GET['buscar']) ? limpio($_GET['buscar']) : '';
$whereClause = "";
if ($busqueda !== '') {
    $busqueda_sql = mysqli_real_escape_string($conn, $busqueda);
    $whereClause = " WHERE nombre LIKE '%$busqueda_sql%' OR periodo LIKE '%$busqueda_sql%'";
}

// 1. Contar el total de horarios
$sql_total = "SELECT COUNT(*) as total FROM horario" . $whereClause;
$resultado_total = mysqli_query($conn, $sql_total);
$fila_total = mysqli_fetch_assoc($resultado_total);
$total_registros = $fila_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 2. Consulta optimizada y paginada
$horariosResumen = [];
$sqlHorarios = "SELECT idhorario, nombre, periodo FROM horario" . $whereClause . " ORDER BY idhorario DESC LIMIT $offset, $registros_por_pagina";
$resultHorarios = mysqli_query($conn, $sqlHorarios);

if ($resultHorarios) {
    while ($row = mysqli_fetch_assoc($resultHorarios)) {
        $horariosResumen[$row['idhorario']] = $row;
    }
    mysqli_free_result($resultHorarios);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios</title>
    <script>
        const disponibilidadProfesores = <?php
            $disp = [];
            foreach ($profesores as $id => $p) {
                $disp[$id] = [
                    'Lunes' => intval($p['Lunes']) === 1,
                    'Martes' => intval($p['Martes']) === 1,
                    'Miercoles' => intval($p['Miercoles']) === 1,
                    'Jueves' => intval($p['Jueves']) === 1,
                    'Viernes' => intval($p['Viernes']) === 1
                ];
            }
            echo json_encode($disp, JSON_UNESCAPED_UNICODE);
        ?>;
        const dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

        function validarProfesorDia(select, diaIndex) {
            const profesorId = select.value;
            if (!profesorId) return;
            const dia = dias[diaIndex];
            const permitido = disponibilidadProfesores[profesorId] && disponibilidadProfesores[profesorId][dia];
            if (!permitido) {
                alert("Ese profesor no está disponible en " + dia + ".");
                select.value = "";
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Roboto", sans-serif; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; color: #720000; margin-bottom: 20px; font-size: 28px; }

        .form-section, .schedule-section, .saved-section {
            background: #FFFFFF;
            padding: 45px;
            margin: 0 auto 20px auto;
            border-radius: 8px;
            box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2), 0 5px 5px 0 rgba(0, 0, 0, 0.24);
            position: relative;
            text-align: center;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input {
            width: 100%; padding: 10px; font-size: 14px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        .form-group input:focus { outline: none; border-color: #217346; box-shadow: 0 0 3px rgba(33,115,70,0.3); }

        .boton {
            background: #217346; color: white; padding: 12px 30px;
            font-size: 16px; border: none; border-radius: 4px;
            cursor: pointer; font-weight: 600;
        }
        .boton:hover { background: #1e6b40; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
        th { background: #217346; color: white; padding: 10px; text-align: center; font-weight: 600; border: 1px solid #1e6b40; }
        td { padding: 8px; text-align: center; border: 1px solid #ccc; min-width: 120px; overflow: hidden; vertical-align: top; }
        td select { width: 100%; max-width: 100%; padding: 4px; font-size: 12px; box-sizing: border-box; display: block; margin-top: 4px; }
        tr:nth-child(even) { background: #f8f8f8; }
        tr:nth-child(odd) { background: white; }
        .time-col { background: #e8f5e9; font-weight: 600; color: #217346; min-width: 130px; text-align: center; padding-left: 10px; }
        .header-time { background: #1e6b40; }
        .mensaje-ok { background:#e8f5e9; color:#217346; padding:12px 15px; border-left:4px solid #217346; margin-bottom:15px; border-radius:4px; }
        .mensaje-error { background:#fdecea; color:#c0392b; padding:12px 15px; border-left:4px solid #c0392b; margin-bottom:15px; border-radius:4px; }
        .schedule-info { background: #e8f5e9; padding: 15px; margin-bottom: 15px; border-radius: 4px; border-left: 4px solid #217346; }
        .schedule-info h2 { color: #217346; font-size: 20px; margin-bottom: 5px; }
        .schedule-info p { color: #555; font-size: 14px; }
        .saved-table td { font-size: 12px; }

        /* CSS para Paginación */
        .paginacion {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .paginacion a {
            padding: 8px 12px;
            background-color: #217346;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .paginacion a.activo {
            background-color: #114227;
            font-weight: bold;
            pointer-events: none;
        }
        .paginacion a:hover {
            background-color: #145230;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Horarios</h1>

    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) { ?>
    <form method="POST" action="">
        <input type="hidden" name="idhorario" value="<?php echo $editando ? intval($idhorarioEditar) : ''; ?>">

        <div class="form-section">
            <div class="form-group">
                <label for="name">Nombre del horario:</label>
                <input type="text" id="name" name="name" placeholder="Ingrese el nombre del horario" value="<?php echo htmlspecialchars($name_val, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label for="period">Año académico</label>
                <input type="number" id="period" name="period" placeholder="Ingrese el año academico" min="1900" max="9999" step="1" value="<?php echo htmlspecialchars((string)$period_val, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label for="individual">Periodo Academico</label>
                <input type="number" id="individual" name="individual" placeholder="Ingrese el periodo academico (numero)" min="1" step="1" value="<?php echo htmlspecialchars((string)$individual_val, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <button type="submit" name="generate" class="boton">Generar horario</button>
            <button type="submit" name="guardar_horario" style="margin-left:10px; background:#2980b9;" class="boton">
                <?php echo $editando ? 'Actualizar horario' : 'Guardar horario'; ?>
            </button>
            <button type="submit" name="reset_schedule" formnovalidate style="margin-left:10px; background:#c0392b;" class="boton">Reiniciar horario</button>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="<?php echo $tipoMensaje === 'success' ? 'mensaje-ok' : 'mensaje-error'; ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['generate']) || isset($_POST['guardar_horario']) || $editando)) {
            $name = limpio($_POST['name'] ?? $name_val);
            $period = isset($_POST['period']) ? intval($_POST['period']) : intval($period_val);
            $individual = isset($_POST['individual']) ? intval($_POST['individual']) : intval($individual_val);
            $periodo_formateado = $period . '-' . $individual;

            echo '<div class="schedule-section">';
            echo '<div class="schedule-info">';
            echo "<h2>Horario para " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ", en el periodo " . htmlspecialchars($periodo_formateado, ENT_QUOTES, 'UTF-8') . "</h2>";
            echo '<p> Lunes - Viernes | 7:30 AM - 12:10 PM | En el caso de aula = 0, este contara como nulo</p>';
            echo '</div>';

            echo '<table>';
            echo '<thead><tr>';
            echo '<th class="header-time">Intervalo de tiempo</th>';
            foreach ($days as $day) echo '<th>' . htmlspecialchars($day, ENT_QUOTES, 'UTF-8') . '</th>';
            echo '</tr></thead><tbody>';

            for ($i = 0; $i < count($timeSlots); $i++) {
                $horaVisual = preg_replace('/\s{2,}/', '<br><br>', htmlspecialchars($timeSlots[$i], ENT_QUOTES, 'UTF-8'));
                echo '<tr><td class="time-col">' . $horaVisual . '</td>';                for ($d = 0; $d < count($days); $d++) {
                    $campoProfesor = 'nombre_' . $i . '_' . $d;
                    $campoSalon = 'slot_' . $i . '_' . $d;

                    if ($editando && isset($detalleHorario[$timeSlots[$i]][$days[$d]])) {
                        $selectedProf = $detalleHorario[$timeSlots[$i]][$days[$d]]['idprofesor'] ?? '';
                        $selectedSalon = intval($detalleHorario[$timeSlots[$i]][$days[$d]]['salon'] ?? 0);
                    } else {
                        $selectedProf = isset($_POST[$campoProfesor]) ? $_POST[$campoProfesor] : '';
                        $selectedSalon = isset($_POST[$campoSalon]) ? intval($_POST[$campoSalon]) : 0;
                    }

                    echo '<td>
                        <div>Profesor:</div>
                        <select class="profesor-select" name="nombre_' . $i . '_' . $d . '" onchange="validarProfesorDia(this, ' . $d . ')">
                            <option value="">--</option>';
                    foreach ($profesores as $id => $profesor) {
                        $label = $profesor['cedula'] . ' - ' . $profesor['Nombre'] . ' ' . $profesor['Apellido'] . ' - ' . $profesor['Carrera'] . ' ' . $profesor['Clase'];                        
                        $disabled = puedeDictarDia($profesor, $d) ? '' : ' disabled';
                        $sel = ((string)$id === (string)$selectedProf) ? ' selected' : '';
                        echo '<option value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' . $disabled . $sel . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                        echo '</select>
                        <div style="margin-top:6px;">Aula:</div>
                        <select class="salon-select" name="slot_' . $i . '_' . $d . '">'; 
                    $diaActual = $days[$d];
                    $intervaloActual = $timeSlots[$i];
                    for ($s = 0; $s <= 30; $s++) {
                        $sel = ($s === $selectedSalon) ? ' selected' : '';
                        $isOcupado = ($s !== 0 && isset($salonesOcupados[$diaActual][$intervaloActual][$s]));
                        $disabledAttr = $isOcupado ? ' disabled ' : '';
                        $textoOpcion = $s . ($isOcupado ? ' (Ocupado)' : '');
                        echo '<option value="' . $s . '"' . $sel . $disabledAttr . '>' . $textoOpcion . '</option>';
                    }
                    echo '</select></td>';
                }
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        }
        ?>
    </form>
    <?php } ?>

    <br><br><br>

    <div class="mostrar horario">
        <div class="form-section">
            <div class="mostrar horario">
                <h3>Horarios guardados</h3>

                <form method="GET" action="" style="margin-bottom: 25px; display: flex; gap: 10px; justify-content: center; align-items: center;">
                    <input type="text" name="buscar" placeholder="Buscar por nombre o periodo..." value="<?php echo htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8'); ?>" style="padding: 10px; width: 60%; border: 1px solid #ccc; border-radius: 4px;">
                    <button type="submit" class="boton" style="padding: 10px 20px; border-radius: 4px;">Buscar</button>
                    <?php if ($busqueda !== ''): ?>
                        <a href="?" class="boton" style="background:#c0392b; text-decoration:none; padding: 10px 20px; display: flex; align-items: center; border-radius: 4px;">Limpiar</a>
                    <?php endif; ?>
                </form>

                <?php if (empty($horariosResumen)): ?>
                    <p>No se encontraron horarios.</p>
                <?php else: ?>
                    <ul style="list-style:none;padding:0;margin:0;">
                        <?php foreach ($horariosResumen as $h): ?>
                            <li style="border:1px solid #ddd;padding:10px;margin-bottom:10px;border-radius:4px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($h['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        <small>Periodo: <?php echo htmlspecialchars($h['periodo'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>

                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <form method="post" action="pdf.php" style="display:inline-block;margin:0;">
                                            <input type="hidden" name="idhorario" value="<?php echo intval($h['idhorario']); ?>">
                                            <button type="submit" name="export_pdf" style="background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;" class="boton">
                                                Guardar como PDF
                                            </button>
                                        </form>

                                        <?php if ($usuario === 'admin') { ?>
                                        <form method="post" action="" style="display:inline-block;margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar este horario?');">
                                            <input type="hidden" name="idhorario" value="<?php echo intval($h['idhorario']); ?>">
                                            <button type="submit" name="eliminar_horario" style="background:#c0392b;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;" class="boton">
                                                Eliminar horario
                                            </button>
                                        </form>

                                        <form method="post" action="" style="display:inline-block;margin:0;">
                                            <input type="hidden" name="idhorario" value="<?php echo intval($h['idhorario']); ?>">
                                            <button type="submit" name="editar_horario" style="background:#f39c12;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;" class="boton">
                                                Editar horario
                                            </button>
                                        </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($total_paginas > 1): ?>
                      <?php $queryBusqueda = ($busqueda !== '') ? '&buscar=' . urlencode($busqueda) : ''; ?>
                      <div class="paginacion">
                          <?php if ($pagina_actual > 1): ?>
                              <a href="?pagina=<?php echo $pagina_actual - 1 . $queryBusqueda; ?>">&laquo; Anterior</a>
                          <?php endif; ?>

                          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                              <a href="?pagina=<?php echo $i . $queryBusqueda; ?>" class="<?php echo ($i == $pagina_actual) ? 'activo' : ''; ?>">
                                  <?php echo $i; ?>
                              </a>
                          <?php endfor; ?>

                          <?php if ($pagina_actual < $total_paginas): ?>
                              <a href="?pagina=<?php echo $pagina_actual + 1 . $queryBusqueda; ?>">Siguiente &raquo;</a>
                          <?php endif; ?>
                      </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>