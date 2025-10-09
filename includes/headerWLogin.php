<?php
SESSION_START();
if(!isset($_SESSION['userid'])) {
  die('<script>window.location.href= "./includes/login.php";</script>');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" media="all" href="./includes/stylesheet.css">
  <title>EggMaster</title>
  <link rel="icon" href="./favicon.ico" type="image/x-icon">
</head>
<?php
  include("./includes/connect.php");
  include("./includes/functions.php");
?>
<body>
  <header> 
    EggMaster
  </header>  
  <nav>
    <?php
    include("./includes/nav.php");
    ?>
  </nav>
  <article>
    <h2><?=$site_name?></h2>
