<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'apex_ems';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Router
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/Projects/Project/backend', '', $path);

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($path, $pdo);
        break;
    case 'POST':
        handlePost($path, $pdo);
        break;
    case 'PUT':
        handlePut($path, $pdo);
        break;
    case 'DELETE':
        handleDelete($path, $pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($path, $pdo) {
    switch($path) {
        case '/employees':
            getEmployees($pdo);
            break;
        case '/users':
            getUsers($pdo);
            break;
        case '/roles':
            getRoles($pdo);
            break;
        case '/dashboard':
            getDashboardData($pdo);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

function handlePost($path, $pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch($path) {
        case '/employees':
            createEmployee($pdo, $input);
            break;
        case '/users':
            createUser($pdo, $input);
            break;
        case '/roles':
            createRole($pdo, $input);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

function handlePut($path, $pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (preg_match('/\/employees\/(\d+)/', $path, $matches)) {
        updateEmployee($pdo, $matches[1], $input);
    } elseif (preg_match('/\/users\/(\d+)/', $path, $matches)) {
        updateUser($pdo, $matches[1], $input);
    } elseif (preg_match('/\/roles\/(\d+)/', $path, $matches)) {
        updateRole($pdo, $matches[1], $input);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDelete($path, $pdo) {
    if (preg_match('/\/employees\/(\d+)/', $path, $matches)) {
        deleteEmployee($pdo, $matches[1]);
    } elseif (preg_match('/\/users\/(\d+)/', $path, $matches)) {
        deleteUser($pdo, $matches[1]);
    } elseif (preg_match('/\/roles\/(\d+)/', $path, $matches)) {
        deleteRole($pdo, $matches[1]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
}

// Employee functions
function getEmployees($pdo) {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createEmployee($pdo, $data) {
    $sql = "INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, position, status, join_date, salary, manager) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['employeeId'],
        $data['firstName'],
        $data['lastName'],
        $data['email'],
        $data['phone'],
        $data['department'],
        $data['position'],
        $data['status'],
        $data['joinDate'],
        $data['salary'],
        $data['manager']
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function updateEmployee($pdo, $id, $data) {
    $sql = "UPDATE employees SET first_name=?, last_name=?, email=?, phone=?, department=?, position=?, status=?, join_date=?, salary=?, manager=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['firstName'],
        $data['lastName'],
        $data['email'],
        $data['phone'],
        $data['department'],
        $data['position'],
        $data['status'],
        $data['joinDate'],
        $data['salary'],
        $data['manager'],
        $id
    ]);
    echo json_encode(['success' => true]);
}

function deleteEmployee($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
}

// User functions
function getUsers($pdo) {
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, role, status, last_login, created_at FROM users ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createUser($pdo, $data) {
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, first_name, last_name, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['username'],
        $data['email'],
        $data['firstName'],
        $data['lastName'],
        $hashedPassword,
        $data['role'],
        $data['status']
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function updateUser($pdo, $id, $data) {
    $sql = "UPDATE users SET email=?, first_name=?, last_name=?, role=?, status=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['email'],
        $data['firstName'],
        $data['lastName'],
        $data['role'],
        $data['status'],
        $id
    ]);
    echo json_encode(['success' => true]);
}

function deleteUser($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
}

// Role functions
function getRoles($pdo) {
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createRole($pdo, $data) {
    $sql = "INSERT INTO roles (name, description, permissions) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['description'],
        json_encode($data['permissions'])
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function updateRole($pdo, $id, $data) {
    $sql = "UPDATE roles SET name=?, description=?, permissions=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['description'],
        json_encode($data['permissions']),
        $id
    ]);
    echo json_encode(['success' => true]);
}

function deleteRole($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
}

// Dashboard data
function getDashboardData($pdo) {
    $data = [];
    
    // Employee count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE status = 'Active'");
    $data['totalEmployees'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // User count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'");
    $data['activeUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent activities (mock data for now)
    $data['recentActivities'] = [
        ['user' => 'System', 'action' => 'Database updated', 'time' => date('Y-m-d H:i:s')]
    ];
    
    echo json_encode($data);
}
?>
