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

$app->get('/daily/:cpf/:year/:month', function ($cpf,$year,$month)  {
  $data = new stdClass();
  $data->categories = array();
  $data->series = array();

  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    //Get total for previous month
    $sql = "SELECT max(water_flow) as total from history h join boards b on
    b.mac_address = h.mac_address where b.cpf_user=:cpf
    and extract(MONTH FROM time_register) =:month and extract(YEAR FROM time_register) =:year";
    $stmt = $db->prepare($sql);
    $stmt->bindParam("cpf",$cpf);
    $previousMonth = $month -1;
    $stmt->bindParam("month",$previousMonth);
    if($previousMonth == 12) {
      $stmt->bindParam("year",$year-1);
    }else{
      $stmt->bindParam("year",$year);
    }
    $stmt->execute();
    $result = $stmt->fetchObject();
    if($result->total == null){
      $result->total = 0;
    }

    $lastTotal = $result->total;

    $sql = "SELECT max(water_flow) as total from history h join boards b on
    b.mac_address = h.mac_address where b.cpf_user=:cpf and extract(DAY FROM time_register) =:day
    and extract(MONTH FROM time_register) =:month and extract(YEAR FROM time_register) =:year";

    $lastDay = date("t", mktime(0,0,0,$month,'01',$year)); // get the last day from month
    for ($day=1; $day <= $lastDay; $day++) {

      $stmt = $db->prepare($sql);
      $stmt->bindParam("cpf",$cpf);
      $stmt->bindParam("day",$day);
      $stmt->bindParam("month",$month);
      $stmt->bindParam("year",$year);
      $stmt->execute();
      $result = $stmt->fetchObject();
      if($result->total == null){
        $result->total = $lastTotal;
      }

      $total = $result->total - $lastTotal;
      $lastTotal = $result->total;
      array_push($data->categories, $day);
      array_push($data->series, $total);

    }
    $db = null;

    echo json_encode($data);

  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->get('/lastYear/:cpf', function ($cpf)  {
  $data = new stdClass();
  $data->categories = array();
  $data->series = array();

  try {
    $conn = new Connection();
    $db = $conn->getConnection();

    $sql = "SELECT max(water_flow) as total from history h join boards b on
    b.mac_address = h.mac_address where b.cpf_user=:cpf
    and extract(MONTH FROM time_register) =:month and extract(YEAR FROM time_register) =:year";

    for ($i=13; $i > 0; $i--) {
      $year = date('Y', strtotime( -$i.' month'));
      $month = date('m', strtotime( -$i.' month'));

      $stmt = $db->prepare($sql);
      $stmt->bindParam("cpf",$cpf);
      $stmt->bindParam("month",$month);
      $stmt->bindParam("year",$year);
      $stmt->execute();
      $result = $stmt->fetchObject();

      if($result->total == null){
        $result->total = 0;
      }

      if($i == 13){
        $lastTotal = $result->total;
      }else{
        $total = $result->total - $lastTotal;
        $lastTotal = $result->total;
        array_push($data->categories, date('M-Y', strtotime( -$i.' month')));
        array_push($data->series, $total);
      }
    }
    $db = null;

    echo json_encode($data);

  } catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
  }
});

$app->run();
