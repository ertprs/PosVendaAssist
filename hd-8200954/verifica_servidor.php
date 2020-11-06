<?php
include '/etc/telecontrol.cfg';
echo (is_resource(@pg_connect("host=$dbhost dbname=$dbnome port=$dbport user=$dbusuario password=$dbsenha"))) ? "1":"2";
?>