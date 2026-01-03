<?php
require_once 'config.php';



// ================= OVERDUE LOGIC =================
$overdue_days_limit = 7; // changed from 14 to 7
$fine_per_day = 5;

$sql = "SELECT br.borrow_id, m.name AS member_name, m.email, b.title AS book_title,
       br.borrow_date, br.return_date
FROM borrow_records br
JOIN members m ON m.member_id = br.member_id
JOIN books_info b ON b.book_id = br.book_id
WHERE br.borrow_date < SYSDATE - :limit
ORDER BY br.borrow_date";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":limit", $overdue_days_limit);
oci_execute($stmt);

$overdues = [];
while ($r = oci_fetch_assoc($stmt)) {
    $borrow_date = strtotime($r['BORROW_DATE']);
    $return_date = isset($r['RETURN_DATE']) ? strtotime($r['RETURN_DATE']) : time();

    $overdue_days = floor(($return_date - $borrow_date)/86400) - $overdue_days_limit;
    $fine_amount = $overdue_days > 0 ? $overdue_days * $fine_per_day : 0;

    $r['OVERDUE_DAYS'] = $overdue_days > 0 ? $overdue_days : 0;
    $r['FINE_AMOUNT'] = $fine_amount;
    $r['SHOW_ACTION'] = !$r['RETURN_DATE'] && $overdue_days > 0; // only show buttons if book not returned

    if($r['OVERDUE_DAYS'] > 0) $overdues[] = $r;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ===== NAVIGATION BAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="libraryUp.php">Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="libraryUp.php">📥 Add Library Data</a></li>
        <li class="nav-item"><a class="nav-link" href="report.php">📊 Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="highCharts.php">📊Visual Reports</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
<h2 class="text-primary mb-4">🏢 Library Admin Dashboard</h2>
<a href="report.php" class="btn btn-primary mb-3">📊 View All Reports</a>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3">
            <h5>Total Books</h5>
            <?php
            $stmt = oci_parse($conn,"SELECT COUNT(*) AS total FROM books_info");
            oci_execute($stmt);
            $r = oci_fetch_assoc($stmt);
            echo "<h3>{$r['TOTAL']}</h3>";
            ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <h5>Total Members</h5>
            <?php
            $stmt = oci_parse($conn,"SELECT COUNT(*) AS total FROM members");
            oci_execute($stmt);
            $r = oci_fetch_assoc($stmt);
            echo "<h3>{$r['TOTAL']}</h3>";
            ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <h5>Total Borrow Records</h5>
            <?php
            $stmt = oci_parse($conn,"SELECT COUNT(*) AS total FROM borrow_records");
            oci_execute($stmt);
            $r = oci_fetch_assoc($stmt);
            echo "<h3>{$r['TOTAL']}</h3>";
            ?>
        </div>
    </div>
</div>

<h4>⚠ Overdue Books</h4>
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Member</th>
<th>Email</th>
<th>Book</th>
<th>Borrow Date</th>
<th>Overdue Days</th>
<th>Fine (₹)</th>
<th>Receipt</th>
</tr>
</thead>
<tbody>
<?php foreach($overdues as $r): ?>
<tr>
<td><?= $r['MEMBER_NAME'] ?></td>
<td><?= $r['EMAIL'] ?></td>
<td><?= $r['BOOK_TITLE'] ?></td>
<td><?= $r['BORROW_DATE'] ?></td>
<td class="text-danger"><?= $r['OVERDUE_DAYS'] ?></td>
<td class="text-primary fw-bold">₹ <?= $r['FINE_AMOUNT'] ?></td>

<?php if($r['SHOW_ACTION']): ?>
<td>
<a href="fine_receipt.php?member=<?= urlencode($r['MEMBER_NAME']) ?>&email=<?= urlencode($r['EMAIL']) ?>&book=<?= urlencode($r['BOOK_TITLE']) ?>&days=<?= $r['OVERDUE_DAYS'] ?>&fine=<?= $r['FINE_AMOUNT'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">🧾 Print</a>
</td>
<?php else: ?>
<td colspan="2" class="text-center text-muted">Already Returned</td>
<?php endif; ?>

</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</body>
</html>
<?php oci_close($conn); ?>

