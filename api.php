<?php
/**
 * Campusly RESTful JSON API (api.php)
 * System: Campusly (CHARUSAT CSPIT Academic Portal)
 * 
 * Features:
 * - Pure RESTful JSON API endpoints for Student Management CRUD
 * - Methods supported: GET (List/Read), POST (Create), PUT (Update), DELETE (Delete)
 * - Method override support via ?action= or $_POST['_method'] or X-HTTP-Method-Override
 * - Real-time Search with Debounce support (?search=query)
 * - Strict Input Validation before DB operations
 * - HTTP Status Codes: 200 OK, 201 Created, 400 Bad Request, 404 Not Found, 409 Conflict, 405 Method Not Allowed, 500 Internal Error
 * - MySQLi Prepared Statements for 100% SQL injection prevention
 * - Centralized Error Handling & JSON response envelope
 */

// Set strict headers for JSON API
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-HTTP-Method-Override');

// Preflight CORS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include database connection
require_once __DIR__ . '/db.php';

/**
 * Standardized JSON Response Helper
 *
 * @param int $statusCode HTTP status code (200, 201, 400, 404, 409, 500, etc.)
 * @param bool $success True if operation succeeded, false otherwise
 * @param string $message Descriptive summary message
 * @param mixed $data Payload data (array, object, or null)
 * @param array $errors Array of validation or error details
 */
function sendJsonResponse(int $statusCode, bool $success, string $message, $data = null, array $errors = []): void {
    http_response_code($statusCode);
    $response = [
        'status'    => $success ? 'success' : 'error',
        'code'      => $statusCode,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c')
    ];
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Global exception and error handler
try {
    // Determine effective HTTP method (supports method override for maximum client compatibility)
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method === 'POST') {
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } elseif (isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        } elseif (isset($_GET['action'])) {
            $action = strtolower($_GET['action']);
            if ($action === 'delete') $method = 'DELETE';
            elseif ($action === 'update' || $action === 'edit') $method = 'PUT';
            elseif ($action === 'create' || $action === 'add') $method = 'POST';
        }
    }

    if (!isset($conn) || $conn->connect_error) {
        if ($method === 'GET' && file_exists(__DIR__ . '/data/students.json')) {
            $raw = json_decode(file_get_contents(__DIR__ . '/data/students.json'), true);
            $list = [];
            foreach ($raw as $item) {
                $dept = $item['department'] ?? 'CSE';
                $code = 'CSE';
                if (stripos($dept, 'Information') !== false || stripos($dept, 'IT') !== false) $code = 'IT';
                elseif (stripos($dept, 'Communication') !== false || stripos($dept, 'EC') !== false) $code = 'EC';
                elseif (stripos($dept, 'Computer Eng') !== false || stripos($dept, 'CE') !== false) $code = 'CE';

                $list[] = [
                    'student_id' => (int)($item['id'] ?? 1),
                    'name'       => $item['name'] ?? '',
                    'email'      => $item['email'] ?? '',
                    'course'     => $code,
                    'status'     => $item['status'] ?? 'Active'
                ];
            }
            sendJsonResponse(200, true, 'Student records retrieved successfully (Demo Cloud Mode)', $list, [
                'total_records' => count($list),
                'mode'          => 'demo_cloud'
            ]);
        }
        sendJsonResponse(500, false, 'Database connection error: Unable to reach MySQL server.');
    }

    // Parse input payload (handles application/json body as well as x-www-form-urlencoded)
    $rawInput = file_get_contents('php://input');
    $inputData = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $inputData = $decoded;
        } else {
            parse_str($rawInput, $parsed);
            if (is_array($parsed)) {
                $inputData = $parsed;
            }
        }
    }
    // Merge $_POST if available
    if (!empty($_POST)) {
        $inputData = array_merge($inputData, $_POST);
    }

    // Route by HTTP Method
    switch ($method) {

        // =====================================================================
        // 1. GET: Fetch list of students OR single student by ID
        // =====================================================================
        case 'GET':
            // Fetch single student by ?id=X
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $studentId = (int)$_GET['id'];
                $stmt = $conn->prepare("SELECT student_id, name, email, course, status FROM students WHERE student_id = ? LIMIT 1");
                $stmt->bind_param("i", $studentId);
                $stmt->execute();
                $result = $stmt->get_result();
                $student = $result->fetch_assoc();
                $stmt->close();

                if ($student) {
                    sendJsonResponse(200, true, "Student record #$studentId retrieved successfully.", $student);
                } else {
                    sendJsonResponse(404, false, "Student record with ID #$studentId was not found.");
                }
            }

            // Fetch list with optional search query & filters
            $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
            $course = trim($_GET['course'] ?? '');
            $status = trim($_GET['status'] ?? '');

            $sql = "SELECT student_id, name, email, course, status FROM students WHERE 1=1";
            $params = [];
            $types  = "";

            if ($search !== '') {
                $sql .= " AND (name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
                $like = "%$search%";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $types   .= "sss";
            }

            if ($course !== '' && $course !== 'all') {
                $sql .= " AND course = ?";
                $params[] = $course;
                $types   .= "s";
            }

            if ($status !== '' && $status !== 'all') {
                $sql .= " AND status = ?";
                $params[] = $status;
                $types   .= "s";
            }

            $sql .= " ORDER BY student_id DESC";

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = [
                    'student_id' => (int)$row['student_id'],
                    'name'       => $row['name'],
                    'email'      => $row['email'],
                    'course'     => $row['course'],
                    'status'     => $row['status']
                ];
            }
            $stmt->close();

            sendJsonResponse(200, true, "Loaded " . count($students) . " student records.", [
                'total'    => count($students),
                'students' => $students
            ]);
            break;

        // =====================================================================
        // 2. POST: Create a new student record
        // =====================================================================
        case 'POST':
            $name   = trim($inputData['name'] ?? '');
            $email  = trim($inputData['email'] ?? '');
            $course = trim($inputData['course'] ?? 'CSE');
            $status = trim($inputData['status'] ?? 'Active');

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Student full name is required.';
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors['name'] = 'Student name must be between 2 and 100 characters.';
            }

            if ($email === '') {
                $errors['email'] = 'University email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please provide a valid email format (e.g., student@charusat.edu.in).';
            }

            $validCourses = ['CSE', 'CE', 'IT', 'EC', 'ME', 'CL'];
            if (!in_array($course, $validCourses)) {
                $course = 'CSE';
            }

            if (!in_array($status, ['Active', 'Inactive'])) {
                $status = 'Active';
            }

            if (!empty($errors)) {
                sendJsonResponse(400, false, 'Validation failed: Please correct the highlighted errors.', null, $errors);
            }

            // Check for duplicate email
            $chk = $conn->prepare("SELECT student_id FROM students WHERE email = ? LIMIT 1");
            $chk->bind_param("s", $email);
            $chk->execute();
            $chkResult = $chk->get_result();
            if ($chkResult->fetch_assoc()) {
                $chk->close();
                sendJsonResponse(409, false, "A student with email '$email' already exists in the system.", null, [
                    'email' => "Email '$email' is already registered."
                ]);
            }
            $chk->close();

            // Insert new student using prepared statement
            $ins = $conn->prepare("INSERT INTO students (name, email, course, status) VALUES (?, ?, ?, ?)");
            $ins->bind_param("ssss", $name, $email, $course, $status);
            if ($ins->execute()) {
                $newId = $conn->insert_id;
                $ins->close();

                $newStudent = [
                    'student_id' => $newId,
                    'name'       => $name,
                    'email'      => $email,
                    'course'     => $course,
                    'status'     => $status
                ];
                sendJsonResponse(201, true, "Student record #$newId ('$name') created successfully.", $newStudent);
            } else {
                $dbErr = $conn->error;
                $ins->close();
                sendJsonResponse(500, false, "Failed to insert student record into database: $dbErr");
            }
            break;

        // =====================================================================
        // 3. PUT: Update an existing student record
        // =====================================================================
        case 'PUT':
            $studentId = (int)($inputData['student_id'] ?? ($_GET['id'] ?? 0));
            $name      = trim($inputData['name'] ?? '');
            $email     = trim($inputData['email'] ?? '');
            $course    = trim($inputData['course'] ?? 'CSE');
            $status    = trim($inputData['status'] ?? 'Active');

            if ($studentId <= 0) {
                sendJsonResponse(400, false, 'Valid Student ID is required for update operation.');
            }

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Student full name is required.';
            }
            if ($email === '') {
                $errors['email'] = 'University email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please provide a valid email format.';
            }

            $validCourses = ['CSE', 'CE', 'IT', 'EC', 'ME', 'CL'];
            if (!in_array($course, $validCourses)) {
                $course = 'CSE';
            }
            if (!in_array($status, ['Active', 'Inactive'])) {
                $status = 'Active';
            }

            if (!empty($errors)) {
                sendJsonResponse(400, false, 'Validation failed: Please correct the highlighted errors.', null, $errors);
            }

            // Verify record existence
            $chkExists = $conn->prepare("SELECT student_id FROM students WHERE student_id = ? LIMIT 1");
            $chkExists->bind_param("i", $studentId);
            $chkExists->execute();
            if (!$chkExists->get_result()->fetch_assoc()) {
                $chkExists->close();
                sendJsonResponse(404, false, "Cannot update: Student record with ID #$studentId does not exist.");
            }
            $chkExists->close();

            // Verify email uniqueness (excluding current student)
            $chkDup = $conn->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ? LIMIT 1");
            $chkDup->bind_param("si", $email, $studentId);
            $chkDup->execute();
            if ($chkDup->get_result()->fetch_assoc()) {
                $chkDup->close();
                sendJsonResponse(409, false, "Email '$email' is already in use by another student.", null, [
                    'email' => "Email '$email' is already registered to another record."
                ]);
            }
            $chkDup->close();

            // Update record
            $upd = $conn->prepare("UPDATE students SET name = ?, email = ?, course = ?, status = ? WHERE student_id = ?");
            $upd->bind_param("ssssi", $name, $email, $course, $status, $studentId);
            if ($upd->execute()) {
                $upd->close();
                $updatedStudent = [
                    'student_id' => $studentId,
                    'name'       => $name,
                    'email'      => $email,
                    'course'     => $course,
                    'status'     => $status
                ];
                sendJsonResponse(200, true, "Student record #$studentId ('$name') updated successfully.", $updatedStudent);
            } else {
                $dbErr = $conn->error;
                $upd->close();
                sendJsonResponse(500, false, "Failed to update student record: $dbErr");
            }
            break;

        // =====================================================================
        // 4. DELETE: Remove a student record
        // =====================================================================
        case 'DELETE':
            $studentId = (int)($inputData['student_id'] ?? ($_GET['id'] ?? 0));
            if ($studentId <= 0) {
                sendJsonResponse(400, false, 'Valid Student ID is required for delete operation.');
            }

            // Check if student exists
            $chk = $conn->prepare("SELECT student_id, name FROM students WHERE student_id = ? LIMIT 1");
            $chk->bind_param("i", $studentId);
            $chk->execute();
            $res = $chk->get_result();
            $existing = $res->fetch_assoc();
            $chk->close();

            if (!$existing) {
                sendJsonResponse(404, false, "Cannot delete: Student record with ID #$studentId does not exist.");
            }

            $del = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $del->bind_param("i", $studentId);
            if ($del->execute()) {
                $del->close();
                sendJsonResponse(200, true, "Student record #$studentId ('{$existing['name']}') was permanently deleted.", [
                    'deleted_id'   => $studentId,
                    'deleted_name' => $existing['name']
                ]);
            } else {
                $dbErr = $conn->error;
                $del->close();
                sendJsonResponse(500, false, "Failed to delete student record: $dbErr");
            }
            break;

        default:
            sendJsonResponse(405, false, "HTTP Method '$method' is not allowed. Supported methods: GET, POST, PUT, DELETE.");
            break;
    }

} catch (\Throwable $e) {
    sendJsonResponse(500, false, 'Internal Server Error: ' . $e->getMessage());
}
