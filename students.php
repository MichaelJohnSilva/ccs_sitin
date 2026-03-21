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
/* SEARCH MODAL FUNCTION (SAFE ADD) */
$searchResults = null;

if(isset($_GET['keyword'])){
    $keyword = "%" . trim($_GET['keyword']) . "%";

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

/* MODAL BACKGROUND */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

/* MODAL BOX */
.modal-content {
    background: #fff;
    width: 70%;
    max-width: 900px;
    border-radius: 10px;
    padding: 25px 35px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

/* HEADER */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.modal-header h2 {
    margin: 0;
    font-weight: 600;
}

.close {
    font-size: 22px;
    cursor: pointer;
}

/* FORM */
.form-container {
    display: flex;
    flex-direction: column;
}
.form-container label {
    font-weight: bold;
    margin-top: 12px;
    margin-bottom: 5px;
}

.form-container input,
.form-container select {
    width: 100%;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

/* BUTTON */
.submit-btn {
    margin-top: 20px;
    width: 90px;
    padding: 6px;
    cursor: pointer;
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
<!-- SEARCH MODAL -->
<div id="searchModal" class="modal">
  <div class="modal-content">

    <!-- HEADER -->
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h2>Search Student</h2>
      <span class="close" onclick="closeSearch()">×</span>
    </div>

    <hr>

    <!-- SEARCH BAR -->
    <form method="GET" action="students.php" style="display:flex; gap:10px; margin:15px 0;">
      <input type="text" name="keyword" placeholder="Search..."
        value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>"
        style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px;">

      <button type="submit" class="btn btn-add">Search</button>
    </form>

    <hr>

    <!-- RESULTS -->
    <?php if (isset($_GET['keyword'])): ?>

    <?php if ($searchResults) $searchResults->data_seek(0); ?>

    <h3>Search Results:</h3>

      <?php if ($searchResults && $searchResults->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>ID Number</th>
              <th>First Name</th>
              <th>Middle Name</th>
              <th>Last Name</th>
              <th>Course</th>
              <th>Remaining Session</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <?php while ($row = $searchResults->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                <td><?php echo htmlspecialchars($row['middle_name']); ?></td>
                <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['course']); ?></td>
                <td><?php echo htmlspecialchars($row['sessions_remaining'] ?? 30); ?></td>

                <td>
                 <button class="search-btn"
                  onclick="selectStudent(
                  '<?php echo $row['id_number']; ?>',
                  '<?php echo $row['first_name'].' '.$row['middle_name'].' '.$row['last_name']; ?>',
                  '<?php echo $row['sessions_remaining']; ?>'
                  )">
                  Sit In
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

      <?php else: ?>
        <p>No students found.</p>
      <?php endif; ?>
    <?php endif; ?>

        <?php if ($searchResults) $searchResults->data_seek(0); ?>
  </div>
</div>

<div id="sitInModal" class="modal">
  <div class="modal-content">

    <div class="modal-header">
      <h2>Sit In Form</h2>
      <span class="close" onclick="closeSitInForm()">×</span>
    </div>

    <form method="POST" action="sit_in.php" class="form-container">

      <label>ID Number</label>
      <input type="text" name="id_number" placeholder="Enter student ID" required>

      <label>Student Name</label>
      <input type="text" name="student_name">

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
      <input type="text" name="lab" required>

      <label>Remaining Session</label>
      <input type="text" name="remaining_session">

      <button type="submit" class="submit-btn">Sit In</button>

    </form>

  </div>
</div>
<script>
  
function selectStudent(id, name, session){
    closeSearch();
    openSitIn();

    document.querySelector('#sitInModal input[name="id_number"]').value = id;
    document.querySelector('#sitInModal input[name="student_name"]').value = name;
    document.querySelector('#sitInModal input[name="remaining_session"]').value = session;
}

/* SEARCH MODAL */
function openSearch() {
    document.getElementById('searchModal').classList.add('show');
}

function closeSearch() {
    document.getElementById('searchModal').classList.remove('show');
}

/* SIT-IN MODAL */
function openSitIn() {
    document.getElementById('sitInModal').classList.add('show');
}

function closeSitIn() {
    document.getElementById('sitInModal').classList.remove('show');
}

/* CLOSE WHEN CLICK OUTSIDE */
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

function closeSitInForm(){
    closeSitIn();
}

<?php if ($searchResults !== null): ?>
document.getElementById('searchModal').classList.add('show');
<?php endif; ?>

</script>
</body>
</html>