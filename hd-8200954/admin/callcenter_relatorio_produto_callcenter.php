<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO POR PRODUTO";


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
	$origem             = $_GET['origem'];
	$linha             = $_GET['linha'];
//	echo "$data_inicial - $data_final - prod:$produto - nature:$natureza_chamado - recla:$reclamado<BR>";

	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";

	if(strlen($produto)>0){
		$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
	}else{
		$cond_1 = " tbl_hd_chamado_extra.produto is null ";
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}

	if(strlen($reclamado)>0){
		$cond_4 = " tbl_hd_chamado_extra.defeito_reclamado = $reclamado  ";
		if($reclamado==0){
			$cond_4 = " tbl_hd_chamado_extra.defeito_reclamado is null ";
		}

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

		if($login_fabrica == 74){
		    $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
		}
		$join_hd = " JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					LEFT JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_extra.produto 
					LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
					LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
			        LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica";

		if($login_fabrica == 151) {
			if($data_inicial > '2015-12-04') {
				$join_hd = " Join tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado 
							JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
								LEFT JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_item.produto 
								LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
								LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
						        LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica";

				$cond_1 = " tbl_hd_chamado_item.produto = $produto ";
			}
		}


		if(in_array($login_fabrica, array(189))){

			$cond_1 = "";
			if (strlen($origem) > 0) {
				$cond_1 .= " tbl_hd_chamado_extra.hd_chamado_origem = {$origem}";
			}
			if (strlen($linha) > 0) {
				$cond_1 .= "  AND tbl_produto.linha={$linha}";
			}
			if(strlen($produto)>0){
				$join_hd= "	JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado ";
				$cond_1 .= " AND tbl_hd_chamado_item.produto = $produto ";
			}
			$sql_joins = " JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					LEFT JOIN tbl_produto ON tbl_produto.produto= tbl_hd_chamado_item.produto 
					LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
					LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
			        LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica";
	    }



		$sql = "
				SELECT	tbl_hd_chamado.hd_chamado                           ,
						tbl_hd_chamado.titulo                              ,
						tbl_hd_chamado.hd_chamado_anterior					,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data  ,
						( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data desc LIMIT 1 ) AS data_interacao ,
						tbl_produto.descricao as produto                   ,
						tbl_hd_chamado.categoria                           ,
						tbl_defeito_reclamado.descricao as defeito_reclamado,
						tbl_admin.login,
						tbl_hd_chamado_extra.posto AS codigo_posto,
						tbl_posto.nome AS descricao_posto,
						tbl_hd_motivo_ligacao.descricao AS providencia
						$sql_campos
				from tbl_hd_chamado
				$join_hd 
				JOIN tbl_admin on tbl_hd_chamado.atendente = tbl_admin.admin
				$sql_joins
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and  tbl_hd_chamado.status<>'Cancelado'
				AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
				and tbl_hd_chamado.posto is null 
				AND $cond_1
				AND $cond_2
				AND $cond_4
				$cond_admin_fale_conosco
		";
		//echo "<pre>".print_r($sql,1)."</pre>";exit;
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){ //echo pg_numrows($res);
			echo "<table width='100%' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Chamado</TD>\n";

			if($login_fabrica == 50){
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Tipo de Atendimento</td>";
			}
			if($login_fabrica == 115){ //hd_chamado=2710901
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Chamado Relacionado</TD>\n";
			}
			if(!in_array($login_fabrica, array(169,170))){
				echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Assunto</TD>\n";
			}

			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Abertura</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Fechamento</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Natureza</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Produto</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Atendente</TD>\n";
			if(in_array($login_fabrica, array(169,170))){
				echo "<th class='menu_top' background='imagens_admin/azul.gif'>Classificação</th>\n";
				echo "<th class='menu_top' background='imagens_admin/azul.gif'>Origem</th>\n";
				echo "<th class='menu_top' background='imagens_admin/azul.gif'>Providência</th>\n";
				echo "<th class='menu_top' background='imagens_admin/azul.gif'>Providência nv. 3</th>\n";
				echo "<th class='menu_top' background='imagens_admin/azul.gif'>Motivo Contato</th>\n";
			}
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Posto Autorizado</TD>\n";

			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter       = pg_result($res,$y,hd_chamado);
				$titulo           = pg_result($res,$y,titulo);
				$abertura         = pg_result($res,$y,data);
				$ultima_interacao = pg_result($res,$y,data_interacao);
				$login            = pg_result($res,$y,login);
				$categoria        = pg_result($res,$y,categoria);
				$produto          = pg_result($res,$y,produto);
				$codigo_posto     = pg_result($res,$y,codigo_posto);
				$descricao_posto  = pg_result($res,$y,descricao_posto);
				if($login_fabrica == 115){ //hd_chamado=2710901
					$hd_chamado_anterior = pg_fetch_result($res, $y, 'hd_chamado_anterior');
				}
				$providencia = pg_fetch_result($res, $y, 'providencia');
				$origem           	 = pg_result($res,$y,'origem');
				$classificacao       = pg_result($res,$y,'classificacao');
				$providencia_descricao    = pg_fetch_result($res, $y, 'descricao_providencia');
				$descricao_motivo_contato = pg_fetch_result($res, $y, 'descricao_motivo_contato');

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				if($login_fabrica == 6){
					echo "<TD align='center' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}else{
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$callcenter' target='_blank'>$callcenter</a></TD>\n";
				}

				if($login_fabrica == 50){
					echo "<td>$providencia</td>";
				}
				if($login_fabrica == 115){ //hd_chamado=2710901
					echo "<TD align='center' nowrap><a href='callcenter_interativo.php?callcenter=$hd_chamado_anterior' target='_blank'>$hd_chamado_anterior</a></TD>\n";
				}
				if(!in_array($login_fabrica, array(169,170))){
					echo "<TD align='left' nowrap>$titulo</TD>\n";
				}

				echo "<TD align='center' nowrap>$abertura</TD>\n";
				echo "<TD align='center' nowrap>$ultima_interacao</TD>\n";
			
				switch ($categoria) {
					case 'troca_produto':
						$categoria_exibir = "Troca do Produto";
						break;
					case 'duvida_produto':
						$categoria_exibir = "Dúvida de Produto";
						break;
					case 'reclamacao_produto':
						$categoria_exibir = "Reclamação de Produto";
						break;
					case 'reclame_aqui':
						$categoria_exibir = "Reclame Aqui";
						break;
					case 'reclamacao_empresa':
						$categoria_exibir = "Reclamação da Empresa";
						break;
					case 'reclamacao_at':
						$categoria_exibir = "Reclamação da Assistência Técnica";
						break;
					case 'procon':
						$categoria_exibir = "Procon";
						break;
					case 'outros_assuntos':
						$categoria_exibir = "Outros Assunto";
						break;
					case 'produto_duvida_sobre_utilizacao':
						$categoria_exibir = "Dúvida sobre utilização";
						break;
					case 'produto_reclamacao':
						$categoria_exibir = "Reclamação";
						break;
					case 'produto_local_de_assistencia':
						$categoria_exibir = "Local de Assistência";
						break;
					case 'produto_onde_comprar':
						$categoria_exibir = "Onde Comprar";
						break;
					case 'produto_outros':
						$categoria_exibir = "Outros Assuntos";
						break;
					case 'empresa_trabelhe_conosco':
						$categoria_exibir = "Trabalhe Conosco";
						break;
					case 'empresa_elogio':
						$categoria_exibir = "Elogio";
						break;
					case 'E-COMMERCE':
						$categoria_exibir = "E-COMMERCE";
						break;
					case 'empresa_outros':
						$categoria_exibir = "Outros Assuntos";
						break;
					case 'at_reclamacao':
						$categoria_exibir = "Reclamação";
						break;
					case 'at_demora_atendimento':
						$categoria_exibir = "Demora no Atendimento";
						break;
					case 'revenda_quero_ser_um_revendedor':
						$categoria_exibir = "Quero ser um revendedor";
						break;
					case 'revenda_outros':
						$categoria_exibir = "Outros Assuntos";
						break;
					case 'sugestao':
						$categoria_exibir = "Sugestão";
						break;

					default:
						$categoria_exibir = $categoria;
						break;
				}

				echo "<TD align='center' nowrap>"; echo "$categoria_exibir"; echo "</TD>\n";
				echo "<TD align='center' nowrap>$produto</TD>\n";
				echo "<TD align='left' nowrap>$login</TD>\n";
				if(in_array($login_fabrica, array(169,170))){
					echo "<TD align='left' nowrap>$classificacao</TD>\n";
					echo "<TD align='left' nowrap>$origem</TD>\n";
					echo "<TD align='left' nowrap>$providencia</TD>\n";
					echo "<TD align='left' nowrap>$providencia_descricao</TD>\n";
					echo "<TD align='left' nowrap>$descricao_motivo_contato</TD>\n";
				}
				echo "<TD align='left' nowrap>$codigo_posto - $descricao_posto</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";

			echo "<center>Quantidade de registros: ";
			echo pg_numrows($res);
			echo "</center>";
		}


	}

?>
