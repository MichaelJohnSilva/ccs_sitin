<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'personal';
$format = $_GET['format'] ?? 'html';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$is_admin = ($user['role'] ?? 'student') === 'admin';

// Handle different report types
switch ($type) {
    case 'personal':
        generatePersonalReport($conn, $user, $format, $is_admin);
        break;
    case 'all_students':
        generateAllStudentsReport($conn, $user, $format, $is_admin);
        break;
    case 'summary':
        generateSummaryReport($conn, $user, $format, $is_admin);
        break;
    case 'monthly':
        generateMonthlyReport($conn, $user, $format, $is_admin);
        break;
    case 'feedback':
        generateFeedbackReport($conn, $user, $format, $is_admin);
        break;
    case 'csv':
        generateCSVExport($conn, $user, $is_admin);
        break;
    default:
        header('Location: dashboard.php');
        exit();
}

$conn->close();

function generatePersonalReport($conn, $user, $format, $is_admin) {
    $id_number = $user['id_number'];
    
    $query = "
        SELECT 
            id,
            purpose,
            lab,
            time_in,
            time_out,
            status,
            TIMESTAMPDIFF(MINUTE, time_in, time_out) as duration_minutes
        FROM sitin_records 
        WHERE id_number = ?
        ORDER BY time_in DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    if ($format === 'csv') {
        generatePersonalCSV($records, $user);
        return;
    }
    
    $total_sessions = count($records);
    $total_minutes = array_sum(array_column($records, 'duration_minutes'));
    $total_hours = $total_minutes / 60;
    $avg_minutes = $total_sessions > 0 ? $total_minutes / $total_sessions : 0;
    $max_minutes = $total_sessions > 0 ? max(array_column($records, 'duration_minutes')) : 0;
    
    if ($format === 'pdf') {
        // HTML with print-friendly styles for PDF generation
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><title>My Sit-in Report</title>';
        echo '<style>
            @media print {
                @page { margin: 0.5cm; size: A4; }
                body { -webkit-print-color-adjust: exact; }
            }
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #fff; }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .user-info { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 30px; }
            .user-info p { margin: 5px 0; }
            .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px; }
            .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
            .stat-box .number { font-size: 24px; font-weight: bold; }
            .stat-box .label { font-size: 12px; opacity: 0.9; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #667eea; color: white; padding: 12px; text-align: left; }
            td { padding: 12px; border-bottom: 1px solid #eee; }
            tr:hover { background: #f8f9fa; }
            .print-btn { display: none; }
            @media print { .print-btn { display: none; } }
            .no-print { margin-top: 20px; text-align: center; }
            .no-print button { padding: 10px 30px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        </style>';
        echo '</head><body>';
        echo '<div class="no-print"><button onclick="window.print()">🖨️ Print / Save as PDF</button></div>';
        renderPersonalReportHTML($user, $records, $total_sessions, $total_hours, $avg_minutes, $max_minutes);
        echo '</body></html>';
        return;
    }
    
    // Default HTML
    renderPersonalReportHTML($user, $records, $total_sessions, $total_hours, $avg_minutes, $max_minutes);
}

function renderPersonalReportHTML($user, $records, $total_sessions, $total_hours, $avg_minutes, $max_minutes) {
    $full_name = htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Sit-in Report - <?= $full_name ?></title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { background: #f5f5f5; padding: 20px; }
            .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .user-info { background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%); padding: 20px; border-radius: 12px; margin-bottom: 30px; }
            .user-info p { margin: 8px 0; font-size: 14px; }
            .user-info strong { color: #667eea; min-width: 100px; display: inline-block; }
            .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px; }
            .stat-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
            .stat-box .number { font-size: 24px; font-weight: 700; display: block; }
            .stat-box .label { font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 10px; text-align: left; font-size: 13px; text-transform: uppercase; }
            td { padding: 14px 10px; border-bottom: 1px solid #eee; font-size: 13px; }
            tr:hover { background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%); }
            .status { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
            .status-active { background: rgba(17,153,142,0.15); color: #11998e; }
            .status-ended { background: rgba(235,51,73,0.15); color: #eb3349; }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            .print-btn:hover { background: #764ba2; }
            @media print {
                body { background: white; padding: 0; }
                .report-container { box-shadow: none; border: 1px solid #ddd; }
                .print-btn { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="report-header">
                <h1>📄 My Sit-in Report</h1>
                <p>Generated on: <?= date('F d, Y \\a\\t h:i A') ?></p>
            </div>
            
            <div class="user-info">
                <p><strong>Student:</strong> <?= $full_name ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($user['id_number']) ?></p>
                <p><strong>Course:</strong> <?= htmlspecialchars($user['course']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="number"><?= $total_sessions ?></span>
                    <span class="label">Total Sessions</span>
                </div>
                <div class="stat-box">
                    <span class="number"><?= number_format($total_hours, 2) ?></span>
                    <span class="label">Total Hours</span>
                </div>
                <div class="stat-box">
                    <span class="number"><?= round($avg_minutes, 1) ?></span>
                    <span class="label">Avg (min)</span>
                </div>
                <div class="stat-box">
                    <span class="number"><?= round($max_minutes, 1) ?></span>
                    <span class="label">Longest (min)</span>
                </div>
            </div>
            
            <?php if (count($records) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Purpose</th>
                        <th>Laboratory</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Duration (min)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($records as $idx => $rec): 
                        $duration = $rec['duration_minutes'] ?? 0;
                        $date = date('M d, Y', strtotime($rec['time_in']));
                        $time_in = date('h:i A', strtotime($rec['time_in']));
                        $time_out = $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '-';
                    ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($rec['purpose']) ?></td>
                        <td><?= htmlspecialchars($rec['lab']) ?></td>
                        <td><?= $time_in ?></td>
                        <td><?= $time_out ?></td>
                        <td><?= round($duration, 1) ?></td>
                        <td><span class="status status-<?= strtolower($rec['status']) ?>"><?= htmlspecialchars($rec['status']) ?></span></td>
                        <td><?= $date ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #666; padding: 40px;">No sit-in records found.</p>
            <?php endif; ?>
            
            <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        </div>
    </body>
    </html>
    <?php
}

function generateCSVExport($conn, $user, $is_admin) {
    $type = $_GET['report'] ?? 'personal';
    
    switch ($type) {
        case 'summary':
            $query = "
                SELECT 
                    s.id_number,
                    CONCAT(st.first_name, ' ', st.middle_name, ' ', st.last_name) as name,
                    COUNT(*) as total_sessions,
                    SUM(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as total_minutes,
                    AVG(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as avg_minutes,
                    MAX(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as max_minutes
                FROM sitin_records s
                INNER JOIN students st ON s.id_number = st.id_number
                WHERE s.status = 'Ended' AND s.time_out IS NOT NULL
                GROUP BY s.id_number, st.first_name, st.middle_name, st.last_name
                ORDER BY total_minutes DESC
            ";
            $result = $conn->query($query);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sit_in_summary_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Rank', 'ID Number', 'Name', 'Total Sessions', 'Total Hours', 'Avg (min)', 'Longest (min)']);
            $rank = 1;
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $rank++,
                    $row['id_number'],
                    $row['name'],
                    $row['total_sessions'],
                    number_format($row['total_minutes'] / 60, 2),
                    round($row['avg_minutes'], 1),
                    round($row['max_minutes'], 1)
                ]);
            }
            fclose($output);
            break;
            
        case 'all':
            $query = "
                SELECT 
                    s.id_number,
                    st.first_name,
                    st.middle_name,
                    st.last_name,
                    st.course,
                    s.purpose,
                    s.lab,
                    s.time_in,
                    s.time_out,
                    s.status,
                    TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out) as duration_minutes
                FROM sitin_records s
                INNER JOIN students st ON s.id_number = st.id_number
                ORDER BY s.time_in DESC
            ";
            $result = $conn->query($query);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="all_students_sitin_report_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['#', 'ID Number', 'First Name', 'Middle Name', 'Last Name', 'Course', 'Purpose', 'Lab', 'Time In', 'Time Out', 'Duration (min)', 'Status', 'Date']);
            $idx = 1;
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $idx++,
                    $row['id_number'],
                    $row['first_name'],
                    $row['middle_name'],
                    $row['last_name'],
                    $row['course'],
                    $row['purpose'],
                    $row['lab'],
                    date('h:i A', strtotime($row['time_in'])),
                    $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '',
                    round($row['duration_minutes'], 1),
                    $row['status'],
                    date('M d, Y', strtotime($row['time_in']))
                ]);
            }
            fclose($output);
            break;
            
        case 'feedback':
            $query = "
                SELECT 
                    f.id_number,
                    st.first_name,
                    st.middle_name,
                    st.last_name,
                    st.course,
                    f.rating,
                    f.comments,
                    f.created_at,
                    s.purpose,
                    s.lab
                FROM feedback f
                INNER JOIN students st ON f.id_number = st.id_number
                LEFT JOIN sitin_records s ON f.record_id = s.id
                ORDER BY f.created_at DESC
            ";
            $result = $conn->query($query);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="feedback_report_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['#', 'ID Number', 'First Name', 'Middle Name', 'Last Name', 'Course', 'Rating', 'Comments', 'Sit-in Purpose', 'Lab', 'Date Submitted']);
            $idx = 1;
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $idx++,
                    $row['id_number'],
                    $row['first_name'],
                    $row['middle_name'],
                    $row['last_name'],
                    $row['course'],
                    $row['rating'] . '/5',
                    $row['comments'],
                    $row['purpose'] ?? 'N/A',
                    $row['lab'] ?? 'N/A',
                    date('M d, Y h:i A', strtotime($row['created_at']))
                ]);
            }
            fclose($output);
            break;
            
        default: // personal
            $id_number = $user['id_number'];
            $query = "
                SELECT 
                    purpose,
                    lab,
                    time_in,
                    time_out,
                    status,
                    TIMESTAMPDIFF(MINUTE, time_in, time_out) as duration_minutes
                FROM sitin_records 
                WHERE id_number = ?
                ORDER BY time_in DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $id_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="my_sitin_report_' . $user['id_number'] . '_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['#', 'Purpose', 'Laboratory', 'Time In', 'Time Out', 'Duration (min)', 'Status', 'Date']);
            
            $idx = 1;
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $idx++,
                    $row['purpose'],
                    $row['lab'],
                    date('h:i A', strtotime($row['time_in'])),
                    $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '',
                    round($row['duration_minutes'], 1),
                    $row['status'],
                    date('M d, Y', strtotime($row['time_in']))
                ]);
            }
            fclose($output);
            $stmt->close();
            break;
    }
    exit();
}

function generateSummaryReport($conn, $user, $format, $is_admin) {
    $query = "
        SELECT 
            s.id_number,
            CONCAT(st.first_name, ' ', st.middle_name, ' ', st.last_name) as name,
            COUNT(*) as total_sessions,
            SUM(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as total_minutes,
            AVG(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as avg_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out)) as max_minutes
        FROM sitin_records s
        INNER JOIN students st ON s.id_number = st.id_number
        WHERE s.status = 'Ended' AND s.time_out IS NOT NULL
        GROUP BY s.id_number, st.first_name, st.middle_name, st.last_name
        ORDER BY total_minutes DESC
    ";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sit_in_summary_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Rank', 'ID Number', 'Name', 'Total Sessions', 'Total Hours', 'Avg (min)', 'Longest (min)']);
        foreach($data as $idx => $row) {
            fputcsv($output, [
                $idx + 1,
                $row['id_number'],
                $row['name'],
                $row['total_sessions'],
                number_format($row['total_minutes'] / 60, 2),
                round($row['avg_minutes'], 1),
                round($row['max_minutes'], 1)
            ]);
        }
        fclose($output);
        exit();
    }
    
    $total_sitters = count($data);
    $total_sessions_all = array_sum(array_column($data, 'total_sessions'));
    $total_hours_all = array_sum(array_column($data, 'total_minutes')) / 60;
    
    if ($format === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><title>Sit-in Summary Report</title>';
        echo '<style>
            @media print { @page { margin: 0.5cm; } body { -webkit-print-color-adjust: exact; } }
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #fff; }
            .report-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
            .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; text-align: center; }
            .summary-box .number { font-size: 32px; font-weight: 700; display: block; }
            .summary-box .label { font-size: 12px; opacity: 0.9; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #667eea; color: white; padding: 14px; text-align: center; font-size: 13px; }
            td { padding: 14px; border-bottom: 1px solid #eee; text-align: center; font-size: 13px; }
            tr:hover { background: #f8f9fa; }
            .rank-1 { color: #f5b301; font-weight: bold; font-size: 18px; }
            .rank-2 { color: #95a5a6; font-weight: bold; }
            .rank-3 { color: #cd7f32; font-weight: bold; }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            @media print { .print-btn { display: none; } }
        </style>';
        echo '</head><body>';
        echo '<div class="report-container">';
        echo '<div class="print-btn"><button onclick="window.print()">🖨️ Print / Save as PDF</button></div>';
        echo '<div class="report-header"><h1>🏆 Sit-in Summary Report</h1><p>Generated on: ' . date('F d, Y \\a\\t h:i A') . '</p></div>';
        echo '<div class="summary-stats">';
        echo '<div class="summary-box"><span class="number">' . $total_sitters . '</span><span class="label">Total Students</span></div>';
        echo '<div class="summary-box"><span class="number">' . $total_sessions_all . '</span><span class="label">Total Sessions</span></div>';
        echo '<div class="summary-box"><span class="number">' . number_format($total_hours_all, 2) . '</span><span class="label">Total Hours</span></div>';
        echo '</div>';
        echo '<table><thead><tr><th>Rank</th><th>ID Number</th><th>Name</th><th>Total Hrs</th><th>Sessions</th><th>Avg (min)</th><th>Longest (min)</th></tr></thead><tbody>';
        foreach($data as $idx => $row) {
            $totalHours = $row['total_minutes'] / 60;
            $rankClass = $idx === 0 ? 'rank-1' : ($idx === 1 ? 'rank-2' : ($idx === 2 ? 'rank-3' : ''));
            echo '<tr>';
            echo '<td class="' . $rankClass . '">' . ($idx + 1) . '</td>';
            echo '<td>' . htmlspecialchars($row['id_number']) . '</td>';
            echo '<td style="text-align: left;">' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . number_format($totalHours, 2) . '</td>';
            echo '<td>' . $row['total_sessions'] . '</td>';
            echo '<td>' . round($row['avg_minutes'], 1) . '</td>';
            echo '<td>' . round($row['max_minutes'], 1) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></body></html>';
        return;
    }
    
    // HTML view
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sit-in Summary Report</title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { background: #f5f5f5; padding: 20px; }
            .report-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
            .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; text-align: center; }
            .summary-box .number { font-size: 32px; font-weight: 700; display: block; }
            .summary-box .label { font-size: 12px; opacity: 0.9; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px; text-align: center; font-size: 13px; text-transform: uppercase; }
            td { padding: 14px; border-bottom: 1px solid #eee; text-align: center; font-size: 13px; }
            tr:hover { background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%); }
            .rank-1 { color: #f5b301; font-weight: bold; font-size: 18px; }
            .rank-2 { color: #95a5a6; font-weight: bold; }
            .rank-3 { color: #cd7f32; font-weight: bold; }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            @media print { body { background: white; } .report-container { box-shadow: none; } .print-btn { display: none; } }
        </style>
    </head>
    <body>
        <div class="report-container">
            <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
            <div class="report-header">
                <h1>🏆 Sit-in Summary Report</h1>
                <p>Generated on: <?= date('F d, Y \\a\\t h:i A') ?></p>
            </div>
            
            <div class="summary-stats">
                <div class="summary-box">
                    <span class="number"><?= $total_sitters ?></span>
                    <span class="label">Total Students</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= $total_sessions_all ?></span>
                    <span class="label">Total Sessions</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= number_format($total_hours_all, 2) ?></span>
                    <span class="label">Total Hours</span>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Total Hrs</th>
                        <th>Sessions</th>
                        <th>Avg (min)</th>
                        <th>Longest (min)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data as $idx => $row): 
                        $totalHours = $row['total_minutes'] / 60;
                        $rankClass = $idx === 0 ? 'rank-1' : ($idx === 1 ? 'rank-2' : ($idx === 2 ? 'rank-3' : ''));
                    ?>
                    <tr>
                        <td class="<?= $rankClass ?>"><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($row['id_number']) ?></td>
                        <td style="text-align: left;"><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= number_format($totalHours, 2) ?></td>
                        <td><?= $row['total_sessions'] ?></td>
                        <td><?= round($row['avg_minutes'], 1) ?></td>
                        <td><?= round($row['max_minutes'], 1) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <script>
        </script>
    </body>
    </html>
    <?php
}

function generateMonthlyReport($conn, $user, $format, $is_admin) {
    $query = "
        SELECT 
            DATE_FORMAT(time_in, '%Y-%m') as month,
            COUNT(*) as total_sessions,
            SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as total_minutes,
            AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out)) as avg_minutes,
            COUNT(DISTINCT id_number) as unique_students
        FROM sitin_records 
        WHERE status = 'Ended' AND time_out IS NOT NULL
        GROUP BY DATE_FORMAT(time_in, '%Y-%m')
        ORDER BY month DESC
    ";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="monthly_sit_in_report_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Month', 'Total Sessions', 'Total Hours', 'Avg Duration (min)', 'Unique Students']);
        foreach($data as $row) {
            $monthName = date('F Y', strtotime($row['month'] . '-01'));
            fputcsv($output, [
                $monthName,
                $row['total_sessions'],
                number_format($row['total_minutes'] / 60, 2),
                round($row['avg_minutes'], 1),
                $row['unique_students']
            ]);
        }
        fclose($output);
        exit();
    }
    
    // HTML view
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Monthly Sit-in Report</title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { background: #f5f5f5; padding: 20px; font-family: 'Poppins', sans-serif; }
            .report-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px; text-align: center; font-size: 13px; text-transform: uppercase; }
            td { padding: 14px; border-bottom: 1px solid #eee; text-align: center; font-size: 13px; }
            tr:hover { background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%); }
            .month-col { text-align: left; font-weight: 500; }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            @media print { body { background: white; } .report-container { box-shadow: none; border: 1px solid #ddd; } .print-btn { display: none; } }
        </style>
    </head>
    <body>
        <div class="report-container">
            <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
            <div class="report-header">
                <h1>📅 Monthly Sit-in Report</h1>
                <p>Generated on: <?= date('F d, Y \\a\\t h:i A') ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Sessions</th>
                        <th>Total Hours</th>
                        <th>Avg Duration (min)</th>
                        <th>Unique Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data as $row): 
                        $monthName = date('F Y', strtotime($row['month'] . '-01'));
                    ?>
                    <tr>
                        <td class="month-col"><?= $monthName ?></td>
                        <td><?= $row['total_sessions'] ?></td>
                        <td><?= number_format($row['total_minutes'] / 60, 2) ?></td>
                        <td><?= round($row['avg_minutes'], 1) ?></td>
                        <td><?= $row['unique_students'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($data)): ?>
            <p style="text-align: center; color: #666; padding: 40px;">No sit-in data available.</p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}

function generateAllStudentsReport($conn, $user, $format, $is_admin) {
    $query = "
        SELECT 
            s.id,
            s.id_number,
            st.first_name,
            st.middle_name,
            st.last_name,
            st.course,
            s.purpose,
            s.lab,
            s.time_in,
            s.time_out,
            s.status,
            TIMESTAMPDIFF(MINUTE, s.time_in, s.time_out) as duration_minutes
        FROM sitin_records s
        INNER JOIN students st ON s.id_number = st.id_number
        ORDER BY s.time_in DESC
    ";
    
    $result = $conn->query($query);
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    if ($format === 'csv') {
        generateAllStudentsCSV($records);
        return;
    }
    
    $total_sessions = count($records);
    $total_minutes = array_sum(array_column($records, 'duration_minutes'));
    $total_hours = $total_minutes / 60;
    $unique_students = count(array_unique(array_column($records, 'id_number')));
    
    if ($format === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><title>All Students Sit-in Report</title>';
        echo '<style>
            @media print { @page { margin: 0.5cm; } body { -webkit-print-color-adjust: exact; } }
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #fff; }
            .report-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
            .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
            .summary-box .number { font-size: 28px; font-weight: 700; display: block; }
            .summary-box .label { font-size: 11px; opacity: 0.9; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            th { color: white; padding: 12px 10px; text-align: left; font-size: 12px; text-transform: uppercase; font-weight: 600; }
            td { padding: 12px 10px; border-bottom: 1px solid #eee; font-size: 12px; }
            tbody tr:hover { background: #f8f9fa; }
            tbody tr:nth-child(even) { background: #f9f9fb; }
            tbody tr:last-child td { border-bottom: none; }
            .status {
                padding: 4px 10px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 600;
            }
            .status-active { background: rgba(17,153,142,0.15); color: #11998e; }
            .status-ended { background: rgba(235,51,73,0.15); color: #eb3349; }
            .lab-badge {
                display: inline-block;
                background: rgba(102, 126, 234, 0.1);
                padding: 4px 10px;
                border-radius: 8px;
                font-size: 12px;
            }
            @media print { .print-btn { display: none; } }
        </style>';
        echo '</head><body>';
        echo '<div class="report-container">';
        echo '<div class="print-btn"><button onclick="window.print()">🖨️ Print / Save as PDF</button></div>';
        echo '<div class="report-header"><h1>👥 All Students Sit-in Report</h1><p>Generated on: ' . date('F d, Y \\a\\t h:i A') . '</p></div>';
        echo '<div class="summary-stats">';
        echo '<div class="summary-box"><span class="number">' . $total_sessions . '</span><span class="label">Total Sessions</span></div>';
        echo '<div class="summary-box"><span class="number">' . number_format($total_hours, 2) . '</span><span class="label">Total Hours</span></div>';
        echo '<div class="summary-box"><span class="number">' . $unique_students . '</span><span class="label">Unique Students</span></div>';
        echo '</div>';
        renderAllStudentsTableHTML($records);
        echo '</div></body></html>';
        return;
    }
    
    // HTML view
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>All Students Sit-in Report</title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { background: #f5f5f5; padding: 20px; }
            .report-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
            .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
            .summary-box .number { font-size: 28px; font-weight: 700; display: block; }
            .summary-box .label { font-size: 11px; opacity: 0.9; text-transform: uppercase; }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            @media print { body { background: white; } .report-container { box-shadow: none; border: 1px solid #ddd; } .print-btn { display: none; } }
        </style>
    </head>
    <body>
        <div class="report-container">
            <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
            <div class="report-header">
                <h1>👥 All Students Sit-in Report</h1>
                <p>Generated on: <?= date('F d, Y \\a\\t h:i A') ?></p>
            </div>
            
            <div class="summary-stats">
                <div class="summary-box">
                    <span class="number"><?= $total_sessions ?></span>
                    <span class="label">Total Sessions</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= number_format($total_hours, 2) ?></span>
                    <span class="label">Total Hours</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= $unique_students ?></span>
                    <span class="label">Unique Students</span>
                </div>
            </div>
            <?php renderAllStudentsTableHTML($records); ?>
        </div>
    </body>
    </html>
    <?php
}

function renderAllStudentsTableHTML($records) {
    ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>ID Number</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Duration (min)</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($records as $idx => $rec): 
                $duration = $rec['duration_minutes'] ?? 0;
                $full_name = $rec['first_name'] . ' ' . $rec['middle_name'] . ' ' . $rec['last_name'];
            ?>
            <tr>
                <td style="text-align: center; font-weight: 700; color: #667eea;"><?= $idx + 1 ?></td>
                <td style="font-family: 'Courier New', monospace; font-size: 13px; color: #444;"><?= htmlspecialchars($rec['id_number']) ?></td>
                <td style="font-weight: 600; color: #1a1a2e;"><?= htmlspecialchars($full_name) ?></td>
                <td style="color: #666;"><?= htmlspecialchars($rec['course']) ?></td>
                <td><?= htmlspecialchars($rec['purpose']) ?></td>
                <td><span class="lab-badge"><?= htmlspecialchars($rec['lab']) ?></span></td>
                <td style="text-align: center;"><?= date('h:i A', strtotime($rec['time_in'])) ?></td>
                <td style="text-align: center;"><?= $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '<span style=\"color: #bbb; font-style: italic;\">-</span>' ?></td>
                <td style="text-align: center; font-weight: 700; color: #333;"><?= round($duration, 1) ?></td>
                <td style="text-align: center;">
                    <span class="status status-<?= strtolower($rec['status']) ?>"><?= htmlspecialchars($rec['status']) ?></span>
                </td>
                <td style="color: #888; font-size: 12px;"><?= date('M d, Y', strtotime($rec['time_in'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function generateAllStudentsCSV($records) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="all_students_sitin_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['#', 'ID Number', 'Student Name', 'Course', 'Purpose', 'Lab', 'Time In', 'Time Out', 'Duration (min)', 'Status', 'Date']);
    
    foreach($records as $idx => $rec) {
        $duration = $rec['duration_minutes'] ?? 0;
        $full_name = $rec['first_name'] . ' ' . $rec['middle_name'] . ' ' . $rec['last_name'];
        fputcsv($output, [
            $idx + 1,
            $rec['id_number'],
            $full_name,
            $rec['course'],
            $rec['purpose'],
            $rec['lab'],
            date('h:i A', strtotime($rec['time_in'])),
            $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '',
            round($duration, 1),
            $rec['status'],
            date('M d, Y', strtotime($rec['time_in']))
        ]);
    }
    fclose($output);
    exit();
}

function generateFeedbackReport($conn, $user, $format, $is_admin) {
    $query = "
        SELECT 
            f.id,
            f.id_number,
            st.first_name,
            st.middle_name,
            st.last_name,
            st.course,
            f.rating,
            f.comments,
            f.created_at,
            s.purpose,
            s.lab
        FROM feedback f
        INNER JOIN students st ON f.id_number = st.id_number
        LEFT JOIN sitin_records s ON f.record_id = s.id
        ORDER BY f.created_at DESC
    ";
    
    $result = $conn->query($query);
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    // Calculate stats
    $total_feedback = count($records);
    $avg_rating = $total_feedback > 0 ? array_sum(array_column($records, 'rating')) / $total_feedback : 0;
    
    $rating_counts = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
    foreach ($records as $r) {
        $rating_counts[$r['rating']]++;
    }
    
    if ($format === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><title>Feedback Report</title>';
        echo '<style>
            @media print { @page { margin: 0.5cm; } body { -webkit-print-color-adjust: exact; } }
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #fff; }
            .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .summary-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
            .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
            .summary-box .number { font-size: 28px; font-weight: 700; display: block; }
            .summary-box .label { font-size: 11px; opacity: 0.9; text-transform: uppercase; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #667eea; color: white; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; }
            td { padding: 12px; border-bottom: 1px solid #eee; font-size: 12px; }
            tr:hover { background: #f8f9fa; }
            .rating-badge { display: inline-flex; align-items: center; gap: 3px; padding: 4px 10px; border-radius: 10px; font-weight: 600; font-size: 12px; }
            .rating-5 { background: rgba(46,204,113,0.15); color: #27ae60; }
            .rating-4 { background: rgba(52,152,219,0.15); color: #2980b9; }
            .rating-3 { background: rgba(241,196,15,0.15); color: #f39c12; }
            .rating-2 { background: rgba(230,126,34,0.15); color: #e67e22; }
.rating-1 { background: rgba(231,76,60,0.15); color: #c0392b; }
            .lab-badge {
                display: inline-block;
                background: rgba(102, 126, 234, 0.1);
                padding: 4px 10px;
                border-radius: 8px;
                font-size: 12px;
            }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            @media print { body { background: white; } .report-container { box-shadow: none; border: 1px solid #ddd; } .print-btn { display: none; } }
            /* Responsive table */
            @media (max-width: 1000px) {
                table { font-size: 11px; }
                th, td { padding: 8px 6px; }
                .summary-stats { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 768px) {
                table { font-size: 10px; }
                th, td { padding: 6px 4px; }
                .summary-stats { grid-template-columns: 1fr; }
            }
        </style>';
        echo '</head><body>';
        echo '<div class="report-container">';
        echo '<button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>';
        echo '<div class="report-header"><h1>⭐ Feedback Report</h1><p>Generated on: ' . date('F d, Y \\a\\t h:i A') . '</p></div>';
        echo '<div class="summary-stats">';
        echo '<div class="summary-box"><span class="number">' . $total_feedback . '</span><span class="label">Total Feedback</span></div>';
        echo '<div class="summary-box"><span class="number">' . number_format($avg_rating, 1) . '</span><span class="label">Average Rating</span></div>';
        echo '<div class="summary-box"><span class="number">' . ($rating_counts[5] + $rating_counts[4]) . '</span><span class="label">Positive (4-5★)</span></div>';
        echo '<div class="summary-box"><span class="number">' . ($rating_counts[1] + $rating_counts[2]) . '</span><span class="label">Negative (1-2★)</span></div>';
        echo '</div>';
        // Render table inline
        ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>ID Number</th>
                    <th>Course</th>
                    <th>Rating</th>
                    <th>Comments</th>
                    <th>Sit-in Purpose</th>
                    <th>Lab</th>
                    <th>Date Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($records as $idx => $rec): 
                    $full_name = $rec['first_name'] . ' ' . $rec['middle_name'] . ' ' . $rec['last_name'];
                ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($full_name) ?></td>
                    <td><?= htmlspecialchars($rec['id_number']) ?></td>
                    <td><?= htmlspecialchars($rec['course']) ?></td>
                    <td>
                        <span class="rating-badge rating-<?= $rec['rating'] ?>">
                            <?= str_repeat('★', $rec['rating']) ?> <?= $rec['rating'] ?>/5
                        </span>
                    </td>
                    <td><?= htmlspecialchars($rec['comments'] ?: 'No comments') ?></td>
                    <td><?= htmlspecialchars($rec['purpose'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($rec['lab'] ?? 'N/A') ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($rec['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        echo '</div></body></html>';
        return;
    }
    
    // HTML view
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Feedback Report</title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { background: #f5f5f5; padding: 20px; }
            .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
            .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
            .report-header h1 { color: #1a1a2e; margin: 0; font-size: 28px; }
            .report-header p { color: #666; margin: 5px 0 0; }
            .summary-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
            .summary-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
            .summary-box .number { font-size: 28px; font-weight: 700; display: block; }
            .summary-box .label { font-size: 11px; opacity: 0.9; text-transform: uppercase; }
            .print-btn { display: block; margin: 20px auto; padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
            @media print { body { background: white; } .report-container { box-shadow: none; border: 1px solid #ddd; } .print-btn { display: none; } }
        </style>
    </head>
    <body>
        <div class="report-container">
            <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
            <div class="report-header">
                <h1>⭐ Feedback Report</h1>
                <p>Generated on: <?= date('F d, Y \\a\\t h:i A') ?></p>
            </div>
            
            <div class="summary-stats">
                <div class="summary-box">
                    <span class="number"><?= $total_feedback ?></span>
                    <span class="label">Total Feedback</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= number_format($avg_rating, 1) ?></span>
                    <span class="label">Average Rating</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= ($rating_counts[5] + $rating_counts[4]) ?></span>
                    <span class="label">Positive (4-5★)</span>
                </div>
                <div class="summary-box">
                    <span class="number"><?= ($rating_counts[1] + $rating_counts[2]) ?></span>
                    <span class="label">Negative (1-2★)</span>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>ID Number</th>
                        <th>Course</th>
                        <th>Rating</th>
                        <th>Comments</th>
                        <th>Sit-in Purpose</th>
                        <th>Lab</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($records as $idx => $rec): 
                        $full_name = $rec['first_name'] . ' ' . $rec['middle_name'] . ' ' . $rec['last_name'];
                    ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($full_name) ?></td>
                        <td><?= htmlspecialchars($rec['id_number']) ?></td>
                        <td><?= htmlspecialchars($rec['course']) ?></td>
                        <td>
                            <span class="rating-badge rating-<?= $rec['rating'] ?>">
                                <?= str_repeat('★', $rec['rating']) ?> <?= $rec['rating'] ?>/5
                            </span>
                        </td>
                        <td><?= htmlspecialchars($rec['comments'] ?: 'No comments') ?></td>
                        <td><?= htmlspecialchars($rec['purpose'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($rec['lab'] ?? 'N/A') ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($rec['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
}

