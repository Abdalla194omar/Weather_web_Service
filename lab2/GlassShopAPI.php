<?php
header('Content-Type: application/json'); 

require_once 'config.php';
require_once 'Models/MySQLHandler.php';

$method = $_SERVER['REQUEST_METHOD'];

$uri = trim($_SERVER['REQUEST_URI'], '/');
$base_path = 'lab2/GlassShopAPI.php';
$uri = str_replace($base_path, '', $uri);
$request = explode('/', trim($uri, '/'));

$resource = isset($request[0]) ? $request[0] : '';
$id = isset($request[1]) ? (int)$request[1] : null;

if ($resource !== 'items' && $resource !== '') {
    http_response_code(404);
    echo json_encode(['error' => "Resource doesn't exist"]);
    exit;
}

$allowed_methods = ['GET', 'POST', 'PUT', 'DELETE'];
if (!in_array($method, $allowed_methods)) {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed!']);
    exit;
}

$db = new MySQLHandler('items', 'id');
if (!$db->connect()) {
    http_response_code(500);
    echo json_encode(['error' => 'internal server error!']);
    exit;
}

switch ($method) {
    case 'GET':
        handleGet($db, $id);
        break;
    case 'POST':
        handlePost($db);
        break;
    case 'PUT':
        handlePut($db, $id);
        break;
    case 'DELETE':
        handleDelete($db, $id);
        break;
}

$db->disconnect();

function handleGet($db, $id) {
    if ($id) {
        $result = $db->get_record_by_id($id);
        if ($result && count($result) > 0) {
            http_response_code(200);
            echo json_encode($result[0]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Resource doesn't exist"]);
        }
    } else {
        $result = $db->get_data();
        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "No resources found"]);
        }
    }
}

function handlePost($db) {
    $data = json_decode(file_get_contents('php://input'), true);

    $valid_keys = ['name', 'price', 'units_in_stock'];
    if (!$data || array_diff(array_keys($data), $valid_keys) || count(array_intersect($valid_keys, array_keys($data))) !== count($valid_keys)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad request']);
        return;
    }

    if (!is_numeric($data['price']) || !is_numeric($data['units_in_stock'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad request']);
        return;
    }

    if ($db->save($data)) {
        http_response_code(201);
        echo json_encode(['status' => 'Resource was added successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'internal server error!']);
    }
}

function handlePut($db, $id) {
    if (!$id) {
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found!']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $valid_keys = ['name', 'price', 'units_in_stock'];
    if (!$data || array_diff(array_keys($data), $valid_keys)) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad request']);
        return;
    }

    if (isset($data['price']) && !is_numeric($data['price']) || 
        isset($data['units_in_stock']) && !is_numeric($data['units_in_stock'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad request']);
        return;
    }

    $exists = $db->get_record_by_id($id);
    if (!$exists || count($exists) == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found!']);
        return;
    }

    if ($db->update($data, $id)) {
        http_response_code(200);
        echo json_encode($data);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'internal server error!']);
    }
}

function handleDelete($db, $id) {
    if (!$id) {
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found!']);
        return;
    }

    $exists = $db->get_record_by_id($id);
    if (!$exists || count($exists) == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found!']);
        return;
    }

    if ($db->delete($id)) {
        http_response_code(200);
        echo json_encode(['status' => 'Resource was deleted successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'internal server error!']);
    }
}