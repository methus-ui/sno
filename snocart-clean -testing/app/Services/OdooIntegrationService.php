<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Odoo Integration Service
 *
 * Provides XML-RPC communication with Odoo server for:
 * - Authentication
 * - CRUD operations on Odoo models
 * - Product/order synchronization
 */
class OdooIntegrationService
{
    protected $url;
    protected $db;
    protected $username;
    protected $password;
    public $uid;

    /**
     * Constructor
     *
     * @param string|null $database Odoo database name
     * @param string|null $username Odoo username
     * @param string|null $password Odoo password
     */
    public function __construct($database = null, $username = null, $password = null)
    {
        $this->url = config('odoo.url');
        $this->db = $database ?? config('odoo.database');
        $this->username = $username ?? config('odoo.username');
        $this->password = $password ?? config('odoo.password');
    }

    /**
     * Authenticate with Odoo
     *
     * @return int User ID
     * @throws \Exception
     */
    public function authenticate()
    {
        if ($this->uid) {
            return $this->uid;
        }

        try {
            $request = xmlrpc_encode_request('authenticate', [
                $this->db,
                $this->username,
                $this->password,
                []
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml',
                    'content' => $request,
                    'timeout' => config('odoo.api_timeout', 30)
                ]
            ]);

            $response = @file_get_contents(
                $this->url . '/xmlrpc/2/common',
                false,
                $context
            );

            if ($response === false) {
                throw new \Exception('Failed to connect to Odoo server');
            }

            $this->uid = xmlrpc_decode($response);

            if (!$this->uid || is_array($this->uid)) {
                throw new \Exception('Odoo authentication failed: ' . print_r($this->uid, true));
            }

            return $this->uid;

        } catch (\Exception $e) {
            Log::error('Odoo authentication failed', [
                'database' => $this->db,
                'username' => $this->username,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Call Odoo model method via XML-RPC
     *
     * @param string $model Model name (e.g., 'product.product', 'pos.order')
     * @param string $method Method name (e.g., 'search', 'create', 'write')
     * @param array $args Positional arguments
     * @param array $kwargs Keyword arguments
     * @return mixed Result from Odoo
     * @throws \Exception
     */
    public function call($model, $method, $args = [], $kwargs = [])
    {
        $this->authenticate();

        try {
            $request = xmlrpc_encode_request('execute_kw', [
                $this->db,
                $this->uid,
                $this->password,
                $model,
                $method,
                $args,
                $kwargs
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml',
                    'content' => $request,
                    'timeout' => config('odoo.api_timeout', 60)
                ]
            ]);

            $response = @file_get_contents(
                $this->url . '/xmlrpc/2/object',
                false,
                $context
            );

            if ($response === false) {
                throw new \Exception('Failed to call Odoo API');
            }

            $result = xmlrpc_decode($response);

            if (is_array($result) && isset($result['faultString'])) {
                throw new \Exception('Odoo API error: ' . $result['faultString']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Odoo API call failed', [
                'database' => $this->db,
                'model' => $model,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Search for records
     *
     * @param string $model Model name
     * @param array $domain Search domain
     * @param array $fields Fields to retrieve
     * @param int $limit Maximum number of records
     * @param int $offset Offset for pagination
     * @return array Records
     */
    public function search($model, $domain = [], $fields = [], $limit = 0, $offset = 0)
    {
        $kwargs = [];
        if (!empty($fields)) {
            $kwargs['fields'] = $fields;
        }
        if ($limit > 0) {
            $kwargs['limit'] = $limit;
        }
        if ($offset > 0) {
            $kwargs['offset'] = $offset;
        }

        return $this->call($model, 'search_read', [$domain], $kwargs);
    }

    /**
     * Create a record
     *
     * @param string $model Model name
     * @param array $data Record data
     * @return int Created record ID
     */
    public function create($model, $data)
    {
        return $this->call($model, 'create', [$data]);
    }

    /**
     * Update records
     *
     * @param string $model Model name
     * @param array $ids Record IDs
     * @param array $data Update data
     * @return bool Success
     */
    public function update($model, $ids, $data)
    {
        return $this->call($model, 'write', [$ids, $data]);
    }

    /**
     * Delete records
     *
     * @param string $model Model name
     * @param array $ids Record IDs
     * @return bool Success
     */
    public function delete($model, $ids)
    {
        return $this->call($model, 'unlink', [$ids]);
    }

    /**
     * Check if Odoo server is accessible
     *
     * @return bool
     */
    public function ping()
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5
                ]
            ]);

            $response = @file_get_contents($this->url . '/web/database/selector', false, $context);
            return $response !== false;

        } catch (\Exception $e) {
            return false;
        }
    }
}
