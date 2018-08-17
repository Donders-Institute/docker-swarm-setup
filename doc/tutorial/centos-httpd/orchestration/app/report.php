<html>
<head>
<?php include('include/header.php'); ?>
</head>
<body>
<div class="container-fluid">
<?php include('include/navbar.php'); ?>
<?php
$servername = "db";
$username = "demo";
$password = "demo123";
$dbname = "registry";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "</div></body></html>");
}

$sql = "SELECT id, name, email FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>List of registered users:</h2>";
    echo "<table class='table'><thead><tr>";
    echo "<th>ID</th>";
    echo "<th>Name</th>";
    echo "<th>Email</th>";
    echo "</tr></thead>";
    echo "<tbody>";

    // output data of each row
    while($row = $result->fetch_assoc()) {
	echo "<tr>";
        echo "<td>". $row["id"]. "</td>";
        echo "<td>". $row["name"]. "</td>";
        echo "<td>". $row["email"]. "</td>";
	echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
} else {
    echo "0 results";
}
$conn->close();
?>
<?php include('include/footer.php'); ?>
</div>
</body>
</html>
