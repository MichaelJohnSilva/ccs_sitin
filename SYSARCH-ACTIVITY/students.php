<?php
session_start();
include "config.php";

/* ADD STUDENT */
if(isset($_POST['add_student'])){
    // Sanitize and validate input
    $id = trim($_POST['id_number']);
    $first = trim($_POST['first_name']);
    $middle = trim($_POST['middle_name']);
    $last = trim($_POST['last_name']);
    $year = trim($_POST['year_level']);
    $course = trim($_POST['course']);
    $email = trim($_POST['email']);
    
    // Validate required fields
    if (empty($id) || empty($first) || empty($last) || empty($course) || empty($email)) {
        $error = "Please fill in all required fields.";
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    
    if (!isset($error)) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $remaining = 30;

        $stmt = $conn->prepare("INSERT INTO students 
            (id_number, first_name, middle_name, last_name, year_level, course, sessions_remaining, email, password, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')");

        // Corrected type string: 6 strings + int + string + string = "sssssiss"
        $stmt->bind_param(
            "ssssssiss",
            $id,
            $first,
            $middle,
            $last,
            $year,
            $course,
            $remaining,
            $email,
            $password
        );

        $stmt->execute();

        echo "<script>location.href='students.php';</script>";
    }
}

/* RESET ALL SESSIONS */
if(isset($_POST['reset_sessions'])){

    // Reset sessions only for students with year level 1, 2, or 3 (NOT year level 4)
    $conn->query("UPDATE students SET sessions_remaining = 30 WHERE role != 'admin' AND year_level < 4");

    echo "<script>alert('Sessions reset successfully for students year level 1, 2, and 3!');</script>";
    echo "<script>location.href='students.php';</script>";
}

/* DELETE STUDENT */
if(isset($_POST['delete_student'])){
    $id = $_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM students WHERE id_number = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();

    echo "<script>location.href='students.php';</script>";
}

/* UPDATE STUDENT */
if(isset($_POST['update_student'])){
    $id = $_POST['edit_id'];
    $first = $_POST['edit_first'];
    $middle = $_POST['edit_middle'];
    $last = $_POST['edit_last'];
    $year = $_POST['edit_year'];
    $course = $_POST['edit_course'];
    $remaining = $_POST['edit_remaining'];

    $stmt = $conn->prepare("
        UPDATE students 
        SET first_name=?, middle_name=?, last_name=?, 
            year_level=?, course=?, sessions_remaining=? 
        WHERE id_number=?
    ");
    $stmt->bind_param("sssssds", $first, $middle, $last, $year, $course, $remaining, $id);
    $stmt->execute();

    echo "<script>location.href='students.php';</script>";
}

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
    $keyword = trim($_GET['keyword']);

    $stmt = $conn->prepare("
        SELECT * FROM students 
        WHERE role != 'admin' AND id_number = ?
    ");

    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $searchResults = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Students | CCS Monitoring</title>

<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
}

/* NAVBAR */
.topnav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 15px 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

#title {
    display: flex;
    align-items: center;
    gap: 15px;
    color: white;
    font-weight: 700;
    font-size: 20px;
}

#uc {
    height: 50px;
    border-radius: 50%;
    transition: transform 0.4s ease;
    box-shadow: 0 0 15px rgba(255,255,255,0.3);
}

#uc:hover {
    transform: rotate(360deg) scale(1.1);
}

.topnavInside ul {
    list-style: none;
    display: flex;
    gap: 5px;
}

.topnavInside ul li a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.topnavInside ul li a::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.topnavInside ul li a:hover::before {
    width: 80%;
}

.topnavInside ul li a:hover {
    color: white;
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.topnavInside ul li a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.topnavInside ul li a.active::before {
    display: none;
}

/* CONTAINER */
.container {
    width: 95%;
    margin: 40px auto;
    animation: fadeInUp 0.6s ease;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

h1, h2 {
    color: white;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

/* TABLE */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    margin: 20px 0;
}

table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 18px 15px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

table th:first-child { border-radius: 20px 0 0 0; }
table th:last-child { border-radius: 0 20px 0 0; }

table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    text-align: center;
    font-size: 14px;
    transition: all 0.3s ease;
}

table tbody tr {
    transition: all 0.3s ease;
}

table tbody tr:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    transform: scale(1.01);
}

table tbody tr:last-child td { border-bottom: none; }
table tbody tr:last-child td:first-child { border-radius: 0 0 0 20px; }
table tbody tr:last-child td:last-child { border-radius: 0 0 20px 0; }

/* BUTTONS */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
}

.btn-add {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.5px;
}

.btn-add:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
}

.btn-reset {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(235, 51, 73, 0.3);
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.5px;
}

.btn-reset:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 10px 30px rgba(235, 51, 73, 0.5);
}

.edit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 500;
}

.edit:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

.delete {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
    font-weight: 500;
}

.delete:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 5px 20px rgba(235, 51, 73, 0.4);
}

.search-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 500;
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(5px);
    justify-content: center;
    align-items: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

.modal.show { display: flex; }

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    width: 90%;
    max-width: 800px;
    border-radius: 25px;
    padding: 35px;
    box-shadow: 0 25px 80px rgba(0,0,0,0.3);
    animation: scaleIn 0.4s ease;
    max-height: 85vh;
    overflow-y: auto;
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.modal-header h2 {
    margin: 0;
    color: black;
    font-size: 24px;
}

.close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close:hover {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
    transform: rotate(90deg);
}

/* FORMS */
.search-form {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
}

.search-form input {
    flex: 1;
    padding: 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 15px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.search-form input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

.btn-search {
    padding: 15px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-search:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* ADD FORM */
#addStudentModal form,
#sitInModal form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

#addStudentModal form input,
#sitInModal form input,
#addStudentModal form select,
#sitInModal form select {
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s ease;
    width: 100%;
}

#addStudentModal form input:focus,
#sitInModal form input:focus,
#addStudentModal form select:focus,
#sitInModal form select:focus,
#editStudentModal form input:focus,
#editStudentModal form select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

#addStudentModal form input[type="hidden"] {
    display: none;
}

#addStudentModal form label,
#editStudentModal form label,
#sitInModal form label,
.form-container label {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: block;
}

#editStudentModal form,
#sitInModal form {
    gap: 15px;
}

.btn-save, .submit-btn {
    grid-column: 1 / -1;
    padding: 15px 40px;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    font-weight: 600;
    font-size: 16px;
    margin-top: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
}

.btn-save:hover, .submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .topnav {
        padding: 10px 20px;
        flex-direction: column;
        gap: 15px;
    }
    .topnavInside ul {
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
    }
    .topnavInside ul li a {
        padding: 8px 12px;
        font-size: 12px;
    }
    table th, table td {
        font-size: 11px;
        padding: 10px 5px;
    }
    #addStudentModal form,
    #sitInModal form {
        grid-template-columns: 1fr;
    }
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
        <li><a href="sit_in.php">Sit-in</a></li>
        <li><a class="<?php echo basename($_SERVER['PHP_SELF'])=='view_sitin_records.php'?'active':'' ?>" href="view_sitin_records.php">View Sit-in Records</a></li>
        
        <li><a href="feedback_reports.php">Feedback Reports</a></li>
        <li><a href="admin_reservations.php">Reservation</a></li>
        <li><a class="logout" href="logout.php">Log out</a></li>
    </ul>
  </div>
</div>


<div class="container">
  <h1>Students Information</h1>
  <button class="btn-add" onclick="openAddStudent()">+ Add Student</button>
  <form method="POST" style="display:inline;">
    <button type="submit" name="reset_sessions" class="btn-reset">
        Reset All Sessions
    </button>
  </form>
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
    <button class="btn edit"
        onclick="openEditStudent(
        '<?php echo $row['id_number']; ?>',
        '<?php echo $row['first_name']; ?>',
        '<?php echo $row['middle_name']; ?>',
        '<?php echo $row['last_name']; ?>',
        '<?php echo $row['year_level']; ?>',
        '<?php echo $row['course']; ?>',
        '<?php echo $row['sessions_remaining']; ?>'
        )">Edit</button>

        <form method="POST" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?php echo $row['id_number']; ?>">
            <button type="submit" name="delete_student" class="btn delete">
                Delete
            </button>
        </form>
    </td>
  </tr>
  <?php } ?>
  </table>
</div>

<!-- ADD STUDENTS -->
 <div id="addStudentModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeAddStudent()">&times;</span>
    <h2 style="color: black;">Add Student</h2>

   <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
    <label>ID Number</label>
    <input type="text" name="id_number" placeholder="ID Number" required>
    
    <label>Year Level</label>
    <input type="text" name="year_level" placeholder="Year Level" required>

    <label>First Name</label>
    <input type="text" name="first_name" placeholder="First Name" required>
    
    <label>Course</label>
    <select name="course" required>
      <option value="" disabled selected hidden>-- Select Course --</option>
      <option value="BS Accountancy">BS Accountancy</option>
      <option value="BS Business Administration">BS Business Administration</option>
      <option value="BS Computer Science">BS Computer Science</option>
      <option value="BS Information Technology">BS Information Technology</option>
      <option value="BS Computer Engineering">BS Computer Engineering</option>
      <option value="BS Criminology">BS Criminology</option>
      <option value="BS Civil Engineering">BS Civil Engineering</option>
      <option value="BS Electrical Engineering">BS Electrical Engineering</option>
      <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
      <option value="BS Industrial Engineering">BS Industrial Engineering</option>
      <option value="BS Commerce">BS Commerce</option>
      <option value="BS Hotel & Restaurant Management">BS Hotel & Restaurant Management</option>
      <option value="BS Tourism Management">BS Tourism Management</option>
      <option value="BS Elementary Education">BS Elementary Education</option>
      <option value="BS Secondary Education">BS Secondary Education</option>
      <option value="BS Customs Administration">BS Customs Administration</option>
      <option value="BS Industrial Psychology">BS Industrial Psychology</option>
      <option value="BS Real Estate Management">BS Real Estate Management</option>
      <option value="BS Office Administration">BS Office Administration</option>
    </select>

    <label>Middle Name</label>
    <input type="text" name="middle_name" placeholder="Middle Name">
    
    <label>Last Name</label>
    <input type="text" name="last_name" placeholder="Last Name" required>

    <label>Email</label>
    <input type="email" name="email" placeholder="Email" required>
    
    <label>Password</label>
    <input type="password" name="password" placeholder="Password" required>

    <!-- Full-width button -->
    <button type="submit" name="add_student" class="btn-save" style="grid-column: 1 / -1; margin-top: 10px;">
            Save Student
    </button>
    </form>
  </div>
</div>

<div id="editStudentModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closeEditStudent()">&times;</span>
<h2 style="color: black;">Edit Student</h2>

<form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">

<input type="hidden" name="edit_id" id="edit_id">

<label>First Name</label>
<input type="text" name="edit_first" id="edit_first" placeholder="First Name">

<label>Middle Name</label>
<input type="text" name="edit_middle" id="edit_middle" placeholder="Middle Name">

<label>Last Name</label>
<input type="text" name="edit_last" id="edit_last" placeholder="Last Name">

<label>Year Level</label>
<input type="text" name="edit_year" id="edit_year" placeholder="Year Level">

    <label>Course</label>
    <select name="edit_course" id="edit_course" required>
      <option value="" disabled selected hidden>-- Select Course --</option>
      <option value="BS Accountancy">BS Accountancy</option>
      <option value="BS Business Administration">BS Business Administration</option>
      <option value="BS Computer Science">BS Computer Science</option>
      <option value="BS Information Technology">BS Information Technology</option>
      <option value="BS Computer Engineering">BS Computer Engineering</option>
      <option value="BS Criminology">BS Criminology</option>
      <option value="BS Civil Engineering">BS Civil Engineering</option>
      <option value="BS Electrical Engineering">BS Electrical Engineering</option>
      <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
      <option value="BS Industrial Engineering">BS Industrial Engineering</option>
      <option value="BS Commerce">BS Commerce</option>
      <option value="BS Hotel & Restaurant Management">BS Hotel & Restaurant Management</option>
      <option value="BS Tourism Management">BS Tourism Management</option>
      <option value="BS Elementary Education">BS Elementary Education</option>
      <option value="BS Secondary Education">BS Secondary Education</option>
      <option value="BS Customs Administration">BS Customs Administration</option>
      <option value="BS Industrial Psychology">BS Industrial Psychology</option>
      <option value="BS Real Estate Management">BS Real Estate Management</option>
      <option value="BS Office Administration">BS Office Administration</option>
    </select>

<label>Remaining Session</label>
<input type="text" name="edit_remaining" id="edit_remaining" placeholder="Remaining Session">

<button type="submit" name="update_student" 
style="grid-column:1/-1;" class="submit-btn">
Update Student
</button>

</form>
</div>
</div>

<!-- SEARCH MODAL -->
<div id="searchModal" class="modal">
  <div class="modal-content">

    <!-- HEADER -->
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="color: black;">Search Student</h3>
      <span class="close" onclick="closeSearch()">×</span>
    </div>

    <hr>

    <!-- SEARCH BAR -->
    <form method="GET" action="students.php" style="display:flex; gap:10px; margin:15px 0;">
      <input type="text" name="keyword" placeholder="Enter ID Number..."
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
                 <button class="btn edit search-btn"
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
      <h2 style="color: black;">Sit In Form</h2>
      <span class="close" onclick="closeSitInForm()">×</span>
    </div>

    <form method="POST" action="sit_in.php" class="form-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">

      <label>ID Number</label>
      <input type="text" name="id_number" placeholder="Enter student ID" required readonly>

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
      <select name="lab" id="form_lab" required onchange="updateComputerOptions()">
        <option value="">Select Lab</option>
        <option value="524">Lab 524</option>
        <option value="526">Lab 526</option>
        <option value="528">Lab 528</option>
        <option value="530">Lab 530</option>
        <option value="542">Lab 542</option>
        <option value="544">Lab 544</option>
      </select>

      <label>Computer</label>
      <select name="computer" id="form_computer" required>
        <option value="">Select Computer</option>
      </select>

      <label>Remaining Session</label>
      <input type="text" name="remaining_session">

      <button type="submit" name="sit_in_submit" class="submit-btn" style="grid-column: 1 / -1;">Sit In</button>

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

function openAddStudent(){
    document.getElementById("addStudentModal").classList.add("show");
}

function closeAddStudent(){
    document.getElementById("addStudentModal").classList.remove("show");
}

function openEditStudent(id, first, middle, last, year, course, remaining){
    document.getElementById("editStudentModal").classList.add("show");

    document.getElementById("edit_id").value = id;
    document.getElementById("edit_first").value = first;
    document.getElementById("edit_middle").value = middle;
    document.getElementById("edit_last").value = last;
    document.getElementById("edit_year").value = year;
    document.getElementById("edit_course").value = course;
    document.getElementById("edit_remaining").value = remaining;
}

function closeEditStudent(){
    document.getElementById("editStudentModal").classList.remove("show");
}

<?php if ($searchResults !== null): ?>
document.getElementById('searchModal').classList.add('show');
<?php endif; ?>

// Lab status data
const labStatusData = <?php
$labs = ['524', '526', '528', '530', '542', '544'];
$labStatusSimple = [];
foreach ($labs as $lab) {
    $q = $conn->prepare("SELECT computer_number FROM sitin_records WHERE lab = ? AND status = 'Active'");
    $q->bind_param("s", $lab);
    $q->execute();
    $r = $q->get_result();
    $occupied = [];
    while ($row = $r->fetch_assoc()) {
        if (!empty($row['computer_number'])) $occupied[] = $row['computer_number'];
    }
    $q->close();
    
    $cntQ = $conn->prepare("SELECT COUNT(*) as cnt FROM sitin_records WHERE lab = ? AND status = 'Active'");
    $cntQ->bind_param("s", $lab);
    $cntQ->execute();
    $cntResult = $cntQ->get_result()->fetch_assoc();
    $totalOccupied = $cntResult['cnt'] ?? 0;
    $cntQ->close();
    
    $identified = count($occupied);
    $unidentified = $totalOccupied - $identified;
    for ($i = 1; $i <= $unidentified; $i++) {
        $pcNum = str_pad($i, 2, '0', STR_PAD_LEFT);
        if (!in_array($pcNum, $occupied)) {
            $occupied[] = $pcNum;
        }
    }
    
    $labStatusSimple[] = ['lab' => $lab, 'occupied_computers' => $occupied];
}
echo json_encode($labStatusSimple);
?>;

function updateComputerOptions() {
    const lab = document.getElementById("form_lab").value;
    const computerSelect = document.getElementById("form_computer");
    
    if (!lab) {
        computerSelect.innerHTML = '<option value="">Select Computer</option>';
        return;
    }
    
    const labData = labStatusData.find(l => l.lab === lab);
    const occupiedComputers = labData ? labData.occupied_computers : [];
    const occupiedCount = occupiedComputers.length;
    const vacantCount = 20 - occupiedCount;
    
    let html = '<option value="">Select Computer (Vacant: ' + vacantCount + '/20)</option>';
    
    for (const pc of occupiedComputers) {
        html += '<option value="' + pc + '" disabled style="background: #ffcccc;">' + pc + ' (Occupied)</option>';
    }
    
    for (let i = 1; i <= 20; i++) {
        const pcNum = String(i).padStart(2, '0');
        if (!occupiedComputers.includes(pcNum)) {
            html += '<option value="' + pcNum + '">' + pcNum + '</option>';
        }
    }
    
    computerSelect.innerHTML = html;
}

</script>
</body>
</html>
