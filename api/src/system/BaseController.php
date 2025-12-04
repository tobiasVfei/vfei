<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/APICore.php';
require_once __DIR__ . '/../validators/Validator.php';

/**
 * Abstract controller class that acts as a bridge between HTTP requests and the APICore logic.
 * It determines the request method (GET, POST, PUT, DELETE) and calls the appropriate APICore method.
 */
class BaseController
{
    /**
     * @var APICore Instance of the core logic handler.
     */
    private APICore $core;

    /**
     * @var array Validation rules for the specific entity managed by this controller.
     */
    private array $rules;

    /**
     * Initializes the controller with database connection and configuration.
     *
     * @param string $tableName The name of the database table.
     * @param string $idField The primary key column name.
     * @param array $rules Validation rules for creating/updating records.
     * @param bool $requiresAuth Whether this controller requires a valid JWT token.
     */
    public function __construct(string $tableName, string $idField, array $rules, bool $requiresAuth = true)
    {
        $this->rules = $rules;

        try {
            $pdo = Database::connect();
            $this->core = new APICore($pdo, $tableName, $idField, $requiresAuth);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Main entry point for handling incoming API requests.
     * Routes the request based on the HTTP method (GET, POST, PUT, DELETE).
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        try {
            switch ($requestMethod) {
                case 'GET':
                    if ($id !== false && $id > 0) {
                        $this->core->readById($id);
                    } else {
                        $this->core->readAll();
                    }
                    break;

                case 'POST':
                    $data = $this->getJsonInput();
                    $fields = Validator::validate($data, $this->rules);
                    $this->core->create($fields);
                    break;

                case 'PUT':
                    if ($id === false || $id <= 0) {
                        throw new Exception("Invalid ID provided.", 400);
                    }
                    $data = $this->getJsonInput();
                    $fields = Validator::validate($data, $this->rules);
                    $this->core->update($id, $fields);
                    break;

                case 'DELETE':
                    if ($id === false || $id <= 0) {
                        throw new Exception("Invalid ID provided.", 400);
                    }
                    $this->core->delete($id);
                    break;

                default:
                    throw new Exception("Method not allowed.", 405);
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Helper to retrieve and decode JSON input from the request body.
     *
     * @return object|null Decoded JSON object or null if failed.
     */
    private function getJsonInput(): ?object
    {
        return json_decode(file_get_contents("php://input"));
    }

    /**
     * Standardized error handler for exceptions thrown within the controller.
     *
     * @param Exception $e The caught exception.
     * @return void Exits script with JSON error response.
     */
    private function sendError(Exception $e): void
    {
        $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
        http_response_code($code);
        echo json_encode(['message' => $e->getMessage()]);
        exit;
    }
}