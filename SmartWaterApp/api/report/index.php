<?php
require '../vendor/autoload.php';
require_once '../connection.php';

//include ("../../session/session.php");

$app = new \Slim\Slim();

$app->get('/totalByUser/:cpf', function ($cpf)  {
  $sql = "SELECT max(water_flow) as total from history h join boards b
  on b.mac_address = h.mac_address where b.cpf_user=:cpf";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindParam("cpf", $cpf);
    $stmt->execute();
    $total = $stmt->fetchObject();
    $db = null;
    echo json_encode($total);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->get('/monthTotalByUser/:cpf', function ($cpf)  {
  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    $sql = "SELECT max(water_flow) as total from history h join boards b
    on b.mac_address = h.mac_address where b.cpf_user=:cpf";
    $stmt = $db->prepare($sql);
    $stmt->bindParam("cpf", $cpf);
    $stmt->execute();
    $monthTotal = $stmt->fetchObject();

    $sql = "SELECT max(water_flow) as total from history h join boards b on
    b.mac_address = h.mac_address where b.cpf_user=:cpf
    and extract(MONTH FROM time_register) =:month and extract(YEAR FROM time_register) =:year";

    $month = date('m',strtotime('-1 month'));
    if($month == 12) {
      $year = date('Y',strtotime('-1 year'));
    } else {
      $year = date('Y');
    }

    $stmt = $db->prepare($sql);
    $stmt->bindParam("cpf",$cpf);
    $stmt->bindParam("month",$month);
    $stmt->bindParam("year",$year);
    $stmt->execute();
    $beforeMonthTotal = $stmt->fetchObject();
    $db = null;

    $total['total'] = ($monthTotal->total) - ($beforeMonthTotal->total);
    echo json_encode($total);

  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->get('/generalAllUsers/', function ()  {
  $sql = "SELECT sum(max) as total from (SELECT max(water_flow) from history group by history.mac_address) as totalwater";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $total = $stmt->fetchObject();
    $db = null;
    echo json_encode($total);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->get('/totalAllUsers/', function ()  {
  $sql = "SELECT sum(max) as total from (SELECT max(water_flow) from history where
  extract(YEAR FROM time_register)=:year group by history.mac_address) as totalwater";
  try {
    $conn = new Connection();
    $db = $conn->getConnection();
    $year = date('Y');
    $stmt = $db->prepare($sql);
    $stmt->bindParam("year", $year);
    $stmt->execute();
    $total = $stmt->fetchObject();
    $db = null;
    echo json_encode($total);
  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->get('/monthTotalAllUsers/', function ()  {
  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    $sql = "SELECT sum(max) as total from (SELECT max(water_flow) from history where
    extract(YEAR FROM time_register)=:year group by history.mac_address) as totalwater";
    $conn = new Connection();
    $db = $conn->getConnection();
    $year = date('Y');
    $stmt = $db->prepare($sql);
    $stmt->bindParam("year", $year);
    $stmt->execute();
    $yearTotal = $stmt->fetchObject();

    $sql = "SELECT sum(max) as total from (SELECT max(water_flow) from history where extract(MONTH FROM time_register)=:month
            and extract(YEAR FROM time_register)=:year group by history.mac_address) as totalwater";

    $month = date('m',strtotime('-1 month'));
    if($month == 12) {
      $year = date('Y',strtotime('-1 year'));
    } else {
      $year = date('Y');
    }

    $stmt = $db->prepare($sql);
    $stmt->bindParam("month",$month);
    $stmt->bindParam("year",$year);
    $stmt->execute();
    $beforeMonthTotal = $stmt->fetchObject();
    $db = null;

    $total['total'] = ($yearTotal->total) - ($beforeMonthTotal->total);
    echo json_encode($total);

  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->run();