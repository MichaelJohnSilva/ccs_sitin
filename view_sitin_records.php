<?php
session_start();
include "config.php";


// Only admins should access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit();
}

// Fetch all sit-in records and current remaining sessions from students table
$result = $conn->query("
    SELECT s.id, s.id_number, st.first_name, st.middle_name, st.last_name, s.purpose, s.lab, st.sessions_remaining, s.status, s.time_in, s.time_out
    FROM sitin_records s
    LEFT JOIN students st ON s.id_number = st.id_number
    ORDER BY s.time_in DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sit-in Records</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Your styles for navbar and content go here */
        body {
            font-family: Arial;
            background: #f5f5f5;
            margin: 0;
        }

        /* NAVBAR */
        .topnav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #a0a0a0;
            padding: 10px 20px;
            color: white;
        }

        .topnav ul {
            list-style: none;
            display: flex;
            gap: 15px;
        }

        .topnav ul li a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .topnav ul li a.active {
            background: #1f1f1f;
        }

        .topnav ul li a:hover {
            background: #575757;
            cursor: pointer;
        }

        .container {
            width: 95%;
            margin: auto;
            margin-top: 30px;
        }

            h2 {
            text-align: center;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<div class="topnav">
    <div id="title">
        <img src="uclogo.png" id="uc" style="height:45px;">
        <span>College of Computer Studies Sit-in Monitoring System</span>
    </div>
    <div class="topnavInside">
        <ul>
            <li><a href="admin_dashboard.php">Home</a></li>
            <li><a href="#" onclick="openSearch()">Search</a></li>
            <li><a href="students.php">Students</a></li>
        <li><a href="javascript:void(0);" onclick="openSitInForm()">Sit-in</a></li>
            <li><a class="active" href="view_sitin_records.php">View Sit-in Records</a></li>
            <li><a href="#">Sit-in Reports</a></li>
            <li><a href="#">Feedback Reports</a></li>
            <li><a href="#">Reservation</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>

<!-- SEARCH MODAL -->
<div id="searchModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    
    <div style="background:#f1f1f1; width:600px; margin:80px auto; border-radius:10px; padding:20px; position:relative;">
        
        <span onclick="closeSearch()" style="position:absolute; right:15px; top:10px; cursor:pointer; font-size:20px;">&times;</span>

        <h2>Search Student</h2>
        <hr>

        <form method="GET">
            <input type="text" name="search" placeholder="Enter name or ID" required 
                   style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
            <br><br>
            <button style="background:#007bff; color:white; padding:8px 15px; border:none; border-radius:5px;">Search</button>
        </form>

        <hr>

        <h3>Search Results:</h3>

        <?php
        if(isset($_GET['search'])){
            $search = "%".$_GET['search']."%";

            $stmt = $conn->prepare("
            SELECT id_number, first_name, middle_name, last_name, course 
            FROM students 
            WHERE id_number LIKE ? 
            OR first_name LIKE ? 
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?
        ");
            $stmt->bind_param("sssss", $search, $search, $search, $search, $search);
            $stmt->execute();
            $res = $stmt->get_result();
            if($res->num_rows > 0){

                echo "<table style='width:100%; border-collapse:collapse; background:white;'>";
                echo "<tr style='background:#ddd;'>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Course</th>
                    </tr>";

                while($row = $res->fetch_assoc()){   // ✅ IMPORTANT LOOP
                    echo "<tr>
                            <td>".$row['id_number']."</td>
                            <td>".$row['first_name']." ".$row['middle_name']." ".$row['last_name']."</td>
                            <td>".$row['course']."</td>
                        </tr>";
                }

                echo "</table>";

            } else {
                echo "<p style='color:red;'>No results found</p>";
            }}
        ?>

    </div>
</div>
    <!-- SIT-IN FORM MODAL -->
    <div id="sitInModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
        <div style="background:#f1f1f1; width:600px; margin:80px auto; border-radius:10px; padding:20px; position:relative;">
            <span onclick="closeSitInForm()" style="position:absolute; right:15px; top:10px; cursor:pointer; font-size:20px;">&times;</span>

            <h2>Sit-in Form</h2>
            <hr>

            <form method="POST" action="view_sitin_records.php">
                <label for="id_number">Student ID:</label>
                <input type="text" name="id_number" placeholder="Enter Student ID" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                <br><br>

                <label for="purpose">Purpose:</label>
                <input type="text" name="purpose" placeholder="Enter Purpose" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                <br><br>

                <label for="lab">Lab:</label>
                <input type="text" name="lab" placeholder="Enter Lab Name" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                <br><br>

                <label for="sessions_remaining">Remaining Sessions:</label>
                <input type="number" name="sessions_remaining" placeholder="Enter Remaining Sessions" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                <br><br>

                <label for="status">Status:</label>
                <select name="status" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                    <option value="Active">Active</option>
                    <option value="Ended">Ended</option>
                </select>
                <br><br>

                <label for="time_in">Time In:</label>
                <input type="datetime-local" name="time_in" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                <br><br>

                <label for="time_out">Time Out:</label>
                <input type="datetime-local" name="time_out" style="width:100%; padding:10px; border-radius:5px; border:1px solid #ccc;">
                <br><br>

                <button type="submit" name="submit_sitin" style="background:#007bff; color:white; padding:8px 15px; border:none; border-radius:5px;">Submit</button>
            </form>
        </div>
    </div>

<div class="container">
    <h2>Sit-in Records</h2>

    <table>
        <tr>
            <th>Sit ID</th>
            <th>ID Number</th>
            <th>Student Name</th>
            <th>Purpose</th>
            <th>Lab</th>
            <th>Remaining Sessions</th>
            <th>Status</th>
            <th>Time In</th>
            <th>Time Out</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['id_number']; ?></td>
                <td>
                <?php 
                if($row['first_name']){
                    echo $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
                } else {
                    echo "Unknown Student";
                }
                ?>
                </td>                
                <td><?php echo $row['purpose']; ?></td>
                <td><?php echo $row['lab']; ?></td>
                <td><?php echo $row['sessions_remaining']; ?></td>
                <td class="<?php echo ($row['status'] == 'Active') ? 'status-active' : 'status-ended'; ?>">
                    <?php echo $row['status']; ?>
                </td>                
                <td><?php echo date("M d, Y h:i A", strtotime($row['time_in'])); ?></td>
                <td>
                <?php 
                echo $row['time_out'] 
                    ? date("M d, Y h:i A", strtotime($row['time_out'])) 
                    : '-'; 
                ?>
                </td>   
            </tr>
        <?php } ?>
    </table>
</div>
</body>
<script>
function openSearch(){
    document.getElementById("searchModal").style.display = "block";
}

function closeSearch(){
    document.getElementById("searchModal").style.display = "none";
}

function openSitInForm() {
    document.getElementById("sitInModal").style.display = "block";
}

function closeSitInForm() {
    document.getElementById("sitInModal").style.display = "none";
}
</script>

<?php if(isset($_GET['search'])){ ?>
<script>
document.getElementById("searchModal").style.display = "block";
</script>
<?php } ?>

</html>