<?php
require '../vendor/autoload.php';
require_once '../connection.php';

$app = new \Slim\Slim();

$app->get('/', function ()  {
  $sql = "SELECT * FROM boards";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();
    $stmt = $db->query($sql);
    $boards = $stmt->fetchAll(PDO::FETCH_OBJ);
    $db = null;
    echo json_encode($boards);
  } catch(PDOException $e) {
  echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->get('/:mac_address', function ($mac_address)  {
  $sql = "SELECT * FROM boards WHERE mac_address=:mac_address";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("mac_address", $mac_address);
    $stmt->execute();
    $board = $stmt->fetchObject();
    $db = null;
    echo json_encode($board);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->post('/', function () {
  $request = \Slim\Slim::getInstance()->request();
  $body = $request->getBody();
  $board = json_decode($body);
  $sql = "INSERT INTO boards (mac_address,cpf_user) VALUES (:mac_address,:cpf_user)";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    $stmt = $db->prepare($sql);
    $stmt->bindParam("mac_address", $board->mac_address);
    $stmt->bindParam("cpf_user", $board->cpf_user);
    $stmt->execute();
    $db = null;
    echo json_encode($board);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->put('/:mac_address', function ($mac_address) {
  $request = \Slim\Slim::getInstance()->request();
  $body = $request->getBody();
  $board = json_decode($body);
  $board->mac_address = $mac_address;

  $sql = "UPDATE boards SET cpf_user=:cpf_user WHERE mac_address=:mac_address";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    $stmt = $db->prepare($sql);
    $stmt->bindParam("mac_address", $board->mac_address);
    $stmt->bindParam("cpf_user", $board->cpf_user);
    $stmt->execute();
    $db = null;
    echo json_encode($board);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->delete('/:mac_address', function($mac_address) {
  $sql = "DELETE FROM boards WHERE mac_address=:mac_address";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    $stmt = $db->prepare($sql);
    $stmt->bindParam("mac_address", $mac_address);
    $stmt->execute();
    $db = null;
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->run();