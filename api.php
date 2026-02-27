<?php

/**
 * License Manager API - Shared Helper Functions
 *
 * This file contains reusable functions for making API calls to the License Manager.
 * Used by both External and Internal API demo pages.
 */

/**
 * Make an API call to the License Manager server
 */
function callApi(string $method, string $endpoint, ?array $data = null, ?array $customConfig = null): array
{
    $config = $customConfig ?? ($_SESSION['config'] ?? []);

    if (empty($config['api_url']) || empty($config['api_key'])) {
        return [
            'success' => false,
            'http_code' => 0,
            'data' => null,
            'error' => 'API URL and API Key are required. Please configure first.',
        ];
    }

    $curl = curl_init();
    $url = rtrim($config['api_url'], '/') . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-API-KEY: ' . $config['api_key'],
        'X-API-URL: ' . getCurrentUrl(),
        'X-API-IP: ' . getClientIp(),
        'X-API-LANGUAGE: en',
    ];

    $curlOptions = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ];

    switch (strtoupper($method)) {
        case 'POST':
            $curlOptions[CURLOPT_POST] = true;
            if ($data) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            }

            break;
        case 'PUT':
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
            if ($data) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            }

            break;
        case 'DELETE':
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';

            break;
    }

    curl_setopt_array($curl, $curlOptions);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    $decodedResponse = json_decode($response, true);

    return [
        'success' => ! $error && $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data' => $decodedResponse,
        'error' => $error ?: null,
        'raw' => $response,
        'request' => [
            'method' => $method,
            'url' => $url,
            'headers' => array_map(function ($h) {
                // Mask API key in display
                if (strpos($h, 'X-API-KEY:') === 0) {
                    return 'X-API-KEY: ***...***';
                }

                return $h;
            }, $headers),
            'body' => $data,
        ],
    ];
}

/**
 * Get the current URL of this demo app
 */
function getCurrentUrl(): string
{
    $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/**
 * Get the client IP address
 */
function getClientIp(): string
{
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

    foreach ($headers as $header) {
        if (! empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '127.0.0.1';
}

/**
 * Check if configuration is complete
 */
function isConfigured(): bool
{
    $config = $_SESSION['config'] ?? [];

    return ! empty($config['api_url']) && ! empty($config['api_key']);
}

/**
 * Get a configuration value
 */
function getConfig(string $key, string $default = ''): string
{
    return $_SESSION['config'][$key] ?? $default;
}

/**
 * Sanitize output for HTML display
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ============================================================================
// External API Operations
// ============================================================================

/**
 * Check API connection
 */
function checkConnection(): array
{
    return callApi('GET', '/api/external/connection-check');
}

/**
 * Activate a license
 */
function activateLicense(): array
{
    $config = $_SESSION['config'] ?? [];

    if (empty($config['product_id']) || empty($config['license_code'])) {
        return [
            'success' => false,
            'http_code' => 0,
            'data' => null,
            'error' => 'Product ID and License Code are required for activation.',
        ];
    }

    $result = callApi('POST', '/api/external/license/activate', [
        'product_id' => $config['product_id'],
        'license_code' => $config['license_code'],
        'client_name' => $config['client_name'] ?? 'Demo User',
        'verify_type' => 'non_envato',
    ]);

    // Store license data if successful
    if ($result['success']) {
        $licenseData = $result['data']['lic_response']
            ?? $result['data']['data']['license_data']
            ?? null;

        if ($licenseData) {
            $_SESSION['license_data'] = $licenseData;
        }
    }

    return $result;
}

/**
 * Verify a license
 */
function verifyLicense(): array
{
    $config = $_SESSION['config'] ?? [];
    $licenseData = $_SESSION['license_data'] ?? '';

    if (empty($licenseData)) {
        return [
            'success' => false,
            'http_code' => 0,
            'data' => null,
            'error' => 'No license data found. Please activate a license first.',
        ];
    }

    return callApi('POST', '/api/external/license/verify', [
        'product_id' => $config['product_id'] ?? '',
        'license_data' => $licenseData,
    ]);
}

/**
 * Deactivate a license
 */
function deactivateLicense(): array
{
    $config = $_SESSION['config'] ?? [];
    $licenseData = $_SESSION['license_data'] ?? '';

    if (empty($licenseData)) {
        return [
            'success' => false,
            'http_code' => 0,
            'data' => null,
            'error' => 'No license data found. Please activate a license first.',
        ];
    }

    $result = callApi('POST', '/api/external/license/deactivate', [
        'product_id' => $config['product_id'] ?? '',
        'license_data' => $licenseData,
    ]);

    // Clear license data on successful deactivation
    if ($result['success'] && ($result['data']['is_active'] ?? false)) {
        unset($_SESSION['license_data']);
    }

    return $result;
}

// ============================================================================
// External API - Updates Operations
// ============================================================================

/**
 * Get latest version info for a product
 */
function getLatestVersion(string $productId): array
{
    return callApi('POST', '/api/external/update/latest', [
        'product_id' => $productId,
    ]);
}

/**
 * Check if update is available
 */
function checkUpdate(string $productId, string $currentVersion): array
{
    return callApi('POST', '/api/external/update/check', [
        'product_id' => $productId,
        'current_version' => $currentVersion,
    ]);
}

/**
 * Get update file size
 */
function getUpdateSize(string $versionId, string $type = 'main'): array
{
    return callApi('GET', '/api/external/update/' . urlencode($versionId) . '/download/' . urlencode($type) . '/size');
}

// ============================================================================
// Internal API Operations
// ============================================================================

/**
 * Check internal API connection
 */
function checkInternalConnection(): array
{
    return callApi('GET', '/api/internal/connection-check');
}

/**
 * List all products
 */
function listProducts(): array
{
    return callApi('GET', '/api/internal/products');
}

/**
 * Get product details
 */
function getProduct(string $productId): array
{
    return callApi('GET', '/api/internal/products/' . urlencode($productId));
}

/**
 * List all licenses
 */
function listLicenses(): array
{
    return callApi('GET', '/api/internal/product-licenses');
}

/**
 * Get license details
 */
function getLicense(string $licenseId): array
{
    return callApi('GET', '/api/internal/product-licenses/' . urlencode($licenseId));
}

/**
 * Create a new license
 */
function createLicense(array $data): array
{
    return callApi('POST', '/api/internal/product-licenses', $data);
}

/**
 * Block a license
 */
function blockLicense(string $licenseId): array
{
    return callApi('POST', '/api/internal/blocked-product-licenses/' . urlencode($licenseId));
}

/**
 * Unblock a license
 */
function unblockLicense(string $licenseId): array
{
    return callApi('DELETE', '/api/internal/blocked-product-licenses/' . urlencode($licenseId));
}
