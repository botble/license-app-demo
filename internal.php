<?php

/**
 * License Manager API Demo - Internal API
 *
 * This demo showcases the Internal API for server-to-server product and license management.
 * Deploy to: license-app-demo.botble.com
 */

session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/api.php';

// Default demo credentials
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
            echo json_encode(checkInternalConnection());
            exit;

        case 'list_products':
            $keyword = trim($_POST['keyword'] ?? '');
            echo json_encode(listProducts($keyword));
            exit;

        case 'get_product':
            $productId = trim($_POST['product_id'] ?? '');
            if (empty($productId)) {
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                exit;
            }
            echo json_encode(getProduct($productId));
            exit;

        case 'list_licenses':
            $keyword = trim($_POST['keyword'] ?? '');
            echo json_encode(listLicenses($keyword));
            exit;

        case 'get_license':
            $licenseId = trim($_POST['license_id'] ?? '');
            if (empty($licenseId)) {
                echo json_encode(['success' => false, 'error' => 'License ID is required']);
                exit;
            }
            echo json_encode(getLicense($licenseId));
            exit;

        case 'create_license':
            $data = [
                'product_id' => trim($_POST['new_product_id'] ?? ''),
                'parallel_uses' => (int) ($_POST['parallel_uses'] ?? 1),
            ];

            if (! empty($_POST['new_license_code'])) {
                $data['license_code'] = trim($_POST['new_license_code']);
            }
            if (! empty($_POST['new_client'])) {
                $data['client'] = trim($_POST['new_client']);
            }
            if (! empty($_POST['new_client_email'])) {
                $data['client_email'] = trim($_POST['new_client_email']);
            }

            if (empty($data['product_id'])) {
                echo json_encode(['success' => false, 'error' => 'Product ID is required']);
                exit;
            }

            echo json_encode(createLicense($data));
            exit;

        case 'block_license':
            $licenseId = trim($_POST['license_id'] ?? '');
            if (empty($licenseId)) {
                echo json_encode(['success' => false, 'error' => 'License ID is required']);
                exit;
            }
            echo json_encode(blockLicense($licenseId));
            exit;

        case 'unblock_license':
            $licenseId = trim($_POST['license_id'] ?? '');
            if (empty($licenseId)) {
                echo json_encode(['success' => false, 'error' => 'License ID is required']);
                exit;
            }
            echo json_encode(unblockLicense($licenseId));
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
}

// Get current config (session or defaults)
$config = $_SESSION['config'] ?? $defaultConfig;
$isConfigured = ! empty($config['api_url']) && ! empty($config['api_key']);
$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title>License Manager API Demo - Internal API</title>
    <link rel="icon" type="image/png" href="favicon.png">
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
            --code-bg: #F1F5F9;
        }

        [data-bs-theme="dark"] {
            --bg: #0F172A;
            --card-bg: #1E293B;
            --text: #E2E8F0;
            --text-muted: #94A3B8;
            --border: #334155;
            --code-bg: #0F172A;
            --json-key: #A78BFA;
            --json-string: #34D399;
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
            max-width: 1200px;
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
            background: var(--card-bg);
            color: var(--text);
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
            background: var(--code-bg);
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
            max-height: 500px;
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
            background: var(--code-bg);
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-family: 'JetBrains Mono', monospace;
        }

        .operation-section {
            border-left: 2px solid var(--border);
            padding-left: 1rem;
            margin-bottom: 1.25rem;
        }

        .operation-section:last-child {
            margin-bottom: 0;
        }

        .operation-section h6 {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
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
            <p>Test Internal API for server-to-server management</p>
        </div>

        <!-- Navigation -->
        <nav class="nav-tabs-custom">
            <a href="index.php">External API</a>
            <a href="internal.php" class="active">Internal API</a>
        </nav>

        <!-- Instructions -->
        <div class="card" style="background: #F0FDF4; border-color: #BBF7D0;">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-6">
                        <h6 style="font-size: 0.8125rem; font-weight: 600; color: #166534; margin-bottom: 0.5rem;">Getting Started</h6>
                        <ol style="font-size: 0.8125rem; color: #15803D; margin: 0; padding-left: 1.25rem;">
                            <li>Enter your License Manager <strong>API URL</strong></li>
                            <li>Add your <strong>Internal API Key</strong> (must be "Internal" type)</li>
                            <li>Click <strong>Test Connection</strong> to verify</li>
                            <li>Explore products and licenses management</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 style="font-size: 0.8125rem; font-weight: 600; color: #166534; margin-bottom: 0.5rem;">Internal vs External API</h6>
                        <ul style="font-size: 0.8125rem; color: #15803D; margin: 0; padding-left: 1.25rem;">
                            <li><strong>External</strong>: For client apps (activate/verify/deactivate)</li>
                            <li><strong>Internal</strong>: For your backend (create licenses, manage products)</li>
                            <li>Internal API has full CRUD access - keep key secure!</li>
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
                        <span id="status-apikey" class="badge" style="background: <?= $isConfigured ? 'var(--border)' : '#FEF3C7' ?>; color: <?= $isConfigured ? 'var(--text-muted)' : '#92400E' ?>; <?= $isConfigured ? 'display:none' : '' ?>">
                            Internal API Key Required
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <!-- Left Column: Config + Create License -->
            <div class="col-lg-4">
                <!-- Configuration Card -->
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
                                <input type="url" class="form-control form-control-sm" name="api_url"
                                       value="<?= e($config['api_url']) ?>"
                                       placeholder="https://license.botble.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Internal API Key <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" name="api_key" id="api_key"
                                           value="<?= e($config['api_key']) ?>"
                                           placeholder="Internal API Key" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('api_key')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem">Must be an Internal type API key</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-save me-1"></i>Save
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Create License Card -->
                <div class="card">
                    <div class="card-header">
                        <span>Create License</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label">Product Reference ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="new_product_id"
                                   placeholder="e.g. BOTBLE-CMS">
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm" id="new_license_code"
                                   placeholder="License Code (auto-generate if empty)">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm" id="new_client"
                                       placeholder="Client Name">
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm" id="parallel_uses"
                                       placeholder="Uses" value="1" min="1">
                            </div>
                        </div>
                        <div class="mb-2">
                            <input type="email" class="form-control form-control-sm" id="new_client_email"
                                   placeholder="Client Email">
                        </div>
                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="createNewLicense()">
                            <i class="bi bi-plus-lg me-1"></i>Create License
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Operations + Request/Response -->
            <div class="col-lg-8">
                <!-- Operations Card -->
                <div class="card">
                    <div class="card-header">API Operations</div>
                    <div class="card-body">
                        <!-- Connection -->
                        <div class="operation-section">
                            <h6>Connection</h6>
                            <button type="button" class="btn btn-operation btn-sm"
                                    onclick="callOperation('check_connection')">
                                <i class="bi bi-plug me-1"></i>Test Connection
                            </button>
                        </div>

                        <!-- Products -->
                        <div class="operation-section">
                            <h6>Products</h6>
                            <div class="d-flex gap-2 mb-2">
                                <div class="input-group input-group-sm flex-grow-1">
                                    <input type="text" class="form-control" id="product_keyword"
                                           placeholder="Search products...">
                                    <button type="button" class="btn btn-operation" onclick="searchProducts()">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-operation btn-sm"
                                        onclick="callOperation('list_products')">
                                    List All
                                </button>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="product_id_input"
                                       placeholder="UUID or Reference ID">
                                <button type="button" class="btn btn-operation" onclick="getProductDetails()">
                                    Get
                                </button>
                            </div>
                        </div>

                        <!-- Licenses -->
                        <div class="operation-section">
                            <h6>Licenses</h6>
                            <div class="d-flex gap-2 mb-2">
                                <div class="input-group input-group-sm flex-grow-1">
                                    <input type="text" class="form-control" id="license_keyword"
                                           placeholder="Search by license code...">
                                    <button type="button" class="btn btn-operation" onclick="searchLicenses()">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <button type="button" class="btn btn-operation btn-sm"
                                        onclick="callOperation('list_licenses')">
                                    List All
                                </button>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control" id="license_id_input"
                                       placeholder="UUID (ID or License Code)">
                                <button type="button" class="btn btn-operation" onclick="getLicenseDetails()">
                                    Get
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-operation btn-sm flex-fill" onclick="blockLicenseById()">
                                    <i class="bi bi-lock me-1"></i>Block
                                </button>
                                <button type="button" class="btn btn-operation btn-sm flex-fill" onclick="unblockLicenseById()">
                                    <i class="bi bi-unlock me-1"></i>Unblock
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Panel -->
                <div class="card">
                    <div class="card-header">Request</div>
                    <div class="card-body p-0">
                        <div class="request-panel p-3" id="request-panel" style="max-height: 150px; overflow: auto;">
                            <span style="color: var(--text-muted)">Click an operation to see the request...</span>
                        </div>
                    </div>
                </div>

                <!-- Response Panel -->
                <div class="card">
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
                <span>FAQ & API Reference</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="card-body" id="faq-content">
                <div class="row">
                    <div class="col-md-6">
                        <p style="font-size: 0.8125rem; margin-bottom: 0.75rem;"><strong>API Operations</strong></p>
                        <ul style="font-size: 0.8125rem; color: var(--text-muted); padding-left: 1.25rem;">
                            <li><strong>List Products</strong> - Get all products in your account</li>
                            <li><strong>Get Product</strong> - Fetch single product details by ID</li>
                            <li><strong>List Licenses</strong> - Get all licenses (paginated)</li>
                            <li><strong>Get License</strong> - Fetch single license by ID</li>
                            <li><strong>Create License</strong> - Generate new license for a product</li>
                            <li><strong>Block/Unblock</strong> - Disable or re-enable a license</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <p style="font-size: 0.8125rem; margin-bottom: 0.75rem;"><strong>Common Issues</strong></p>
                        <ul style="font-size: 0.8125rem; color: var(--text-muted); padding-left: 1.25rem;">
                            <li><strong>401 Unauthorized</strong> - API key is invalid or not "Internal" type</li>
                            <li><strong>404 Not Found</strong> - Product or license ID doesn't exist</li>
                            <li><strong>422 Validation Error</strong> - Missing required fields (check response)</li>
                            <li><strong>License code exists</strong> - Use auto-generate or pick unique code</li>
                        </ul>
                        <p style="font-size: 0.8125rem; margin-bottom: 0.5rem; margin-top: 1rem;"><strong>ID Types</strong></p>
                        <ul style="font-size: 0.8125rem; color: var(--text-muted); padding-left: 1.25rem;">
                            <li><strong>Product</strong> - Get by UUID or Reference ID (e.g. BOTBLE-CMS)</li>
                            <li><strong>License</strong> - Get/Block/Unblock by UUID (ID or license code)</li>
                            <li><strong>Create</strong> - Requires Product Reference ID</li>
                            <li><strong>Search</strong> - Licenses searched by license code</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <a href="https://docs.botble.com/license-manager" target="_blank">Documentation</a>
            <span class="mx-2">&middot;</span>
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
        async function callOperation(action, extraData = {}) {
            setLoading(true);

            const formData = new FormData();
            formData.append('action', action);
            formData.append('csrf_token', csrfToken);

            for (const [key, value] of Object.entries(extraData)) {
                formData.append(key, value);
            }

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
                setLoading(false);
            }
        }

        function setLoading(loading) {
            document.querySelectorAll('.btn-operation, .btn-primary').forEach(btn => {
                btn.classList.toggle('btn-loading', loading);
                btn.disabled = loading;
            });
        }

        // Search products
        function searchProducts() {
            const keyword = document.getElementById('product_keyword').value.trim();
            callOperation('list_products', { keyword });
        }

        // Search licenses
        function searchLicenses() {
            const keyword = document.getElementById('license_keyword').value.trim();
            callOperation('list_licenses', { keyword });
        }

        // Enter key support for search inputs
        document.getElementById('product_keyword').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); searchProducts(); }
        });
        document.getElementById('license_keyword').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); searchLicenses(); }
        });

        // Get product details
        function getProductDetails() {
            const productId = document.getElementById('product_id_input').value.trim();
            if (!productId) {
                showToast('Please enter a Product ID', 'warning');
                return;
            }
            callOperation('get_product', { product_id: productId });
        }

        // Get license details
        function getLicenseDetails() {
            const licenseId = document.getElementById('license_id_input').value.trim();
            if (!licenseId) {
                showToast('Please enter a License ID', 'warning');
                return;
            }
            callOperation('get_license', { license_id: licenseId });
        }

        // Block license
        function blockLicenseById() {
            const licenseId = document.getElementById('license_id_input').value.trim();
            if (!licenseId) {
                showToast('Please enter a License ID', 'warning');
                return;
            }
            callOperation('block_license', { license_id: licenseId });
        }

        // Unblock license
        function unblockLicenseById() {
            const licenseId = document.getElementById('license_id_input').value.trim();
            if (!licenseId) {
                showToast('Please enter a License ID', 'warning');
                return;
            }
            callOperation('unblock_license', { license_id: licenseId });
        }

        // Create new license
        function createNewLicense() {
            const productId = document.getElementById('new_product_id').value.trim();
            if (!productId) {
                showToast('Product ID is required', 'warning');
                return;
            }

            callOperation('create_license', {
                new_product_id: productId,
                new_license_code: document.getElementById('new_license_code').value.trim(),
                new_client: document.getElementById('new_client').value.trim(),
                new_client_email: document.getElementById('new_client_email').value.trim(),
                parallel_uses: document.getElementById('parallel_uses').value || 1
            });
        }

        // Display request details
        function displayRequest(request) {
            if (!request) {
                document.getElementById('request-panel').innerHTML =
                    '<span style="color: var(--text-muted)">No request data available</span>';
                return;
            }

            let html = `
                <div class="mb-2">
                    <span class="badge bg-primary">${request.method}</span>
                    <code class="ms-2" style="word-break: break-all;">${escapeHtml(request.url)}</code>
                </div>
                <div class="mb-2">
                    <strong style="font-size: 0.75rem; color: var(--text-muted);">Headers:</strong>
                    <ul class="mb-0 ps-3" style="font-size: 0.75rem;">
                        ${request.headers.map(h => `<li><code>${escapeHtml(h)}</code></li>`).join('')}
                    </ul>
                </div>
            `;

            if (request.body) {
                html += `<pre class="mb-0 p-2 rounded" style="font-size: 0.75rem; background: var(--card-bg); border: 1px solid var(--border);">${escapeHtml(JSON.stringify(request.body, null, 2))}</pre>`;
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
                if (result.success) {
                    statusEl.style.background = 'var(--success)';
                    statusEl.style.color = 'white';
                    statusEl.textContent = 'Connection: OK';
                } else {
                    statusEl.style.background = 'var(--danger)';
                    statusEl.style.color = 'white';
                    statusEl.textContent = 'Connection: Failed';
                }
            }
        }

        function updateConfigStatus(configured) {
            const statusEl = document.getElementById('status-config');
            const apikeyEl = document.getElementById('status-apikey');
            if (configured) {
                statusEl.style.background = 'var(--success)';
                statusEl.style.color = 'white';
                statusEl.textContent = 'Config: Ready';
                apikeyEl.style.display = 'none';
            } else {
                statusEl.style.background = 'var(--border)';
                statusEl.style.color = 'var(--text-muted)';
                statusEl.textContent = 'Config: Not Set';
                apikeyEl.style.display = '';
            }
        }

        // Clear configuration
        async function clearConfig() {
            if (!confirm('Clear all configuration?')) return;

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

        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            toast.style.cssText = 'z-index: 9999; font-size: 0.875rem; padding: 0.75rem 1rem; animation: fadeIn 0.2s;';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        }
    </script>
</body>
</html>
