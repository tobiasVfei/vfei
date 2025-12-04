<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../APICore.php';
require_once __DIR__ . '/../validators/Validator.php';

class BaseController
{
    private APICore $core;
    private array $rules;

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
                        throw new Exception("Ungültige ID.", 400);
                    }
                    $data = $this->getJsonInput();
                    $fields = Validator::validate($data, $this->rules);
                    $this->core->update($id, $fields);
                    break;

                case 'DELETE':
                    if ($id === false || $id <= 0) {
                        throw new Exception("Ungültige ID.", 400);
                    }
                    $this->core->delete($id);
                    break;

                default:
                    throw new Exception("Methode nicht erlaubt.", 405);
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    private function getJsonInput(): ?object
    {
        return json_decode(file_get_contents("php://input"));
    }

    private function sendError(Exception $e): void
    {
        $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
        http_response_code($code);
        echo json_encode(['message' => $e->getMessage()]);
        exit;
    }
}