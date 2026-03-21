    <?php
    session_start();
    include "config.php";

    // Default to null for search results
    $searchResults = null;

    // Handle Search
    if (isset($_POST['search'])) {
        $keyword = "%" . trim($_POST['keyword']) . "%";

        // Prepare and execute search query
        $stmt = $conn->prepare("SELECT * FROM students WHERE 
                                id_number LIKE ? 
                                OR first_name LIKE ? 
                                OR middle_name LIKE ? 
                                OR last_name LIKE ? 
                                OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?");
        $stmt->bind_param("sssss", $keyword, $keyword, $keyword, $keyword, $keyword);
        $stmt->execute();
        $searchResults = $stmt->get_result(); // Store the result of the query
    }

    // Fetch sit-in records
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

                        /* MODAL BACKGROUND */
            .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            }

            /* MODAL BOX */
            .modal-content {
            background: #f9f9f9;
            width: 80%;
            max-width: 1000px;
            margin: 60px auto;
            padding: 25px 40px;
            border-radius: 10px;
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
            }

            .close {
            font-size: 22px;
            cursor: pointer;
            }

            /* FORM LAYOUT */
            .form-container {
            display: flex;
            flex-direction: column;
            }

            /* LABELS */
            .form-container label {
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 16px;
            }

            /* INPUTS */
            .form-container input,
            .form-container select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            }

            /* BUTTON */
            .submit-btn {
            margin-top: 20px;
            width: 100px;
            padding: 6px;
            font-size: 14px;
            cursor: pointer;
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

    <!-- SIT-IN RECORDS -->
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
            <td><?php echo $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']; ?></td>
            <td><?php echo $row['purpose']; ?></td>
            <td><?php echo $row['lab']; ?></td>
            <td><?php echo $row['sessions_remaining']; ?></td>
            <td class="<?php echo ($row['status'] == 'Active') ? 'status-active' : 'status-ended'; ?>">
            <?php echo $row['status']; ?>
            </td>
            <td><?php echo date("M d, Y h:i A", strtotime($row['time_in'])); ?></td>
            <td><?php echo $row['time_out'] ? date("M d, Y h:i A", strtotime($row['time_out'])) : '-'; ?></td>
        </tr>
        <?php } ?>
    </table>
    </div>
    <!-- SIT-IN MODAL -->
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
    </body>

    <script>
    function openSearch() {
    document.getElementById("searchModal").style.display = "block";
    }

    function closeSearch() {
    document.getElementById("searchModal").style.display = "none";
    }

    function openSitInForm() {
    document.getElementById("sitInModal").style.display = "block";
    }

    function closeSitInForm() {
    document.getElementById("sitInModal").style.display = "none";
    }

    window.onclick = function(event) {
    let searchModal = document.getElementById("searchModal");
    let sitInModal = document.getElementById("sitInModal");

    if (event.target == searchModal) {
        searchModal.style.display = "none";
    }

    if (event.target == sitInModal) {
        sitInModal.style.display = "none";
    }
    }

    function selectStudent(id, name){
    document.getElementById("searchModal").style.display = "none";
    document.getElementById("sitInModal").style.display = "block";

    document.querySelector('input[name="id_number"]').value = id;
    document.querySelector('input[name="student_name"]').value = name;
    }
    </script>

    </html> 