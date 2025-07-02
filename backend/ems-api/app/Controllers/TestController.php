<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class TestController extends ResourceController
{
    protected $format = 'json';

    /**
     * Display all data from all tables in JSON format
     * This is for testing and debugging purposes only
     */
    public function index()
    {
        try {
            $db = \Config\Database::connect();
            
            $data = [];
            
            // Get all tables in the database
            $tables = $db->listTables();
            
            foreach ($tables as $table) {
                try {
                    // Get all data from each table
                    $query = $db->table($table)->get();
                    $data[$table] = $query->getResultArray();
                    
                    // Add count information
                    $data[$table . '_count'] = count($data[$table]);
                } catch (\Exception $e) {
                    $data[$table . '_error'] = $e->getMessage();
                }
            }
            
            // Add database info
            $data['_database_info'] = [
                'database_name' => $db->getDatabase(),
                'platform' => $db->getPlatform(),
                'version' => $db->getVersion(),
                'total_tables' => count($tables),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            return $this->respond([
                'status' => 'success',
                'message' => 'All database data retrieved successfully',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to retrieve database data',
                'error' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get specific table data
     */
    public function table($tableName = null)
    {
        if (!$tableName) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Table name is required'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Check if table exists
            if (!$db->tableExists($tableName)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => "Table '{$tableName}' does not exist"
                ], ResponseInterface::HTTP_NOT_FOUND);
            }
            
            // Get table data
            $query = $db->table($tableName)->get();
            $data = $query->getResultArray();
            
            // Get table structure
            $fields = $db->getFieldData($tableName);
            
            return $this->respond([
                'status' => 'success',
                'message' => "Data from table '{$tableName}' retrieved successfully",
                'data' => [
                    'table_name' => $tableName,
                    'row_count' => count($data),
                    'structure' => $fields,
                    'data' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => "Failed to retrieve data from table '{$tableName}'",
                'error' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Test database connection
     */
    public function connection()
    {
        try {
            $db = \Config\Database::connect();
            
            // Test the connection
            $db->reconnect();
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Database connection successful',
                'data' => [
                    'database_name' => $db->getDatabase(),
                    'platform' => $db->getPlatform(),
                    'version' => $db->getVersion(),
                    'hostname' => $db->hostname,
                    'port' => $db->port,
                    'username' => $db->username,
                    'connected' => true,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
