<?
/*GUSTAVO, criei a tbl_loja_site onde serуo gravadas as informaчѕes basicas de configuraчуo da loja virtual e setamos elas como globais.
Alterar todos os programas que vocъ deixou fixo loja 27 para a variavel $login_empresa
Na tabela tbl_loja_site tambem tem o texto de apresentacao do site (quem somos).
Criar uma area no ERP para cadastro dessas informaчѕes*/
$sql = "SELECT empresa, cabecalho,email
		from tbl_loja_site
		WHERE tbl_loja_site.empresa = 27";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$login_empresa    = trim (pg_result ($res,0,empresa));
	$imagem_cabecalho = trim (pg_result ($res,0,cabecalho));
	$email_faleconosco= trim (pg_result ($res,0,email));

	global $login_empresa      ;
	global $imagem_cabecalho   ;
	global $email_faleconosco   ;
}else{
echo "Dados cadastrais da loja virtual nуo cadastrados";
}

?>