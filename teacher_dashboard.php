<?php
// teacher_dashboard.php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard - Evaluation Summary</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #ecf0f1;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .summary {
            margin-top: 30px;
        }
        .summary h3 {
            margin-bottom: 10px;
            color: #34495e;
        }
        .summary p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Teacher Dashboard - Evaluation Summary</h2>

    <table>
        <thead>
            <tr>
                <th>Teacher</th>
                <th>Average Rating</th>
                <th>Most Mentioned Comment</th>
                <th>Evaluated by</th>
            </tr>
        </thead>
        <tbody>
            <!-- Sample Data - Replace with dynamic content -->
            <tr>
                <td>Mr. Smith</td>
                <td>4.6</td>
                <td>Clear explanations</td>
                <td>45 students</td>
            </tr>
            <tr>
                <td>Ms. Johnson</td>
                <td>4.2</td>
                <td>Engaging activities</td>
                <td>38 students</td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <h3>Summary Insights</h3>
        <p><strong>Total Teachers Evaluated:</strong> 12</p>
        <p><strong>Highest Rated Teacher:</strong> Mr. Smith (4.6)</p>
        <p><strong>Common Suggestion:</strong> Provide more examples in lessons</p>
    </div>
</div>
</body>
</html>
