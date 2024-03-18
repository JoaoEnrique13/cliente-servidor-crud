<?php
namespace App;
require "../vendor/autoload.php";
use App\Model\Cliente;
use App\Repository\ClienteRepository;

header("Access-Control-Allow-Origin: *"); //permite requisição de outros servidores
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); //métodos permitidos (OPTIONS)
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     exit(0);
// }

//  verifica qual foi o método da requisição
switch ($_SERVER['REQUEST_METHOD']) {
    // OPTIONS
    case 'OPTIONS':
        // Lista de métodos permitidos
        $allowed_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        http_response_code(200); // OK
        echo json_encode($allowed_methods);
        break;

    // POST
    case 'POST':
        $requiredFields = ['nome', 'email', 'cidade', 'estado'];
        $data = json_decode(file_get_contents("php://input")); //obtem dados
        
        if (!isValid($data, $requiredFields)) {
            http_response_code(400);
            echo json_encode(["error" => "Dados de entrada inválidos."]);
            break;
        }

        // cria novo cliente
        $cliente = new Cliente();
        
        $cliente->setNome($data->nome);
        $cliente->setEmail($data->email);
        $cliente->setCidade($data->cidade);
        $cliente->setEstado($data->estado);

        $repository = new ClienteRepository();
        $success = $repository->insert($cliente);
        
        if ($success) {
            http_response_code(201); //resposta de criado com sucesso
            echo json_encode(["message" => "Dados inseridos com sucesso."]);
        } else {
            http_response_code(500); //resposta de problemas internos com servidor
            echo json_encode(["message" => "Falha ao inserir dados."]);
        }
        break;

    // GET
    case 'GET':
        try{
            // instancia cliente
            $cliente = new Cliente();
            $repository = new ClienteRepository();

            // Se houver id, busca pelo id. Se não tiver id, busca todos
            if (isset($_GET['id'])) {
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); //id via GET


                if ($id === false) {
                    http_response_code(400); //erro do cliente
                    echo json_encode(['error' => 'O valor do ID fornecido não é um inteiro válido.']);
                    exit;
                } else {
                    $cliente = new Cliente();
                    $repository = new ClienteRepository();
                    $cliente->setClienteId($id);
                    $result = $repository->getById($cliente);//retorna cliente por id
                }
            } else
                $result = $repository->getAll(); //retorna todos se nao houver id

            if ($result) {
                http_response_code(200); // resposta ok
                echo json_encode($result);
            } else {
                http_response_code(404); //nao encontrado
                echo json_encode(["message" => "Nenhum dado encontrado."]);
            }
        }catch(Exception $error){
            http_response_code(500); //nao encontrado
            echo json_encode(["message" => "erro" + $error]);
        }

        break;

    // PUT
    case 'PUT':
        $data = json_decode(file_get_contents("php://input")); //recebe dados via PUT

        $requiredFields = ['nome', 'email', 'cidade', 'estado'];
        
        if (!isValid($data, $requiredFields)) {
            http_response_code(400);
            echo json_encode(["error" => "Dados de entrada inválidos."]);
            break;
        }


        $cliente = new Cliente();
        $repository = new ClienteRepository();
        
        $cliente->setNome($data->nome);
        $cliente->setEmail($data->email);
        $cliente->setCidade($data->cidade);
        $cliente->setEstado($data->estado);


        // Se cliente existir, atualiza
        // Se cliente nao existir, cria
        if(isset($data->cliente_id)){ //caso tenha id na requisição
            $cliente->setClienteId($data->cliente_id);

            // caso ache o cliente com id, atualiza
            if($repository->getById($cliente)){
                $success = $repository->update($cliente);
                
                if ($success) {
                    http_response_code(200); //resposta ok
                    echo json_encode(["message" => "Dados atualizados com sucesso."]);
                } else {
                    http_response_code(500); //resposta de problemas internos com servidor
                    echo json_encode(["message" => "Falha ao atualizar dados."]);
                }
            }else{ //caso nao ache, retorne erro 404
                http_response_code(404); //nao encontrado
                echo json_encode(["message" => "Falha ao atualizar, nenhum dado encontrado."]);
            }
        }
        else{ //caso nao tenha id, cria um novo
            $success = $repository->insert($cliente);
            
            if ($success) {
                http_response_code(200); //resposta ok
                echo json_encode(["message" => "Dados inseridos com sucesso."]);
            } else {
                http_response_code(500); //resposta de problemas internos com servidor
                echo json_encode(["message" => "Falha ao inserir dados."]);
            }
        }
        
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input")); //obtem dados
        $requiredFields  = ['id'];

        if (!isValid($data, $requiredFields)) {
            http_response_code(400);
            echo json_encode(["error" => "Dados de entrada inválidos."]);
            break;
        }

        $id = $data->id;

        // cria novo cliente
        $cliente = new Cliente();
        $cliente->setClienteId($id);

        $repository = new ClienteRepository();
        $result = $repository->getById($cliente);//retorna cliente por id

        if(!$result){
            http_response_code(404); //nao encontrado
            echo json_encode(["message" => "Nenhum dado encontrado."]);
        }
        
        $success = $repository->delete($cliente);

        if ($success) {
            http_response_code(200); //resposta ok
            echo json_encode(["message" => "Dados apagados com sucesso."]);
        } else {
            http_response_code(500); //resposta de problemas internos com servidor
            echo json_encode(["message" => "Falha ao apagar dados."]);
        }

        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Método não permitido."]);
        break;
}

function isValid($data, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($data->$field)) {
            return false;
        }
    }
    return true;
}
