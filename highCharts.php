<?php
// charts.php
require_once 'config.php';

// ===== BOOKS AVAILABILITY =====
$sql = "SELECT title, total_copies, available_copies FROM books_info ORDER BY title";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$books = [];
while ($row = oci_fetch_assoc($stmt)) {
    $books[] = [
        'title' => $row['TITLE'],
        'total' => (int)$row['TOTAL_COPIES'],
        'available' => (int)$row['AVAILABLE_COPIES']
    ];
}

$titles = array_column($books, 'title');
$total_copies = array_column($books, 'total');
$available_copies = array_column($books, 'available');

// ===== MONTHLY BORROW =====
$sql = "SELECT TO_CHAR(borrow_date,'YYYY-MM') AS month, COUNT(*) AS total_borrows
        FROM borrow_records
        GROUP BY TO_CHAR(borrow_date,'YYYY-MM')
        ORDER BY month";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$months = [];
$totalBorrows = [];
while($row = oci_fetch_assoc($stmt)){
    $months[] = $row['MONTH'];
    $totalBorrows[] = (int)$row['TOTAL_BORROWS'];
}

oci_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Charts Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
</head>
<body class="bg-light">
<div class="container my-4">

<div class="card mb-4 shadow">
  <div class="card-header bg-info text-white"><h4>Books Availability Chart</h4></div>
  <div class="card-body">
    <div id="books_chart" style="width:100%; height:400px;"></div>
    <script>
      Highcharts.chart('books_chart', {
          chart: { type: 'column' },
          title: { text: 'Books Availability' },
          xAxis: { categories: <?php echo json_encode($titles); ?> },
          yAxis: { min:0, title:{text:'Copies'} },
          series: [
              { name:'Total Copies', data: <?php echo json_encode($total_copies); ?>, color:'#7cb5ec' },
              { name:'Available Copies', data: <?php echo json_encode($available_copies); ?>, color:'#90ed7d' }
          ]
      });
    </script>

    <div id="monthly_borrow_chart" style="height:400px; margin-top:50px;"></div>
    <script>
      Highcharts.chart('monthly_borrow_chart', {
          chart: { type: 'line' },
          title: { text: 'Monthly Borrow Count' },
          xAxis: { categories: <?php echo json_encode($months); ?>, title: { text: 'Month' } },
          yAxis: { min: 0, title: { text: 'Total Borrows' } },
          series: [{ name: 'Borrows', data: <?php echo json_encode($totalBorrows); ?>, color:'#f45b5b' }]
      });
    </script>
  </div>
</div>

</div>
</body>
</html>

