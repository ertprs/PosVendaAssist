<?php
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
?>

<form method="post" action="upload.php" enctype="multipart/form-data">
<br><br>&nbsp;&nbsp;&nbsp;Arquivo
<input type="file" name="arquivo" />
<input type="submit" value="Enviar" />
</form>
<?
include 'rodape.php';
?>