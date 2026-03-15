<?php
require_once 'config.php';
header("Content-Type: application/json");

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

// Grabbing the ID's
$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$id   = $path[1] ?? null;

switch ($method) {
    
    case 'GET':
        if ($id) {
            // Fetch one student
            $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $student = $stmt->fetch();
            $student ? print_json($student) : print_json(["msg" => "Not found"], 404);
        } else {
            // Fetch all students
            $stmt = $db->query("SELECT * FROM students");
            print_json($stmt->fetchAll());
        }
        break;

    case 'POST':
        if (empty($input['name'])) print_json(["error" => "Name is required"], 400);

        $sql = "INSERT INTO students (name, course, status) VALUES (?, ?, ?)";
        $db->prepare($sql)->execute([
            $input['name'],
            $input['course'] ?? 'Unassigned',
            $input['status'] ?? 'Enrolled'
        ]);
        
        print_json(["id" => $db->lastInsertId(), "status" => "Student added!"], 201);
        break;
    
    case 'PUT':
        if (!$id) print_json(["error" => "Missing ID"], 400);

        // Check if student exists first
        $check = $db->prepare("SELECT id FROM students WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) print_json(["error" => "Student not found"], 404);

        $sql = "UPDATE students SET name = IFNULL(?, name), course = IFNULL(?, course), status = IFNULL(?, status) WHERE id = ?";
        $db->prepare($sql)->execute([$input['name'] ?? null, $input['course'] ?? null, $input['status'] ?? null, $id]);
        
        print_json(["message" => "Student updated successfully"]);
        break;

    case 'DELETE':
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt->rowCount() 
            ? print_json(["message" => "Student deleted"]) 
            : print_json(["error" => "Student not found"], 404);
        break;

     default:
        print_json(["error" => "Method not supported"], 405);
        break;
}

function print_json($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}