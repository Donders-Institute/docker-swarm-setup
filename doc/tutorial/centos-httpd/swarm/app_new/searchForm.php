<html>
<head>
	<?php include('include/header.php'); ?>
</head>
<body>
	<?php include('include/navbar.php'); ?>
	<main class="container">
		<h1 class="mt-5">Search</h1>
                <form action="search.php">
		<div class="input-group">
			<input type="text" class="form-control input-lg" id="name" name="name" placeholder="Search for name"></input>
			<div class="input-group-btn">
				<button class="btn btn-info btn-lg" type="submit"><i class="fa fa-search"></i></button>
			</div>
		</div>
                </form>
	</main>
	<?php include('include/footer.php'); ?>
</body>
</html>
