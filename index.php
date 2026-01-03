<?php
require_once 'config.php';

// ---------- STATS ----------

// Total Books
$sql = "SELECT COUNT(*) AS total_books FROM books_info";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$total_books = oci_fetch_assoc($stmt)['TOTAL_BOOKS'];

// Total Members
$sql = "SELECT COUNT(*) AS total_members FROM members";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$total_members = oci_fetch_assoc($stmt)['TOTAL_MEMBERS'];

// Currently Borrowed (not returned)
$sql = "SELECT COUNT(*) AS borrowed_books 
        FROM borrow_records 
        WHERE return_date IS NULL";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$borrowed_books = oci_fetch_assoc($stmt)['BORROWED_BOOKS'];

// Overdue Books (not returned & due date passed)
// ---------- OVERDUE BOOKS (not returned & due date passed) ----------
$sql = "SELECT COUNT(*) AS overdue_books
        FROM borrow_records
        WHERE return_date IS NULL 
        AND TRUNC(due_date) < TRUNC(SYSDATE)";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$overdue_books = $row ? $row['OVERDUE_BOOKS'] : 0;
// ---------- LATEST BOOKS ----------
$sql = "SELECT title, published_year, available_copies 
        FROM books_info 
        ORDER BY book_id DESC FETCH FIRST 5 ROWS ONLY";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$latest_books = [];
while($row = oci_fetch_assoc($stmt)){
    $latest_books[] = $row;
}

// ---------- RECENTLY BORROWED ----------
$sql = "SELECT b.borrow_date, m.name AS member_name, bk.title AS book_title
        FROM borrow_records b
        JOIN members m ON b.member_id = m.member_id
        JOIN books_info bk ON b.book_id = bk.book_id
        ORDER BY b.borrow_date DESC FETCH FIRST 5 ROWS ONLY";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$recent_borrows = [];
while($row = oci_fetch_assoc($stmt)){
    $recent_borrows[] = $row;
}

oci_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background:#f8f9fa; }

        /* QUOTE BAR */
        .quote-bar {
            background:#212529;
            color:#fff;
            text-align:center;
            padding:12px;
            font-style: italic;
            font-size:1.1rem;
        }

        /* HERO */
        .hero {
            background:url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570') no-repeat center;
            background-size:cover;
            position:relative;
            padding:140px 0;
            color:white;
            text-align:center;
        }
        .hero::before {
            content:"";
            position:absolute;
            inset:0;
            background:rgba(0,0,0,0.55);
        }
        .hero-content {
            position:relative;
            z-index:1;
        }

        .card-icon { font-size:2rem; }
        .card:hover { transform: translateY(-5px); transition:0.3s; }
        .table thead { background:#343a40; color:white; }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="frontpage.php">Library System</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
      <li class="nav-item"><a class="nav-link" href="adminDashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="report.php">Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="highCharts.php">Visual Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="libraryUp.php">Add Data</a></li>
    </ul>
  </div>
</nav>


<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <h1>Welcome to the Library</h1>
        <p>“A library is infinity under a roof.”
</p>
        <a href="libraryUp.php" class="btn btn-success btn-lg mt-3">Add Library Data</a>
    </div>
</div>

<!-- STATS -->
<div class="container my-5">
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">📚</div>
                <h5>Total Books</h5>
                <h3><?= $total_books ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">👥</div>
                <h5>Total Members</h5>
                <h3><?= $total_members ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">📖</div>
                <h5>Currently Borrowed</h5>
                <h3><?= $borrowed_books ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">⏰</div>
                <h5>Overdue Books</h5>
                <h3><?= $overdue_books ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- LIBRARY RULES -->
<div class="container mb-5">
    <h3 class="mb-3">Library Rules & Information</h3>
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">⏳</div>
                <h5>Borrow Period</h5>
                <p>7 days per book</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">💰</div>
                <h5>Fine Policy</h5>
                <p>₹5 per day after due date</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">📚</div>
                <h5>Max Limit</h5>
                <p>5 books per member</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4">
                <div class="card-icon">📌</div>
                <h5>Note</h5>
                <p>Return books on time to avoid fines</p>
            </div>
        </div>
    </div>
</div>

<!-- LATEST BOOKS -->
<div class="container mb-5">
    <h3>Latest Books Added</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Published Year</th>
                <th>Available Copies</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($latest_books as $b): ?>
            <tr>
                <td><?= $b['TITLE'] ?></td>
                <td><?= $b['PUBLISHED_YEAR'] ?></td>
                <td><?= $b['AVAILABLE_COPIES'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- RECENT BORROWS -->
<div class="container mb-5">
    <h3>Recently Borrowed Books</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Book</th>
                <th>Member</th>
                <th>Borrow Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($recent_borrows as $r): ?>
            <tr>
                <td><?= $r['BOOK_TITLE'] ?></td>
                <td><?= $r['MEMBER_NAME'] ?></td>
                <td><?= date('Y-m-d', strtotime($r['BORROW_DATE'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>

