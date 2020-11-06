<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "Relatório maior tempo entre interações";

?>
<head>
<title>Relatório maior tempo entre interações</title>
</head>
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
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>

<? include "javascript_pesquisas.php" ?>

<?
	$data_inicial       = $_GET['data_inicial'];
	$data_final         = $_GET['data_final'];
	$produto            = $_GET['produto'];
	$natureza_chamado   = $_GET['natureza'];
	$status             = utf8_encode($_GET['status']);
	$tipo               = $_GET['tipo'];
	$atendente          = $_GET['atendente'];
	$origem 			= $_GET['origem'];

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	$cond_5 = " 1 = 1 ";

	if(strlen(trim($origem)) > 0){
		$cond_origem = "AND tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
	}

	if(strlen($produto)>0){
		$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}elseif($login_fabrica == 85 and strlen(trim($natureza_chamado))==0){
		$cond_2 = " tbl_hd_chamado.categoria <> 'garantia_estendida' ";
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

	if(strlen($atendente)>0){
		$cond_5 = " tbl_hd_chamado.atendente = $atendente ";
	}

	if(in_array($login_fabrica, array(169,170))){
		$sql_campos = ", tbl_hd_chamado_origem.descricao AS origem ,
						tbl_hd_classificacao.descricao AS classificacao,
						tbl_motivo_contato.descricao as descricao_motivo_contato,
						tbl_hd_providencia.descricao as descricao_providencia";

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
		$sql = "SELECT	tbl_hd_chamado.hd_chamado                           ,
						tbl_hd_chamado.titulo                              ,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data  ,
						( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao ,
						tbl_produto.descricao as produto                   ,
						tbl_hd_chamado.categoria                           ,
						tbl_defeito_reclamado.descricao as defeito_reclamado,
						tbl_admin.login

		FROM(
			SELECT	Y.hd_chamado,
					CASE WHEN (dias_aberto - feriado - fds) = 0 THEN 1
					ELSE (dias_aberto - feriado - fds)
					END AS dias,
					item
			FROM (
				SELECT	X.hd_chamado,
						(	SELECT COUNT(*)
							FROM fn_calendario(X.data_abertura::date,X.ultima_data::date)
							where nome_dia in('Domingo','Sábado')
						) AS fds,
						(	SELECT COUNT(*)
							FROM tbl_feriado
							WHERE tbl_feriado.fabrica = 6 AND tbl_feriado.ativo IS TRUE
							AND tbl_feriado.data BETWEEN X.data_abertura::date AND X.ultima_data::date
						) AS feriado,
						X.item ,
						EXTRACT('days' FROM X.ultima_data::timestamp - X.data_abertura ::timestamp) AS dias_aberto,
						X.data_abertura, X.ultima_data
				FROM(	SELECT	tbl_hd_chamado.hd_chamado,
								TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD') AS data_abertura,
								COUNT(tbl_hd_chamado_item.hd_chamado) AS item,
								(	SELECT to_char(tbl_hd_chamado_item.data,'YYYY-MM-DD')
									FROM tbl_hd_chamado_item
									WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
									ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC LIMIT 1
								) AS ultima_data
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_item using(hd_chamado)
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado_item.interno is not true
						and tbl_hd_chamado.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'
						AND tbl_hd_chamado_item.data >= tbl_hd_chamado.data
						and $cond_1
						and $cond_2
						and $cond_3
						and $cond_4
						and $cond_5
						GROUP BY tbl_hd_chamado.hd_chamado, tbl_hd_chamado.data
				) AS X
			) as Y
		) as W
		join tbl_hd_chamado on W.hd_chamado = tbl_hd_chamado.hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		JOIN tbl_admin on tbl_hd_chamado.atendente = tbl_admin.admin
		where (dias/item)::integer = $tipo

			";
	$sql = "SELECT tbl_hd_chamado.hd_chamado                           ,
					tbl_hd_chamado.titulo                              ,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data  ,
					( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data desc LIMIT 1 ) AS data_interacao ,
					tbl_produto.descricao as produto                   ,
					tbl_hd_chamado.categoria                           ,
					tbl_defeito_reclamado.descricao as defeito_reclamado,
					tbl_admin.login,
					tbl_hd_motivo_ligacao.descricao AS providencia
					$sql_campos
			FROM (
			SELECT tbl_hd_chamado.hd_chamado,
					CASE WHEN
						(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
							FROM  tbl_hd_chamado_item
							WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
							AND   tbl_hd_chamado_item.interno IS NOT TRUE
							AND tbl_hd_chamado_item.data >= tbl_hd_chamado.data
							LIMIT 1) IS NULL THEN '0'
					ELSE
						(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
							FROM  tbl_hd_chamado_item
							WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
							AND   tbl_hd_chamado_item.interno IS NOT TRUE
							AND tbl_hd_chamado_item.data >= tbl_hd_chamado.data
							LIMIT 1)
					END AS intervalo
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra     on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
	        WHERE tbl_hd_chamado.fabrica  = $login_fabrica
			AND   tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND tbl_hd_chamado.posto is null 
			AND   $cond_1
			AND   $cond_2
			AND   $cond_3
			AND   $cond_4
			AND   $cond_5
			$cond_origem
			) AS X
			JOIN tbl_hd_chamado              on tbl_hd_chamado.hd_chamado               = X.hd_chamado
			JOIN tbl_hd_chamado_extra        on tbl_hd_chamado.hd_chamado               = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
			LEFT JOIN tbl_produto            on tbl_produto.produto                     = tbl_hd_chamado_extra.produto
			LEFT JOIN tbl_defeito_reclamado  on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
			JOIN tbl_admin                   on tbl_hd_chamado.atendente                = tbl_admin.admin
			$sql_joins
			WHERE X.intervalo = $tipo";

		// echo nl2br($sql);

###########################################

		//echo $sql;

		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			echo "<table width='100%' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Chamado</TD>\n";
			if($login_fabrica == 50){
				echo "<td class='menu_top' background='imagens_admin/azul.gif'> Tipo de Atendimento \n";
			}
			if(!in_array($login_fabrica, array(169,170))){
				echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Assunto</TD>\n";
			}

			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Abertura</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Última Interação</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Atendente</TD>\n";
			if(in_array($login_fabrica, array(169,170))){
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Classificação</td>\n";
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Origem</td>\n";
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Providência</td>\n";
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Providência nv. 3</td>\n";
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Motivo Contato</td>\n";
			}
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter       = pg_result($res,$y,hd_chamado);
				$titulo           = pg_result($res,$y,titulo);
				$abertura         = pg_result($res,$y,data);
				$login            = pg_result($res,$y,login);
				$categoria        = pg_result($res,$y,categoria);
				$defeito_reclamado= pg_result($res,$y,defeito_reclamado);
				$produto          = pg_result($res,$y,produto);
				$ultima_interacao = pg_result($res,$y,data_interacao);
				$providencia = pg_fetch_result($res, $y, 'providencia');
				$descricao_providencia = pg_fetch_result($res, $y, 'descricao_providencia');
				$motivo_contato        = pg_fetch_result($res, $y, 'descricao_motivo_contato');

				$origem           	 = pg_result($res,$y,'origem');
				$classificacao       = pg_result($res,$y,'classificacao');
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				if($login_fabrica == 6){
					echo "<TD align='center' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}else{
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}
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
					echo "<TD align='left' nowrap>$descricao_providencia</TD>\n";
					echo "<TD align='left' nowrap>$motivo_contato</TD>\n";
				}
				echo "</TR >\n";
			}
			echo "</table>";
			echo "<center>Quantidade de registros: ";
			echo pg_numrows($res);
			echo "</center>";
		}


	}

?>
