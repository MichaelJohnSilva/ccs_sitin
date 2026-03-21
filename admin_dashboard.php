<?php
session_start();
include "config.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

/* TOTAL STUDENTS */
$result = $conn->query("SELECT COUNT(*) as total FROM students WHERE role != 'admin'");
$data = $result->fetch_assoc();
$total_students = $data['total'];


/* POST ANNOUNCEMENT */
if(isset($_POST['post_announcement'])){
    $message = trim($_POST['announcement']);

    if(!empty($message)){
        $stmt = $conn->prepare("INSERT INTO announcements(message) VALUES(?)");
        $stmt->bind_param("s",$message);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_dashboard.php");
    exit();
}

/* DELETE ANNOUNCEMENT */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php");
    exit();
}

/* GET ANNOUNCEMENTS */
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");

/* SEARCH STUDENT */
$searchResults = null;

if(isset($_POST['search'])){
    $keyword = trim($_POST['keyword']);
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

/* CHART DATA (LANGUAGE USAGE) */
$chart_labels = [];
$chart_data = [];

$chart_query = $conn->query("SELECT purpose, COUNT(*) as total FROM sitin_records GROUP BY purpose");

while($row = $chart_query->fetch_assoc()){
    $chart_labels[] = $row['purpose'];
    $chart_data[] = $row['total'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>

<link rel="stylesheet" href="styles.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* RESET */
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial;
}

/* NAVBAR */
.topnav{
display:flex;
justify-content:space-between;
align-items:center;
background:#a0a0a0;
padding:10px 20px;
}

#title{
display:flex;
align-items:center;
gap:10px;
color:white;
font-size:18px;
font-weight:bold;
}

#uc{
height:45px;
}

.topnavInside ul{
display:flex;
list-style:none;
gap:18px;
}

.topnavInside ul li a{
text-decoration:none;
color:white;
font-size:14px;
padding:6px 10px;
border-radius:4px;
transition:.3s;
}

.topnavInside ul li a:hover{
background:#575757;
}

.topnavInside ul li a.active{
background:#1f1f1f;
}

/* DASHBOARD */
.dashboard-container{
max-width:1200px;
margin:40px auto;
display:flex;
gap:20px;
}

.dashboard-card{
flex:1;
background:white;
border-radius:8px;
box-shadow:0 4px 10px #a0a0a0;
padding:20px;
}

.dashboard-title{
background:#a0a0a0;
color:white;
padding:10px;
border-radius:5px;
margin-bottom:15px;
font-weight:bold;
}

/* MODAL */
.modal{
display:none;
position:fixed;
z-index:999;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.5);
}

.modal-content{
background:white;
width:90%;
max-width:900px;   /* 👈 makes it wider */
margin:80px auto;
border-radius:8px;
box-shadow:0 4px 15px rgba(0,0,0,0.3);
overflow-x:auto;   /* 👈 enables scroll if needed */

border-radius:8px;
box-shadow:0 4px 15px rgba(0,0,0,0.3);
overflow:hidden;
}

.modal-header{
display:flex;
justify-content:space-between;
align-items:center;
padding:15px;
border-bottom:1px solid #ddd;
}

.close{
cursor:pointer;
font-size:20px;
font-weight:bold;
}

.modal-body{
padding:20px;
}

.modal-body label{
display:block;
margin-top:10px;
font-weight:bold;
}

.modal-body input{
width:100%;
padding:8px;
border:1px solid #ccc;
border-radius:4px;
margin-top:5px;
}

/* BUTTONS */
.search-btn{
padding:8px 15px;
background:#0d6efd;
border:none;
color:white;
border-radius:5px;
cursor:pointer;
margin-left:10px;
}

.delete-btn{
margin-top:5px;
padding:5px 12px;
background:#dc3545;
color:white;
border:none;
border-radius:4px;
cursor:pointer;
font-size:12px;
}

.announcement-form{
display:flex;
flex-direction:column;
gap:10px;
}

.announcement-form textarea{
width:100%;
height:90px;
padding:10px;
border:1px solid #ccc;
border-radius:6px;
resize:none;
}

.announcement-btn{
align-self:flex-end;
padding:8px 18px;
background:#a0a0a0;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
font-weight:bold;
}

table{
width:100%;
margin-top:20px;
border-collapse:collapse;
}

table th, table td{
border:1px solid #ddd;
padding:8px;
font-size:14px;
}

table th{
background:#f0f0f0;
}

</style>
</head>

<body>

<!-- NAVBAR -->
<div class="topnav">

<div id="title">
<img src="uclogo.png" id="uc">
<span>College of Computer Studies Sit-in Monitoring System</span>
</div>
    <div class="topnavInside">
        <ul>
        <li><a class="active" href="admin_dashboard.php">Home</a></li>
        <li><a href="#" onclick="openSearch()">Search</a></li>
        <li><a href="students.php">Students</a></li>
        <li><a href="#" onclick="openSitIn()">Sit-in</a></li>
        <li><a href="view_sitin_records.php">View Sit-in Records</a></li>
        <li><a href="#">Sit-in Reports</a></li>
        <li><a href="#">Feedback Reports</a></li>
        <li><a href="#">Reservation</a></li>
        <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>

<!-- SEARCH MODAL -->
<div id="searchModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Search Student</h3>
      <span class="close" onclick="closeSearch()">×</span>
    </div>
    <div class="modal-body">
      <form method="POST" id="searchForm">
        <input type="text" name="keyword" placeholder="Search..." required value="<?php echo isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '' ?>">
        <button type="submit" name="search" class="search-btn">Search</button>
      </form>

      <?php if ($searchResults !== null): ?>
        <hr>
        <h4>Search Results:</h4>
        <?php if ($searchResults->num_rows > 0): ?>
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
                              '<?php echo $row['first_name'].' '.$row['middle_name'].' '.$row['last_name']; ?>'
                          )">
                          Sit In
                      </button>
                  </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No students found matching your search.</p>
        <?php endif; ?>
      <?php endif; ?>
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
<input type="text" name="student_name" readonly>

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

<button type="submit" onclick="this.disabled=true;this.form.submit();">
Sit In
</button>

</form>

</div>
</div>
</div>


<!-- DASHBOARD -->
<div class="dashboard-container">

<div class="dashboard-card">

<div class="dashboard-title">Statistics</div>

<p><b>Students Registered:</b> <?php echo $total_students; ?></p>
<?php
$current = $conn->query("SELECT COUNT(*) as total FROM sitin_records WHERE status='Active'");
$total   = $conn->query("SELECT COUNT(*) as total FROM sitin_records");

$current_count = $current->fetch_assoc()['total'];
$total_count   = $total->fetch_assoc()['total'];
?>

<p><b>Currently Sit-in:</b> <?php echo $current_count; ?></p>
<p><b>Total Sit-in:</b> <?php echo $total_count; ?></p>

<canvas id="chart"></canvas>

</div>

<div class="dashboard-card">

<div class="dashboard-title">Announcement</div>

<form method="POST" class="announcement-form">

<textarea name="announcement" placeholder="Write announcement here..." required></textarea>

<button name="post_announcement" class="announcement-btn">Post Announcement</button>

</form>

<hr>

<h4>Posted Announcement</h4>

<?php while($row = $announcements->fetch_assoc()){ ?>

<div class="announcement-item">

<p><b>CCS Admin | <?php echo date("Y-M-d", strtotime($row['created_at'])); ?></b></p>

<p><?php echo htmlspecialchars($row['message']); ?></p>

<button class="delete-btn"
onclick="deleteAnnouncement(<?php echo $row['id']; ?>)">
Delete
</button>

</div>

<hr>

<?php } ?>

</div>

</div>

<script>

/* SEARCH MODAL */
function openSearch(){
document.getElementById("searchModal").style.display="block";
}

function closeSearch(){
document.getElementById("searchModal").style.display="none";
}

/* SIT-IN MODAL */
function openSitIn(){
document.getElementById("sitInModal").style.display="block";
}

function closeSitIn(){
document.getElementById("sitInModal").style.display="none";
}

/* CLOSE MODAL OUTSIDE CLICK */
window.onclick = function(event){

let searchModal = document.getElementById("searchModal");
let sitInModal = document.getElementById("sitInModal");

if(event.target == searchModal){
searchModal.style.display="none";
}

if(event.target == sitInModal){
sitInModal.style.display="none";
}

}

/* DELETE ANNOUNCEMENT */
function deleteAnnouncement(id){

if(confirm("Delete this announcement?")){
window.location = "admin_dashboard.php?delete=" + id;
}

}

/* CHART */
const ctx = document.getElementById('chart');

// PHP injects the chart data dynamically
const labels = <?php echo json_encode($chart_labels); ?>;
const data = <?php echo json_encode($chart_data); ?>;

new Chart(ctx,{
type:'pie',
data:{
labels: labels,
datasets:[{
data: data,
backgroundColor:['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff']
}]
}
});

<?php if ($searchResults !== null): ?>
document.getElementById("searchModal").style.display = "block";
<?php endif; ?>

function selectStudent(id, name){
    // Close search modal
    document.getElementById("searchModal").style.display = "none";

    // Open sit-in modal
    document.getElementById("sitInModal").style.display = "block";

    // Autofill data
    document.querySelector('input[name="id_number"]').value = id;
    document.querySelector('input[name="student_name"]').value = name;
}
</script>

</body>
</html>