<?php
session_start();
require_once __DIR__ . '/file-components/profile/ProfileSection.php';
require_once __DIR__ . '/file-components/editor/MarkdownEditor.php';

// Error handling and logging setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
function logError($message, $context = []) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . " - Context: " . json_encode($context));
}

$msg = '';
$msgType = 'success';
$content = '';
$current_file = '';
$fileType = $_GET['type'] ?? 'all';

// Get all files with allowed extensions
try {
    $files = glob('*.{txt,md}', GLOB_BRACE);
    if ($files === false) {
        throw new Exception("Failed to read files from directory");
    }
} catch (Exception $e) {
    logError("File listing error: " . $e->getMessage());
    $files = [];
    $msg = "Error: Unable to list files";
    $msgType = 'danger';
}

// Filter files based on type
if ($fileType !== 'all') {
    $files = array_filter($files, function($file) use ($fileType) {
        return str_ends_with($file, ".$fileType");
    });
}

// Handle file operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $analytics = new UserAnalytics();
        
        if (isset($_POST['save']) || isset($_POST['delete'])) {
            $filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
            
            if (empty($filename)) {
                $filename = 'untitled.md';
            }
            
            // Handle file extensions
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['txt', 'md'])) {
                $filename .= '.md';
            }

            // Validate filename
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
                throw new Exception("Invalid filename. Use only letters, numbers, dots, dashes and underscores.");
            }

            if (isset($_POST['save'])) {
                $content = $_POST['content'] ?? '';
                if (file_put_contents($filename, $content) === false) {
                    throw new Exception("Failed to save file: $filename");
                }
                $analytics->logFileActivity($filename, file_exists($filename) ? 'edit' : 'create');
                $msg = "File saved successfully!";
                $current_file = $filename;
            } elseif (isset($_POST['delete']) && file_exists($filename)) {
                $analytics->logFileActivity($filename, 'delete');
                if (!unlink($filename)) {
                    throw new Exception("Failed to delete file: $filename");
                }
                $msg = "File deleted successfully!";
                $content = '';
                $current_file = '';
            }
            $files = glob('*.{txt,md}', GLOB_BRACE) ?: [];
        }
    } catch (Exception $e) {
        logError("File operation error: " . $e->getMessage(), [
            'post' => $_POST,
            'file' => $filename ?? null
        ]);
        $msg = "Error: " . $e->getMessage();
        $msgType = 'danger';
    }
}

// Handle file loading
if (isset($_GET['file'])) {
    try {
        $file = basename($_GET['file']);
        if (!file_exists($file)) {
            throw new Exception("File not found: $file");
        }
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("Failed to read file: $file");
        }
        $current_file = $file;
    } catch (Exception $e) {
        logError("File loading error: " . $e->getMessage(), ['file' => $file ?? null]);
        $msg = "Error: " . $e->getMessage();
        $msgType = 'danger';
        $content = '';
        $current_file = '';
    }
}

// Initialize editor with current file data
$markdownEditor = new MarkdownEditor($content, $current_file);
$profileSection = new ProfileSection();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Web Notepad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-main: #4F46E5;
            --primary-light: #EEF2FF;
            --primary-dark: #3730A3;
            --bg-white: #FFFFFF;
            --bg-light: #F8FAFC;
            --bg-subtle: #F1F5F9;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --border: #E2E8F0;
            --success: #059669;
            --warning: #D97706;
            --danger: #DC2626;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --transition: all 0.2s ease-in-out;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-white);
            color: var(--text-primary);
            overflow: hidden;
            line-height: 1.6;
        }

        /* Button styles */
        .btn {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--primary-main);
            border-color: var(--primary-main);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            color: var(--text-secondary);
            border-color: var(--border);
        }

        .btn-outline-secondary:hover {
            background: var(--bg-subtle);
            color: var(--text-primary);
        }

        /* Sidebar styles */
        .sidebar {
            width: 260px;
            background: var(--bg-light);
            border-right: 1px solid var(--border);
        }

        .nav-pills .nav-link {
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .nav-pills .nav-link:hover {
            color: var(--text-primary);
            background: var(--bg-subtle);
        }

        .nav-pills .nav-link.active {
            background: var(--primary-light);
            color: var(--primary-main);
        }

        /* File list styles */
        .file-item {
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .file-item:hover {
            background: var(--bg-subtle);
        }

        .file-type-filter {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem 0;
        }

        .file-type-filter .btn {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
        }

        .file-type-filter .btn.active {
            background: var(--primary-light);
            color: var(--primary-main);
            border-color: transparent;
        }

        /* Form controls */
        .form-control {
            border-color: var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-main);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        /* Main content area */
        .main-content {
            flex: 1;
            background: var(--bg-white);
        }

        /* ... (keeping existing styles) ... */

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-overlay.show {
            display: flex;
        }

        /* Save indicator */
        .save-indicator {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: var(--success);
            color: white;
            border-radius: 4px;
            display: none;
            z-index: 1000;
        }

        .save-indicator.show {
            display: block;
            animation: fadeOut 2s forwards;
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; }
        }

        /* Unsaved changes indicator */
        .unsaved-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--warning);
            border-radius: 50%;
            margin-left: 0.5rem;
            display: none;
        }

        .unsaved-indicator.show {
            display: inline-block;
        }
    </style>
</head>
<body class="vh-100">
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Save Indicator -->
    <div class="save-indicator">
        Changes saved successfully!
    </div>

    <!-- Header -->
    <header class="bg-white border-bottom">
        <div class="d-flex align-items-center justify-content-between px-4 py-3">
            <div class="d-flex align-items-center">
                <h2 class="h4 mb-0 me-4">Web Notepad</h2>
                <span class="unsaved-indicator" id="unsavedIndicator"></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="saveBtn" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-save"></i> Save
                    <small class="text-white-50">(Ctrl+S)</small>
                </button>
                <button type="button" onclick="closeFile()" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?> rounded-0 py-2 mb-0">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
    </header>

    <!-- Main Content -->
    <div class="d-flex flex-grow-1 h-100">
        <!-- Sidebar -->
        <div class="sidebar p-3">
            <div class="nav nav-pills flex-column mb-4" role="tablist">
                <button class="nav-link active d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#files" type="button" role="tab">
                    <i class="bi bi-file-text"></i> My Files
                </button>
                <button class="nav-link d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab">
                    <i class="bi bi-graph-up"></i> Analytics
                </button>
            </div>

            <!-- File Type Filter -->
            <div class="file-type-filter">
                <a href="?type=all" class="btn btn-sm btn-light <?php echo $fileType === 'all' ? 'active' : ''; ?>">
                    All Files
                </a>
                <a href="?type=md" class="btn btn-sm btn-light <?php echo $fileType === 'md' ? 'active' : ''; ?>">
                    .md
                </a>
                <a href="?type=txt" class="btn btn-sm btn-light <?php echo $fileType === 'txt' ? 'active' : ''; ?>">
                    .txt
                </a>
            </div>
            
            <div id="fileList" class="overflow-auto mt-3" style="max-height: calc(100vh - 250px);">
                <?php if (empty($files)): ?>
                    <div class="text-center text-muted p-4">
                        <i class="bi bi-file-earmark-text display-4"></i>
                        <p class="mt-2">No files yet</p>
                    </div>
                <?php else: ?>
                    <ul class="list-unstyled">
                        <?php foreach ($files as $file): ?>
                            <li class="mb-1">
                                <a href="?file=<?php echo urlencode($file); ?>&type=<?php echo $fileType; ?>" 
                                   class="file-item d-flex align-items-center p-2 text-decoration-none text-dark">
                                    <span class="file-icon me-2">
                                        <?php if (str_ends_with($file, '.md')): ?>
                                            <i class="bi bi-markdown text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-file-text text-secondary"></i>
                                        <?php endif; ?>
                                    </span>
                                    <?php echo htmlspecialchars($file); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Area -->
        <main class="main-content">
            <div class="tab-content h-100">
                <!-- Files Tab -->
                <div class="tab-pane fade show active h-100" id="files" role="tabpanel">
                    <form id="editForm" method="POST" class="d-flex flex-column h-100" 
                          onsubmit="return handleFormSubmit(event)">
                        <div class="border-bottom bg-light p-3">
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   name="filename" 
                                   value="<?php echo htmlspecialchars($current_file); ?>" 
                                   placeholder="Enter filename (e.g., notes.md)"
                                   pattern="[a-zA-Z0-9_\-\.]+">
                        </div>
                        
                        <div class="flex-grow-1 overflow-hidden">
                            <?php echo $markdownEditor->render(); ?>
                        </div>

                        <input type="submit" name="save" id="saveSubmit" style="display: none;">
                        <input type="submit" name="delete" id="deleteSubmit" style="display: none;">
                    </form>
                </div>

                <!-- Analytics Tab -->
                <div class="tab-pane fade h-100" id="analytics" role="tabpanel">
                    <?php echo $profileSection->render(); ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-markdown.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        // Global variables
        let hasUnsavedChanges = false;
        let originalContent = '';
        const editForm = document.getElementById('editForm');
        const editor = document.getElementById('markdownContent');
        const loadingOverlay = document.querySelector('.loading-overlay');
        const saveIndicator = document.querySelector('.save-indicator');
        const unsavedIndicator = document.getElementById('unsavedIndicator');

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize marked.js
            if (typeof marked !== 'undefined') {
                marked.setOptions({
                    breaks: true,
                    gfm: true
                });
            }

            // Store original content for change detection
            originalContent = editor.value;

            // Track changes
            editor.addEventListener('input', function() {
                hasUnsavedChanges = editor.value !== originalContent;
                unsavedIndicator.classList.toggle('show', hasUnsavedChanges);
            });

            // Handle navigation away
            window.addEventListener('beforeunload', function(e) {
                if (hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        });

        // Save functionality
        document.getElementById('saveBtn').addEventListener('click', saveFile);
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveFile();
            }
        });

        function saveFile() {
            if (validateForm()) {
                showLoading();
                document.getElementById('saveSubmit').click();
            }
        }

        function validateForm() {
            const filename = document.querySelector('input[name="filename"]').value.trim();
            if (!filename) {
                alert('Please enter a filename');
                return false;
            }
            if (!/^[a-zA-Z0-9_\-\.]+$/.test(filename)) {
                alert('Invalid filename. Use only letters, numbers, dots, dashes and underscores.');
                return false;
            }
            return true;
        }

        function handleFormSubmit(event) {
            if (!validateForm()) {
                event.preventDefault();
                return false;
            }
            showLoading();
            return true;
        }

        function closeFile() {
            if (hasUnsavedChanges) {
                if (confirm('You have unsaved changes. Are you sure you want to close this file?')) {
                    window.location.href = window.location.pathname + '?type=<?php echo $fileType; ?>';
                }
            } else {
                window.location.href = window.location.pathname + '?type=<?php echo $fileType; ?>';
            }
        }

        function showLoading() {
            loadingOverlay.classList.add('show');
        }

        function showSaveIndicator() {
            saveIndicator.classList.add('show');
            setTimeout(() => {
                saveIndicator.classList.remove('show');
            }, 2000);
        }

        <?php if (strpos($msg, 'successfully') !== false): ?>
            showSaveIndicator();
        <?php endif; ?>
    </script>
</body>
</html>
