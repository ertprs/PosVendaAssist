<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

$title = "Consultas";


?>
<link rel="stylesheet" type="text/css" href="css/css/ext-all.css"/>
<link rel="stylesheet" type="text/css" href="css/ext.css"/>

<script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
<script type="text/javascript" src="../js/ext-jquery-adapter.js"></script>
<script type="text/javascript" src="../js/ext-all3.js"></script>
<script type="text/javascript" src="js/consultas.js"></script>

<? include "menu.php";?>
<center>
<div id='resultado'></div>
<br/>
<div id='consulta'></div>
</center>
