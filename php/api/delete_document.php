<?php
/**
 * API Endpoint: Delete Document
 * Allows authorized users (Admin, HR Manager) to delete an employee document record
 * and the associated file.
 * v2.0 - Added Authentication & Authorization checks.
 */

// --- Error Reporting & Headers ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log');

// IMPORTANT: Session must be started BEFORE any output
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production
header('Access-Control-Allow-Methods: POST, OPTIONS, DELETE'); // Allow POST/DELETE and OPTIONS
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true'); // Needed for sessions

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Database Connection ---
$pdo = null;
try {
    require_once '../db_connect.php';
    if (!isset($pdo) || !$pdo instanceof PDO) {
         throw new Exception('$pdo object not created by db_connect.php');
    }
} catch (Throwable $e) {
    error_log("CRITICAL PHP Error: Failed to include or connect via db_connect.php in delete_document.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error: Cannot connect to database.']);
    exit;
}

// --- Authentication & Authorization Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication required. Please log in.']);
    exit;
}

// Define roles allowed to perform this action (e.g., Admin=1, HR Manager=2)
$allowed_roles = [1, 2];
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], $allowed_roles)) {
     http_response_code(403); // Forbidden
     echo json_encode(['error' => 'Permission denied. You do not have rights to delete documents.']);
     exit;
}
$current_user_id = $_SESSION['user_id']; // For potential logging
// --- End Auth Check ---


// Check if it's a POST or DELETE request (using POST for simplicity with HTML forms if needed)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'POST or DELETE method required.']);
    exit;
}

// --- Get Data ---
// Get document_id from request body (for DELETE/JSON POST) or form data (for standard POST)
$input_data = json_decode(file_get_contents('php://input'), true); // For JSON payload
$document_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($input_data['document_id'])) {
     $document_id = filter_var($input_data['document_id'], FILTER_VALIDATE_INT);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($input_data['document_id'])) { // Check JSON body first for POST
         $document_id = filter_var($input_data['document_id'], FILTER_VALIDATE_INT);
    } elseif (isset($_POST['document_id'])) { // Fallback to form data for POST
         $document_id = filter_var($_POST['document_id'], FILTER_VALIDATE_INT);
    }
}


// Validate input
if (empty($document_id) || $document_id === false || $document_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Valid Document ID is required.']);
    exit;
}
// --- End Get Data ---


// --- Process Deletion ---
try {
    $pdo->beginTransaction(); // Start transaction for atomicity

    // 1. Get the file path before deleting the DB record
    $sql_select = "SELECT FilePath FROM EmployeeDocuments WHERE DocumentID = :document_id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->bindParam(':document_id', $document_id, PDO::PARAM_INT);
    $stmt_select->execute();
    $document = $stmt_select->fetch();

    $file_path_relative = null;
    $file_path_absolute = null;

    if ($document && !empty($document['FilePath'])) {
        $file_path_relative = $document['FilePath'];
        // Construct absolute path (relative to *this* script's location)
        // Goes up two levels from php/api/ to hr34/, then uses the relative path from DB
        $file_path_absolute = realpath(__DIR__ . '/../../' . $file_path_relative);
         if ($file_path_absolute === false) {
             error_log("Warning: Could not resolve absolute path for DocumentID: " . $document_id . ". Relative path: " . $file_path_relative);
             // Path resolution failed, treat as if file doesn't exist for deletion purposes
             $file_path_absolute = null;
         }
    } else {
        error_log("Document file path not found or empty in DB for DocumentID: " . $document_id . ". Attempting DB delete only.");
    }


    // 2. Delete the database record
    $sql_delete = "DELETE FROM EmployeeDocuments WHERE DocumentID = :document_id";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->bindParam(':document_id', $document_id, PDO::PARAM_INT);
    $stmt_delete->execute();
    $rows_affected = $stmt_delete->rowCount(); // Use rowCount() to check if delete was successful

    if ($rows_affected > 0) {
        // 3. If DB deletion was successful, attempt to delete the file
        $file_deleted_successfully = false;
        $file_existed = false;

        if ($file_path_relative && $file_path_absolute) {
             if (file_exists($file_path_absolute)) {
                $file_existed = true;
                if (unlink($file_path_absolute)) {
                    $file_deleted_successfully = true;
                    error_log("Successfully deleted file: " . $file_path_absolute . " for DocumentID: " . $document_id);
                } else {
                    // File deletion failed - Log this, but DB record is already gone.
                    error_log("Failed to delete file: " . $file_path_absolute . " for DocumentID: " . $document_id . ". Check permissions.");
                }
            } else {
                 error_log("File path found in DB but file does not exist on server: " . $file_path_absolute . " for DocumentID: " . $document_id);
            }
        }

        $pdo->commit(); // Commit DB change

        // Determine response message based on file deletion outcome
        if ($file_path_relative && $file_existed && !$file_deleted_successfully) {
             http_response_code(200); // OK, but with a warning
             echo json_encode(['message' => 'Document record deleted, but failed to delete the associated file. Please check server logs and permissions.']);
        } else {
             http_response_code(200); // OK
             echo json_encode(['message' => 'Document deleted successfully.']);
        }

    } else {
         // Document ID not found in database
         $pdo->rollBack(); // Rollback (though nothing was changed)
         http_response_code(404); // Not Found
         echo json_encode(['error' => 'Document not found with the specified ID.']);
    }

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); } // Rollback on any DB error
    error_log("API Error (delete_document DB): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred while deleting document.']);
} catch (Throwable $e) {
     if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) { $pdo->rollBack(); }
     error_log("API Error (delete_document General): " . $e->getMessage());
     http_response_code(500);
     echo json_encode(['error' => 'An unexpected server error occurred.']);
}
// --- End Process Deletion ---

exit;
?>
