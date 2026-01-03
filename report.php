<?php
// ====== DATABASE CONNECTION ======
require_once 'config.php';

// ====== PER DAY FINE & OVERDUE LIMIT ======
$per_day_fine = 5;
$overdue_days_limit = 7;

// ================== 1️⃣ BOOK AVAILABILITY ==================
$sql = "SELECT b.title, a.name as author, b.total_copies, b.available_copies
        FROM books_info b
        JOIN author a ON b.author_id = a.author_id
        ORDER BY b.title";
$stmt = oci_parse($conn,$sql);
oci_execute($stmt);
$availability = [];
while($row = oci_fetch_assoc($stmt)){
    $row['STATUS'] = ($row['AVAILABLE_COPIES'] > 0) ? "Available" : "Not Available";
    $availability[] = $row;
}

// ================== 2️⃣ OVERDUE BOOKS ==================
$sql = "SELECT br.borrow_id, m.name as member_name, m.email, bk.title as book_title,
               TO_CHAR(br.borrow_date,'YYYY-MM-DD') as borrow_date,
               br.return_date
        FROM borrow_records br
        JOIN members m ON br.member_id = m.member_id
        JOIN books_info bk ON br.book_id = bk.book_id
        WHERE br.borrow_date < SYSDATE - :limit
        ORDER BY br.borrow_date";
$stmt = oci_parse($conn,$sql);
oci_bind_by_name($stmt, ":limit", $overdue_days_limit);
oci_execute($stmt);

$overdue = [];
while($row = oci_fetch_assoc($stmt)) {
    $borrow_date = strtotime($row['BORROW_DATE']);
    $return_date = isset($row['RETURN_DATE']) && $row['RETURN_DATE'] != '' ? strtotime($row['RETURN_DATE']) : time();

    $days_overdue = floor(($return_date - $borrow_date)/86400) - $overdue_days_limit;
    $row['OVERDUE_DAYS'] = $days_overdue > 0 ? $days_overdue : 0;
    $row['FINE'] = $days_overdue > 0 ? $days_overdue * $per_day_fine : 0;

    if($row['OVERDUE_DAYS'] > 0) $overdue[] = $row;
}

// ================== 3️⃣ MONTHLY BORROW ==================
$sql = "SELECT TO_CHAR(borrow_date,'YYYY-MM') as month, COUNT(*) as borrow_count
        FROM borrow_records
        GROUP BY TO_CHAR(borrow_date,'YYYY-MM')
        ORDER BY month";
$stmt = oci_parse($conn,$sql);
oci_execute($stmt);
$monthly = [];
while($row = oci_fetch_assoc($stmt)){
    $monthly[] = ['month'=>$row['MONTH'], 'count'=>$row['BORROW_COUNT']];
}

oci_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ====== NAVBAR ====== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="#">Library System</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="libraryUp.php">Add Data</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="highCharts.php">Visual Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="adminDashboard.php">Admin Dashboard</a></li>
      </ul>
    </div>
 </div>
</nav>

<div class="container">

<!-- ====== PRINT BUTTON ====== -->
<div class="mb-3 text-end">
    <button class="btn btn-success" onclick="printReport()">🖨️ Print Full Report</button>
</div>

<!-- ====== BOOTSTRAP TABS ====== -->
<ul class="nav nav-tabs" id="reportTab" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#availability">Book Availability</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#overdue">Overdue Books</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#monthly">Monthly Borrow</button>
  </li>
</ul>

<div class="tab-content mt-3">

<!-- ====== 1️⃣ Book Availability ====== -->
<div class="tab-pane fade show active" id="availability">
    <h4>📗 Book Availability</h4>
    <table class="table table-bordered table-striped bg-white shadow-sm">
        <thead class="table-success">
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Total</th>
                <th>Available</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($availability as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b['TITLE']) ?></td>
                <td><?= htmlspecialchars($b['AUTHOR']) ?></td>
                <td><?= $b['TOTAL_COPIES'] ?></td>
                <td><?= $b['AVAILABLE_COPIES'] ?></td>
                <td style="color:<?= $b['STATUS']=='Available'?'green':'red' ?>; font-weight:bold;"><?= $b['STATUS'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ====== 2️⃣ Overdue Books ====== -->
<div class="tab-pane fade" id="overdue">
    <h4>📌 Overdue Books (After 7 Days)</h4>
    <?php if(count($overdue)==0): ?>
        <p class="text-success">No overdue books!</p>
    <?php else: ?>
    <table class="table table-bordered table-striped bg-white shadow-sm">
        <thead class="table-danger">
            <tr>
                <th>Member</th>
                <th>Email</th>
                <th>Book</th>
                <th>Borrow Date</th>
                <th>Overdue Days</th>
                <th>Fine (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($overdue as $o): ?>
            <tr style="color:red;">
                <td><?= htmlspecialchars($o['MEMBER_NAME']) ?></td>
                <td><?= htmlspecialchars($o['EMAIL']) ?></td>
                <td><?= htmlspecialchars($o['BOOK_TITLE']) ?></td>
                <td><?= $o['BORROW_DATE'] ?></td>
                <td><?= $o['OVERDUE_DAYS'] ?></td>
                <td>₹ <?= $o['FINE'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ====== 3️⃣ Monthly Borrow ====== -->
<div class="tab-pane fade" id="monthly">
    <h4>📊 Monthly Borrow</h4>
    <table class="table table-bordered table-striped bg-white shadow-sm mb-3">
        <thead class="table-info">
            <tr>
                <th>Month</th>
                <th>Total Borrows</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($monthly as $m): ?>
            <tr>
                <td><?= $m['month'] ?></td>
                <td><?= $m['count'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div> <!-- tab-content -->
</div> <!-- container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ====== Print Full Report ======
function printReport() {
    const navbar = document.querySelector('nav.navbar');
    const buttons = document.querySelectorAll('button');
    navbar.style.display = 'none';
    buttons.forEach(btn => btn.style.display = 'none');

    // Show all tab contents
    const tabs = document.querySelectorAll('.tab-pane');
    tabs.forEach(tab => tab.classList.add('show','active'));

    window.print();

    navbar.style.display = '';
    buttons.forEach(btn => btn.style.display = '');
}
</script>

</body>
</html>

