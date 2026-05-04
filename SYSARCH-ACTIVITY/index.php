<?php
// Start session and database connection for leaderboard
session_start();
include 'config.php';

// Fetch leaderboard data with profile photos
$leaderboardQuery = "
    SELECT 
        s.id_number,
        CONCAT(st.first_name, ' ', st.middle_name, ' ', st.last_name) as name,
        st.photo,
        COUNT(*) as total_sessions,
        SUM(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as total_minutes
    FROM sitin_records s
    INNER JOIN students st ON s.id_number = st.id_number
    WHERE s.status = 'Ended' AND s.time_out IS NOT NULL
    GROUP BY s.id_number, st.first_name, st.middle_name, st.last_name, st.photo
    ORDER BY total_minutes DESC
    LIMIT 10
";
$leaderboardResult = $conn->query($leaderboardQuery);
$leaderboardData = [];
if($leaderboardResult && $leaderboardResult->num_rows > 0) {
    while($row = $leaderboardResult->fetch_assoc()) {
        $leaderboardData[] = $row;
    }
}
$conn->close();
unset($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCS | Home</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="icon" href="uclogo.png" type="image/png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
  /* GENERAL STYLES */
  body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    position: relative;
  }

  /* NAVBAR STYLES */
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
    width: 50px;
    border-radius: 50%;
    transition: transform 0.4s ease;
    box-shadow: 0 0 15px rgba(255,255,255,0.3);
  }

  #uc:hover {
    transform: rotate(360deg) scale(1.1);
  }

  .topnavInside ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 5px;
    transition: transform 0.3s ease-in-out;
  }

  .topnavInside li {
    position: relative;
  }

  .topnavInside a {
    display: block;
    color: white;
    padding: 12px 20px;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 8px;
    position: relative;
    overflow: hidden;
  }

  .topnavInside a::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: all 0.3s ease;
    transform: translateX(-50%);
    border-radius: 2px;
  }

  .topnavInside a:hover {
    color: white;
    background: rgba(255,255,255,0.1);
  }

  .topnavInside a:hover::before {
    width: 80%;
  }

  .topnavInside a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  /* DROPDOWN MENU */
  .dropdown-content {
    display: none;
    position: absolute;
    top: 50px;
    left: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    min-width: 200px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    border-radius: 12px;
    overflow: hidden;
    transform: translateY(-10px);
    opacity: 0;
    transition: all 0.3s ease;
    pointer-events: none;
    z-index: 11000;
    list-style: none;
    padding: 8px 0;
  }

  .dropdown:hover .dropdown-content {
    display: block;
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
  }

  .dropdown-content li {
    margin: 0;
    padding: 0;
  }

  .dropdown-content li a {
    display: block;
    padding: 12px 20px;
    font-size: 14px;
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
  }

  .dropdown-content li a:hover {
    background: rgba(255,255,255,0.1);
    border-left: 3px solid #667eea;
    padding-left: 25px;
  }

  /* MAIN CONTENT STYLES */
  .content {
    max-width: 1000px;
    margin: 30px auto;
    padding: 40px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    position: relative;
    z-index: 1;
  }

  @keyframes fadeInContent {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
  }

  /* Welcome Section */
  .welcome-section {
    margin-bottom: 40px;
    animation: fadeInContent 0.6s ease forwards;
  }

  .welcome-section h1 {
    font-size: 36px;
    color: #1a1a2e;
    margin-bottom: 15px;
    font-weight: 700;
  }

  .welcome-section p {
    font-size: 18px;
    color: #666;
  }

  /* Leaderboard Section */
  .leaderboard-section {
    margin-top: 50px;
    text-align: left;
    animation: fadeInContent 0.8s ease forwards;
  }

  .leaderboard-section h2 {
    text-align: center;
    color: #1a1a2e;
    margin-bottom: 25px;
    font-size: 28px;
    font-weight: 700;
    position: relative;
    display: inline-block;
    width: 100%;
  }

  .leaderboard-section h2::after {
    content: '';
    display: block;
    width: 80px;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 10px auto 0;
    border-radius: 2px;
  }

  .leaderboard {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
  }

  .leaderboard-table-container {
    overflow-x: auto;
  }

  .leaderboard-table {
    display: block;
    width: 100%;
  }

  .leaderboard-body {
    display: grid;
    gap: 8px;
    margin-top: 10px;
  }

  .leaderboard-header,
  .leaderboard-row {
    display: grid;
    grid-template-columns: 60px 50px 2fr 120px 80px;
    gap: 15px;
    padding: 14px 20px;
    font-weight: 600;
    border-radius: 10px;
    align-items: center;
  }

  .leaderboard-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
  }

  .leaderboard-row {
    background: white;
    transition: all 0.3s ease;
    border: 1px solid rgba(102, 126, 234, 0.1);
  }

  .leaderboard-row:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
    border-color: #667eea;
  }

  .leaderboard-row .rank-cell {
    font-size: 18px;
    color: #667eea;
    font-weight: 700;
    text-align: center;
  }

  .leaderboard-row .photo-cell {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .leaderboard-photo {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #667eea;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .leaderboard-photo:hover {
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  }

  .leaderboard-row .name-cell {
    font-weight: 500;
    color: #1a1a2e;
    font-size: 15px;
  }

  .leaderboard-row .hours-cell {
    font-weight: 600;
    color: #11998e;
    text-align: center;
    font-size: 14px;
  }

  .leaderboard-row .sessions-cell {
    font-weight: 600;
    color: #667eea;
    text-align: center;
    font-size: 14px;
  }

  /* Staggered animation for rows */
  .leaderboard-row {
    opacity: 0;
    transform: translateY(8px);
    animation: fadeUp 0.5s ease forwards;
  }

  .leaderboard-row:nth-child(1) { animation-delay: 0.1s; }
  .leaderboard-row:nth-child(2) { animation-delay: 0.15s; }
  .leaderboard-row:nth-child(3) { animation-delay: 0.2s; }
  .leaderboard-row:nth-child(4) { animation-delay: 0.25s; }
  .leaderboard-row:nth-child(5) { animation-delay: 0.3s; }
  .leaderboard-row:nth-child(6) { animation-delay: 0.35s; }
  .leaderboard-row:nth-child(7) { animation-delay: 0.4s; }
  .leaderboard-row:nth-child(8) { animation-delay: 0.45s; }
  .leaderboard-row:nth-child(9) { animation-delay: 0.5s; }
  .leaderboard-row:nth-child(10) { animation-delay: 0.55s; }

  @keyframes fadeUp {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 16px;
    font-style: italic;
  }

  /* Watermark */
  body::before {
    content: "";
    position: fixed;
    top: 50%;
    left: 50%;
    width: 400px;
    height: 400px;
    background: url('ucmainccslogo.png') no-repeat center center;
    background-size: contain;
    opacity: 0.08;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 0;
  }

  /* MOBILE RESPONSIVE */
  @media (max-width: 768px) {
    .topnav {
      flex-direction: column;
      padding: 20px;
    }

    .topnavInside ul {
      flex-direction: column;
      width: 100%;
      margin-top: 15px;
    }

    .topnavInside a {
      padding: 12px;
    }

    .content {
      width: 90%;
      padding: 25px;
      margin: 20px auto;
    }

    .welcome-section h1 {
      font-size: 28px;
    }

    .leaderboard-section h2 {
      font-size: 24px;
    }

    .leaderboard {
      padding: 15px;
    }

    .leaderboard-header,
    .leaderboard-row {
      grid-template-columns: 50px 1fr;
      gap: 10px;
      padding: 12px 15px;
      font-size: 13px;
    }

    .leaderboard-header div:nth-child(2),
    .leaderboard-header div:nth-child(3),
    .leaderboard-header div:nth-child(4),
    .leaderboard-header div:nth-child(5),
    .leaderboard-row div:nth-child(2),
    .leaderboard-row div:nth-child(3),
    .leaderboard-row div:nth-child(4),
    .leaderboard-row div:nth-child(5) {
      display: none;
    }

    .leaderboard-row .name-cell {
      font-size: 13px;
    }
  }

  @media (max-width: 480px) {
    .topnavInside ul {
      gap: 0;
    }

    .topnavInside a {
      padding: 10px;
      font-size: 14px;
    }

    .content {
      padding: 20px;
    }

    .welcome-section h1 {
      font-size: 24px;
    }

    .welcome-section p {
      font-size: 16px;
    }

    .leaderboard-header,
    .leaderboard-row {
      grid-template-columns: 40px 1fr;
      gap: 8px;
      padding: 10px 12px;
    }

    .leaderboard-row .rank-cell {
      font-size: 16px;
    }

    .leaderboard-row .name-cell {
      font-size: 12px;
    }
  }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <div class="topnav">
    <div id="title">
      <img src="uclogo.png" alt="uclogo" id="uc">
      <span>College of Computer Studies Sit-in Monitoring System</span>
    </div>
    <div class="topnavInside">
      <ul>
        <li><a class="active" href="index.php">Home</a></li>
        <li class="dropdown">
          <a href="#">Community &#9662;</a>
          <ul class="dropdown-content">
            <li><a href="events.html">Events</a></li>
            <li><a href="clubs.html">Clubs</a></li>
            <li><a href="forum.html">Forum</a></li>
          </ul>
        </li>
        <li><a href="about.php">About</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php">Register</a></li>
      </ul>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="content">
    <!-- Welcome Section -->
    <div class="welcome-section">
      <h1>Welcome to CCS Sit-in Monitoring System</h1>
      <p>Track lab usage, manage reservations, and view sit-in records in one place.</p>
    </div>

    <!-- Leaderboard Section -->
    <div class="leaderboard-section">
      <h2>🏆 Sit-in Leaderboard</h2>
      <div class="leaderboard">
        <div class="leaderboard-table-container">
            <div class="leaderboard-table">
              <div class="leaderboard-header">
                <div class="rank-col">#</div>
                <div class="photo-col"></div>
                <div class="name-col">Name</div>
                <div class="hours-col">Total Hours</div>
                <div class="sessions-col">Sessions</div>
              </div>
            <div class="leaderboard-body">
              <?php if(!empty($leaderboardData)): ?>
                <?php foreach($leaderboardData as $index => $row): 
                  $totalHours = $row['total_minutes'] > 0 ? $row['total_minutes'] / 60 : 0;
                  $photoPath = !empty($row['photo']) ? $row['photo'] : 'default_avatar.png';
                ?>
                <div class="leaderboard-row">
                  <div class="rank-cell"><?php echo $index + 1; ?></div>
                  <div class="photo-cell">
                    <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                         alt="<?php echo htmlspecialchars($row['name']); ?>" 
                         class="leaderboard-photo"
                         onerror="this.onerror=null;this.src='default_avatar.png';">
                  </div>
                  <div class="name-cell"><?php echo htmlspecialchars($row['name']); ?></div>
                  <div class="hours-cell"><?php echo number_format($totalHours, 2); ?> hrs</div>
                  <div class="sessions-cell"><?php echo $row['total_sessions']; ?></div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="no-data">No sit-in records found.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
