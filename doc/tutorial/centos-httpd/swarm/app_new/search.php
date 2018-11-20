<html>
<head>
    <?php include('include/header.php'); ?>
</head>
<body>
<?php include('include/navbar.php'); ?>
<main class="container">
<?php
	$servername = "db";
	$username = "demo";
	$password = "demo123";
	$dbname = "registry";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
		echo "<div class='alert alert-danger mt-5'>".
             "<strong>Connection failed:</strong> " . $conn->connect_error.
			 "</div>";
	} else {
		$sql = "SELECT id, name, email FROM users WHERE name LIKE '%" + $_POST["name"] + "%'";
		$result = $conn->query($sql);
        
		if ($result->num_rows > 0) {
		    echo "<h1 class='mt-5'>Found users:</h1>";
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
		    echo "<div class='alert alert-danger mt-5'>".
                 "<strong>SQL error:</strong> " . $sql . "<br>". $conn->connect_error.
		         "</div>";
		}
	}
?>
</main>
<?php include('include/footer.php'); ?>
</body>
</html>
