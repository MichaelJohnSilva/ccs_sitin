<?php
session_start();
include "config.php";

/* FETCH ALL STUDENTS (DEFAULT VIEW) */
$students = $conn->query("SELECT * FROM students WHERE role != 'admin'");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

/* FETCH STUDENTS */
$searchResults = null;

if(isset($_GET['search'])){
    $keyword = trim($_GET['search']);
    $keyword = "%$keyword%";

    $stmt = $conn->prepare("
        SELECT * FROM students 
        WHERE role != 'admin' AND (
            id_number LIKE ? 
            OR first_name LIKE ? 
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?
        )
    ");

    $stmt->bind_param("sssss", $keyword, $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $searchResults = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Students</title>

<link rel="stylesheet" href="styles.css">

<style>

body{
font-family:Arial;
background:#f5f5f5;
margin:0;
}

/* NAVBAR */
.topnav{
display:flex;
justify-content:space-between;
align-items:center;
background:#a0a0a0;
padding:10px 20px;
color:white;
}

.topnav ul{
list-style:none;
display:flex;
gap:15px;
}

.topnav ul li a{
color:white;
text-decoration:none;
}



/* If you have a specific class for logout button */
.logout-btn {
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  text-decoration: none;
  transition: background-color 0.3s ease;
}

.logout-btn:hover {
  color: #fff;
  cursor: pointer;
}

.topnav a[href="logout.php"] {
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  text-decoration: none;
  transition: background-color 0.3s ease;
}

.topnav a[href="logout.php"]:hover {
  background-color: #23211e;
  cursor: pointer;
}

/* PAGE */
.container{
width:90%;
margin:auto;
margin-top:20px;
}

h1{
text-align:center;
}

/* BUTTONS */

.btn{
padding:8px 12px;
border:none;
border-radius:4px;
cursor:pointer;
color:white;
}

.btn-add{
background:#0d6efd;
}

.btn-reset{
background:#dc3545;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
margin-top:20px;
background:white;
}

table th, table td{
padding:10px;
border-bottom:1px solid #ddd;
text-align:center;
}

table th{
background:#f2f2f2;
}

/* ACTION BUTTONS */

.edit{
background:#0d6efd;
}

.delete{
background:#dc3545;
}

.search-box{
float:right;
margin-top:-35px;
}

.modal {
 display: none; /* hidden by default */
  position: fixed;
  z-index: 9999;
  top: 0; left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.5);

  justify-content: center; /* horizontal center */
  align-items: center;     /* vertical center */
}

.modal.show {
  display: flex; /* show modal with flex */
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 400px;
  max-width: 90vw;
  padding: 20px 25px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  position: relative;
  font-family: Arial, sans-serif;
}

/* Modal header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.modal-header h3 {
  margin: 0;
  font-weight: bold;
  font-size: 1.25rem;
}

.modal-header .close {
  cursor: pointer;
  font-size: 22px;
  font-weight: bold;
  user-select: none;
}

/* Form labels */
.modal-body label {
  display: block;
  margin-top: 12px;
  font-weight: 600;
  font-size: 0.9rem;
}

/* Inputs and select */
.modal-body input[type="text"],
.modal-body select {
  width: 100%;
  padding: 8px 10px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 0.9rem;
  box-sizing: border-box;
}

/* Submit button */
.modal-submit-btn {
  margin-top: 20px;
  padding: 10px 16px;
  background-color: #0d6efd; /* Bootstrap blue */
  border: none;
  color: white;
  font-weight: 600;
  font-size: 1rem;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
  transition: background-color 0.3s ease;
}

.modal-submit-btn:hover:not(:disabled) {
  background-color: #084cd6;
}

.modal-submit-btn:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

</style>
</head>

<body>

<div class="topnav">
  <div id="title">
        <img src="uclogo.png" id="uc">
        <span>College of Computer Studies Sit-in Monitoring System</span>
  </div>
  <div class="topnavInside">
    <ul>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])=='admin_dashboard.php'?'active':'' ?>" href="admin_dashboard.php">Home</a></li>
        <li><a href="#" onclick="openSearch(); return false;">Search</a></li>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])=='students.php'?'active':'' ?>" href="students.php">Students</a></li>
        <li><a href="#" onclick="openSitIn()">Sit-in</a></li>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])=='view_sitin_records.php'?'active':'' ?>" href="view_sitin_records.php">View Sit-in Records</a></li>
        <li><a href="#">Sit-in Reports</a></li>
        <li><a href="#">Feedback Reports</a></li>
        <li><a href="#">Reservation</a></li>
        <li><a class="logout" href="logout.php">Log out</a></li>
    </ul>
  </div>
</div>


<div class="container">

<h1>Students Information</h1>

<button class="btn btn-add">Add Students</button>
<button class="btn btn-reset">Reset All Session</button>

</div>

<table>

<tr>
<th>ID Number</th>
<th>Name</th>
<th>Year Level</th>
<th>Course</th>
<th>Remaining Session</th>
<th>Actions</th>
</tr>

<?php 
$data = $searchResults ? $searchResults : $students;
while($row = $data->fetch_assoc()){ 
?>

<tr>

<td><?php echo $row['id_number']; ?></td>

<td>
<?php
echo $row['first_name']." ".
$row['middle_name']." ".
$row['last_name'];
?>
</td>

<td><?php echo $row['year_level'] ?? "-"; ?></td>

<td><?php echo $row['course']; ?></td>

<td><?php echo $row['sessions_remaining'] ?? 30; ?></td>

<td>

<button class="btn edit">Edit</button>

<button class="btn delete">Delete</button>

</td>

</tr>

<?php } ?>

</table>

</div>

<!-- SEARCH MODAL -->
<div id="searchModal" class="modal">
  <div class="modal-content" style="width:400px; margin:100px auto; padding:20px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.3); background:white;">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
      <h3>Search Student</h3>
      <span class="close" onclick="closeSearch()" style="cursor:pointer; font-weight:bold; font-size:20px;">×</span>
    </div>
    <div class="modal-body">
     <form method="GET" action="students.php">
      <input type="text" name="search" placeholder="Search..." required
          value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
          style="width:100%; padding:8px; margin-bottom:12px; border-radius:4px; border:1px solid #ccc;">
        <button type="submit" style="background:#007bff; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer;">Search</button>
      </form>
    </div>
  </div>
</div>

<!-- SIT-IN MODAL -->
<div id="sitInModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Sit In Form</h3>
      <span class="close" onclick="closeSitIn()">×</span>
    </div>
    <div class="modal-body">
      <form method="POST" action="sit_in.php">
        <label>ID Number</label>
        <input type="text" name="id_number" required placeholder="Enter student ID" />
        
        <label>Student Name</label>
        <input type="text" name="student_name" />
        
        <label>Purpose</label>
        <select name="purpose" required>
          <option value="">Select Language</option>
          <option value="C">C</option>
          <option value="C#">C#</option>
          <option value="Java">Java</option>
          <option value="PHP">PHP</option>
          <option value="ASP.Net">ASP.Net</option>
        </select>
        
        <label>Lab</label>
        <input type="text" name="lab" required />
        
        <label>Remaining Session</label>
        <input type="text" name="remaining_session" />
        
        <button type="submit" onclick="this.disabled=true;this.form.submit();">Sit In</button>
      </form>
    </div>
  </div>
</div>
<script>
   function openSearch() {
    document.getElementById('searchModal').classList.add('show');
  }

  function closeSearch() {
    document.getElementById('searchModal').classList.remove('show');
  }

  function openSitIn() {
    document.getElementById('sitInModal').classList.add('show');
  }

  function closeSitIn() {
    document.getElementById('sitInModal').classList.remove('show');
  }

  // Close modal when clicking outside modal content for both modals
  window.onclick = function(event) {
    const searchModal = document.getElementById('searchModal');
    const sitInModal = document.getElementById('sitInModal');

    if (event.target === searchModal) {
      closeSearch();
    }
    if (event.target === sitInModal) {
      closeSitIn();
    }
  };


</script>
</body>
</html>