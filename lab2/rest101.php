<?php

require 'vendor/autoload.php';

/*
 * 1- VERB
 * 2- Resource //Products
 * 3- Resource ID
 * 4- Extra Params
 * 
 * 1- Validate
 */


$method = $_SERVER['REQUEST_METHOD'];

$urlParts = explode('/', $_SERVER['REQUEST_URI']);

$resource = $urlParts[2] ?? null;

$resourceID = $urlParts[3] ?? null;

header("Content-Type: application/json");

if ($resource !== 'products') {
    http_response_code(404);
    echo json_encode(["message" => "Resource $resource doesn't exist"]);
    exit;
}

$db = new MySQLHandler('products');

switch ($method) {
    case 'GET':
        if ($resourceID) {
            $data = $db->get_record_by_id($resourceID);
            if ($data && !empty($data)) {//array of rows
                echo json_encode($data[0]);
            } else {
                http_response_code(404);
                echo json_encode(['message' => "Product with id: $resourceID not found"]);
            }
            break;
        }
        $data = $db->get_data();
        echo json_encode($data);
        break;

    case 'POST':
        //validate required product data

        $invalidFields   = [];
        if (empty($_POST['name']))
            $invalidFields[] = 'name';

        if (empty($_POST['price']) || !is_numeric($_POST['price']))
            $invalidFields[] = 'price';

        if (empty($_POST['units_in_stock']) || !is_numeric($_POST['units_in_stock']))
            $invalidFields[] = 'units_in_stock';

        if (!empty($invalidFields)) {
            http_response_code(400);
            echo json_encode(['message' => 'Fields ' . implode(',', $invalidFields) . ' invalid.']);
            break;
        }

        $success = $db->save($_POST);
        if ($success) {
            http_response_code(201);
            echo json_encode(['message' => 'Product created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Something went wrong']);
        }

        break;

    case 'PUT':
        if ($resourceID) {
            $_PUT = json_decode(file_get_contents("php://input"), true);

            //Validation
            $invalidFields   = [];
            if (empty($_PUT['name']))
                $invalidFields[] = 'name';

            if (empty($_PUT['price']) || !is_numeric($_PUT['price']))
                $invalidFields[] = 'price';

            if (empty($_PUT['units_in_stock']) || !is_numeric($_PUT['units_in_stock']))
                $invalidFields[] = 'units_in_stock';

            if (!empty($invalidFields)) {
                http_response_code(400);
                echo json_encode(['message' => 'Fields ' . implode(',', $invalidFields) . ' invalid.']);
                break;
            }

            $success = $db->update($_PUT, $resourceID);
            if ($success) {
                echo json_encode(['message' => "Product with id:$resourceID updated successfully"]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Something went wrong']);
            }
            break;
        }
        http_response_code(400);
        echo json_encode(['message' => 'Product id is required.']);

        break;

    case 'DELETE':
        if ($resourceID) {
            $db->delete($resourceID);
            echo json_encode(['message' => 'Product deleted successfully.']);
            break;
        }
        http_response_code(400);
        echo json_encode(['message' => 'Product id is required.']);

        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => "Method ($method) not allowed."]);
        break;
}


