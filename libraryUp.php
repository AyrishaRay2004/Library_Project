<?php
// ======================= ORACLE CONNECTION =======================
require_once 'config.php';

// ======================= HANDLE FORM SUBMISSION =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author_name       = trim($_POST['author_name']);
    $birth_year        = (int)$_POST['birth_year'];
    $title             = trim($_POST['title']);
    $book_author_name  = trim($_POST['book_author']);
    $published_year    = (int)$_POST['published_year'];
    $total_copies      = (int)$_POST['total_copies'];
    $available_copies  = (int)$_POST['available_copies'];
    $member_name       = trim($_POST['member_name']);
    $member_email      = trim($_POST['member_email']);
    $borrow_member_email = trim($_POST['borrow_member_email']);
    $borrow_book_title   = trim($_POST['borrow_book']);
    $borrow_date         = $_POST['borrow_date'];
    $return_date         = $_POST['return_date'] ?: null;

    // -------- AUTHOR --------
    $sql = "SELECT author_id FROM author WHERE name=:name AND birth_year=:year";
    $stmt = oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":name",$author_name);
    oci_bind_by_name($stmt,":year",$birth_year);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    if($row){ $author_id = $row['AUTHOR_ID']; }
    else{
        $sql = "INSERT INTO author(author_id,name,birth_year) VALUES(author_seq.NEXTVAL,:name,:year) RETURNING author_id INTO :aid";
        $stmt = oci_parse($conn,$sql);
        oci_bind_by_name($stmt,":name",$author_name);
        oci_bind_by_name($stmt,":year",$birth_year);
        oci_bind_by_name($stmt,":aid",$author_id,-1,SQLT_INT);
        if(!oci_execute($stmt)) { $e=oci_error($stmt); die("Author insert error: ".$e['message']); }
    }

    // -------- BOOK --------
    $sql = "SELECT book_id FROM books_info WHERE title=:title AND author_id=:aid";
    $stmt = oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":title",$title);
    oci_bind_by_name($stmt,":aid",$author_id);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    if($row){ $book_id=$row['BOOK_ID']; }
    else{
        $sql = "INSERT INTO books_info(book_id,title,author_id,published_year,total_copies,available_copies)
                VALUES(books_info_seq.NEXTVAL,:title,:aid,:pyear,:total,:avail)
                RETURNING book_id INTO :bid";
        $stmt = oci_parse($conn,$sql);
        oci_bind_by_name($stmt,":title",$title);
        oci_bind_by_name($stmt,":aid",$author_id);
        oci_bind_by_name($stmt,":pyear",$published_year);
        oci_bind_by_name($stmt,":total",$total_copies);
        oci_bind_by_name($stmt,":avail",$available_copies);
        oci_bind_by_name($stmt,":bid",$book_id,-1,SQLT_INT);
        if(!oci_execute($stmt)) { $e=oci_error($stmt); die("Book insert error: ".$e['message']); }
    }

    // -------- MEMBER --------
    $sql = "SELECT member_id FROM members WHERE email=:email";
    $stmt = oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":email",$member_email);
    oci_execute($stmt);
    $row=oci_fetch_assoc($stmt);
    if($row){ $member_id=$row['MEMBER_ID']; }
    else{
        $sql="INSERT INTO members(member_id,name,email) VALUES(members_seq.NEXTVAL,:name,:email) RETURNING member_id INTO :mid";
        $stmt=oci_parse($conn,$sql);
        oci_bind_by_name($stmt,":name",$member_name);
        oci_bind_by_name($stmt,":email",$member_email);
        oci_bind_by_name($stmt,":mid",$member_id,-1,SQLT_INT);
        if(!oci_execute($stmt)) { $e=oci_error($stmt); die("Member insert error: ".$e['message']); }
    }

    // -------- BORROW RECORD --------
    $sql="SELECT member_id FROM members WHERE email=:email";
    $stmt=oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":email",$borrow_member_email);
    oci_execute($stmt);
    $borrow_member=oci_fetch_assoc($stmt);
    if(!$borrow_member) die("<div class='alert alert-danger'>Borrow member not found!</div>");
    $borrow_member_id=$borrow_member['MEMBER_ID'];

    $sql="SELECT book_id,available_copies FROM books_info WHERE title=:title";
    $stmt=oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":title",$borrow_book_title);
    oci_execute($stmt);
    $borrow_book=oci_fetch_assoc($stmt);
    if(!$borrow_book) die("<div class='alert alert-danger'>Borrow book not found!</div>");
    if($borrow_book['AVAILABLE_COPIES']<=0) die("<div class='alert alert-warning'>Book not available!</div>");
    $borrow_book_id=$borrow_book['BOOK_ID'];

    if(empty($return_date)){
        $sql="INSERT INTO borrow_records(borrow_id,member_id,book_id,borrow_date,return_date)
              VALUES(borrow_seq.NEXTVAL,:mid,:bid,TO_DATE(:bdate,'YYYY-MM-DD'),NULL)";
        $stmt=oci_parse($conn,$sql);
        oci_bind_by_name($stmt,":mid",$borrow_member_id);
        oci_bind_by_name($stmt,":bid",$borrow_book_id);
        oci_bind_by_name($stmt,":bdate",$borrow_date);
    }else{
        $sql="INSERT INTO borrow_records(borrow_id,member_id,book_id,borrow_date,return_date)
              VALUES(borrow_seq.NEXTVAL,:mid,:bid,TO_DATE(:bdate,'YYYY-MM-DD'),TO_DATE(:rdate,'YYYY-MM-DD'))";
        $stmt=oci_parse($conn,$sql);
        oci_bind_by_name($stmt,":mid",$borrow_member_id);
        oci_bind_by_name($stmt,":bid",$borrow_book_id);
        oci_bind_by_name($stmt,":bdate",$borrow_date);
        oci_bind_by_name($stmt,":rdate",$return_date);
    }
    if(!oci_execute($stmt)){ $e=oci_error($stmt); die("Borrow insert error: ".$e['message']); }

    // Update available copies
    $sql="UPDATE books_info SET available_copies=available_copies-1 WHERE book_id=:bid";
    $stmt=oci_parse($conn,$sql);
    oci_bind_by_name($stmt,":bid",$borrow_book_id);
    if(!oci_execute($stmt)) { $e=oci_error($stmt); die("Book update error: ".$e['message']); }

    oci_commit($conn);
    echo "<div class='alert alert-success'>Library data saved successfully!</div>";
}

// ======================= FETCH DATA FOR BOOKS CHART =======================
$conn = oci_connect('scott','tiger','10.141.120.154/comdb.saildsp.co.in');
$sql = "SELECT title, total_copies, available_copies FROM books_info ORDER BY title";
$stmt = oci_parse($conn,$sql);
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

// ======================= MONTHLY BORROW DATA =======================
$sql = "SELECT TO_CHAR(borrow_date,'YYYY-MM') AS month, COUNT(*) AS total_borrows
        FROM borrow_records
        GROUP BY TO_CHAR(borrow_date,'YYYY-MM')
        ORDER BY month";
$stmt = oci_parse($conn,$sql);
oci_execute($stmt);

$months = [];
$totalBorrows = [];
while($row = oci_fetch_assoc($stmt)){
    $months[] = $row['MONTH'];
    $totalBorrows[] = (int)$row['TOTAL_BORROWS'];
}

// ======================= OVERDUE + FINE CALCULATION =======================
$today = date('Y-m-d');
$fine_per_day = 5;

$sql = "SELECT b.borrow_id, b.borrow_date, b.return_date, m.name AS member_name, m.email, bk.title AS book_title
        FROM borrow_records b
        JOIN members m ON b.member_id = m.member_id
        JOIN books_info bk ON b.book_id = bk.book_id
        WHERE b.return_date IS NOT NULL AND b.return_date < TO_DATE(:today,'YYYY-MM-DD')";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":today", $today);
oci_execute($stmt);

$overdues = [];
while($row = oci_fetch_assoc($stmt)){
    $diff_days = floor((strtotime($today) - strtotime($row['RETURN_DATE']))/86400);
    $row['OVERDUE_DAYS'] = $diff_days;
    $row['FINE'] = $diff_days * $fine_per_day;
    $overdues[] = $row;
}

oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Library Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="libraryUp.php">Library System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="libraryUp.php">📥 Add Library Data</a></li>
      </ul>
    </div>
  </div>
</nav>


<div class="container">

<!-- ADD DATA FORM -->
<div id="addData" class="card mb-4 shadow">
<div class="card-header bg-success text-white"><h4>Add Library Data</h4></div>
<div class="card-body">
<form method="post">
<!-- Author, Book, Member, Borrow fields -->
<h5 class="text-success">Author Details</h5>
<div class="row mb-3">
  <div class="col-md-6"><input type="text" name="author_name" class="form-control" placeholder="Author Name" required></div>
  <div class="col-md-6"><input type="number" name="birth_year" class="form-control" placeholder="Birth Year" required></div>
</div>
<hr>
<h5 class="text-success">Book Details</h5>
<div class="row mb-3">
  <div class="col-md-6"><input type="text" name="title" class="form-control" placeholder="Book Title" required></div>
  <div class="col-md-6"><input type="text" name="book_author" class="form-control" placeholder="Book Author" required></div>
</div>
<div class="row mb-3">
  <div class="col-md-4"><input type="number" name="published_year" class="form-control" placeholder="Published Year"></div>
  <div class="col-md-4"><input type="number" name="total_copies" class="form-control" placeholder="Total Copies" required></div>
  <div class="col-md-4"><input type="number" name="available_copies" class="form-control" placeholder="Available Copies" required></div>
</div>
<hr>
<h5 class="text-success">Member Details</h5>
<div class="row mb-3">
  <div class="col-md-6"><input type="text" name="member_name" class="form-control" placeholder="Member Name" required></div>
  <div class="col-md-6"><input type="email" name="member_email" class="form-control" placeholder="Member Email" required></div>
</div>
<hr>
<h5 class="text-success">Borrow Record</h5>

<div class="row mb-3">
  <div class="col-md-6">
    <input type="email" name="borrow_member_email" class="form-control" placeholder="Enter member email" required>
  </div>

  <div class="col-md-6">
    <input type="text" name="borrow_book" class="form-control" placeholder="Enter book title" required>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-6">
    <input type="date" name="borrow_date" class="form-control" required>
    <small class="text-muted">Borrowed Date</small>
  </div>

  <div class="col-md-6">
    <input type="date" name="return_date" class="form-control">
    <small class="text-muted">Returned Date - Leave empty if book is not returned yet</small>
  </div>
</div>

<div class="form-check mb-3">
  <input type="checkbox" class="form-check-input" required>
  <label class="form-check-label">I confirm the above information is correct</label>
</div>
<button class="btn btn-success w-100">Submit</button>
</form>
</div></div>

</div>
</body>
</html>

