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
<?php include('include/footer.php'); ?>
</div>
</body>
</html>
