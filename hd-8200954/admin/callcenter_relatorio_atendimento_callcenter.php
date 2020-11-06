<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$data_inicial       = $_GET['data_inicial'];
$data_final         = $_GET['data_final'];
$produto            = $_GET['produto'];
$natureza_chamado   = $_GET['natureza'];
$status             = utf8_decode($_GET['status']);
$tipo               = $_GET['tipo'];
$posto              = $_GET['posto'];
$atendente          = $_GET['atendente'];
$tipo_data          = $_GET['tipo_data'];
$op                 = $_GET['op'];
$chamado            = $_GET['chamado'];
$tipo_cliente       = $_GET['tipo_cliente'];
$motivo_atendimento = $_GET['motivo_atendimento'];
$linhaDeProduto		= $_GET["linhaDeProduto"];
$providencia3 		= $_GET['providencia_nivel_3'];
$motivo_contato 	= $_GET['motivo_contato'];
$hd_classificacao 	= $_GET["hd_classificacao"];

if ($login_fabrica == 50) {
	$arr_motivo_atendimento = explode(",", $motivo_atendimento);
}

if(in_array($login_fabrica, array(101,169,170))){
	$origem = $_GET["origem"];
}

if($login_fabrica == 162){ //HD-3352176
	$motivo_transferencia = $_GET['motivo_transferencia'];
}

if($login_fabrica == 35){
	$tipo_atendimento = $_GET['tipo_atendimento'];
	if(strlen($tipo_atendimento) > 0){
		$sql_tipo_atendimento = ($tipo_atendimento == "1") ? " AND tbl_hd_chamado_extra.array_campos_adicionais = '{\"fale_conosco\":\"true\"}' " : " AND tbl_hd_chamado_extra.array_campos_adicionais is null ";
	}
}

$pesquisa_satisfacao = trim ($_GET['pesquisa_satisfacao']); // HD 720502

	if(strlen($chamado) > 0 AND $op=='ver') {
		$sql = "SELECT
					tbl_hd_chamado_item.hd_chamado_item    ,
					to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
					tbl_hd_chamado_item.comentario         ,
					tbl_admin.login    ,
					tbl_hd_chamado_item.interno            ,
					tbl_hd_chamado_item.status_item        ,
					tbl_hd_chamado_item.interno            ,
					tbl_hd_chamado_item.enviar_email
				FROM tbl_hd_chamado_item
				JOIN tbl_admin on tbl_hd_chamado_item.admin = tbl_admin.admin
				JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
				WHERE tbl_hd_chamado_item.hd_chamado = $chamado
				AND   tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				order by tbl_hd_chamado_item.data ";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$resposta = "<table width='600' border='1' align='center' cellpadding='2' cellspacing='1' style=' border:#485989 1px solid; font-size:10px'>";
			$resposta .="<tr><td class='menu_top'>Interação</td>";
			$resposta .="<td class='menu_top'>Atendente</td>";
			$resposta .="<td align='center' class='menu_top'>Data Interação</td></tr>";
			for($x=0;pg_numrows($res)>$x;$x++){
				$data               = pg_result($res,$x,data);
				$comentario         = pg_result($res,$x,comentario);
				$atendente_resposta = pg_result($res,$x,login);
				$status_item        = pg_result($res,$x,status_item);
				$interno            = pg_result($res,$x,interno);
				$enviar_email       = pg_result($res,$x,enviar_email);
				$xx = $xx + 1;


				$resposta .= "<tr class='Conteudo2'>";
				$resposta .= "<td align='center'>$xx</td>";
				$resposta .= "<td align='center' class='Conteudo2'>".nl2br($atendente_resposta)."</td>";
				$resposta .= "<td align='center' nowrap >$data</td>";
				$resposta .= "</tr>";
				$resposta .= "<tr>";
				$resposta .= "<td align='left' align='center' bgcolor='#FFFFFF' colspan='1' class='Conteudo2'>Comentário:</td>";
				$resposta .= "<td colspan='2'  valign='center' bgcolor='#FFFFFF' ><font size=2>".nl2br($comentario)."</font></td>";
				$resposta .= "</tr>";
			}
			$resposta .= "</table><br>";
		}else{
			$resposta = "Nenhuma interação feita neste chamado";
		}
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			echo "ok|$resposta";
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "erro;$sql ==== $msg_erro ";
		}
		flush();
		exit;
	}


$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";


?>
<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="plugins/dataTable.js"></script>
<script src="plugins/resize.js"></script>

<script language='javascript'>

function retornaChamado (http , componente ) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com = document.getElementById(componente);
					com.innerHTML   = results[1];
				}else{
					alert ('Erro ao abrir chamado' );
				}
			}
		}
	}
}

function pegaChamado (chamado,dados) {
	url = "<?= $PHP_SELF ?>?op=ver&chamado=" + escape(chamado) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaChamado (http , dados) ; } ;
	http.send(null);
}



function MostraEsconde(dados,chamado,imagem){
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		var img    = document.getElementById(imagem);
		if (style2.style.display){
			style2.style.display = "";
			img.src='imagens/mais.gif';
		}else{
			style2.style.display = "block";
			img.src='imagens/menos.gif';
			pegaChamado(chamado,dados);
		}
	}
}

function AbreCallcenter(data_inicial,data_final,produto,natureza,status){
janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>
<?php
		if($natureza_chamado =='Reclamação') {
			$xnatureza_chamado="('reclamacao_at','reclamacao_empresa','reclamacao_produto')";
		}elseif($natureza_chamado =='Dúvida') {
			$xnatureza_chamado="('duvida_produto')";
		}else{
			$xnatureza_chamado="('$natureza_chamado')";
		}

	$cond_1 = "";
	$cond_2 = "";
	$cond_3 = "";
	$cond_4 = "";
	$cond_5 = "";

	$cond_9 = "";
    
	if ($login_fabrica == 80) {
		    
	    $tipo = $_GET['tipo_os'];

	    if ($tipo != "") {

	        $cond_9 = " AND tbl_hd_chamado_extra.consumidor_revenda = '{$tipo}'";
	    }

	}

	$cond_group = "";

	if(strlen($produto)>0){
		$cond_1 = " and tbl_hd_chamado_extra.produto = $produto ";
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " and tbl_hd_chamado.categoria in $xnatureza_chamado ";
	}elseif($login_fabrica == 85 and strlen(trim($natureza_chamado))==0){
        $cond_2 = " and tbl_hd_chamado.categoria <> 'garantia_estendida' ";
    }

	if(strlen($status)>0){
		$cond_3 = " and fn_retira_especiais(tbl_hd_chamado.status) = '$status'  ";
	}

	if($login_fabrica==6){
		$cond_4 = " and tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if(strlen($posto) >0 ){
		$cond_5 = " and tbl_hd_chamado_extra.posto=$posto ";
	}

	if (in_array($login_fabrica, [169, 170])) {
    	if (!empty($providencia3)) {
    		$condProv3 = "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
    	}

    	if (!empty($motivo_contato)) {
    		$condMotivoContato = "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
    	}
    }

	if (strlen($atendente)>0){
		$cond_atend = "AND tbl_hd_chamado.atendente = $atendente";
	}

	$cond_6=" and tbl_hd_chamado.data between '$data_inicial 00:00:00' and '$data_final 23:59:59' ";

	if(in_array($login_fabrica,[11,172])){
		$sql_join = " JOIN (select hd_chamado, max(tbl_hd_chamado_item.data) as data FROM tbl_hd_chamado_item JOIN tbl_hd_chamado USING(hd_chamado) WHERE fabrica_responsavel = $login_fabrica GROUP BY hd_chamado) hi ON hi.hd_chamado = tbl_hd_chamado.hd_chamado ";
		$cond_6 = " AND hi.data between '$data_inicial 00:00:00' and '$data_final 23:59:59' ";
	}

	if ($login_fabrica == 178 AND !empty($hd_classificacao)){
		$cond_classificacao = "AND tbl_hd_chamado.hd_classificacao = $hd_classificacao";
	}

	if(strlen($tipo_data) > 0){ // HD 46566
		if($tipo_data =='abertura') {
			$cond_6= " and tbl_hd_chamado.data between '$data_inicial 00:00:00' and '$data_final 23:59:59' ";
		}elseif($tipo_data =='interacao'){
			$sql_join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado= tbl_hd_chamado_item.hd_chamado ";
			$cond_6=" and tbl_hd_chamado_item.data between '$data_inicial 00:00:00' and '$data_final 23:59:59' ";
		}
	}

	if ( !empty($pesquisa_satisfacao) ) { // HD 720502

		$sql_join .= " JOIN tbl_resposta ON tbl_hd_chamado.hd_chamado = tbl_resposta.hd_chamado ";

	}

	$sem_pesquisa = trim ( $_GET['sem_pesquisa'] );
	if ( !empty($sem_pesquisa) ) {
		if($sem_pesquisa == "sem_pesquisa"){
			$sql_join .= " LEFT JOIN tbl_resposta ON tbl_hd_chamado.hd_chamado = tbl_resposta.hd_chamado ";
			$cond_sem_pesquisa = " 	AND resposta IS NULL AND tbl_hd_chamado.status <> 'Resolvido'
									AND recusou_pesquisa IS NULL
									AND cliente_nao_encontrado IS NULL ";
		}elseif($sem_pesquisa == "sem_pesquisa_resolvido"){
			$sql_join .= " LEFT JOIN tbl_resposta ON tbl_hd_chamado.hd_chamado = tbl_resposta.hd_chamado ";
			$cond_sem_pesquisa = " 	AND resposta IS NULL AND tbl_hd_chamado.status = 'Resolvido'
									AND recusou_pesquisa IS NULL
									AND cliente_nao_encontrado IS NULL ";
		}else{
			$cond_sem_pesquisa = " AND $sem_pesquisa = 't' ";
		}

	}

	if(!empty($tipo_cliente)) {
		$cond_7 = " AND tbl_hd_chamado_extra.consumidor_revenda = '$tipo_cliente'";
	}

	if(!empty($motivo_atendimento)) {
		$cond_8 = " AND tbl_hd_chamado_extra.hd_motivo_ligacao in ($motivo_atendimento)";
	}

	if(in_array($login_fabrica, array(101,169,170)) and strlen(trim($origem))>0){
       	if(in_array($login_fabrica, array(169,170))){
       		$cond_origem = "and tbl_hd_chamado_extra.hd_chamado_origem = $origem ";
       	}else{
       		$cond_origem = "and tbl_hd_chamado_extra.origem = '$origem' ";
       	}
    }

    if(strlen(trim($motivo_transferencia)) > 0 AND $login_fabrica == 162){ //HD-3352176
    	$cond_hd_situacao = " AND tbl_hd_chamado_extra.hd_situacao = $motivo_transferencia ";

    }


	if(strlen($msg_erro)==0){

######################################

		if($login_fabrica == 74){
	        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
	    }

		$sql = "SELECT  distinct tbl_hd_chamado.hd_chamado                           ,
						tbl_hd_chamado.titulo                              ,
						tbl_hd_chamado.status,
						tbl_hd_chamado.hd_chamado_anterior,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data  ,
						to_char(tbl_hd_chamado.resolvido,'DD/MM/YYYY') AS resolvido  ,
						to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf  ,";

					if($login_fabrica == 11){
						$sql .= " to_char(hi.data,'DD/MM/YYYY') AS data_interacao ,  ";

					}else{
						$sql .= " ( SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY tbl_hd_chamado_item.data desc LIMIT 1 ) AS data_interacao , ";
					}

					if ($login_fabrica == 50) {
						$sql .= "tbl_hd_chamado_extra.hd_motivo_ligacao,";
					}

					if(in_array($login_fabrica, array(169,170))){
						$sql .= "tbl_hd_chamado_origem.descricao AS origem ,
									tbl_hd_classificacao.descricao AS classificacao ,
									tbl_hd_motivo_ligacao.descricao AS providencia ,
						";
					}

					$sql .=	" tbl_produto.descricao as produto                   ,
						tbl_hd_chamado.categoria                           ,
						tbl_defeito_reclamado.descricao as defeito_reclamado,
						tbl_hd_chamado.admin as login_abertura,
						tbl_admin.login,
						tbl_hd_chamado_extra.nome,
						tbl_hd_chamado_extra.fone ,
						tbl_cidade.nome as cidade_nome,
						tbl_cidade.estado,
						tbl_produto.produto,
						tbl_produto.linha,
						tbl_linha.nome as linha_nome,
						tbl_produto.referencia as produto_referencia,
						tbl_produto.descricao as produto_nome,
						tbl_hd_chamado_extra.serie,
						tbl_posto_fabrica.posto,
						tbl_posto.nome as posto_nome,
						tbl_motivo_contato.descricao as motivo_contato_descricao,
						tbl_hd_providencia.descricao as hd_providencia_descricao
				FROM tbl_hd_chamado
				INNER JOIN tbl_hd_chamado_extra ON ( tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado )
				LEFT JOIN tbl_produto ON ( tbl_produto.produto = tbl_hd_chamado_extra.produto ) 
				LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
				AND tbl_hd_providencia.fabrica = {$login_fabrica}
				LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
				AND tbl_motivo_contato.fabrica = {$login_fabrica}";


				if( ($login_fabrica == 11) && (!empty($linhaDeProduto))){

					$linha = $_GET["linhaDeProduto"];
					$sqlLinha = " JOIN tbl_linha ON ( tbl_produto.linha = tbl_linha.linha ) and
							tbl_produto.linha = $linhaDeProduto and
					  		tbl_linha.ativo ";
					$sql .= $sqlLinha;
				}else{
					$sqlLinha = " LEFT JOIN tbl_linha ON ( tbl_produto.linha = tbl_linha.linha ) "	;
					$sql .= $sqlLinha;
				}

				if(in_array($login_fabrica, array(169,170))){
					$sql .= "JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_chamado_extra.hd_chamado_origem
								AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
							JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
								AND tbl_hd_classificacao.fabrica = {$login_fabrica}
							JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
								AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}";
				}

				$sql .= " LEFT JOIN tbl_defeito_reclamado ON ( tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado )
				INNER JOIN tbl_admin ON ( tbl_hd_chamado.atendente = tbl_admin.admin )
				LEFT JOIN tbl_cidade ON ( tbl_hd_chamado_extra.cidade = tbl_cidade.cidade )
				LEFT JOIN tbl_posto ON ( tbl_hd_chamado_extra.posto = tbl_posto.posto )
				LEFT JOIN tbl_posto_fabrica ON ( tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} )
				$sql_join
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				$cond_1
				$cond_2
				$cond_3
				$cond_4
				$cond_5
				$cond_6
				$cond_7
				$cond_8
				$cond_9
				$cond_origem
				$cond_classificacao
				$cond_hd_situacao
				$sql_tipo_atendimento
				$cond_sem_pesquisa
				$cond_admin_fale_conosco
				$cond_atend order by tbl_hd_chamado.hd_chamado";
###########################################

		function _acronym_helper($string, $length = 10) {
			$short = substr($string,0,$length);
			return "<acronym title=\"{$string}\">{$short}</acronym>";
		}

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
?>
        <div id="border_table">
			<table class="table table-striped table-bordered table-hover table-fixed " >
                <thead>

                    <tr class='titulo_coluna'>
<?
			if($tipo_data =='interacao'){
?>
                        <th>Ver</th>
<?
			}
?>
                        <th>Chamado</th>
 					<?php if($login_fabrica == 115){ //hd_chamado=2710901 ?>
 					<th>At. Relacionado</th>
 					<?php } ?>
<?
			if ( !in_array($login_fabrica,array(5,74,169,170))) {
?>
                        <th>Assunto</th>
<?
			}
?>

<?
			if ($login_fabrica == 50) {
?>
						<th>Tipo Atendimento</th>
<?
			}
?>
                        <th>Abertura</th>
                        <th>Última Interação</th>
<?
			if ( $login_fabrica == 5 ) {
?>
                        <th>Atendente de Abertura</th>
<?
			}
			if ( $login_fabrica != 74 ) {
?>
                        <th>Atendente</th>
<?
			}

			if(in_array($login_fabrica, array(169,170))){
?>
				<th>Classificação</th>
    	        <th>Origem</th>
        	    <th>Providência</th>
<?php
			}
			if ( in_array($login_fabrica,array(5,74))) {
                if ( $login_fabrica == 5 ) {
?>
                        <th>Resolvido</th>
                        <th>Resolvido por</th>
<?
				}
?>
                        <th>Consumidor</th>
<?
				if ( $login_fabrica == 5 ) {
?>
                        <th>Telefone</th>
<?
				}
?>
                        <th>Cidade</th>
                        <th>UF</th>
                        <th>Ref. Produto</th>
                        <th>Produto</th>
<?
				if ( $login_fabrica == 5 ) {
?>
                        <th>Data Compra</th>
                        <th>Série</th>
                        <th>Linha</th>
                        <th>Região</th>
                        <th>Posto</th>
<?
				}
			}

			if (in_array($login_fabrica, [169,170])) {
?>
				<th>Providência Nv. 3</th>
				<th>Motivo Contato</th>
<?php
			}
?>
                    </tr >
                </thead>
                <tbody>
<?php
			for($y=0;pg_numrows($res)>$y;$y++){
				$callcenter       = pg_result($res,$y,'hd_chamado');
				$titulo           = pg_result($res,$y,'titulo');
				$status           = pg_result($res,$y,'status');
				$abertura         = pg_result($res,$y,'data');
				$login            = pg_result($res,$y,'login');
				$categoria        = pg_result($res,$y,'categoria');
				$defeito_reclamado= pg_result($res,$y,'defeito_reclamado');
				$produto          = pg_result($res,$y,'produto');
				$ultima_interacao = pg_result($res,$y,'data_interacao');
				$login_abertura   = pg_result($res,$y,'login_abertura');
				$resolvido        = '&nbsp;'; // buscar somente se for Mondial (5)
				$resolvido_por    = '&nbsp;'; // buscar somente se for Mondial (5)
				$consumidor_nome  = pg_result($res,$y,'nome');
				$consumidor_tel   = pg_result($res,$y,'fone');
				$consumidor_cidade= pg_result($res,$y,'cidade_nome');
				$consumidor_estado= pg_result($res,$y,'estado');
				$produto_ref      = pg_result($res,$y,'produto_referencia');
				$produto_descr    = pg_result($res,$y,'produto_nome');
				$data_compra      = pg_result($res,$y,'data_nf');
				$produto_serie    = pg_result($res,$y,'serie');
				$produto_linha    = pg_result($res,$y,'linha_nome');
				$posto            = pg_result($res,$y,'posto');
				$posto_nome       = pg_result($res,$y,'posto_nome');
				$origem 		  = pg_result($res,$y,'origem');
				$classificacao 	  = pg_result($res,$y,'classificacao');
				$providencia 	  = pg_result($res,$y,'providencia');


				if ($login_fabrica == 50) {
					$hd_motivo_ligacao_id = pg_result($res,$y,'hd_motivo_ligacao');

					$sql_motivo_ligacao = "SELECT descricao
							FROM tbl_hd_motivo_ligacao
							WHERE fabrica = 50
							AND hd_motivo_ligacao = {$hd_motivo_ligacao_id}";
					$res_motivo_atendimento = pg_exec($con,$sql_motivo_ligacao);

					while ($linha = pg_fetch_array($res_motivo_atendimento)) {
						$tipo_do_atendimento = $linha["descricao"];
					}

				}
				$titulo = empty($titulo) ? 'Atendimento Interativo' : $titulo; 
				if (in_array($login_fabrica, [169,170])) {
					$hd_providencia_descricao = pg_fetch_result($res, $y, 'hd_providencia_descricao');
					$motivo_contato_descricao = pg_fetch_result($res, $y, 'motivo_contato_descricao');
				}

				if($login_fabrica == 115){ //hd_chamado=2710901
					$hd_chamado_anterior = pg_fetch_result($res, $y, 'hd_chamado_anterior');
				}

				if($login_fabrica == 11 && strlen($ultima_interacao) > 0){

					// Buscando login de abertura
					if ( ! empty($login_abertura) ) {
						$_sql = "SELECT login FROM tbl_admin where admin = %s";
						$_sql = sprintf($_sql,$login_abertura);
						$_res = pg_exec($con,$_sql);
						if ( is_resource($_res) ) {
							$_rows          = pg_numrows($_res);
							$login_abertura = ( $_rows > 0 ) ? pg_result($_res,0,0) : null ;
						} else {
							break;
						}
						unset($_sql,$_res,$_rows);
					}
					$login_abertura = ( empty($login_abertura) ) ? '&nbsp;' : $login_abertura ;
				}
				// Se necessario, busca quando o chamado foi resolvido
					if ( $login_fabrica == 5 && strtolower($status) == 'resolvido' ) {
						$_sql = "SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') AS data,
										tbl_admin.login as resolvido_por
								 FROM tbl_hd_chamado_item
								 INNER JOIN tbl_admin USING (admin)
								 INNER JOIN tbl_hd_chamado USING (hd_chamado)
								 WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
								 AND tbl_hd_chamado.hd_chamado = {$callcenter}
								 AND tbl_hd_chamado_item.status_item = 'Resolvido'
								 ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC
								 LIMIT 1";
						$_res = pg_exec($con,$_sql);
						if ( ! is_resource($_res) ) {
							break;
						}
						$_rows= pg_numrows($_res);
						if ( $_rows > 0 ) {
							$resolvido     = pg_result($_res,0,'data');
							$resolvido_por = pg_result($_res,0,'resolvido_por');
						}
					}

					if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
?>
					<tr bgcolor='<?=$cor?>'>
<?
					if($tipo_data =='interacao'){
?>
                        <td>
                            <img src='imagens/mais.gif' id='visualizar_$y' style='cursor: pointer' onclick="javascript:MostraEsconde('dados_<?=$y?>','<?=$callcenter?>','visualizar_<?=$y?>');">
						</td>
<?
					}
					if($login_fabrica == 6){
?>
						<td>
                            <a href='cadastra_callcenter.php?callcenter=<?=$callcenter?>' target='blank'><?=$callcenter?></a>
                        </td>
<?
					}else{
?>
						<td>
                            <a href='callcenter_interativo.php?callcenter=<?=$callcenter?>' target='_blank'><?=$callcenter?></a>
                        </td>

                    <?php if($login_fabrica == 115){ //hd_chamado=2710901 ?>
                    	<td>
                            <a href='callcenter_interativo.php?callcenter=<?=$hd_chamado_anterior?>' target='_blank'><?=$hd_chamado_anterior?></a>
                        </td>
                    <?php } ?>
<?
					}
					if (!in_array($login_fabrica,array(5,74,169,170))) {
?>
						<td><?=$titulo?></td>
<?
					}

					if ($login_fabrica == 50) {
?>
						<td class="tar"><?=$tipo_do_atendimento?></td>
<?
					}
?>
                        <td class="tar"><?=$abertura?></td>
                        <td class="tar"><?=$ultima_interacao?></td>
<?
					if ( $login_fabrica == 5 ) {
?>
						<td><?=$login_abertura?></td>
<?
					}
					if ( $login_fabrica != 74 ) {
?>
                        <td><?=$login?></td>
<?
					}
					if(in_array($login_fabrica, array(169,170))){
?>
						<td><?=$classificacao?></td>
						<td><?=$origem?></td>
						<td><?=$providencia?></td>
						<td><?=$hd_providencia_descricao?></td>
						<td><?=$motivo_contato_descricao?></td>
<?php
					}


					if ( in_array($login_fabrica,array(5,74))) {
						$consumidor_estado = strtoupper($consumidor_estado);
						switch (true) {
							case ( in_array($consumidor_estado,array('AC','AP','AM','PA','RR','RO','TO','GO','MT','MS','DF')) ):
								$regiao = 'Norte + C.O.';
								break;
							case ( in_array($consumidor_estado,array('AL','BA','CE','MA','PB','PE','PI','RN','SE','ES')) ):
								$regiao = 'Nordeste + E.S.';
								break;
							case ( in_array($consumidor_estado,array('PR','SC','RS')) ):
								$regiao = 'Sul';
								break;
							default:
								$regiao = $consumidor_estado;
								break;
						}
						if ( $login_fabrica == 5 ) {
?>
                        <td ><?=$resolvido?></td>
                        <td ><?=$resolvido_por?></td>
<?
						}
?>
                        <td ><?php echo _acronym_helper($consumidor_nome); ?></td>
<?
						if ( $login_fabrica == 5 ) {
?>
                        <td ><?php echo _acronym_helper($consumidor_tel); ?></td>
<?
						}
?>
                        <td ><?php echo _acronym_helper($consumidor_cidade); ?></td>
                        <td class="tac"><?=$consumidor_estado?></td>
                        <td class="tar"><?=$produto_ref?></td>
                        <td ><?php echo _acronym_helper($produto_descr);?></td>
<?
						if ( $login_fabrica == 5 ) {
?>
                        <td ><?=$data_compra?></td>
                        <td ><?=$produto_serie?></td>
                        <td ><?=$produto_linha?></td>
                        <td ><?=$regiao?></td>
                        <td ><?php echo _acronym_helper($posto.' - '.$posto_nome); ?></td>
<?
                        }
					}
?>
                    </tr >
<?
					if($tipo_data =='interacao'){
?>
                    <tr>
                        <td colspan='100%'>
                            <div class='exibe' id='dados_<?=$y?>'>
                                <B>Carregando...</B><br>
                                <img src='imagens/carregar_os.gif' border='0'>
                            </div>
                        </td>
                    </tr>
<?
					}
			}
?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="100%" class="tac">Quantidade de registros: <strong><? echo pg_numrows($res); ?></strong></td>
                    <tr>
                </tfoot>
			</table>
        </div>
<?
		}else{
?>
			<div class="alert">
                <h4>Nenhum resultado encontrado</h4>
            </div>
<?
        }
	}

?>
