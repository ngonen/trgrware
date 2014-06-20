<?php

	use google\appengine\api\users\User;
	use google\appengine\api\users\UserService;

	# Looks for current Google account session
	$user = UserService::getCurrentUser();

	if (!$user) {
		header('Location: ' . UserService::createLoginURL($_SERVER['REQUEST_URI']));
		die;
	}
?>

<html>
	<head>
	  <link type="text/css" rel="stylesheet" href="/stylesheets/main.css" />
	</head>

  	<body>
	    <?php
		    if ($user) {
				echo 'Hello, ' . htmlspecialchars($user->getNickname());

				if (array_key_exists('content', $_POST)) {
					echo "<br/><br/>";
			      	echo "You wrote:<pre>";
			      	echo htmlspecialchars($_POST['content']);
			      	echo "</pre>";
			    }
	    ?>

	    <form action="/sign" method="post">
	      <div><textarea name="content" rows="3" cols="60"></textarea></div>
	      <div><input type="submit" value="Sign Guestbook"></div>
	    </form>

		<?php } ?>
  	</body>
</html>