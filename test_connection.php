<?php
/**
 * Database Connection Test
 * Access via: http://localhost/chefbot/test_connection.php
 */

require_once 'config.php';

$results = [];
$hasError = false;

// Test 1: Connection
try {
    $conn = getDBConnection();
    $results[] = ['test' => 'Database Connection', 'status' => 'success', 'message' => 'Connected successfully!'];
} catch (Exception $e) {
    $results[] = ['test' => 'Database Connection', 'status' => 'error', 'message' => $e->getMessage()];
    $hasError = true;
}

// Test 2: Check if tables exist
if (!$hasError) {
    $tables = ['users', 'chat_sessions', 'messages', 'user_preferences'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        $results[] = ['test' => 'Database Tables', 'status' => 'success', 'message' => 'All tables exist'];
    } else {
        $results[] = ['test' => 'Database Tables', 'status' => 'warning', 'message' => 'Missing tables: ' . implode(', ', $missing_tables)];
    }
}

// Test 3: Count users
if (!$hasError) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        $results[] = ['test' => 'Users Count', 'status' => 'info', 'message' => $row['count'] . ' users in database'];
    }
}

// Test 4: Test insert capability (rollback after)
if (!$hasError) {
    $conn->begin_transaction();
    try {
        $test_email = 'test_' . time() . '@example.com';
        $test_password = password_hash('test123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $test_email, $test_password);
        
        if ($stmt->execute()) {
            $conn->rollback(); // Don't actually save test data
            $results[] = ['test' => 'Write Permission', 'status' => 'success', 'message' => 'Can write to database'];
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $results[] = ['test' => 'Write Permission', 'status' => 'error', 'message' => $e->getMessage()];
    }
}

// Test 5: PHP Configuration
$results[] = ['test' => 'PHP Version', 'status' => 'info', 'message' => phpversion()];
$results[] = ['test' => 'MySQL Extension', 'status' => extension_loaded('mysqli') ? 'success' : 'error', 'message' => extension_loaded('mysqli') ? 'Loaded' : 'Not loaded'];

if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefBot - Connection Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 mb-4">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2">
                    <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/>
                    <line x1="6" y1="17" x2="18" y2="17"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Connection Test</h1>
            <p class="text-gray-600 mt-2">Testing database connectivity</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="space-y-3">
                <?php foreach ($results as $result): ?>
                    <div class="flex items-center justify-between p-4 rounded-lg border
                        <?php 
                            if ($result['status'] === 'success') echo 'bg-green-50 border-green-200';
                            elseif ($result['status'] === 'error') echo 'bg-red-50 border-red-200';
                            elseif ($result['status'] === 'warning') echo 'bg-yellow-50 border-yellow-200';
                            else echo 'bg-blue-50 border-blue-200';
                        ?>
                    ">
                        <div class="flex items-center gap-3">
                            <?php if ($result['status'] === 'success'): ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            <?php elseif ($result['status'] === 'error'): ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                            <?php elseif ($result['status'] === 'warning'): ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            <?php else: ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                            <?php endif; ?>
                            
                            <div>
                                <div class="font-medium text-gray-800"><?php echo $result['test']; ?></div>
                                <div class="text-sm text-gray-600"><?php echo $result['message']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-6 flex gap-3">
                <a href="setup.php" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 rounded-lg transition text-center">
                    Run Setup
                </a>
                <a href="register.php" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-3 rounded-lg transition text-center">
                    Register
                </a>
            </div>
        </div>

        <div class="mt-6 text-center">
            <button onclick="location.reload()" class="text-sm text-orange-500 hover:text-orange-600">
                🔄 Refresh Test
            </button>
        </div>
    </div>
</body>
</html>