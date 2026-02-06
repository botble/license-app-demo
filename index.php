<?php

/**
 * License Manager API Demo - External API
 *
 * This demo showcases the External API for license activation, verification, and deactivation.
 * Deploy to: license-app-demo.botble.com
 */

session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/api.php';

// Default demo credentials (pre-populated)
$defaultConfig = [
    'api_url' => 'https://license-manager.botble.com',
    'api_key' => '',
    'product_id' => '',
    'license_code' => '',
    'client_name' => 'Demo User',
];

// Handle AJAX API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // CSRF validation
    $token = $_POST['csrf_token'] ?? '';
    if (! hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'];

    switch ($action) {
        case 'save_config':
            $_SESSION['config'] = [
                'api_url' => rtrim(trim($_POST['api_url'] ?? ''), '/'),
                'api_key' => trim($_POST['api_key'] ?? ''),
                'product_id' => trim($_POST['product_id'] ?? ''),
                'license_code' => trim($_POST['license_code'] ?? ''),
                'client_name' => trim($_POST['client_name'] ?? 'Demo User'),
            ];
            echo json_encode(['success' => true, 'message' => 'Configuration saved']);
            exit;

        case 'clear_config':
            unset($_SESSION['config'], $_SESSION['license_data']);
            echo json_encode(['success' => true, 'message' => 'Configuration cleared']);
            exit;

        case 'check_connection':
            echo json_encode(checkConnection());
            exit;

        case 'activate':
            echo json_encode(activateLicense());
            exit;

        case 'verify':
            echo json_encode(verifyLicense());
            exit;

        case 'deactivate':
            echo json_encode(deactivateLicense());
            exit;

        case 'latest_version':
            $productId = trim($_POST['update_product_id'] ?? '');
            if (empty($productId)) {
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                exit;
            }
            echo json_encode(getLatestVersion($productId));
            exit;

        case 'check_update':
            $productId = trim($_POST['update_product_id'] ?? '');
            $currentVersion = trim($_POST['current_version'] ?? '');
            if (empty($productId) || empty($currentVersion)) {
                echo json_encode(['success' => false, 'error' => 'Product ID and Current Version are required']);
                exit;
            }
            echo json_encode(checkUpdate($productId, $currentVersion));
            exit;

        case 'update_size':
            $versionId = trim($_POST['version_id'] ?? '');
            $fileType = trim($_POST['file_type'] ?? 'main');
            if (empty($versionId)) {
                echo json_encode(['success' => false, 'error' => 'Version ID is required']);
                exit;
            }
            echo json_encode(getUpdateSize($versionId, $fileType));
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
}

// Get current config (session or defaults)
$config = $_SESSION['config'] ?? $defaultConfig;
$hasLicenseData = ! empty($_SESSION['license_data']);
$isConfigured = ! empty($config['api_url']) && ! empty($config['api_key']);
$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title>License Manager API Demo - External API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #F8FAFC;
            --card-bg: #FFFFFF;
            --text: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --primary: #3B82F6;
            --success: #22C55E;
            --warning: #F59E0B;
            --danger: #EF4444;
            --json-key: #7C3AED;
            --json-string: #059669;
            --json-bool: #3B82F6;
            --json-number: #EA580C;
            --json-null: #64748B;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'IBM Plex Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.5;
        }

        .main-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .nav-tabs-custom {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .nav-tabs-custom a {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .nav-tabs-custom a:hover {
            color: var(--text);
            border-color: var(--text-muted);
        }

        .nav-tabs-custom a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .card-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent;
        }

        .card-body { padding: 1rem; }

        .form-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 0.375rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-secondary {
            color: var(--text-muted);
            border-color: var(--border);
        }

        .btn-outline-secondary:hover {
            background: var(--bg);
            color: var(--text);
        }

        .btn-operation {
            background: #F1F5F9;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-operation:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .btn-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .status-bar {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .response-panel {
            background: #1E293B;
            border-radius: 0.375rem;
            min-height: 200px;
            max-height: 350px;
            overflow: auto;
        }

        .response-panel pre {
            margin: 0;
            padding: 1rem;
            color: #E2E8F0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8125rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .json-key { color: var(--json-key); }
        .json-string { color: var(--json-string); }
        .json-bool { color: var(--json-bool); }
        .json-number { color: var(--json-number); }
        .json-null { color: var(--json-null); }

        .request-panel {
            background: var(--bg);
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .request-panel {
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-family: 'JetBrains Mono', monospace;
        }

        .section-title {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .footer {
            text-align: center;
            padding: 2rem 0 1rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .footer a { color: var(--text-muted); }
        .footer a:hover { color: var(--text); }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="page-header">
            <h1>License Manager API Demo</h1>
            <p>Test External API for license operations</p>
        </div>

        <!-- Navigation -->
        <nav class="nav-tabs-custom">
            <a href="index.php" class="active">External API</a>
            <a href="internal.php">Internal API</a>
        </nav>

        <!-- Instructions -->
        <div class="card" style="background: #EFF6FF; border-color: #BFDBFE;">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-6">
                        <h6 style="font-size: 0.8125rem; font-weight: 600; color: #1E40AF; margin-bottom: 0.5rem;">Getting Started</h6>
                        <ol style="font-size: 0.8125rem; color: #1E3A8A; margin: 0; padding-left: 1.25rem;">
                            <li>Enter your License Manager <strong>API URL</strong> and <strong>External API Key</strong></li>
                            <li>Add your <strong>Product ID</strong> and <strong>License Code</strong></li>
                            <li>Click <strong>Test Connection</strong> to verify setup</li>
                            <li>Use <strong>Activate</strong> → <strong>Verify</strong> → <strong>Deactivate</strong> to test the flow</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 style="font-size: 0.8125rem; font-weight: 600; color: #1E40AF; margin-bottom: 0.5rem;">Where to find credentials?</h6>
                        <ul style="font-size: 0.8125rem; color: #1E3A8A; margin: 0; padding-left: 1.25rem;">
                            <li><strong>API Key</strong>: Admin → Settings → API Keys → Create "External" type</li>
                            <li><strong>Product ID</strong>: Admin → Products → Copy the product ID/UUID</li>
                            <li><strong>License Code</strong>: Admin → Licenses → Copy an existing code or create new</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="card">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="status-bar">
                        <span id="status-config" class="badge" style="background: <?= $isConfigured ? 'var(--success)' : 'var(--border)' ?>; color: <?= $isConfigured ? 'white' : 'var(--text-muted)' ?>">
                            Config: <?= $isConfigured ? 'Ready' : 'Not Set' ?>
                        </span>
                        <span id="status-connection" class="badge" style="background: var(--border); color: var(--text-muted)">
                            Connection: Unknown
                        </span>
                        <span id="status-license" class="badge" style="background: <?= $hasLicenseData ? 'var(--success)' : 'var(--border)' ?>; color: <?= $hasLicenseData ? 'white' : 'var(--text-muted)' ?>">
                            License: <?= $hasLicenseData ? 'Active' : 'None' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <!-- Configuration Card -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <span>Configuration</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="clearConfig()">
                            Clear
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="config-form">
                            <div class="mb-3">
                                <label class="form-label">API URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" name="api_url"
                                       value="<?= e($config['api_url']) ?>"
                                       placeholder="https://license.botble.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Key <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="api_key" id="api_key"
                                           value="<?= e($config['api_key']) ?>"
                                           placeholder="Your External API Key" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('api_key')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Product ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="product_id"
                                       value="<?= e($config['product_id']) ?>"
                                       placeholder="Product Reference ID" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">License Code</label>
                                <input type="text" class="form-control" name="license_code"
                                       value="<?= e($config['license_code']) ?>"
                                       placeholder="XXXX-XXXX-XXXX-XXXX">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Client Name</label>
                                <input type="text" class="form-control" name="client_name"
                                       value="<?= e($config['client_name']) ?>"
                                       placeholder="Your name or company">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save me-1"></i>Save Configuration
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Operations Card -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">License Operations</div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-operation"
                                    onclick="callOperation('check_connection')">
                                Test Connection
                            </button>
                            <button type="button" class="btn btn-operation"
                                    onclick="callOperation('activate')">
                                Activate
                            </button>
                            <button type="button" class="btn btn-operation"
                                    onclick="callOperation('verify')">
                                Verify
                            </button>
                            <button type="button" class="btn btn-operation"
                                    onclick="callOperation('deactivate')">
                                Deactivate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Updates Card -->
                <div class="card mt-3">
                    <div class="card-header">Updates Manager</div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" id="update_product_id"
                                       placeholder="Product ID" value="<?= e($config['product_id']) ?>">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" id="current_version"
                                       placeholder="Current Version (e.g. 1.0.0)">
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-operation btn-sm"
                                    onclick="getLatestVersion()">
                                Get Latest Version
                            </button>
                            <button type="button" class="btn btn-operation btn-sm"
                                    onclick="checkForUpdate()">
                                Check Update
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm" id="version_id"
                                       placeholder="Version ID (from response)">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" id="file_type">
                                    <option value="main">Main (ZIP)</option>
                                    <option value="sql">SQL</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-operation btn-sm w-100"
                                        onclick="getFileSize()">
                                    Get Size
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Panel -->
                <div class="card mt-3">
                    <div class="card-header">Request</div>
                    <div class="card-body p-0">
                        <div class="request-panel p-3" id="request-panel">
                            <span style="color: var(--text-muted)">Click an operation to see the request...</span>
                        </div>
                    </div>
                </div>

                <!-- Response Panel -->
                <div class="card mt-3">
                    <div class="card-header">
                        <span>Response</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyResponse()" id="copy-btn" style="display: none; padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            Copy
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="response-panel" id="response-panel">
                            <pre id="response-content">Click an operation to see the response...</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="card mt-3">
            <div class="card-header" style="cursor: pointer;" onclick="document.getElementById('faq-content').classList.toggle('d-none')">
                <span>FAQ & Troubleshooting</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="card-body" id="faq-content">
                <div class="row">
                    <div class="col-md-6">
                        <p style="font-size: 0.8125rem; margin-bottom: 0.75rem;"><strong>What does each operation do?</strong></p>
                        <ul style="font-size: 0.8125rem; color: var(--text-muted); padding-left: 1.25rem;">
                            <li><strong>Test Connection</strong> - Verify API URL and key are correct</li>
                            <li><strong>Activate</strong> - Register license for this domain/IP (uses 1 slot)</li>
                            <li><strong>Verify</strong> - Check if current activation is valid</li>
                            <li><strong>Deactivate</strong> - Release the license slot for use elsewhere</li>
                        </ul>
                        <p style="font-size: 0.8125rem; margin-bottom: 0.75rem; margin-top: 1rem;"><strong>Updates Manager</strong></p>
                        <ul style="font-size: 0.8125rem; color: var(--text-muted); padding-left: 1.25rem;">
                            <li><strong>Get Latest Version</strong> - Fetch newest version info for a product</li>
                            <li><strong>Check Update</strong> - Compare current version with latest</li>
                            <li><strong>Get Size</strong> - Get download file size before downloading</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <p style="font-size: 0.8125rem; margin-bottom: 0.75rem;"><strong>Common Issues</strong></p>
                        <ul style="font-size: 0.8125rem; color: var(--text-muted); padding-left: 1.25rem;">
                            <li><strong>Connection Failed</strong> - Check API URL (include https://) and verify API key is "External" type</li>
                            <li><strong>License not found</strong> - Ensure license code exists and belongs to the specified product</li>
                            <li><strong>Already activated</strong> - License reached max parallel uses; deactivate from another domain first</li>
                            <li><strong>Invalid product</strong> - Product ID must match exactly (case-sensitive if UUID)</li>
                            <li><strong>Blocked license</strong> - Admin has blocked this license; contact support</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <a href="https://docs.botble.com/license-manager" target="_blank">Documentation</a>
            <span class="mx-2">·</span>
            <a href="https://codecanyon.net/user/botble/portfolio" target="_blank">CodeCanyon</a>
            <br><span style="margin-top: 0.5rem; display: inline-block;">License Manager by Botble</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lastResponse = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Save configuration
        document.getElementById('config-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save_config');
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    showToast('Configuration saved!', 'success');
                    updateConfigStatus(true);
                }
            } catch (error) {
                showToast('Failed to save configuration', 'danger');
            }
        });

        // Call API operation
        async function callOperation(action) {
            const buttons = document.querySelectorAll('.btn-operation');
            buttons.forEach(btn => {
                btn.classList.add('btn-loading');
                btn.disabled = true;
            });

            const formData = new FormData();
            formData.append('action', action);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();

                lastResponse = result;
                displayRequest(result.request);
                displayResponse(result);
                updateStatuses(action, result);
                document.getElementById('copy-btn').style.display = 'block';
            } catch (error) {
                displayError(error.message);
            } finally {
                buttons.forEach(btn => {
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                });
            }
        }

        // Updates Manager functions
        async function getLatestVersion() {
            const productId = document.getElementById('update_product_id').value.trim();
            if (!productId) {
                showToast('Product ID is required', 'warning');
                return;
            }
            await callOperationWithData('latest_version', { update_product_id: productId });
        }

        async function checkForUpdate() {
            const productId = document.getElementById('update_product_id').value.trim();
            const currentVersion = document.getElementById('current_version').value.trim();
            if (!productId || !currentVersion) {
                showToast('Product ID and Current Version are required', 'warning');
                return;
            }
            await callOperationWithData('check_update', {
                update_product_id: productId,
                current_version: currentVersion
            });
        }

        async function getFileSize() {
            const versionId = document.getElementById('version_id').value.trim();
            const fileType = document.getElementById('file_type').value;
            if (!versionId) {
                showToast('Version ID is required', 'warning');
                return;
            }
            await callOperationWithData('update_size', {
                version_id: versionId,
                file_type: fileType
            });
        }

        async function callOperationWithData(action, data = {}) {
            const buttons = document.querySelectorAll('.btn-operation');
            buttons.forEach(btn => {
                btn.classList.add('btn-loading');
                btn.disabled = true;
            });

            const formData = new FormData();
            formData.append('action', action);
            formData.append('csrf_token', csrfToken);
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();

                lastResponse = result;
                displayRequest(result.request);
                displayResponse(result);
                document.getElementById('copy-btn').style.display = 'block';

                // Auto-fill version_id if available in response
                if (result.data?.update_id) {
                    document.getElementById('version_id').value = result.data.update_id;
                }
            } catch (error) {
                displayError(error.message);
            } finally {
                buttons.forEach(btn => {
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                });
            }
        }

        // Display request details
        function displayRequest(request) {
            if (!request) {
                document.getElementById('request-panel').innerHTML =
                    '<p class="text-muted mb-0">No request data available</p>';
                return;
            }

            let html = `
                <div class="mb-2">
                    <span class="badge bg-primary">${request.method}</span>
                    <code class="ms-2">${escapeHtml(request.url)}</code>
                </div>
                <div class="mb-2">
                    <strong>Headers:</strong>
                    <ul class="mb-0 ps-3">
                        ${request.headers.map(h => `<li><code>${escapeHtml(h)}</code></li>`).join('')}
                    </ul>
                </div>
            `;

            if (request.body) {
                html += `
                    <div>
                        <strong>Body:</strong>
                        <pre class="mb-0 mt-1 p-2 bg-light rounded" style="font-size: 0.8rem;">${escapeHtml(JSON.stringify(request.body, null, 2))}</pre>
                    </div>
                `;
            }

            document.getElementById('request-panel').innerHTML = html;
        }

        // Display response
        function displayResponse(result) {
            const statusBadge = result.success
                ? `<span class="badge bg-success">HTTP ${result.http_code}</span>`
                : `<span class="badge bg-danger">HTTP ${result.http_code || 'Error'}</span>`;

            let content = '';
            if (result.error && !result.data) {
                content = `Error: ${escapeHtml(result.error)}`;
            } else {
                content = formatJson(result.data);
            }

            document.getElementById('response-content').innerHTML = `${statusBadge}\n\n${content}`;
        }

        // Display error
        function displayError(message) {
            document.getElementById('response-content').innerHTML =
                `<span class="badge bg-danger">Error</span>\n\n${escapeHtml(message)}`;
        }

        // Format JSON with syntax highlighting
        function formatJson(obj) {
            if (!obj) return 'null';

            const json = JSON.stringify(obj, null, 2);
            return json
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?)/g, function(match) {
                    let cls = 'json-string';
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    }
                    return '<span class="' + cls + '">' + match + '</span>';
                })
                .replace(/\b(true|false)\b/g, '<span class="json-bool">$1</span>')
                .replace(/\b(null)\b/g, '<span class="json-null">$1</span>')
                .replace(/\b(\d+)\b/g, '<span class="json-number">$1</span>');
        }

        // Update status badges
        function updateStatuses(action, result) {
            if (action === 'check_connection') {
                const statusEl = document.getElementById('status-connection');
                if (result.success && result.data?.is_active) {
                    statusEl.style.background = 'var(--success)';
                    statusEl.style.color = 'white';
                    statusEl.textContent = 'Connection: OK';
                } else {
                    statusEl.style.background = 'var(--danger)';
                    statusEl.style.color = 'white';
                    statusEl.textContent = 'Connection: Failed';
                }
            }

            if (action === 'activate' && result.success) {
                updateLicenseStatus(true);
            }

            if (action === 'deactivate' && result.success && result.data?.is_active) {
                updateLicenseStatus(false);
            }

            if (action === 'verify') {
                const isValid = result.success && result.data?.is_active;
                updateLicenseStatus(isValid);
            }
        }

        function updateConfigStatus(configured) {
            const statusEl = document.getElementById('status-config');
            if (configured) {
                statusEl.style.background = 'var(--success)';
                statusEl.style.color = 'white';
                statusEl.textContent = 'Config: Ready';
            } else {
                statusEl.style.background = 'var(--border)';
                statusEl.style.color = 'var(--text-muted)';
                statusEl.textContent = 'Config: Not Set';
            }
        }

        function updateLicenseStatus(active) {
            const statusEl = document.getElementById('status-license');
            if (active) {
                statusEl.style.background = 'var(--success)';
                statusEl.style.color = 'white';
                statusEl.textContent = 'License: Active';
            } else {
                statusEl.style.background = 'var(--border)';
                statusEl.style.color = 'var(--text-muted)';
                statusEl.textContent = 'License: None';
            }
        }

        // Clear configuration
        async function clearConfig() {
            if (!confirm('Clear all configuration and license data?')) return;

            const formData = new FormData();
            formData.append('action', 'clear_config');
            formData.append('csrf_token', csrfToken);

            try {
                await fetch('', { method: 'POST', body: formData });
                location.reload();
            } catch (error) {
                showToast('Failed to clear configuration', 'danger');
            }
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Copy response to clipboard
        function copyResponse() {
            if (!lastResponse) return;

            const text = JSON.stringify(lastResponse.data, null, 2);
            navigator.clipboard.writeText(text).then(() => {
                showToast('Copied to clipboard!', 'success');
            });
        }

        // Toggle dark/light theme
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme');
            html.setAttribute('data-bs-theme', current === 'dark' ? 'light' : 'dark');
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Check system dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        }
    </script>
</body>
</html>
