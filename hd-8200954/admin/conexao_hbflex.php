<?

$db_host = "70.86.75.82";
$db_user = "hbflexc_garantia";
$db_pass = "h2f0e0x7bl";
$db_name = "hbflexc_garantia";
    
$con = mysql_connect("$db_host","$db_user","$db_pass") or die ("Not possible make connection\n".mysql_error());
mysql_select_db("$db_name",$con);


/*
Dados do login.
Nome do banco: hbflexc_garantia
Usuário: hbflexc_garantia
Senha: h2f0e0x7bl
Estrutura.
Tabela: garantia
Banco de dados: Mysql
host: www.hbflex.com
IP: 70.86.75.82
porta padrao: 3306

*/

?>

