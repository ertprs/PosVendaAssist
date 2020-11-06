<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";


?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>

<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status){
janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>

<? include "javascript_pesquisas.php" ?>



<?
	$data_inicial       = $_GET['data_inicial'];
	$data_final         = $_GET['data_final'];
	$produto            = $_GET['produto'];
	$natureza_chamado   = $_GET['natureza'];
	$status             = $_GET['status'];
	$tipo               = $_GET['tipo'];
	$adm                = $_GET['adm'];
	$defeito_reclamado               = $_GET['defeito_reclamado'];


	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";


	if(strlen($produto)>0){
		$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	if(strlen($status)>0){
		if($status == "nao_resolvido"){
			$cond_3 = " tbl_hd_chamado.status <> 'Resolvido'  ";
		}else{
			$cond_3 = " tbl_hd_chamado.status = '".utf8_decode($status)."'  ";
		}
	}

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if(in_array($login_fabrica, array(169,170))){
		$sql_campos = ", tbl_hd_chamado_origem.descricao AS origem ,
						tbl_hd_classificacao.descricao AS classificacao,
						tbl_motivo_contato.descricao as descricao_motivo_contato,
						tbl_hd_providencia.descricao as descricao_providencia
		";

		$sql_joins .= "	JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
								AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
						JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
								AND tbl_hd_classificacao.fabrica = {$login_fabrica}
						LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
						AND tbl_hd_providencia.fabrica = {$login_fabrica}
						LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
						AND tbl_motivo_contato.fabrica = {$login_fabrica}";
	}

	if(strlen($msg_erro)==0){

######################################
			if ($login_fabrica != 2) {
				$sql = "SELECT tbl_hd_chamado.hd_chamado                           ,
								tbl_hd_chamado.titulo                              ,
								to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data  ,
								( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao ,
								tbl_produto.descricao as produto                   ,
								tbl_hd_chamado.categoria                           ,
								tbl_admin.login,
								tbl_hd_motivo_ligacao.descricao AS providencia
								$sql_campos
						FROM tbl_hd_chamado
						LEFT JOIN tbl_hd_chamado_extra  ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						LEFT JOIN tbl_produto           ON tbl_produto.produto = tbl_hd_chamado_extra.produto
				        LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
						JOIN tbl_admin                  ON tbl_hd_chamado.admin = tbl_admin.admin
						$sql_joins
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado.data between '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						AND tbl_hd_chamado.admin = $adm
						AND $cond_1
						AND $cond_2
						AND $cond_3
						AND $cond_4
					";

			} else {
				$sql = "select tbl_hd_chamado_item.hd_chamado                                      ,
								to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data                  ,
								to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') AS data_interacao   ,
								tbl_produto.descricao as produto                                   ,
								tbl_hd_chamado.categoria                                           ,
								tbl_hd_chamado.titulo                                              ,
								tbl_admin.login
						FROM tbl_hd_chamado_item
						LEFT JOIN tbl_hd_chamado_extra  ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						LEFT JOIN tbl_produto           ON tbl_produto.produto = tbl_hd_chamado_extra.produto
						JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
						JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
						WHERE tbl_hd_chamado_item.admin = $adm
						AND tbl_hd_chamado_item.data between '$data_inicial 00:00:00' AND '$data_final 23:59:59'
						AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND $cond_1
						AND $cond_2
						AND $cond_3
						AND $cond_4
					";
			}
		//echo $sql;
		//exit;


###########################################


		//echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='100%' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
			echo "<thead><TR class='menu_top' background='imagens_admin/azul.gif'>\n";
			echo "<th>Chamado</th>\n";
			if($login_fabrica == 50){
				echo "<th>Tipo de Atendimento</th>\n";
			}
			if(!in_array($login_fabrica, array(169,170))){
				echo "<th>Assunto</th>\n";
			}
			echo "<th>Abertura</th>\n";
			echo "<th>Última Interação</th>\n";
			echo "<th>Atendente</th>\n";
			if(in_array($login_fabrica, array(169,170))){
				echo "<th >Classificação</th>\n";
				echo "<th >Origem</th>\n";
				echo "<th >Providência</th>\n";
				echo "<th >Providência nv. 3</th>\n";
				echo "<th >Motivo Contato</th>\n";
			}
			echo "<th>Interações</th>\n";
			echo "</TR >\n</thead>\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter       = pg_result($res,$y,hd_chamado);
				$titulo           = pg_result($res,$y,titulo);
				$abertura         = pg_result($res,$y,data);
				$login            = pg_result($res,$y,login);
				$categoria        = pg_result($res,$y,categoria);
				$produto          = pg_result($res,$y,produto);
				$ultima_interacao = pg_result($res,$y,data_interacao);
				$providencia = pg_fetch_result($res, $y, 'providencia');
				$origem           	 	  = pg_result($res,$y,'origem');
				$classificacao            = pg_result($res,$y,'classificacao');
				$providencia_descricao    = pg_fetch_result($res, $y, 'descricao_providencia');
				$descricao_motivo_contato = pg_fetch_result($res, $y, 'descricao_motivo_contato');

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$callcenter' target='blank'>$callcenter</a></TD>\n";
				if($login_fabrica == 50){
					echo "<td nowrap>$providencia</td>";
				}
				if(!in_array($login_fabrica, array(169,170))){
					echo "<TD align='left' nowrap>$titulo</TD>\n";
				}
				echo "<TD align='center' nowrap>$abertura</TD>\n";
				echo "<TD align='center' nowrap>$ultima_interacao</TD>\n";

					echo "<TD align='left' nowrap>$login</TD>\n";


				if(in_array($login_fabrica, array(169,170))){
					echo "<TD align='center' nowrap>$classificacao</TD>\n";
					echo "<TD align='center' nowrap>$origem</TD>\n";
					echo "<TD align='left' nowrap>$providencia</TD>\n";
					echo "<TD align='left' nowrap>$providencia_descricao</TD>\n";
					echo "<TD align='left' nowrap>$descricao_motivo_contato</TD>\n";
				}

				echo "<TD align='left' nowrap>";
				$sql_1 = "SELECT tbl_admin.login,
								TO_CHAR(tbl_hd_chamado_item.data,'DD/MM/YYYY') AS data_interacao
							FROM tbl_hd_chamado_item
							JOIN tbl_admin using(admin)
							WHERE hd_chamado = $callcenter
							AND tbl_hd_chamado_item.admin <> $adm;";
				$res_1 = pg_exec($con,$sql_1);
				if(pg_numrows($res_1) > 0){
					for($k=0;$k<pg_numrows($res_1);$k++){
						$interacao_login = pg_result($res_1,$k,login);
						$interacao_data  = pg_result($res_1,$k,data_interacao);
						echo "$interacao_login - $interacao_data <br>";
					}
				}
				echo "</TD>\n";

				echo "</TR >\n";
			}
			echo "</table>";
			echo "<center>Quantidade de registros: ";
			echo pg_numrows($res);
			echo "</center>";
		}


	}

?>
