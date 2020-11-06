<?php
setcookie("cook_pedidoweb", "", time() - 28800);
setcookie("cook_fabrica", "", time() - 28800);
setcookie("cook_admin", "", time() - 28800);
header('Location: index.php'); 
?>
