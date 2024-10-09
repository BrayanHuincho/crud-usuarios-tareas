<?php
require '../base_de_datos/db.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener el método de la petición
$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'POST':
        // Crear un nuevo usuario
        if (isset($data['nombre'], $data['email'])) {
            $nombre = $data['nombre'];
            $email = $data['email'];

            $stmt = $pdo->prepare("CALL crear_usuario(:nombre, :email)");
            try {
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':email' => $email
                ]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(["message" => "Usuario creado", "id" => $result['id']]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al crear usuario", "error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos: nombre o email"]);
        }
        break;

    case 'GET':
        // Obtener todos los usuarios o un usuario específico
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $pdo->prepare("CALL obtener_usuario_por_id(:id)");
            try {
                $stmt->execute([':id' => $id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($usuario) {
                    echo json_encode($usuario);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Usuario no encontrado"]);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al obtener usuario", "error" => $e->getMessage()]);
            }
        } else {
            // Obtener todos los usuarios
            $stmt = $pdo->prepare("CALL obtener_usuarios()");
            try {
                $stmt->execute();
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($usuarios);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al obtener usuarios", "error" => $e->getMessage()]);
            }
        }
        break;

    case 'PUT':
        // Actualizar un usuario
        if (isset($data['id'], $data['nombre'], $data['email'])) {
            $id = $data['id'];
            $nombre = $data['nombre'];
            $email = $data['email'];

            $stmt = $pdo->prepare("CALL actualizar_usuario(:id, :nombre, :email)");
            try {
                $stmt->execute([
                    ':id' => $id,
                    ':nombre' => $nombre,
                    ':email' => $email
                ]);
                echo json_encode(["message" => "Usuario actualizado"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al actualizar usuario", "error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Faltan datos: id, nombre o email"]);
        }
        break;

    case 'DELETE':
        // Eliminar un usuario
        if (isset($data['id'])) {
            $id = $data['id'];

            $stmt = $pdo->prepare("CALL eliminar_usuario(:id)");
            try {
                $stmt->execute([':id' => $id]);
                echo json_encode(["message" => "Usuario eliminado"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error al eliminar usuario", "error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Falta el id del usuario a eliminar"]);
        }
        break;

    default:
        http_response_code(405); 
        echo json_encode(["message" => "Método no soportado"]);
}
?>
