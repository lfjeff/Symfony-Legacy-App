<?php
// this is my horrible legacy code
//
session_start();

?>
<html>
<head>
<title>Legacy App Show Data</title>
</head>
<body>
<br/>
Legacy - Show Data Page
<br/>
<br/>
$_SESSION[userid] = <?php echo $_SESSION['userid'] ?><br/>
$_SESSION[username] = <?php echo $_SESSION['username'] ?><br/>
<br/>
<br/>
<br/>
<a href="/">Home<a/>
</form>
</body>
</html>
