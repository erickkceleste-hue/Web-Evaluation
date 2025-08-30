<?php
// view_student_evaluations.php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Student Evaluations</title>
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
            margin-bottom: 20px;
            color: #2c3e50;
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
    </style>
</head>
<body>
<div class="container">
    <h2>Student Evaluation Results</h2>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Teacher</th>
                <th>Question</th>
                <th>Rating</th>
                <th>Comments</th>
            </tr>
        </thead>
        <tbody>
            <!-- Sample Data - Replace with dynamic content -->
            <tr>
                <td>202310001</td>
                <td>Mr. Smith</td>
                <td>Explains lessons clearly</td>
                <td>5</td>
                <td>Very clear and helpful</td>
            </tr>
            <tr>
                <td>202310002</td>
                <td>Ms. Johnson</td>
                <td>Engages with students</td>
                <td>4</td>
                <td>Good, but can improve</td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
