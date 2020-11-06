<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$admin_privilegios = 'financeiro';

if ($login_fabrica <> 1) {
	header ("Location: menu_financeiro.php");
	exit;
}

if (strlen($extrato) > 0 and $extrato <= '560482') {//HD 237471
	header("Location: os_extrato_detalhe_print_blackedecker.php?extrato=$extrato");
	exit;
}

if (strlen(trim($_GET["extrato"])) > 0) $extrato = trim($_GET["extrato"]);
if($extrato){
	$sql = "SELECT  tbl_posto_fabrica.tipo_posto            ,
					tbl_posto_fabrica.posto                 ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM    tbl_posto_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto_fabrica.posto
			WHERE   tbl_extrato.extrato       = $extrato
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) == 1) {
		$posto                  = trim(pg_fetch_result($res,0,posto));
		$tipo_posto             = trim(pg_fetch_result($res,0,tipo_posto));
		$reembolso_peca_estoque = trim(pg_fetch_result($res,0,reembolso_peca_estoque));
	}
}

include_once('../anexaNF_inc.php');
$layout_menu = "financeiro";
$title = "Black & Decker - Detalhe Extrato - Ordem de Serviço";
?>
<html>
<head>
<title><? echo $title ?></title>
<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
<meta http-equiv="Expires"       content="0">
<meta http-equiv="Pragma"        content="no-cache, public">
<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
<link type="text/css" rel="stylesheet" href="css/css_press.css">

<script type="text/javascript" src="../js/jquery-1.6.2.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link type="text/css" href="../plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">

<style>
/*******************************
 ELEMENTOS DE COR FONTE EXTRATO
*******************************/
.TdBold   {font-weight: bold;}
.TdNormal {font-weight: normal;}
.TdCompres{background-color: #cbcbcb;}
</style>

<script type="text/javascript">
$(window).load(function(){
	Shadowbox.init();
});

function alteraNf(os){
	Shadowbox.open({
		content : "../os_altera_nfs_blackedecker.php?os="+os+"&amb=admin",
		player 	: "iframe",
		title	: "Atualiza Nota Fiscal",
		width 	: 800,
		height	: 500,
		options : {
			onClose: retornoShadow
		}
	});
}

function retornoShadow(){
	window.location.reload();
}
</script>

</head>
<body>
<TABLE width="600px" border="0" cellspacing="1" cellpadding="0">
<TR>
	<TD>
		<!--
			<IMG SRC="logos/cabecalho_print_<? echo strtolower ($login_fabrica_nome) ?>.png" ALT="ORDEM DE SERVIÇO" style='width: 130px;'>
		-->
		<IMG SRC="logos/logo_black_2016.png" ALT="ORDEM DE SERVIÇO" style='width: 180px;'>
	</TD>
</TR>
</TABLE>
<br>
<?
if (strlen($extrato) > 0) {
	$sql = "SELECT sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1))) 
				from tbl_os
				join tbl_os_extra using(os)
				join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
				where tbl_os_extra.extrato = $extrato
				and tbl_os.pecas > 0
				and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0
	 ";
	$resX = pg_query($con, $sql);
	$totalTx = pg_fetch_result($resX,0, 0); 

	$data_atual = date("d/m/Y");
	$sql = "SELECT  to_char(min(tbl_os.data_fechamento),'DD/MM/YYYY') AS inicio,
					to_char(max(tbl_os.data_fechamento),'DD/MM/YYYY') AS final
			FROM    tbl_os
			JOIN    tbl_os_extra USING (os)
			WHERE   tbl_os_extra.extrato = $extrato
			  AND   tbl_os.fabrica = $login_fabrica;";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		$inicio_extrato = trim(pg_fetch_result($res,0,'inicio'));
		$final_extrato  = trim(pg_fetch_result($res,0,'final'));
	}
	if (strlen($inicio_extrato) == 0 AND strlen($final_extrato) == 0) {
		$sql = "SELECT  to_char(min(tbl_extrato.data_geracao),'DD/MM/YYYY') AS inicio,
						to_char(max(tbl_extrato.data_geracao),'DD/MM/YYYY') AS final
				FROM    tbl_extrato
				WHERE   tbl_extrato.extrato = $extrato
				 AND    tbl_extrato.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$inicio_extrato = trim(pg_fetch_result($res,0,'inicio'));
			$final_extrato  = trim(pg_fetch_result($res,0,'final'));
		}
	}

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto                                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto_fabrica.contato_endereco AS endereco          ,
					tbl_posto_fabrica.contato_cidade   AS cidade            ,
					tbl_posto_fabrica.contato_estado   AS estado            ,
					tbl_posto_fabrica.contato_cep      AS cep               ,
					tbl_posto_fabrica.contato_fone_comercial AS fone        ,
					tbl_posto_fabrica.contato_fax            AS fax         ,
					tbl_posto_fabrica.contato_nome           AS contato     ,
					tbl_posto_fabrica.contato_email    AS email             ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta                                 ,
					tbl_extrato.protocolo                                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data ,
                    tbl_extrato_financeiro.admin_pagto
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto.posto
            LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
			WHERE   tbl_extrato.extrato = $extrato
			  AND   tbl_extrato.fabrica = $login_fabrica;";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$codigo       = trim(pg_fetch_result($res,0,codigo_posto));
		$posto        = trim(pg_fetch_result($res,0,posto));
		$nome         = trim(pg_fetch_result($res,0,nome));
		$endereco     = trim(pg_fetch_result($res,0,endereco));
		$cidade       = trim(pg_fetch_result($res,0,cidade));
		$estado       = trim(pg_fetch_result($res,0,estado));
		$cep          = substr(pg_fetch_result($res,0,cep),0,2) .".". substr(pg_fetch_result($res,0,cep),2,3) ."-". substr(pg_fetch_result($res,0,cep),5,3);
		$fone         = trim(pg_fetch_result($res,0,fone));
		$fax          = trim(pg_fetch_result($res,0,fax));
		$contato      = trim(pg_fetch_result($res,0,contato));
		$email        = trim(pg_fetch_result($res,0,email));
		$cnpj         = trim(pg_fetch_result($res,0,cnpj));
		$ie           = trim(pg_fetch_result($res,0,ie));
		$banco        = trim(pg_fetch_result($res,0,banco));
		$agencia      = trim(pg_fetch_result($res,0,agencia));
		$conta        = trim(pg_fetch_result($res,0,conta));
		$data_extrato = trim(pg_fetch_result($res,0,data));
        $protocolo    = trim(pg_fetch_result($res,0,protocolo));

        $admin_pagto  = pg_fetch_result($res, 0, "admin_pagto");
        if (strlen($admin_pagto)>0){
            $sql = "SELECT nome_completo
                    FROM   tbl_admin
                    WHERE  admin = $admin_pagto";
            $res_adm = pg_query($con,$sql);

            if (pg_num_rows($res_adm) > 0){
                $nome_completo = pg_fetch_result($res_adm,0,"nome_completo");
            }
        }

        $verificaAdmin = "SELECT nome_completo, conferido
 				 FROM tbl_extrato_status
				 JOIN tbl_admin ON tbl_extrato_status.admin_conferiu = tbl_admin.admin
				 WHERE extrato = {$extrato}";
        $resConferido = pg_query($con, $verificaAdmin);
        $numRowsConferido  = pg_num_rows($resConferido);
        if($numRowsConferido > 0){
            $admin_conferido = pg_fetch_result($resConferido, 0, "nome_completo");
            $data_conferido = date("d/m/Y" , strtotime(pg_fetch_result($resConferido, 0, "conferido")));
        }
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' width='100%' align='left' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>BLACK & DECKER DO BRASIL LTDA</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>End.</b> Rod. BR 050 S/N KM 167-LOTE 5 QVI &nbsp;&nbsp;-&nbsp;&nbsp; <b>Bairro:</b> DI II</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td nowrap bgcolor='#FFFFFF' width='100%' colspan=2 align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Cidade:</b> Uberaba &nbsp;&nbsp;-&nbsp;&nbsp; <b>Estado:</b> MG &nbsp;&nbsp;-&nbsp;&nbsp; <b>Cep:</b> 38064-750</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição CNPJ: 53.296.273/0001-91</font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='50%' align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>Inscrição Estadual: 701.948.711.00-98</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' nowrap align='left'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>EXTRATO DE SERVIÇOS $data_extrato</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' nowrap align='right' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>$protocolo</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF'  nowrap align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' nowrap  align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Até:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='120' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='230' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_atual</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Código:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Posto:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$nome</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Endereço:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left' nowrap>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$endereco</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='left' width='70'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Cidade:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='530' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cidade - $estado - $cep</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Telefone:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$fone</font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fax:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$fax</font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>E-mail:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='250' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$email</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>CNPJ:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='130' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$cnpj</font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='30' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>IE:</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='370' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$ie</font>\n";
		echo "</td>\n";
		echo "</tr>\n";
        echo "</table>";

        if(!empty($nome_completo) OR (!empty($admin_conferido) && !empty($data_conferido))){
            echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>";
            echo "<tr>\n";
            if(!empty($nome_completo)){
                echo "<td bgcolor='#FFFFFF' align='left' width='168'>\n";
                echo "<br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Admin que aprovou o pagamento:</b></font>\n";
                echo "</td>\n";

                echo "<td bgcolor='#FFFFFF' align='left'>\n";
                echo "<br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$nome_completo</font>\n";
                echo "</td>\n";
            }
            if(!empty($admin_conferido) && !empty($data_conferido)){
                echo "<td bgcolor='#FFFFFF' align='left' width='168'>\n";
                echo "<br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Admin que conferiu a pendência:</b></font>\n";
                echo "</td>\n";

                echo "<td bgcolor='#FFFFFF' align='left'>\n";
                echo "<br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$admin_conferido em $data_conferido</font>\n";
                echo "</td>\n";
            }
            echo "</tr>\n";
            echo "</table>\n";
        }
	}

	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td bgcolor='#FFFFFF' width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	$xtotal = 0;

	#HD:83010 -RETORNO VISITA ENTRA COMO MÃO DE OBRA NA OS-GEO METAL
	# lancamento | fabrica |         descricao          | debito_credito | ativo
	#------------+---------+----------------------------+----------------+-------
	#        112 |       1 | OS GEO - Retorno de Visita | C              | t
	$sql = "SELECT  total_os_geo_visita
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res_retorno = pg_query ($con,$sql);
	if (pg_num_rows($res_retorno) > 0) {
		$total_retorno= pg_fetch_result($res_retorno,0,total_os_geo_visita);
	}

	### OS NORMAL
//			AND   (length(tbl_os.obs) = 0 OR tbl_os.obs isnull)
	$sql =	"SELECT tbl_os.os                                                     ,
					tbl_os.sua_os                                                 ,
                    tbl_os_campo_extra.campos_adicionais::JSONB->'TxAdm'            AS taxa_administrativa,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'TxAdmGrad'        AS taxa_administrativa_gradual,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'total_custo_peca' AS total_custo_peca,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'total_produto'    AS total_produto,
					tbl_os.consumidor_revenda                                     ,
					tbl_produto.produto 										  ,
					tbl_produto.familia                                           ,
					tbl_produto.linha                                             ,
					(SELECT sum(tbl_os_item.qtde)
		                FROM
		                tbl_os_produto
		                inner join tbl_os_item ON tbl_os_produto.os_produto =  tbl_os_item.os_produto
		                WHERE tbl_os_produto.os =  tbl_os.os) as qtde_pecas,
					tbl_produto.referencia             as produto_referencia      ,
					(SELECT SUM (case when valor_pecas_garantia_os_item is null then 0 else valor_pecas_garantia_os_item end + case when valor_pecas_compradas_os_item is null then 0 else valor_pecas_compradas_os_item end) FROM tbl_extrato_extra_item WHERE tbl_extrato_extra_item.os = tbl_os.os and extrato= $extrato) AS pecas  ,
					(SELECT valor_mo_os from tbl_extrato_extra_item where tbl_extrato_extra_item.os = tbl_os.os and extrato= $extrato) as mao_de_obra       ,
					tbl_os.nota_fiscal                                            ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf       ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					to_char (tbl_os.data_conserto  ,'DD/MM/YYYY')               AS conserto    ,
            		tbl_os.data_abertura - tbl_os.data_conserto::DATE           AS dias   ,     
					(tbl_os_extra.qtde_km * tbl_os_extra.valor_por_km) as qtde_km  ,
					(tbl_os_extra.qtde_horas * tbl_os_extra.valor_total_hora_tecnica) as valor_total_hora_tecnica  ,
					tbl_os_extra.valor_total_hora_tecnica as valor_total_hora_tecnicax,
					tbl_os_extra.mao_de_obra_adicional
			INTO     TEMP tmp_blackedecker_$login_admin
			FROM    tbl_os_extra
			JOIN    tbl_os USING (os)
			LEFT JOIN tbl_os_campo_extra USING (os)
			JOIN 	tbl_produto using(produto)
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			AND     ( tbl_os.satisfacao IS NULL OR tbl_os.satisfacao IS FALSE )
			AND     (
				(tbl_os.tipo_atendimento not in(17,18,35) OR tbl_os.tipo_atendimento is null OR length(tbl_os.tipo_atendimento::text)=0)
				OR
				/*hd 48756*/
				(tbl_os.tipo_atendimento in(17) AND tbl_os.os IN(4576449,4483522,4462249))
			)
			AND (tbl_os.tipo_os <>13 or tbl_os.tipo_os is null);

			SELECT * FROM tmp_blackedecker_$login_admin
			ORDER BY lpad(substr(sua_os,0,strpos(sua_os,'-'))::text,20,'0') ASC,
					replace(lpad(substr(sua_os,strpos(sua_os,'-'))::text,20,'0'),'-','') ASC;";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {

		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' width='20%' nowrap align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' nowrap align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF Data</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' nowrap align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Produto</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' nowrap  align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abertura</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' nowrap  align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Conserto</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' nowrap  align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Dias</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF'  nowrap width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fechamento</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='5%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Km</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Valor Adicional</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Hora Trab.</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total MO</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Taxa Adm.</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>% Taxa Adm.</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='5%' align='center' >\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='5%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>% Custo Peça X Produto</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";

		$busca_array     = array();
		$localizou_array = array();
		$sql = "SELECT nota_fiscal FROM tbl_os JOIN tbl_os_extra USING(os) WHERE extrato = $extrato order by nota_fiscal";
		$res2 = pg_query ($con,$sql);
		for ($x = 0; $x < pg_num_rows($res2); $x++) {
			$nota_fiscal   = trim(pg_fetch_result($res2,$x,nota_fiscal));
			if (in_array($nota_fiscal, $busca_array)) {
				$localizou_array[] = $nota_fiscal;
				$z++;
			}
			$busca_array[] = $nota_fiscal;
		}
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$sua_os    = trim(pg_fetch_result($res,$x,sua_os));
			$os        = trim(pg_fetch_result($res,$x,os));
			$pecas     = trim(pg_fetch_result($res,$x,pecas));
			$maodeobra = trim(pg_fetch_result($res,$x,mao_de_obra));
			$valor_total_hora_tecnica = trim(pg_fetch_result($res,$x,valor_total_hora_tecnica));
			/*hd 51346*/
			$valor_total_hora_tecnicax = trim(pg_fetch_result($res,$x,valor_total_hora_tecnicax));
			$data_abertura   = trim(pg_fetch_result($res,$x,data_abertura));
			$data_fechamento = trim(pg_fetch_result($res,$x,data_fechamento));
			$nota_fiscal     = trim(pg_fetch_result($res,$x,nota_fiscal));
			$data_nf         = trim(pg_fetch_result($res,$x,data_nf));
			$qtde_km         = trim(pg_fetch_result($res,$x,qtde_km));
			$familia         = trim(pg_fetch_result($res,$x,familia));
			$consumidor_revenda         = trim(pg_fetch_result($res,$x,'consumidor_revenda'));
			$linha           = trim(pg_fetch_result($res,$x,linha));
			$mao_de_obra_adicional  = trim(pg_fetch_result($res,$x,mao_de_obra_adicional));
			$produto = trim(pg_fetch_result($res,$x,produto));
			$produto_referencia  = trim(pg_fetch_result($res,$x,produto_referencia));
			$produto_referencia = substr($produto_referencia,0,8);
			$total_os = $pecas + $maodeobra;

			$data_conserto      = trim(pg_fetch_result($res,$x,conserto));
			$dias_conserto      = trim(pg_fetch_result($res,$x,dias));

            $qtde_pecas = pg_fetch_result($res,$x,qtde_pecas);
            $taxa_administrativa_gradual    = pg_fetch_result($res,$x,taxa_administrativa_gradual);
            $valor_pecas                    = pg_fetch_result($res,$x,total_custo_peca);
            $valor_produto                  = pg_fetch_result($res,$x,total_produto);

			if ($familia==347 and ($linha==198 or $linha == 200)) {
				$cor_compress = "#dedede";
			}else{
				$cor_compress = "#FFFFFF";
			}

			$maodeobrax = $maodeobra;
			if($valor_total_hora_tecnicax>0) {
				$maodeobra = 0;
			}

			if(empty($valor_total_hora_tecnica)){
				$valor_total_hora_tecnica = 0;
			}

			$bold = "TdNormal";
			$negrito="";

			if(!empty($nota_fiscal)) {
				if (in_array($nota_fiscal, $localizou_array)) {
					$bold = "TdBold";
					$negrito ="<b>";
				}
			}

			echo "<tr class='$bold'>\n";
			echo "<td align='center' colspan='2'  nowrap bgcolor='$cor_compress'>\n";
			if ($familia==347 and ($linha==198 or $linha == 200)) { echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>*</b></font> ";}else{echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp;</font> ";}
			echo " <a href='os_press.php?os=$os' target='_blank'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $codigo$sua_os</font></a>\n";

			$anexo = $os;
			if ($consumidor_revenda == "R") {
				$sqlr = "SELECT tbl_os_revenda.os_revenda
							FROM tbl_os
							JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
							JOIN tbl_os_revenda_item USING(os_revenda)
							WHERE tbl_os.fabrica = $login_fabrica
							AND os = $os
							AND (os_lote = $os)";
				$resr = pg_query($con, $sqlr);
				if (pg_num_rows($resr)> 0 ) {
					$anexo = pg_fetch_result($resr, 0 , 'os_revenda');
				}
			}
			$temNFs = temNF($anexo, 'count');
 
			$linkVerAnexosNF = null;
			if(/*$nf_os == 't' or*/ $temNFs) {

				if ($temNFs > 0) {
					$linkNF = temNF($anexo, 'url');

					$linksNew = '';

					foreach ($linkNF as $key => $value) {
						$arqExt = pathinfo($value, PATHINFO_EXTENSION);
						$arqExt = preg_replace('/\?.+/','',$arqExt);
						switch($arqExt) {
							case 'gif':
							case 'jpg':
							case 'png':
								$linkVerAnexosNF = 'js/jpie/nf_digital_mlg_nf.php?os=' . $anexo;
								break;

						}
						if(empty($linkVerAnexosNF)) {
							$linkVerAnexosNF = $value;
						}

						if (!empty($linkVerAnexosNF)) {
							$linksNew .= 'window.open("'.$linkVerAnexosNF.'", "_blank"); ';
							$linkVerAnexosNF = ""; 
						}
					}
				}
				if(!empty($linksNew)){
					echo "<a href='javascript:void(0);' onclick='javascript:".$linksNew." '>" .
						"<img src='../helpdesk/imagem/clips.gif' style='cursor:pointer' alt='Com Anexo' />".
						"</a>";
				}
			}

			echo "</td>\n";
			echo "<td align='center'  nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><span style='color: #333;cursor: pointer;text-decoration: underline;' href='#' onClick='alteraNf($os)'>$nota_fiscal</span></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp;$data_nf</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><a href='lbm_consulta.php?produto=$produto' target='blank'>$produto_referencia</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_abertura</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_conserto</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>".abs($dias_conserto)."</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_fechamento</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap  colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($qtde_km,2,",",".") ."</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap  colspan='2' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($mao_de_obra_adicional,2,",",".") ."</a></font>\n";
			echo "</td>\n";
			echo "<td align='right'  nowrap bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor_total_hora_tecnica,2,",",".") ."</font>\n";
			echo "</td>\n";
			$maodeobra_total = $maodeobra + $mao_de_obra_adicional + $qtde_km + $valor_total_hora_tecnica;
			echo "<td align='right' bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($maodeobra_total,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
            if ($taxa_administrativa_gradual == 0.0) {
                $taxa_administrativa_gradual = 1;
            }
            $percTx = ($taxa_administrativa_gradual - 1) * 100;
            $valorTx = ($pecas * $taxa_administrativa_gradual) - $pecas;
			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valorTx,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($percTx,2,",",".") ." %</font>\n";
			echo "</td>\n";

			if (strlen($valor_pecas) > 0 && strlen($valor_produto) > 0) {
				$produto_pecas = ($valor_pecas * 100) / $valor_produto;
			} else {
				$produto_pecas = "0";
			}

			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";

			if (empty($qtde_pecas)) {
				$qtde_pecas = 0;
			}

			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$qtde_pecas</font>\n";
			echo "</td>\n";

			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($produto_pecas, 2, '.', '') ."%</font>\n";
			echo "</td>\n";


			echo "</tr>\n";
		}



		echo "<tr class='$bold'>\n";
		# Extrato 47265 foi o ultimo antes de 30/06
		echo "<td align='center' colspan='20'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>TOTAL DE PEÇAS COMPRADAS</a></font>\n";
		echo "</td>\n";

		$sql = "SELECT total_custo_peca_os_item
				FROM  tbl_extrato_extra
				WHERE tbl_extrato_extra.extrato = $extrato ";
		$resX = pg_query ($con,$sql);
		$total_pecas = 0 ;
		if (pg_num_rows ($resX) > 0) $total_pecas = pg_fetch_result ($resX,0,0);

		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr class='$bold'>\n";
		echo "<td align='center' colspan='20'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>TOTAL DE PEÇAS ENVIADAS EM GARANTIA</a></font>\n";
		echo "</td>\n";

		$sql = "SELECT total_pecas_garantia_os_item
				FROM  tbl_extrato_extra
				WHERE tbl_extrato_extra.extrato = $extrato ";
		$resX = pg_query ($con,$sql);
		$total_pecas = 0 ;
		if (pg_num_rows ($resX) > 0) $total_pecas = pg_fetch_result ($resX,0,0);

		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr class='$bold'>\n";
		echo "<td align='center' colspan='20'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>TOTAL DE MÃO DE OBRA</a></font>\n";
		echo "</td>\n";

		$sql = "SELECT total_mao_de_obra_os - total_mo_os_troca
				FROM  tbl_extrato_extra
				WHERE tbl_extrato_extra.extrato = $extrato ";
		$resX = pg_query ($con,$sql);
		$total_pecas = 0 ;
		if (pg_num_rows ($resX) > 0) $total_mao_obra = pg_fetch_result ($resX,0,0);
        echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_mao_obra +$total_retorno + $totalTx,2,",",".") ."</font>\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "<td align='right'>\n";
		echo "&nbsp;\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	### OS SATISFAÇÃO DEWALT
	$sql =	"SELECT tbl_os.os          ,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'total_custo_peca' AS total_custo_peca,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'total_produto'    AS total_produto,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'TxAdmGrad'        AS taxa_administrativa_gradual,
					(SELECT SUM (case when valor_pecas_garantia_os_item is null then 0 else valor_pecas_garantia_os_item end + case when valor_pecas_compradas_os_item is null then 0 else valor_pecas_compradas_os_item end) FROM tbl_extrato_extra_item WHERE tbl_extrato_extra_item.os = tbl_os.os and extrato= $extrato) AS pecas  ,
					(SELECT valor_mo_os from tbl_extrato_extra_item where tbl_extrato_extra_item.os = tbl_os.os and extrato= $extrato) as mao_de_obra       ,
					tbl_os.sua_os      ,
					CASE
						WHEN tbl_os.laudo_tecnico IS NOT NULL THEN
							tbl_os.laudo_tecnico
						ELSE
							tbl_laudo_tecnico_os.ordem::text
					END AS laudo_tecnico,
					tbl_os.nota_fiscal,
					to_char(tbl_os.data_nf,'DD/MM/YY') as data_nf                      ,
					tbl_produto.linha                                                    ,
					tbl_produto.familia                                                  ,
					tbl_produto.referencia             as produto_referencia             ,
					tbl_produto.produto,
					to_char(tbl_os.data_abertura,'DD/MM/YY')   AS data_abertura        ,
					to_char(tbl_os.data_fechamento,'DD/MM/YY') AS data_fechamento      ,
					(tbl_os_extra.qtde_km * tbl_os_extra.valor_por_km) as qtde_km        ,
					(tbl_os_extra.qtde_horas * tbl_os_extra.valor_total_hora_tecnica) as valor_total_hora_tecnica  ,
					tbl_os_extra.mao_de_obra_adicional,
					to_char (tbl_os.data_conserto  ,'DD/MM/YYYY')               AS conserto    ,
            		tbl_os.data_abertura - tbl_os.data_conserto::DATE           AS dias         
			INTO     TEMP tmp_blackedecker_2_$login_admin
			FROM    tbl_os_extra
			JOIN    tbl_os on tbl_os.os = tbl_os_extra.os
			join    tbl_produto on tbl_os.produto = tbl_produto.produto
			LEFT JOIN tbl_laudo_tecnico_os ON tbl_os.os = tbl_laudo_tecnico_os.os AND tbl_laudo_tecnico_os.fabrica = $login_fabrica
			left join tbl_os_campo_extra on tbl_os.os = tbl_os_campo_extra.os and tbl_os.fabrica = $login_fabrica
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_os.satisfacao IS TRUE
			AND     (tbl_os.tipo_os <> 13 or tbl_os.tipo_os is null);

			SELECT * FROM tmp_blackedecker_2_$login_admin
			ORDER BY substr(sua_os,0,strpos(sua_os,'-')) ASC,
					lpad(substr(sua_os,strpos(sua_os,'-')+1,length(sua_os))::text,5,'0') ASC,
					sua_os;";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='20%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";
		echo "<td width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF</b></font>\n";
		echo "</td>\n";
		echo "<td width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF Data</b></font>\n";
		echo "</td>\n";
		echo "<td width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Laudo Técnico</b></font>\n";
		echo "</td>\n";
		echo "<td width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Produto</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Aber.</b></font>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Conserto</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Dias</b></font>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fech.</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>KM</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Valor Adicional</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Hora Trab.</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total MO</b></font>\n";
		echo "</td>\n";
		echo "<td width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Taxa Adm.</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>% Taxa Adm.</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='5%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>% Custo Peça X Produto</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$sua_os        = trim(pg_fetch_result($res,$x,sua_os));
			$os            = trim(pg_fetch_result($res,$x,os));
			$pecas         = trim(pg_fetch_result($res,$x,pecas));
			$maodeobra     = trim(pg_fetch_result($res,$x,mao_de_obra));
			$laudo_tecnico = trim(pg_fetch_result($res,$x,laudo_tecnico));
			$nf            = trim(pg_fetch_result($res,$x,nota_fiscal));
			$nf_data       = trim(pg_fetch_result($res,$x,data_nf));

            $taxa_administrativa_gradual    = pg_fetch_result($res,$x,taxa_administrativa_gradual);
            $valor_pecas                    = pg_fetch_result($res,$x,total_custo_peca);
            $valor_produto                  = pg_fetch_result($res,$x,total_produto);

			$linha         = trim(pg_fetch_result($res,$x,linha));
			$familia       = trim(pg_fetch_result($res,$x,familia));
			$produto_referencia  = trim(pg_fetch_result($res,$x,produto_referencia));
			$produto_referencia  = substr($produto_referencia,0,8);
			$produto       = pg_fetch_result($res,$x,produto);
			$data_abertura   = trim(pg_fetch_result($res,$x,data_abertura));
			$data_fechamento = trim(pg_fetch_result($res,$x,data_fechamento));
			$qtde_km         = trim(pg_fetch_result($res,$x,qtde_km));
			$valor_total_hora_tecnica = trim(pg_fetch_result($res,$x,valor_total_hora_tecnica));
			$mao_de_obra_adicional  = trim(pg_fetch_result($res,$x,mao_de_obra_adicional));
			$cor_compress = ($familia==347 and ($linha==198 or $linha == 200)) ? "#dedede" : "#FFFFFF";
			$satisfacao_data_conserto      = trim(pg_fetch_result($res,$x,conserto));
			$satisfacao_dias_conserto      = trim(pg_fetch_result($res,$x,dias));

			$negrito = '';
			if(!empty($nf) and count($localizou_array) > 0) {
				if (in_array($nf, $localizou_array)) {
					$bold = "TdBold";
					$negrito ="<b>";
				}
			}

            if(strlen($valor_pecas)>0 AND strlen($valor_produto)>0){
                $produto_pecas = number_format(($valor_pecas *100)/$valor_produto, 2, '.', '') ."%";
            }else{
                $produto_pecas = "";
            }

			$total_os = $pecas + $maodeobra;
			echo "<tr>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>";
			echo ($familia==347 and ($linha==198 or $linha == 200)) ? "<b>*</b>" : " ";
			echo "</font> ";
			echo "<a href = os_press.php?os=$os target='blank'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $codigo$sua_os</a></font>&nbsp;\n";

			$anexo = $os;
			if ($consumidor_revenda == "R") {
				$sqlr = "SELECT tbl_os_revenda.os_revenda
							FROM tbl_os
							JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
							JOIN tbl_os_revenda_item USING(os_revenda)
							WHERE tbl_os.fabrica = $login_fabrica
							AND os = $os
							AND (os_lote = $os )";
				$resr = pg_query($con, $sqlr);
				if (pg_num_rows($resr)> 0 ) {
					$anexo = pg_fetch_result($resr, 0, "os_revenda");
				}
			}

			$temNFs = temNF($anexo, 'count');

			$linkVerAnexosNF = null;
			if(/*$nf_os == 't' or*/ $temNFs) {

				if ($temNFs > 0) {
					$linkNF = temNF($anexo, 'url');

					$linksNew = '';

					foreach ($linkNF as $key => $value) {
						$arqExt = pathinfo($value, PATHINFO_EXTENSION);
						$arqExt = preg_replace('/\?.+/','',$arqExt);
						switch($arqExt) {
							case 'gif':
							case 'jpg':
							case 'png':
								$linkVerAnexosNF = 'js/jpie/nf_digital_mlg_nf.php?os=' . $anexo;
								break;

						}
						if(empty($linkVerAnexosNF)) {
							$linkVerAnexosNF = $value;
						}

						if (!empty($linkVerAnexosNF)) {
							$linksNew .= 'window.open("'.$linkVerAnexosNF.'", "_blank"); ';
							$linkVerAnexosNF = "";
						}
					}
				}
				if(!empty($linksNew)){
					echo "<a href='javascript:void(0);' onclick='javascript:".$linksNew." '>" .
						"<img src='../helpdesk/imagem/clips.gif' style='cursor:pointer' alt='Com Anexo' />".
						"</a>";
				}
			}
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><span style='color: #333;cursor: pointer;text-decoration: underline;' href='#' onClick='alteraNf($os)'>$negrito $nf</span></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $nf_data</a></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $laudo_tecnico</a></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><a href='lbm_consulta.php?produto=$produto' target='blank'>$produto_referencia</a></font>";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $data_abertura</a></font>\n";
			echo "</td>\n";

			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $satisfacao_data_conserto</a></font>\n";
			echo "</td>\n";

			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito ".abs($satisfacao_dias_conserto)."</a></font>\n";
			echo "</td>\n";

			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $data_fechamento</a></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito". number_format($qtde_km,2,",",".") ."</a></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito". number_format($mao_de_obra_adicional,2,",",".") ." </a></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='center' colspan='2' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito". number_format($valor_total_hora_tecnica,2,",",".") ." </a></font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='right' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito". number_format($maodeobra,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='right' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito". number_format($pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
            if ($taxa_administrativa_gradual == 0.0) {
                $taxa_administrativa_gradual = 1;
            }
            $percTx = ($taxa_administrativa_gradual - 1) * 100;
            $valorTx = ($pecas * $taxa_administrativa_gradual) - $pecas;
			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valorTx,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td align='right' nowrap  bgcolor='$cor_compress'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($percTx,2,",",".") ."%</font>\n";
			echo "</td>\n";
			echo "<td width='15%' align='right' bgcolor='$cor_compress' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$produto_pecas</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	/*	OS GEO INICIO - BASEADA EM OS-REVENDA */
	### OS geo
	/*
	112 |       1 | OS GEO - Retorno de Visita   | C              | t
	113 |       1 | OS GEO - Despesas Adicionais | C              | t
	138 |       1 | OS GEO - Deslocamento de KM  | C              | t
	*/

	$sql =	"
			SELECT  os_revenda,
					(
						SELECT  count(tbl_os.os) as qtde
						FROM    tbl_os_revenda_item
						JOIN    tbl_os              ON tbl_os_revenda_item.os_lote = tbl_os.os
						JOIN    tbl_os_extra        ON tbl_os_extra.os             = tbl_os.os
						WHERE   tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
						AND     tbl_os_extra.extrato = $extrato
						AND     tbl_os.fabrica = $login_fabrica
						AND     tbl_os.tipo_os = 13
						AND     tbl_os.excluida is not true
					)as qtde,
					(
						SELECT sum(valor_mo_os_geo)
						FROM    tbl_extrato_extra_item
						JOIN    tbl_os              ON tbl_extrato_extra_item.os = tbl_os.os
						WHERE   tbl_extrato_extra_item.os_revenda = tbl_os_revenda.os_revenda
						AND     tbl_extrato_extra_item.extrato = $extrato
						AND     tbl_os.fabrica = $login_fabrica
						AND     tbl_os.tipo_os = 13
						AND     tbl_os.excluida is not true
					)as mao_de_obra,
					(SELECT  sum(valor_extrato_lancamento)
						FROM    tbl_extrato_extra_item
						WHERE   tbl_extrato_extra_item.extrato =$extrato
						AND     lancamento = 138
						AND     tbl_extrato_extra_item.os_revenda = tbl_os_revenda.os_revenda) as valor_km,
					(SELECT  sum(valor_extrato_lancamento)
						FROM    tbl_extrato_extra_item
						WHERE   tbl_extrato_extra_item.extrato =$extrato
						AND     lancamento = 113
						AND     tbl_extrato_extra_item.os_revenda = tbl_os_revenda.os_revenda) as valor_adicional,
					(SELECT  sum(valor_extrato_lancamento)
						FROM    tbl_extrato_extra_item
						WHERE   tbl_extrato_extra_item.extrato =$extrato
						AND     lancamento = 112
						AND     tbl_extrato_extra_item.os_revenda = tbl_os_revenda.os_revenda) as valor_retorno
			FROM    tbl_os_revenda
			WHERE   tbl_os_revenda.extrato_revenda = $extrato
			AND     tbl_os_revenda.fabrica = $login_fabrica
			AND     tbl_os_revenda.os_geo IS TRUE ";

	$res_rev = pg_query ($con,$sql);

	if (pg_num_rows($res_rev) > 0) {
		echo "<br><br>OS GEO METAL\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td bgcolor='#FFFFFF' width='20%' nowrap align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' nowrap align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF Data</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' nowrap align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Produto</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' nowrap  align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abertura</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF'  nowrap width='15%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fechamento</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='5%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Km</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='10%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Valor Adicional</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>M.O.</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Taxa de Retorno</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total MO</b></font>\n";
		echo "</td>\n";
		echo "<td bgcolor='#FFFFFF' width='15%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";

		for ($x_rev = 0; $x_rev < pg_num_rows($res_rev); $x_rev++) {
			$os_revenda        = trim(pg_fetch_result($res_rev,$x_rev,os_revenda));
			$qtde              = trim(pg_fetch_result($res_rev,$x_rev,qtde));
			$mao_de_obra       = trim(pg_fetch_result($res_rev,$x_rev,mao_de_obra));
			$valor_km          = trim(pg_fetch_result($res_rev,$x_rev,valor_km));
			$valor_adicional   = trim(pg_fetch_result($res_rev,$x_rev,valor_adicional));
			$valor_retorno     = trim(pg_fetch_result($res_rev,$x_rev,valor_retorno));

			$sql =	"DROP TABLE IF EXISTS tmp_blackedecker_3_$login_admin;
                     SELECT tbl_os.os                                                     ,
							tbl_os.sua_os                                                 ,
							tbl_produto.familia                                           ,
							tbl_produto.linha                                             ,
							tbl_produto.referencia             as produto_referencia      ,
							(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS pecas  ,
							tbl_os.mao_de_obra                                            ,
							tbl_os.nota_fiscal                                            ,
							to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf       ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura ,
							to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
							tipo_atendimento
					INTO TEMP tmp_blackedecker_3_$login_admin
					FROM    tbl_os_extra
					JOIN    tbl_os USING (os)
					JOIN 	tbl_produto using(produto)
					WHERE   tbl_os_extra.extrato = $extrato
					AND     tbl_os.fabrica = $login_fabrica
					and tbl_os.os_numero = $os_revenda
					AND     (
						(tbl_os.tipo_atendimento not in(17,18,35) OR tbl_os.tipo_atendimento is null OR length(tbl_os.tipo_atendimento::text)=0)
					)
					AND (tbl_os.tipo_os =13 or tbl_os.tipo_os is null);

					SELECT * FROM tmp_blackedecker_3_$login_admin
					ORDER BY lpad(substr(sua_os,0,strpos(sua_os,'-'))::text,20,'0') ASC,
							replace(lpad(substr(sua_os,strpos(sua_os,'-'))::text,20,'0'),'-','') ASC;";
			$res = pg_query ($con,$sql);
			if (pg_num_rows($res) > 0) {
				// monta array para ver duplicidade
				$busca_array     = array();
				$localizou_array = array();
				$sql = "SELECT nota_fiscal FROM tbl_os JOIN tbl_os_extra USING(os) WHERE extrato = $extrato order by nota_fiscal";
				$res2 = pg_query ($con,$sql);
				for ($x = 0; $x < pg_num_rows($res2); $x++) {
					$nota_fiscal   = trim(pg_fetch_result($res2,$x,nota_fiscal));
					if (in_array($nota_fiscal, $busca_array)) {
						$localizou_array[] = $nota_fiscal;
						$z++;
					}
					$busca_array[] = $nota_fiscal;
				}

				// monta array da tela
				for ($x = 0; $x < pg_num_rows($res); $x++) {
					$sua_os    = trim(pg_fetch_result($res,$x,sua_os));
					$os        = trim(pg_fetch_result($res,$x,os));
					$pecas     = trim(pg_fetch_result($res,$x,pecas));
					$maodeobra = trim(pg_fetch_result($res,$x,mao_de_obra));
					$data_abertura   = trim(pg_fetch_result($res,$x,data_abertura));
					$data_fechamento = trim(pg_fetch_result($res,$x,data_fechamento));
					$nota_fiscal     = trim(pg_fetch_result($res,$x,nota_fiscal));
					$data_nf         = trim(pg_fetch_result($res,$x,data_nf));
					$familia         = trim(pg_fetch_result($res,$x,familia));
					$linha           = trim(pg_fetch_result($res,$x,linha));
					$tipo_atendimento= trim(pg_fetch_result($res,$x,tipo_atendimento));
					$produto_referencia  = trim(pg_fetch_result($res,$x,produto_referencia));
					$produto_referencia = substr($produto_referencia,0,8);
					$total_os = $pecas + $maodeobra;

					$bold = "TdNormal";
					$negrito="";
					if (in_array($nota_fiscal, $localizou_array)) {
						$bold = "TdBold";
						$negrito ="<b>";
					}

					echo "<tr class='$bold'>\n";
					echo "<td align='center' colspan='2'  nowrap bgcolor='#FFFFFF'>\n";
					echo " <font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp;</font> ";
					echo " <a href='os_press.php?os=$os' target='_blank'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $codigo$sua_os</font></a>\n";
					$anexo = $os;
					if ($consumidor_revenda == "R") {
						$sqlr = "SELECT tbl_os_revenda.os_revenda
									FROM tbl_os
									JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
									JOIN tbl_os_revenda_item USING(os_revenda)
									WHERE tbl_os.fabrica = $login_fabrica
									AND os = $os
									AND (os_lote = $os  )";
						$resr = pg_query($con, $sqlr);
						if (pg_num_rows($resr)> 0 ) {
							$anexo = pg_fetch_result($resr, 0, "os_revenda");
						}
					}

					$temNFs = temNF($anexo, 'count');
					$linkVerAnexosNF = null;
					if(/*$nf_os == 't' or*/ $temNFs) {

						if ($temNFs > 0) {
							$linkNF = temNF($anexo, 'url');

							$linksNew = '';

							foreach ($linkNF as $key => $value) {
								$arqExt = pathinfo($value, PATHINFO_EXTENSION);
								$arqExt = preg_replace('/\?.+/','',$arqExt);
								switch($arqExt) {
									case 'gif':
									case 'jpg':
									case 'png':
										$linkVerAnexosNF = 'js/jpie/nf_digital_mlg_nf.php?os=' . $anexo;
										break;

								}
								if(empty($linkVerAnexosNF)) {
									$linkVerAnexosNF = $value;
								}

								if (!empty($linkVerAnexosNF)) {
									$linksNew .= 'window.open("'.$linkVerAnexosNF.'", "_blank"); ';
									$linkVerAnexosNF = "";
								}
							}
						}
						if(!empty($linksNew)){
							echo "<a href='javascript:void(0);' onclick='javascript:".$linksNew." '>" .
								"<img src='../helpdesk/imagem/clips.gif' style='cursor:pointer' alt='Com Anexo' />".
								"</a>";
						}
					}
					echo "</td>\n";
					echo "<td align='center'  nowrap colspan='2' bgcolor='#FFFFFF'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><span style='color: #333;cursor: pointer;text-decoration: underline;' href='#' onClick='alteraNf($os)'>$nota_fiscal</span></font>\n";
					echo "</td>\n";
					echo "<td align='center' nowrap colspan='2' bgcolor='#FFFFFF'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>&nbsp;$data_nf</a></font>\n";
					echo "</td>\n";
					echo "<td align='center' nowrap colspan='2' bgcolor='#FFFFFF'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$produto_referencia</a></font>\n";
					echo "</td>\n";
					echo "<td align='center' nowrap colspan='2' bgcolor='#FFFFFF'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_abertura</a></font>\n";
					echo "</td>\n";
					echo "<td align='center' nowrap colspan='2' bgcolor='#FFFFFF'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_fechamento</a></font>\n";
					echo "</td>\n";
					if($os_revenda<> $os_revenda_ant){
						echo "<td align='center' nowrap  colspan='2' bgcolor='#FFFFFF' rowspan='$qtde'>\n";
						echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor_km,2,",",".") ."</a></font>\n";
						echo "</td>\n";
						echo "<td align='center' nowrap  colspan='2' bgcolor='#FFFFFF' rowspan='$qtde'>\n";
						echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor_adicional,2,",",".") ."</a></font>\n";
						echo "</td>\n";
						echo "<td align='center' nowrap  colspan='1' bgcolor='#FFFFFF' rowspan='$qtde'>\n";
						echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($mao_de_obra,2,",",".") ."</a></font>\n";
						echo "</td>\n";
						echo "<td align='center' nowrap  colspan='1' bgcolor='#FFFFFF' rowspan='$qtde'>\n";
						echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor_retorno,2,",",".") ."</a></font>\n";
						echo "</td>\n";
						echo "<td align='center' nowrap  colspan='1' bgcolor='#FFFFFF' rowspan='$qtde'>\n";
						echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($mao_de_obra+$valor_retorno,2,",",".") ."</a></font>\n";
						echo "</td>\n";
						$os_revenda_ant = $os_revenda;
					}else{
						$os_revenda_ant = $os_revenda;
					}
					$maodeobra_total = $maodeobra + $mao_de_obra_adicional + $qtde_km + $valor_total_hora_tecnica;
					echo "<td align='right' nowrap  bgcolor='#FFFFFF'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($pecas,2,",",".") ."</font>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
			}
		}
		echo "</table>\n";
		echo "<BR>\n";
	}
/*	OS GEO FIM*/

	### PEÇAS ENVIADAS EM GARANTIA
	if ($tipo_posto == 4 OR $tipo_posto == 5 OR $tipo_posto == 10) {
		$sql = "SELECT   DISTINCT
						 tbl_peca_ressarcida.pedido_garantia
				FROM     tbl_peca
				JOIN     tbl_peca_ressarcida ON tbl_peca_ressarcida.peca = tbl_peca.peca
				WHERE    tbl_peca_ressarcida.extrato  = $extrato
				AND      tbl_peca_ressarcida.qtde = 0
				ORDER BY tbl_peca_ressarcida.pedido_garantia;";
		$res = @pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
			echo "<td width='100%' align='left'>\n";
			echo "<hr>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
			echo "<td align='left' colspan='6'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PEÇAS FATURADAS EM GARANTIA (SN-GART)</b></font>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PEDIDO</b></font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PEÇA</b></font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>QTDE</b></font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>TOTAL</b></font>\n";
			echo "</td>\n";
			echo "</tr>\n";
			for ($x = 0; $x < pg_num_rows($res); $x++) {
				$pedido_garantia = trim(pg_fetch_result($res,$x,pedido_garantia));
				$sql = "SELECT  tbl_peca.referencia                  ,
								tbl_peca.nome                        ,
								tbl_posicao_faturamento.qtde_faturada,
								tbl_posicao_faturamento.valor_unitario_peca
						FROM    tbl_posicao_faturamento
						JOIN    tbl_peca ON tbl_peca.referencia_antiga = tbl_posicao_faturamento.referencia_peca
						WHERE   tbl_posicao_faturamento.natureza_operacao = 'SN-GART'
						AND     substr(trim(tbl_posicao_faturamento.pedido_mfg),4,length(pedido_mfg::text))::integer = $pedido_garantia
						ORDER BY tbl_peca.referencia;";
				$res1 = @pg_query ($con,$sql);
				for ($y = 0; $y < @pg_num_rows($res1); $y++) {
					$peca = trim(pg_fetch_result($res1,$y,referencia)) ." - ". trim(pg_fetch_result($res1,$y,nome));
					$qtde = trim(pg_fetch_result($res1,$y,qtde_faturada));
					$ress = trim(pg_fetch_result($res1,$y,valor_unitario_peca));
					echo "<tr>\n";
					echo "<td align='center' colspan='2' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$pedido_garantia</a></font>\n";
					echo "</td>\n";
					echo "<td align='left' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$peca</a></font>\n";
					echo "</td>\n";
					echo "<td align='right'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$qtde</font>\n";
					echo "</td>\n";
					echo "<td align='right'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($ress,2,",",".") ."</font>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
			}
			echo "</table>\n";
		}
	}

	# Peças das OSs
	$sql = "SELECT  tbl_os.os,
					tbl_os.sua_os,
					tbl_produto.referencia AS ref_equipamento ,
					tbl_produto.descricao  AS nome_equipamento,
					tbl_produto.produto                       ,
					tbl_peca.referencia    AS ref_peca        ,
					tbl_peca.descricao     AS nome_peca       ,
					tbl_os_campo_extra.campos_adicionais::JSONB->>'total_custo_peca' AS total_custo_peca,
                    tbl_os_campo_extra.campos_adicionais::JSONB->>'total_produto'    AS total_produto,
					tbl_os_item.qtde                          ,
					tbl_os_item.custo_peca
			INTO  TEMP tmp_blackedecker_4_$login_admin
			FROM    tbl_os_item
			JOIN    tbl_os_produto        ON tbl_os_item.os_produto                  = tbl_os_produto.os_produto
			JOIN    tbl_os                ON tbl_os_produto.os                       = tbl_os.os
			left join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and tbl_os.fabrica = $login_fabrica
			JOIN    tbl_os_extra          ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_peca.produto_acabado IS NOT TRUE;

			SELECT * FROM tmp_blackedecker_4_$login_admin
			ORDER BY substr(sua_os,0,strpos(sua_os,'-')) ASC,
					lpad(substr(sua_os,strpos(sua_os,'-')+1,length(sua_os))::text,5,'0') ASC,
					sua_os;";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
				echo "<td align='center'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
				echo "</td>\n";
				echo "<td align='center'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Equipamento</b></font>\n";
				echo "</td>\n";
				echo "<td align='center'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
				echo "</td>\n";
				echo "<td align='center'>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
				echo "</td>\n";
				echo "<td align='center'>\n";//HD 237471 - Modifiquei o label
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Vlr Unit</b></font>\n";
				echo "</td>\n";
				echo "<td align='center'>\n";//HD 237471 - Adicionei a coluna
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total</b></font>\n";
				echo "</td>\n";
				echo "<td align='center'>\n";//HD 237471 - Adicionei a coluna
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>% Custo Peça X Produto</b></font>\n";
				echo "</td>\n";
			echo "</tr>\n";

		for ($x = 0; $x < pg_num_rows($res); $x++) {

			$qtd_peca   = pg_result($res,$x,'qtde');//HD 237471 - Adicionei a variavel
			$custo_peca = pg_result($res,$x,'custo_peca');//HD 237471 - Adicionei a variavel
			$os = pg_fetch_result($res,$x,'os');

			$valor_pecas                    = pg_fetch_result($res,$x,total_custo_peca);
            $valor_produto                  = pg_fetch_result($res,$x,total_produto);

			if(strlen($valor_pecas)>0 AND strlen($valor_produto)>0){
				$produto_pecas = number_format(($valor_pecas *100)/$valor_produto, 2, '.', '') ."%";
			}else{
				$produto_pecas = "";
			}

			$linkVerAnexosNF = "";
			echo "<tr>\n";
				echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo". trim(pg_fetch_result($res,$x,sua_os)) ."</font>\n";
					
					$anexo = pg_fetch_result($res,$x,'os');
					
					if ($consumidor_revenda == "R") {
						$sqlr = "SELECT tbl_os_revenda.os_revenda
									FROM tbl_os
									JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
									JOIN tbl_os_revenda_item USING(os_revenda)
									WHERE tbl_os.fabrica = $login_fabrica
									AND os = $anexo
									AND (os_lote = $anexo  )";
						$resr = pg_query($con, $sqlr);
						if (pg_num_rows($resr)> 0 ) {
							$anexo = pg_fetch_result($resr, 0, "os_revenda");
						}
					}

					$temNFs = temNF($anexo, 'count');

					if(/*$nf_os == 't' or*/ $temNFs) {

						if ($temNFs > 0) {
							$linkNF = temNF($anexo, 'url');

							$linksNew = '';

							foreach ($linkNF as $key => $value) {
								$arqExt = pathinfo($value, PATHINFO_EXTENSION);
								$arqExt = preg_replace('/\?.+/','',$arqExt);
								switch($arqExt) {
									case 'gif':
									case 'jpg':
									case 'png':
										$linkVerAnexosNF = 'js/jpie/nf_digital_mlg_nf.php?os=' . $anexo;
										break;

								}
								if(empty($linkVerAnexosNF)) {
									$linkVerAnexosNF = $value;
								}

								if (!empty($linkVerAnexosNF)) {
									$linksNew .= 'window.open("'.$linkVerAnexosNF.'", "_blank"); ';
									$linkVerAnexosNF = "";
								}
							}
						}
						if(!empty($linksNew)){
							echo "<a href='javascript:void(0);' onclick='javascript:".$linksNew." '>" .
								"<img src='../helpdesk/imagem/clips.gif' style='cursor:pointer' alt='Com Anexo' />".
								"</a>";
						}
					}
				echo "</td>\n";
				echo "<td align='left' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><a href='lbm_consulta.php?produto=".pg_fetch_result($res,$x,produto)."' target='_blank'>". trim(pg_fetch_result($res,$x,ref_equipamento)) ." - ". substr(trim(pg_fetch_result($res,$x,nome_equipamento)),0,15) ."</a></font>\n";
				echo "</td>\n";
				echo "<td align='left' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_fetch_result($res,$x,ref_peca)) ." - ". trim(pg_fetch_result($res,$x,nome_peca)) ."</font>\n";
				echo "</td>\n";
				echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim($qtd_peca) ."</font>\n";
				echo "</td>\n";
				echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($custo_peca,2,",",".") ."</font>\n";
				echo "</td>\n";
				echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($custo_peca * $qtd_peca,2,",",".") ."</font>\n";
				echo "</td>\n";
				echo "<td align='center' nowrap>\n";//HD 237471 - Adicionei a coluna
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". $produto_pecas ."</font>\n";
				echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	$sql = "SELECT *
			FROM (
				(
					SELECT	lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex ,
							tbl_extrato_extra_item.valor_extrato_lancamento as total_pecas,
							extra_item2.valor_extrato_lancamento as despesas,
							tbl_os_sedex.controle
					FROM	tbl_os_sedex
					JOIN	tbl_extrato_extra_item USING (os_sedex)
					JOIN	tbl_extrato_extra_item extra_item2 USING (os_sedex)
					WHERE	tbl_os_sedex.extrato_origem = $extrato
					AND		tbl_os_sedex.fabrica = $login_fabrica
					AND		tbl_os_sedex.finalizada is not null
					AND     tbl_extrato_extra_item.extrato_lancamento IS NOT NULL
					AND     tbl_extrato_extra_item.lancamento = 41
					AND     extra_item2.extrato_lancamento IS NOT NULL
					AND     extra_item2.lancamento = 40
					AND     tbl_extrato_extra_item.extrato=$extrato
					AND     extra_item2.extrato=$extrato
					ORDER BY tbl_os_sedex.os_sedex
				) union (
					SELECT	lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex ,
							tbl_extrato_extra_item.valor_extrato_lancamento as total_pecas,
							extra_item2.valor_extrato_lancamento as despesas,
							tbl_os_sedex.controle
					FROM	tbl_os_sedex
					JOIN	tbl_extrato_extra_item USING (os_sedex)
					JOIN	tbl_extrato_extra_item extra_item2 USING (os_sedex)
					WHERE	tbl_os_sedex.extrato_origem = $extrato
					AND		tbl_os_sedex.fabrica = $login_fabrica
					AND		tbl_os_sedex.finalizada is not null
					AND     tbl_extrato_extra_item.extrato_lancamento IS NOT NULL
					AND     tbl_extrato_extra_item.lancamento = 41
					AND     extra_item2.extrato_lancamento IS NOT NULL
					AND     extra_item2.lancamento = 40
					AND     tbl_extrato_extra_item.extrato=$extrato
					AND     extra_item2.extrato=$extrato
					ORDER BY tbl_os_sedex.os_sedex
				)
			) AS x;";

	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		echo "<br>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Crédito</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Despesas</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Número do Objeto</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		$total_S_PC='';
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$sua_os      = trim(pg_fetch_result($res,$x,os_sedex));
			$total_pecas = trim(pg_fetch_result($res,$x,total_pecas));
			$despesas    = trim(pg_fetch_result($res,$x,despesas));
			$controle    = trim(pg_fetch_result($res,$x,controle));
			$xtotal   = $xtotal + $total_pecas + $despesas;
			$total_S_PC = $total_S_PC + $total_pecas ;
			echo "<tr>\n";
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo$sua_os</a></font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($despesas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($xtotal,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". $controle ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	$sql = "SELECT *
			FROM (
				(
					SELECT	lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex ,
							tbl_extrato_extra_item.valor_extrato_lancamento as total_pecas,
							extra_item2.valor_extrato_lancamento as despesas,
							tbl_os_sedex.controle
					FROM	tbl_os_sedex
					JOIN	tbl_extrato_extra_item USING (os_sedex)
					JOIN	tbl_extrato_extra_item extra_item2 USING (os_sedex)
					WHERE	tbl_os_sedex.extrato_origem = $extrato
					AND		tbl_os_sedex.fabrica = $login_fabrica
					AND		tbl_os_sedex.finalizada is not null
					AND     (tbl_extrato_extra_item.lancamento not in (41,40) or tbl_extrato_extra_item.lancamento is null)
					AND    tbl_extrato_extra_item.valor_total_pecas_destion_os_sedex = 0
					AND     extra_item2.extrato_lancamento IS NOT NULL
					AND     extra_item2.lancamento = 40
					AND     tbl_extrato_extra_item.extrato=$extrato
					AND     extra_item2.extrato=$extrato
					ORDER BY tbl_os_sedex.os_sedex
				) union (
					SELECT	lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex ,
							tbl_extrato_extra_item.valor_extrato_lancamento as total_pecas,
							extra_item2.valor_extrato_lancamento as despesas,
							tbl_os_sedex.controle
					FROM	tbl_os_sedex
					JOIN	tbl_extrato_extra_item USING (os_sedex)
					JOIN	tbl_extrato_extra_item extra_item2 USING (os_sedex)
					WHERE	tbl_os_sedex.extrato_origem = $extrato
					AND		tbl_os_sedex.fabrica = $login_fabrica
					AND		tbl_os_sedex.finalizada is not null
						AND     (tbl_extrato_extra_item.lancamento not in (41,40) or tbl_extrato_extra_item.lancamento is null)
					AND    tbl_extrato_extra_item.valor_total_pecas_destion_os_sedex = 0
					AND     extra_item2.extrato_lancamento IS NOT NULL
					AND     extra_item2.lancamento = 40
					AND     tbl_extrato_extra_item.extrato=$extrato
					AND     extra_item2.extrato=$extrato
					ORDER BY tbl_os_sedex.os_sedex
				)
			) AS x;";

	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0 ) {
		echo "<br>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Crédito</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Despesas</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Número do Objeto</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		$total_S_PC='';
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$xtotal = "";
			$sua_os      = trim(pg_fetch_result($res,$x,os_sedex));
			$total_pecas = trim(pg_fetch_result($res,$x,total_pecas));
			$despesas    = trim(pg_fetch_result($res,$x,despesas));
			$controle    = trim(pg_fetch_result($res,$x,controle));
			$xtotal   = $xtotal + $total_pecas + $despesas;
			$total_S_PC = $total_S_PC + $total_pecas ;
			echo "<tr>\n";
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo$sua_os</a></font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($despesas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($xtotal,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". $controle ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}


	# SEDEX CR?DITO
	$sql = "SELECT  lpad(tbl_os_sedex.os_sedex::text,5,'0') AS os_sedex ,
					tbl_peca.referencia             AS ref_peca ,
					tbl_peca.descricao              AS nome_peca,
					tbl_os_sedex_item.qtde
			FROM    tbl_os_sedex_item
			JOIN    tbl_os_sedex ON tbl_os_sedex_item.os_sedex = tbl_os_sedex.os_sedex
			JOIN    tbl_peca     ON tbl_os_sedex_item.peca     = tbl_peca.peca
			WHERE   tbl_os_sedex.extrato_origem = $extrato
			AND     tbl_os_sedex.fabrica = $login_fabrica
			AND     tbl_os_sedex.finalizada is not null
			ORDER BY tbl_os_sedex.os_sedex";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td align='center'>\n";
		echo "<font width='150' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Crédito</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font width='50' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			echo "<tr>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$codigo". trim(pg_fetch_result($res,$x,os_sedex)) ."</a></font>\n";
			echo "</td>\n";
			echo "<td align='left' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_fetch_result($res,$x,ref_peca)) ." - ". trim(pg_fetch_result($res,$x,nome_peca)) ."</font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_fetch_result($res,$x,qtde)) ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	# SEDEX D?BITO
	$sql = "SELECT  DISTINCT tbl_os_sedex.os_sedex               ,
					tbl_os_sedex.sua_os_destino                  ,
					valor_total_pecas_destion_os_sedex           ,
					valor_despesas_os_sedex                      ,
					valor_total_destino_os_sedex                 ,
					tbl_os_sedex.controle
			FROM    tbl_os_sedex
			JOIN    tbl_extrato_extra_item USING(os_sedex)
			WHERE   tbl_os_sedex.extrato_destino = $extrato
			AND     tbl_os_sedex.fabrica         = $login_fabrica
			AND     tbl_os_sedex.posto_destino   = $posto
			AND     tbl_os_sedex.posto_origem   <> 6901
			AND     tbl_os_sedex.total_pecas_destino > 0
			AND     tbl_os_sedex.finalizada is not null
			AND     tbl_extrato_extra_item.extrato_lancamento IS NULL
			AND     (tbl_os_sedex.obs not ilike 'Débito gerado por troca de produto na OS%' or tbl_os_sedex.obs is null)
			ORDER BY tbl_os_sedex.os_sedex                       ,
					tbl_os_sedex.sua_os_destino                  ,
					valor_total_pecas_destion_os_sedex           ,
					valor_despesas_os_sedex                      ,
					valor_total_destino_os_sedex                 ,
					tbl_os_sedex.controle";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		echo "<br>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Débito..</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peça</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Despesas</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Número do Objeto</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$sua_os      = trim(pg_fetch_result($res,$x,sua_os_destino));
			$total_pecas = trim(pg_fetch_result($res,$x,valor_total_pecas_destion_os_sedex));
			$despesas    = trim(pg_fetch_result($res,$x,valor_despesas_os_sedex));
			$total       = trim(pg_fetch_result($res,$x,valor_total_destino_os_sedex));
			$controle    = trim(pg_fetch_result($res,$x,controle));
			$xtotal   = $xtotal + $total_pecas + $despesas;
			echo "<tr>\n";
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$sua_os</a></font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($despesas,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>".$controle ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

		// monta array para ver duplicidade
		$busca_array     = array();
		$localizou_array = array();
		$sql = "SELECT nota_fiscal FROM tbl_os JOIN tbl_os_extra USING(os) WHERE extrato = $extrato order by nota_fiscal";
		$res2 = pg_query ($con,$sql);
		for ($x = 0; $x < pg_num_rows($res2); $x++) {
			$nota_fiscal   = trim(pg_fetch_result($res2,$x,nota_fiscal));
			if (in_array($nota_fiscal, $busca_array)) {
				$localizou_array[] = $nota_fiscal;
				$z++;
			}
			$busca_array[] = $nota_fiscal;
		}
	$sql = "SELECT  tbl_os_sedex.sua_os_destino             ,
					tbl_peca.referencia         AS ref_peca ,
					tbl_peca.descricao          AS nome_peca,
					tbl_os_sedex_item.qtde
			FROM    tbl_os_sedex_item
			JOIN    tbl_os_sedex ON tbl_os_sedex_item.os_sedex = tbl_os_sedex.os_sedex
			JOIN    tbl_peca     ON tbl_os_sedex_item.peca     = tbl_peca.peca
			WHERE   tbl_os_sedex.extrato_destino = $extrato
			AND     tbl_os_sedex.fabrica = $login_fabrica
			AND     tbl_os_sedex.posto_origem   <> 6901
			AND     tbl_os_sedex.finalizada is not null
			ORDER BY tbl_os_sedex.sua_os_destino ASC";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td align='center'>\n";
		echo "<font width='150' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS SEDEX - Débito</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font width='50' face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			echo "<tr>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_fetch_result($res,$x,sua_os_destino)) ."</a></font>\n";
			echo "</td>\n";
			echo "<td align='left' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_fetch_result($res,$x,ref_peca)) ." - ". trim(pg_fetch_result($res,$x,nome_peca)) ."</font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_fetch_result($res,$x,qtde)) ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

## TROCA FATURADA
	$sql = "SELECT  DISTINCT tbl_os.os                                             ,
					sua_os                                                         ,
					tbl_produto.referencia                                         ,
					tbl_produto.produto                                            ,
					tbl_os.nota_fiscal                                             ,
					tbl_os.nota_fiscal_saida                                       ,
					tbl_os.consumidor_revenda                                      ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida  ,
					case when tbl_extrato_lancamento.valor *-1 <> valor_troca_os_troca then tbl_extrato_lancamento.valor * -1 else valor_troca_os_troca end as total_troca                                      ,
					valor_mo_os_troca as mao_de_obra                               ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento ,
					tbl_extrato_lancamento.os_sedex ,
					tipo_atendimento
			INTO TEMP tmp_blackedecker_5_$login_admin
				FROM tbl_os_extra
				JOIN tbl_os         ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				LEFT JOIN tbl_os_item USING (os_produto)
				LEFT JOIN tbl_peca       ON tbl_os_item.peca = tbl_peca.peca
				JOIN tbl_extrato_extra_item   ON tbl_extrato_extra_item.os = tbl_os_extra.os and tbl_extrato_extra_item.extrato = $extrato
				JOIN tbl_produto    ON tbl_produto.produto = tbl_os.produto
				LEFT JOIN tbl_os_sedex   ON tbl_os_sedex.os = tbl_os.os
				LEFT JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento.os_sedex = tbl_os_sedex.os_sedex and tbl_extrato_lancamento.extrato = $extrato
				WHERE tbl_os_extra.extrato = $extrato
				AND tipo_atendimento in (17,18,35)
				AND (tbl_peca.produto_acabado IS TRUE OR tbl_os_item.peca IS NULL OR tbl_peca.acessorio IS TRUE);
				SELECT * FROM tmp_blackedecker_5_$login_admin
				ORDER BY sua_os, os_sedex asc";
	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<br><table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td align='center' colspan='12'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>PRODUTOS TROCADOS</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS</b></font>\n";
		echo "</td>\n";
		echo "<td  align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF</b></font>\n";
		echo "</td>\n";
		echo "<td  align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>NF Data</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Produto</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Abertura</b></font>\n";

		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Fechamento</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Tipo</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Nota Fiscal Saída</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data NF</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font>\n";
		echo "</td>\n";

		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total</b></font>\n";
		echo "</td>\n";
		echo "<td align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>M.O</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$troca_os                 = trim(pg_fetch_result($res,$x,os));
			$troca_sua_os             = trim(pg_fetch_result($res,$x,sua_os));
			$troca_referencia         = trim(pg_fetch_result($res,$x,referencia));
			$troca_produto            = pg_fetch_result($res,$x,produto);
			$troca_nota_fiscal        = trim(pg_fetch_result($res,$x,nota_fiscal));
			$troca_data_nf            = trim(pg_fetch_result($res,$x,data_nf));
			$troca_nota_fiscal_saida  = trim(pg_fetch_result($res,$x,nota_fiscal_saida));
			$troca_os_sedex   = trim(pg_fetch_result($res,$x,'os_sedex'));
			$troca_dt_nota_fiscal     = trim(pg_fetch_result($res,$x,data_nf_saida));
			$troca_total              = trim(pg_fetch_result($res,$x,total_troca));
			$troca_tipo_atendimento   = trim(pg_fetch_result($res,$x,tipo_atendimento));
			$consumidor_revenda   = trim(pg_fetch_result($res,$x,'consumidor_revenda'));
			$troca_mao_de_obra        = trim(pg_fetch_result($res,$x,mao_de_obra));
			$troca_data_abertura      = trim(pg_fetch_result($res,$x,data_abertura));
			$troca_fechamento         = trim(pg_fetch_result($res,$x,data_fechamento));

			if(empty($troca_nota_fiscal_saida)  or empty($troca_dt_nota_fiscal)) {
				$sqln="SELECT DISTINCT tbl_os_item_nf.nota_fiscal   AS nota_fiscal_item,
					TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf_item
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_os_item_nf USING(os_item)
					WHERE os = $troca_os";
				$resn = pg_query($con,$sqln);
				if(pg_num_rows($resn) > 0){
					for($n =0;$n<pg_num_rows($resn);$n++) {
						$troca_nf_item            = trim(pg_fetch_result($resn,$n,nota_fiscal_item));
						$troca_data_nf_item       = trim(pg_fetch_result($resn,$n,data_nf_item));

						$troca_nota_fiscal_saida .= ($n == 0) ? $troca_nf_item :"<br/>".$troca_nf_item ;
						$troca_dt_nota_fiscal    .= ($n == 0) ? $troca_data_nf_item:"<br/>".$troca_data_nf_item;
					}
				}
			}
			$troca_referencia         = substr($troca_referencia,0,8);
			if(($troca_os == $troca_os_anterior and $os_sedex_anterior == $troca_os_sedex) or empty($troca_os_sedex)) {
				$troca_total = 0;
				$troca_mao_de_obra = 0;
			}
			$xtotal   = $xtotal - $troca_total;
			$troca_total = round($troca_total,2);
			$troca_sub_total = $troca_sub_total + $troca_total;
			$troca_total_mo = $troca_total + $troca_mao_de_obra;
			$troca_total *= -1;
			$troca_total = number_format($troca_total,2,",","."); #HD 94303

			$bold = "TdNormal";
			$negrito="";
			if (in_array($troca_nota_fiscal, $localizou_array)) {
				$bold = "TdBold";
				$negrito ="<b>";
			}

			echo "<tr class='$bold'>\n";
			echo "<td align='center' nowrap>\n";
			echo "<a href='os_press.php?os=$troca_os' target='_blank'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$negrito $troca_sua_os</a></font>\n";
			$anexo = $troca_os;
			if ($consumidor_revenda == "R") {
				$sqlr = "SELECT tbl_os_revenda.os_revenda
							FROM tbl_os
							JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
							JOIN tbl_os_revenda_item USING(os_revenda)
							WHERE tbl_os.fabrica = $login_fabrica
							AND os = $troca_os
							AND (os_lote = $troca_os )";
				$resr = pg_query($con, $sqlr);
				if (pg_num_rows($resr)> 0 ) {
					$anexo = pg_fetch_result($resr, 0, "os_revenda");
				}
			}

			$temNFs = temNF($anexo, 'count');
			$linkVerAnexosNF = null;
			if(/*$nf_os == 't' or*/ $temNFs) {

				if ($temNFs > 0) {
					$linkNF = temNF($anexo, 'url');

					$linksNew = '';

					foreach ($linkNF as $key => $value) {
						$arqExt = pathinfo($value, PATHINFO_EXTENSION);
						$arqExt = preg_replace('/\?.+/','',$arqExt);
						switch($arqExt) {
							case 'gif':
							case 'jpg':
							case 'png':
								$linkVerAnexosNF = 'js/jpie/nf_digital_mlg_nf.php?os=' . $anexo;
								break;

						}
						if(empty($linkVerAnexosNF)) {
							$linkVerAnexosNF = $value;
						}

						if (!empty($linkVerAnexosNF)) {
							$linksNew .= 'window.open("'.$linkVerAnexosNF.'", "_blank"); ';
							$linkVerAnexosNF = ""; 
						}
					}
				}
				if(!empty($linksNew)){
					echo "<a href='javascript:void(0);' onclick='javascript:".$linksNew." '>" .
						"<img src='../helpdesk/imagem/clips.gif' style='cursor:pointer' alt='Com Anexo' />".
						"</a>";
				}
			}
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>";
			if(strlen($troca_nota_fiscal) > 0){
				echo "<span style='color: #333;cursor: pointer;text-decoration: underline;' href='#' onClick='alteraNf($os)'>";
				echo (strlen($troca_nota_fiscal) > 0) ? "$troca_nota_fiscal" : "&nbsp;"; echo "</a></font>\n";
				echo "</span>";
			}


			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>"; echo (strlen($troca_data_nf) > 0) ? "$troca_data_nf" : "&nbsp;";
			echo "</a></font>\n";
			echo "</td>\n";
			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><a href='lbm_consulta.php?produto=$troca_produto' target='blank'>$troca_referencia</a></font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$troca_data_abertura</a></font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$troca_fechamento</a></font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>"; echo ($troca_tipo_atendimento == '17') ? " GAR" : (($troca_tipo_atendimento == '18') ? " FAT" : " COR") ;
			echo "</font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$troca_nota_fiscal_saida</font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$troca_dt_nota_fiscal</font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>1</font>\n";
			echo "</td>\n";

			echo "<td align='center' nowrap>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". $troca_total ."</font>\n";
			echo "</td>\n";
			echo "<td align='center'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($troca_mao_de_obra,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
			$troca_os_anterior = $troca_os;
			$os_sedex_anterior = $troca_os_sedex;
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	$sql = "SELECT sua_os_destino, os_sedex, obs FROM tbl_os_sedex WHERE extrato = '$extrato'; ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		echo "<br>";
		echo "<table width='700' style='font-family: verdana; font-size: 12px' border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>";
			echo "<td colspan='2' align='center'><b>LANÇAMENTOS</b></td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td align='center'><b>OS</b></td>";
			echo "<td align='center'><b>Descrição</b></td>";
		echo "</tr>";
		for($i=0; $i < pg_num_rows($res); $i++){
			$os_sedex  = pg_fetch_result($res,$i,os_sedex);
			$obs       = pg_fetch_result($res,$i,obs);
			$os_origem = pg_fetch_result($res,$i,sua_os_destino);
			echo "<tr>";
				echo "<td height='35' nowrap>$codigo$os_origem</td>";
				echo "<td>$obs</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	// MOSTRA TODAS AS OS EXCLU?DAS COM SUAS PE?AS E VALORES.
	$sql = "SELECT DISTINCT tbl_os_sedex.sua_os_destino              ,
					tbl_extrato_extra_item.valor_total_pecas_os_sedex,
					tbl_os_sedex.os_sedex
				FROM    tbl_os_sedex
				JOIN    tbl_extrato_lancamento using(os_sedex)
				JOIN    tbl_os_status ON tbl_os_sedex.os_sedex = tbl_os_status.os_sedex
				JOIN    tbl_extrato_extra_item ON tbl_os_sedex.os_sedex = tbl_extrato_extra_item.os_sedex
			WHERE   tbl_os_sedex.extrato_destino = $extrato
			AND     tbl_os_sedex.fabrica         = $login_fabrica
			AND     tbl_os_sedex.posto_destino   = $posto
			AND     tbl_extrato_extra_item.lancamento IS NULL
			AND     tbl_os_sedex.total > 0
			ORDER BY tbl_os_sedex.os_sedex";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		echo "<br>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
			$sua_os      = trim(pg_fetch_result($res,$x,sua_os_destino));
			echo "<td width='25%' align='center' colspan='4'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OS EXCLUÍDA: </b></font><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='1'>$codigo$sua_os</a></font>";
			echo "</td>\n";
			echo "</tr>\n";
			$total_pecas = trim(pg_fetch_result($res,$x,valor_total_pecas_os_sedex));
			$os_sedex    = trim(pg_fetch_result($res,$x,os_sedex));
			$xtotal   = $xtotal + $total_pecas + $despesas;
			$total_S_PC = $xtotal;
			$sqlX = "SELECT DISTINCT tbl_os_status.os         ,
							tbl_peca.referencia      ,
							tbl_peca.descricao       ,
							tbl_os_item.custo_peca
						FROM tbl_os_status
						JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_status.os
						JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						JOIN tbl_peca       ON tbl_peca.peca = tbl_os_item.peca
						JOIN tbl_os         ON tbl_os.os = tbl_os_produto.os
					WHERE tbl_os_status.status_os = 15
					AND   tbl_os_status.os_sedex = '$os_sedex'
					AND   tbl_os.sua_os          = '$sua_os';";
			$resX = pg_query($con,$sqlX);
			if (pg_num_rows($resX) > 0) {
				echo "<tr>";
				echo "<td colspan='4' align='left' style='font-family: Verdana, Arial, Helvetica, sans; font-size: 10px; font-weight: bold'>\n";
				echo "PEÇAS\n";
				echo "</td>\n";
				echo "</tr>";
				for($k = 0 ; $k < pg_num_rows($resX); $k++){
					$ref   = pg_fetch_result($resX,$k,referencia);
					$desc  = pg_fetch_result($resX,$k,descricao);
					$custo = pg_fetch_result($resX,$k,custo_peca);
					echo "<tr style='font-family: Verdana, Arial, Helvetica, sans; font-size: 9px; color: #797979'>";
					echo "<td colspan='3' align='left'>\n";
					echo "$ref - $desc\n";
					echo "</td>\n";
					echo "<td width='25%' align='right'>\n";
					echo "". number_format($custo,2,",",".") ."\n";
					echo "</td>\n";
					echo "</tr>";
				}
			}
			echo "</tr>";
			echo "<tr>";
				echo "<td width='25%' align='right' colspan='2'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Total Peças:</b></font> <font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
				echo "</td>\n";
				echo "<td width='25%' align='right' colspan='2'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Peça + Despesas:</b></font> <font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_pecas,2,",",".") ."</font>\n";
				echo "</td>\n";
			echo "</tr>\n";
			echo "</table>";
			echo "<tr>";
				echo "<td>&nbsp;</td>";
			echo "</tr>";
		}
		echo "</table>\n";
	}

	$sql = "SELECT  *
			FROM tbl_extrato_lancamento
			WHERE extrato = $extrato
				AND os_revenda is null ";
	$res = pg_query ($con,$sql);
	$outros = 0 ;
	if (pg_num_rows($res) > 0 AND 1==1 ) {
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='25%' align='center' colspan='2'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Outros Lançamentos</b></font>\n";
		echo "</td>\n";
		echo "<td width='25%' align='center'>\n";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Valor</b></font>\n";
		echo "</td>\n";
		echo "</tr>\n";
		for ($x = 0; $x < pg_num_rows($res); $x++) {
			$descricao      = trim(pg_fetch_result($res,$x,descricao));
			$valor          = trim(pg_fetch_result($res,$x,valor));
			$historico      = trim(pg_fetch_result($res,$x,historico));
			$os_sedex_troca = trim(pg_fetch_result($res,$x,os_sedex));
			$outros += $valor ;
			if(strlen($os_sedex_troca)>0){ //HD 57068
				$sql_troca = "SELECT tbl_os_sedex.obs
								FROM tbl_os_sedex
								WHERE os_sedex = $os_sedex_troca
								AND   tbl_os_sedex.obs ilike '%Débito gerado por troca de produto na OS%'";
				$res_troca = pg_query($con, $sql_troca);
				if(pg_num_rows($res_troca)>0){
					$obs_sedex_troca = trim(pg_fetch_result($res_troca,0,obs));
					$descricao       = $obs_sedex_troca;
				}
			}
			echo "<tr>\n";
			echo "<td width='25%' align='center' colspan='2'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$descricao</a></font>\n";
			echo "</td>\n";
			echo "<td width='25%' align='right'>\n";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($valor,2,",",".") ."</font>\n";
			echo "</td>\n";
			echo "</tr>\n";
			if($login_fabrica==1 and strlen($historico) >0){ // HD 17276
				echo "<tr>\n";
				echo "<td width='100%' align='left' colspan='3'>\n";
				echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-8'>Obs: $historico</a></font>\n";
				echo "</td>\n";
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		echo "<hr>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}

	##### DEVOLUÇÃO DE BATERIAS - INÍCIO #####

	$sql = "SELECT  tbl_residuo_solido.protocolo,
					TO_CHAR(tbl_residuo_solido.digitacao::date,'DD/MM/YYYY') AS digitacao,
					tbl_residuo_solido.numero_devolucao,
					TO_CHAR(tbl_residuo_solido.data_aprova,'DD/MM/YYYY') AS data_aprova,
					tbl_residuo_solido.qtde,
					tbl_residuo_solido.total,
					tbl_peca.referencia AS peca_referencia,
					tbl_peca.descricao AS peca_descricao,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.descricao AS produto_descricao,
					tbl_residuo_solido_item.troca_garantia
				FROM tbl_residuo_solido_item
				JOIN tbl_residuo_solido ON tbl_residuo_solido.residuo_solido = tbl_residuo_solido_item.residuo_solido AND tbl_residuo_solido.fabrica = $login_fabrica
				JOIN tbl_peca ON tbl_peca.peca = tbl_residuo_solido_item.peca AND tbl_peca.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_produto.produto = tbl_residuo_solido_item.produto AND tbl_produto.fabrica_i = $login_fabrica
				JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento.extrato_lancamento = tbl_residuo_solido.extrato_lancamento AND tbl_extrato_lancamento.fabrica = $login_fabrica AND tbl_extrato_lancamento.extrato = $extrato
				WHERE tbl_residuo_solido.confirmar_envio IS NOT NULL";

	$res = pg_query($con,$sql);

	if(pg_numrows($res) > 0){
	?>
		<br />
		<table align="center" border='1'>
		<tr><td colspan='9' align='center'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Devoluçaõ de Baterias</b></font></td></tr>
		<tr>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Nº Relatório</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Nº PAC</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Data(PAC)</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Item</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Produto</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Garantia</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Qtde</b></font></td>
			<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Valor M.O</b></font></td>
		</tr>
	<?php
		$total_mao_obra = 0;
		for($i = 0; $i < pg_numrows($res); $i++){
			$protocolo			= pg_result($res,$i,protocolo);
			$digitacao			= pg_result($res,$i,digitacao);
			$numero_devolucao	= pg_result($res,$i,numero_devolucao);
			$data_aprova		= pg_result($res,$i,data_aprova);
			$qtde				= pg_result($res,$i,qtde);
			$peca_referencia	= pg_result($res,$i,peca_referencia);
			$peca_descricao		= pg_result($res,$i,peca_descricao);
			$produto_referencia	= pg_result($res,$i,produto_referencia);
			$produto_descricao	= pg_result($res,$i,produto_descricao);
			$troca_garantia		= pg_result($res,$i,troca_garantia);
			$total_mao_obra_bateria		= pg_result($res,$i,total);

			$troca_garantia = ($troca_garantia == "t") ? "Sim" : "Não";
	?>
			<tr>
				<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $protocolo;?></font></td>
				<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $digitacao;?></font></td>
				<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $numero_devolucao;?></font></td>
				<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $data_aprova;?></font></td>
				<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $peca_referencia." - ".$peca_descricao;?></font></td>
				<td><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $$produto_referencia;?></font></td>
				<td align='center'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo $troca_garantia;?></font></td>
				<td align='center'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>1</font></td>
				<td align='right'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><?php echo number_format(2,2,',','.');?></font></td>
			</tr>
	<?php
		}

	?>
			<tr>
				<td align='right' colspan='7'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>TOTAL</b></font></td>
				<td align='center'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b><?php echo $qtde;?></b></font> </td>
				<td align='right'><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b><?php echo number_format($total_mao_obra_bateria,2,',','.');?> </b></font></td>
			</tr>
		</table>
	<?php
	}

##### DEVOLUÇÃO DE BATERIAS - FIM #####


	$sql = "SELECT  (tbl_extrato.total ) AS total
		FROM    tbl_extrato
		JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			WHERE   tbl_extrato.extrato = $extrato";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {

		$total_GE = pg_fetch_result($res,0,total);
		$sql = "SELECT total_custo_peca_os_item
				FROM tbl_extrato_extra
				WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$total_PC   = pg_fetch_result($res,0,0);
		}

		$sql = "SELECT  total_mao_de_obra_os AS total_MO
				FROM tbl_extrato_extra
				WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$total_MO   = pg_fetch_result($res,0,total_MO);
		}

		$sql = "SELECT  tbl_extrato.avulso AS total_DP_S FROM tbl_extrato WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$total_DP_S = pg_fetch_result($res,0,total_DP_S);
		}
		$total_PC = $total_PC + $total_RE;
	}

	// DESPESAS AVULSAS
	$sql = "SELECT  total_avulso_os_sedex AS total_Avulso
			FROM tbl_extrato_extra
			WHERE extrato = $extrato";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$total_AV = pg_fetch_result($res,0,total_Avulso);
	}

	if (strlen($total_MO) == 0 or $total_MO == 0) {
		### PARA CASOS APENAS DE ALUGUEL ###
		$total_MO = $total_AV;
		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		echo "<td width='100%' align='left'>\n";
		if (strlen($total_SD) > 0) {
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OUTROS SERVIÇOS PRESTADOS</b></font>\n";
		}else{
			$total_DP_S = $total_SD;
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>OUTRAS DESPESAS</b></font>\n";
		}
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
	}else{
		$sql = "  SELECT sum(valor_extrato_lancamento)
					FROM tbl_extrato_extra_item
					WHERE lancamento in (165,132,45)
					AND extrato=$extrato;";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_MO += pg_fetch_result($res,0,0);
		}
	}

	$total_geral = $total_GE + $totalTx;

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Período Inicial:</b></font>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Peça OS:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_PC,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	$sql = "SELECT SUM(valor_extrato_lancamento) FROM tbl_extrato_extra_item WHERE extrato = $extrato AND lancamento = 47";
	$resX = pg_query ($con,$sql);
	$taxa_adm = 0 ;
	if (pg_num_rows ($resX) > 0) $taxa_adm = pg_fetch_result ($resX,0,0);

	$taxa_adm =  ($taxa_adm > 0) ? $taxa_adm : $totalTx;

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Taxa Administrativa:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($taxa_adm,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	#---------- Total de Peças SEDEX ------------
		$sql = "SELECT total_pecas_os_sedex as total_S_PC
				FROM tbl_extrato_extra
				WHERE extrato = $extrato";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {//HD 71885
			$total_S_PC = pg_fetch_result($res,0,'total_S_PC');
		}
	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Periodo Final:</b></font>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Peça SEDEX:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_S_PC,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Mão-de-obra OS:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_MO+ $total_retorno,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

/* --------------- DESLOCAMENTO OS GEO --------------------- */
	#138 |       1 | OS GEO - Deslocamento de KM  | C              | t
	$sql = "SELECT  total_os_geo_deslocamento AS total_km
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$total_km = pg_fetch_result($res,0,'total_km');
	}

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total deslocamento km (OS Geo):&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_km,2,",",".") ."</font>\n";	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

/* --------------- DESPESAS ADICIONAIS OS GEO --------------------- */
	#113 |       1 | OS GEO - Despesas Adicionais | C              | t
	$sql = "SELECT  total_os_geo_despesa AS total_adicional
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$total_adicional = pg_fetch_result($res,0,'total_adicional');
	}

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Valor Adicional(OS Geo):&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_adicional,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

/* --------------- TROCA FATURADA --------------------- */
	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Troca Faturada:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>-". number_format($troca_sub_total,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

/* --------------- Sub Total Despesas SEDEX ---------------------*/
	$sql = "SELECT  total_despesas_os_sedex AS total_DP_S
			FROM    tbl_extrato_extra
			WHERE   tbl_extrato_extra.extrato = $extrato ";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res) > 0) {
		$total_DP_S = pg_fetch_result($res,0,'total_DP_S');
	}

	$sql = "SELECT SUM(valor) FROM tbl_extrato_lancamento WHERE extrato = $extrato AND admin notnull and lancamento not in (47) ";
	$resX = pg_query ($con,$sql);
	$outros = 0 ;
	if (pg_num_rows ($resX) > 0) $outros = pg_fetch_result ($resX,0,0);

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>Sub Total Despesas SEDEX:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_DP_S,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table border='0' cellpadding='2' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='120' align='center'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	echo "<td width='150' align='left'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'></font>\n";
	echo "</td>\n";
	echo "<td width='250' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>TOTAL GERAL:&nbsp;</b></font>\n";
	echo "</td>\n";
	echo "<td width='50' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". number_format($total_geral,2,",",".") ."</font>\n";
	echo "</td>\n";
	echo "<td width='10%' align='right'>\n";
	echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'></font>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
	echo "<tr>\n";
	echo "<td width='100%' align='left'>\n";
	echo "<hr>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}
?>
<br>
</body>
</html>
