<html>
<head>
	<!-- fontawesome CSS -->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
	<!-- jquery javascript -->
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<!-- bootstrap javascript -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
	<!-- bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>
<body>
<div class="container">

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

$sql = "INSERT INTO users (name, email)
VALUES ('" . $_POST['name']. "', '". $_POST['email']. "')";

if ($conn->query($sql) === TRUE) {
    echo "<h2>Your registration</h2>";
    echo "<div class='card'>";
    echo "<div class='card-header'>Hello ". $_POST["name"]. "!</div>";
    echo "<div class='card-body'>".
         "Your email ". $_POST["email"]. " has been registered successfully.".
         "</div>";
    echo "</div>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>

<div class="card">
	<div class="card-body">
		<a href="index.html"><span class="fa fa-home"></span> Form</a>
		<a href="report.php"><span class="fa fa-eye"></span> Report</a>
	</div>
</div>
</div>
</body>
</html>
