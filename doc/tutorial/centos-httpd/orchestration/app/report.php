<html>
<head>
	<!-- bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<!-- fontawesome CSS -->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
	<!-- jquery javascript -->
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<!-- bootstrap javascript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
</head>
<body>
	<div class="container">
	<h2>List of registered users:</h2>

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
    echo "<table class='table'><thead><tr>";
    echo "<td>ID</td>";
    echo "<td>Name</td>";
    echo "<td>Email</td>";
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
<div class="card">
	<div class="card-body">
		<a href="index.html"><span class="fa fa-home"></span> Form</a>
	</div>
</div>
</div>
</body>
</html>
