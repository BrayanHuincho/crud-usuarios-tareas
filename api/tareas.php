<?php
require '../base_de_datos/db.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el método de la petición
$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);

function convertirCompletada($valor) {
    $valorLower = strtolower(trim($valor));
    return ($valorLower === 'sí' || $valorLower === 'si' || $valorLower === '1') ? 1 : 0;
}

function convertirCompletadaSalida($valor) {
    return $valor == 1 ? 'Sí' : 'No';
}

switch ($method) {
    case 'POST':
        // Crear una nueva tarea
        if (isset($data['usuario_id'], $data['descripcion'], $data['completada'])) {
            $usuario_id = $data['usuario_id'];
            $descripcion = $data['descripcion'];
            $completada = convertirCompletada($data['completada']); 

            $stmt = $pdo->prepare("CALL crear_tarea(:usuario_id, :descripcion, :completada)");
            try {
                $stmt->execute([
                    ':usuario_id' => $usuario_id,
                    ':descripcion' => $descripcion,
                    ':completada' => $completada
                ]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(["message" => "Tarea creada", "id" => $result['id']]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al crear tarea", "error" => $e->getMessage()]);
            }
            $stmt->closeCursor(); 
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos: usuario_id, descripcion o completada"]);
        }
        break;

    case 'GET':
        // Obtener todas las tareas o una tarea específica
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $pdo->prepare("CALL obtener_tarea_por_id(:id)");
            try {
                $stmt->execute([':id' => $id]);
                $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($tarea) {
                    $tarea['completada'] = convertirCompletadaSalida($tarea['completada']);
                    echo json_encode($tarea);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Tarea no encontrada"]);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al obtener tarea", "error" => $e->getMessage()]);
            }
            $stmt->closeCursor();
        } else {
            // Obtener todas las tareas
            $stmt = $pdo->prepare("CALL obtener_tareas()");
            try {
                $stmt->execute();
                $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($tareas as &$tarea) {
                    $tarea['completada'] = convertirCompletadaSalida($tarea['completada']);
                }
                echo json_encode($tareas);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al obtener tareas", "error" => $e->getMessage()]);
            }
            $stmt->closeCursor();
        }
        break;

    case 'PUT':
        // Actualizar una tarea
        if (isset($data['id'], $data['descripcion'], $data['completada'])) {
            $id = $data['id'];
            $descripcion = $data['descripcion'];
            $completada = convertirCompletada($data['completada']); 

            $stmt = $pdo->prepare("CALL actualizar_tarea(:id, :descripcion, :completada)");
            try {
                $stmt->execute([
                    ':id' => $id,
                    ':descripcion' => $descripcion,
                    ':completada' => $completada
                ]);
                echo json_encode(["message" => "Tarea actualizada"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al actualizar tarea", "error" => $e->getMessage()]);
            }
            $stmt->closeCursor();
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos: id, descripcion o completada"]);
        }
        break;

    case 'DELETE':
        // Eliminar una tarea
        if (isset($data['id'])) {
            $id = $data['id'];

            $stmt = $pdo->prepare("CALL eliminar_tarea(:id)");
            try {
                $stmt->execute([':id' => $id]);
                echo json_encode(["message" => "Tarea eliminada"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar tarea", "error" => $e->getMessage()]);
            }
            $stmt->closeCursor();
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Falta el id de la tarea a eliminar"]);
        }
        break;

    default:
        http_response_code(405); 
        echo json_encode(["message" => "Método no soportado"]);
}
?>
