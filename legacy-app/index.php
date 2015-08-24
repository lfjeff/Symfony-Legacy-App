<?php
// this is my horrible legacy code
//
session_start();

if ($_POST) {
    $userid = @$_POST['userid'];
    $username = @$_POST['username'];

    $_SESSION['userid'] = $userid;
    $_SESSION['username'] = $username;
}

$time = date('c');

?>
<html>
<head>
<title>Legacy App Home Page</title>
</head>
<body>
<br/>
Legacy App Home Page
<br/>
<br/>
Time: <?php echo $time?>
<br/>
<br/>
<br/>
<form method="POST">
Please enter:
<br/>
userid: <input type="text" name="userid" value="<?=$userid?>"><br/>
username: <input type="text"" name="username" value="<?=$username?>"><br/>
<br/>
<input type="submit" value="Submit">
<br/>
<br/>
<a href="show_data.php">Show Data in Legacy Code</a>
<br/>
<a href="showdata">Show Data in Symfony</a>
</form>
</body>
</html>

