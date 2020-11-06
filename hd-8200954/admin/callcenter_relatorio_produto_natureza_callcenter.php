<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO PRODUTO X NATUREZA";


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

	if(in_array($login_fabrica,array(101,162))){
		$origem = $_GET["origem"];
	}

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";

	if(strlen($produto)>0){
		$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	if(strlen($status)>0){
		$cond_3 = " tbl_hd_chamado.status = '$status'  ";
	}

	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if(in_array($login_fabrica,array(101,162)) and strlen(trim($origem))>0){
        $cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
    }



	if(strlen($msg_erro)==0){
		$sql = "
				SELECT Y.hd_chamado AS callcenter  ,
						Y.titulo                   ,
						to_char(Y.data_abertura,'DD/MM/YYYY') as abertura            ,
						to_char(Y.data_interacao,'DD/MM/YYYY') AS ultima_interacao   ,
						tbl_admin.login
				FROM(
					SELECT  extract( 'days' from data_interacao::timestamp - data_abertura ::timestamp) as periodo,*
					FROM (
						SELECT tbl_hd_chamado.hd_chamado , tbl_hd_chamado.titulo, tbl_hd_chamado.atendente,
						tbl_hd_chamado.data as data_abertura ,
						( SELECT tbl_hd_chamado_item.data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00'
						AND '$data_final 23:59:59'
						AND $cond_1
						AND $cond_2
						AND $cond_3
						$cond_origem
					) AS X
				) as Y
				JOIN tbl_admin on tbl_admin.admin = Y.atendente
				WHERE Y.periodo = $tipo
					ORDER BY abertura
		";
		$sql = "
			SELECT tbl_hd_chamado.hd_chamado as callcenter,
				tbl_hd_chamado.titulo,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as abertura,
				( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY')  FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS ultima_interacao ,
				tbl_admin.login
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra  on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.atendente
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND $cond_1
			AND $cond_2
			AND $cond_3
			AND	$cond_4
			$cond_origem
			AND tbl_hd_chamado_extra.dias_aberto =  $tipo";

		if($login_fabrica == 74){
            $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
        }

		$sql = "
		select	tbl_hd_chamado.hd_chamado as callcenter,
				tbl_hd_chamado.titulo,
				tbl_hd_chamado.hd_chamado_anterior,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as abertura,
				( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY')  FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS ultima_interacao ,
				tbl_admin.login,
				tbl_hd_motivo_ligacao.descricao AS hd_motivo_ligacao
		from tbl_hd_chamado
		join tbl_hd_chamado_extra using(hd_chamado)
		join tbl_produto using(produto)
		JOIN tbl_admin on tbl_admin.admin = tbl_hd_chamado.atendente
        LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
		WHERE fabrica_responsavel =  $login_fabrica
		AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
		AND $cond_1
		AND $cond_2
		AND $cond_3
		AND $cond_4
		$cond_admin_fale_conosco
		$cond_origem
		AND tbl_hd_chamado.status <> 'Cancelado'

		";

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='100%' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Chamado</TD>\n";
			if($login_fabrica == 50){
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Tipo de Atendimento</TD>\n";
			}
			if($login_fabrica == 115){ //hd_chamado=2710901
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Chamado Relacionado</TD>\n";
			}
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Assunto</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Abertura</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Fechamento</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Atendente</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter       = pg_result($res,$y,callcenter);
				$titulo           = pg_result($res,$y,titulo);
				$abertura         = pg_result($res,$y,abertura);
				$ultima_interacao = pg_result($res,$y,ultima_interacao);
				$login            = pg_result($res,$y,login);
				$hd_motivo_ligacao = pg_fetch_result($res, $y, 'hd_motivo_ligacao');
				if($login_fabrica == 115){ //hd_chamado=2710901
					$hd_chamado_anterior = pg_fetch_result($res, $y, "hd_chamado_anterior");
				}
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				if($login_fabrica == 6){
					echo "<TD align='center' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}else{
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}
				if($login_fabrica == 50){
					echo "<td nowrap>$hd_motivo_ligacao</td>";
				}
				if($login_fabrica == 115){ //hd_chamado=2710901
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$hd_chamado_anterior' target='_blank'>$hd_chamado_anterior</a></TD>\n";
				}
				echo "<TD align='left' nowrap>$titulo</TD>\n";
				echo "<TD align='center' nowrap>$abertura</TD>\n";
				echo "<TD align='center' nowrap>$ultima_interacao</TD>\n";
				echo "<TD align='left' nowrap>$login</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
			echo "<center>Quantidade de registros: ";
			echo pg_numrows($res);
			echo "</center>";

		}else {
			echo "<center>Nenhum resultado encontrado!</center>";
		}


	}

?>
