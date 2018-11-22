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
        $sql = "INSERT INTO users (name, email)
        VALUES ('" . $_POST['name']. "', '". $_POST['email']. "')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<h1 class='mt-5'>Your registration</h1>";
            echo "<div class='card'>";
            echo "<div class='card-header'>Hello ". $_POST["name"]. "!</div>";
            echo "<div class='card-body'>".
                 "Your email ". $_POST["email"]. " has been registered successfully.".
                 "</div>";
            echo "</div>";
        } else {
	        echo "<div class='alert alert-danger mt-5'>".
                 "<strong>SQL error:</strong> " . $sql . "<br>". $conn->connect_error.
	             "</div>";
        }
	$conn->close();
    }
?>
</main>
<?php include('include/footer.php'); ?>
</body>
</html>
