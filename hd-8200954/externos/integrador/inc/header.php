<!-- Topo -->
<?php
if(empty($_SESSION['header']) and !strpos($_SERVER['PHP_SELF'], 'config.php'))
	header("Location: config.php");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
      <meta charset="utf-8">
      <title>Rest Json / TXT</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="description" content="Integração Rest/JSON" />      
      <link rel="stylesheet" href="theme/bootstrap/css/bootstrap.min.css" />
      <link rel="stylesheet" href="theme/index.css" />  
      <script type="text/javascript" src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
    </head>

    <body>
      <div class="container-fluid env-conteudo">
            <div class="row-fluid">
                <div class="span2">
                  <?php include_once 'menu.php';?>          
                </div>
<!-- Topo -->