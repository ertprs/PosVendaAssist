<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



?>

<html>
<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />


<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src='../plugins/jquery.maskedinput_new.js'></script>





<head>
<title>Consulta de embarque</title>
</head>

<body>

<? include 'menu.php' ?>

<center><h1>Consulta de embarque</h1></center>

<p>

<?
$cnpj        		= trim($_POST['cnpj']);
$embarque    		= trim($_REQUEST['embarque']);
$nota_fiscal 		= trim($_POST['nota_fiscal']);
$os          		= trim($_POST['os']);
$pedido      		= trim($_POST['pedido']);
$nome_destinatario  = trim($_POST['nome_destinatario']);
$cep_destinatario   = trim($_POST['cep_destinatario']);
$data_inicial       = trim($_POST['data_inicial']);
$data_final         = trim($_POST['data_final']);
$btn_acao           = $_REQUEST['btn_acao'];

?>

<script>
	/*HD - 6156446*/
	function somenteNumeros(num) {
        var er = /[^0-9.]/;
        er.lastIndex = 0;
        var campo = num;
        if (er.test(campo.value)) {
          campo.value = "";
        }
    }

    $(document).ready(function(){
		$("input[name=cep_destinatario]").mask("99.999-999");
		$('#data_inicial, #data_final').mask("99/99/9999");
	});
</script>


<form class='form-search form-inline tc_formulario' name="frm_embarque" method="post" action="<? echo $PHP_SELF;?>">

<div class="row-fluid">
	<div class="span3">
		<div class="control-group">
			<label class="control-label" for=''>CNPJ do Posto</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span10' autocomplete='off' name='cnpj' value='<? echo $cnpj ?>'>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>Embarque</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span8' autocomplete='off' name='embarque' value='<? echo $embarque ?>'>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>Nota Fiscal</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span8' autocomplete='off' name='nota_fiscal' value='<? echo $nota_fiscal ?>'>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>OS</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span8' autocomplete='off' name='os' value='<? echo $os ?>'>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>Pedido</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span8' autocomplete='off' onkeyup="somenteNumeros(this);"  name='pedido' value='<? echo $pedido ?>'>
			</div>
		</div>
	</div>
</div>
<br />
<div class="row-fluid">
	<div class="span3">
		<div class="control-group">
			<label class="control-label" for=''>Nome do Destinatário</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span10' autocomplete='off' name='nome_destinatario' value='<? echo $nome_destinatario ?>'>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>CEP Destinatário</label>
			<div class="controls controls-row">
				 <input type='text' class='span8' autocomplete='off' name='cep_destinatario' value='<? echo $cep_destinatario ?>'>
			</div>
		</div>
	</div>
</div>
<br />
<div class="row-fluid">
	<div class="span4"></div>
	<div class="span4 tac">
		<label><b>Busca Por Data De Nota Fiscal</b></label>
	</div>
</div>
<div class="row-fluid">
	<div class="span4"></div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>Data Inicial</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span8' autocomplete='off' id='data_inicial' name='data_inicial' value='<? echo $data_inicial ?>'>
			</div>
		</div>
	</div>
	<div class="span2">
		<div class="control-group">
			<label class="control-label" for=''>Data Final</label>
			<div class="controls controls-row tac">
				 <input type='text' class='span8' id='data_final' autocomplete='off' name='data_final' value='<? echo $data_final ?>'>
			</div>
		</div>
	</div>
</div>
<br /><br />
<div class="row-fluid">
	<div class="span4"></div>
		<div class="span4 tac">
			<input type='submit' name='btn_acao' class='btn' value='Pesquisar'>
		</div>
	</div>
</div>

</form>


<?

if(!empty($btn_acao)) {
	if (strlen ($cnpj) > 10 ||  strlen($embarque) > 4 || strlen($nota_fiscal) > 4 || strlen($os) > 5 || strlen($pedido) > 4 || strlen($nome_destinatario) || strlen($cep_destinatario) || strlen($data_inicial) || strlen($data_final)) {
		/*HD - 6156446*/
		if (strlen($pedido) > 0) {
			$sql = "SELECT pedido FROM tbl_pedido JOIN tbl_fabrica USING(fabrica) WHERE pedido = $pedido AND parametros_adicionais::jsonb->>'telecontrol_distrib' = 't'";
			$res = pg_query($con, $sql);
			$val = pg_fetch_result($res, 0, 'pedido');

			if (strlen($val) == 0) {
				echo '<div class="alert alert-error"><h4>O pedido informado está incorreto</h4></div>';
				include "rodape.php";
				exit;
			} else {
				$condicao = " tbl_embarque.embarque IN (
					SELECT embarque
					FROM tbl_pedido_item
					JOIN tbl_pedido USING(pedido)
					JOIN tbl_embarque_item USING(pedido_item)
					JOIN tbl_fabrica USING(fabrica)
					WHERE tbl_pedido.pedido= $pedido
					AND tbl_fabrica.parametros_adicionais ~* 'telecontrol_distrib'
					AND ativo_fabrica
				) ";
			}
		}

		if (strlen ($cnpj) > 10) {
			$condicao = " tbl_posto.cnpj ~ '$cnpj' ";
		}


		if (strlen ($embarque) > 4) {
			$condicao = " tbl_embarque.embarque = $embarque ";
		}

		if (strlen ($nota_fiscal) > 4) {
			$condicao = " tbl_faturamento.nota_fiscal = LPAD ('$nota_fiscal',6,'0') ";
		}

		if(strlen(trim($os)) > 5) {
			$condicao = " tbl_embarque.embarque IN (
							SELECT embarque
							FROM tbl_os_item
							JOIN tbl_os_produto USING(os_produto)
							JOIN tbl_os USING(os)
							JOIN tbl_embarque_item USING(os_item)
							JOIN tbl_fabrica USING(fabrica)
							WHERE sua_os='$os'
							AND tbl_fabrica.parametros_adicionais ~* 'telecontrol_distrib'
							AND ativo_fabrica
						) ";
		}

		if (strlen($nome_destinatario) > 0) {
			$nome_destinatario = strtoupper($nome_destinatario);
			$condicao = " tbl_faturamento_destinatario.nome = '$nome_destinatario'";
		}

		if (strlen($cep_destinatario) > 0) {
			$especiais = [".","-"];
			$cep_destinatario = str_replace($especiais, "", $cep_destinatario);

			if (strlen($cep_destinatario) != 8) {
				echo '<div class="alert alert-error"><h4>Cep Invalida</h4></div>';
				include "rodape.php";
				exit;
			}

			$condicao = " tbl_faturamento_destinatario.cep = '$cep_destinatario'";	
		}

		if ((strlen($data_inicial) > 0) || (strlen($data_final) > 0)) {
			if ((strlen($data_inicial) > 0) && (strlen($data_final) > 0)) {

				$data_i = explode("/","$data_inicial"); 
				$d = $data_i[0];
				$m = $data_i[1];
				$y = $data_i[2];

				$resultado = checkdate($m,$d,$y);
				if ($resultado != 1){
					echo '<div class="alert alert-error"><h4>Data Inicial Invalida</h4></div>';
					include "rodape.php";
					exit;
				}

				$data_f = explode("/","$data_final"); 
				$d = $data_f[0];
				$m = $data_f[1];
				$y = $data_f[2];

				$resultado = checkdate($m,$d,$y);
				if ($resultado != 1){
					echo '<div class="alert alert-error"><h4>Data Final Invalida</h4></div>';
					include "rodape.php";
					exit;
				}

				if (empty($condicao)) {
					$condicao = " tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
				} else {
					$condicao .= " AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
				}

			} else {
				echo '<div class="alert alert-error"><h4>Informe as Duas Datas</h4></div>';
				include "rodape.php";
				exit;
			}
		}

		$sql = "SELECT tbl_posto_fabrica.codigo_posto, 
							tbl_posto.cnpj ,
							tbl_posto.fone,
							tbl_posto.posto, 
							tbl_posto.nome, 
							tbl_posto.cidade,
							tbl_posto.estado, 
							tbl_peca.peca, 
							tbl_peca.referencia, 
							tbl_peca.descricao, 
							sum(tbl_embarque_item.qtde) as qtde, 
							tbl_embarque.embarque, 
							to_char (tbl_embarque.data,'DD/MM/YYYY') AS embarque_data, tbl_faturamento.nota_fiscal, 
							to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS nf_emissao , 
							CASE WHEN tbl_etiqueta_servico.etiqueta IS NOT NULL 
								 THEN tbl_etiqueta_servico.etiqueta 
								 WHEN tbl_frete_transportadora.codigo_rastreio IS NOT NULL
								 THEN tbl_frete_transportadora.codigo_rastreio
								 ELSE tbl_faturamento.conhecimento 
							END as etiqueta,
							COALESCE(tbl_etiqueta_servico.peso, tbl_frete_transportadora.peso) as peso
					FROM tbl_posto
					JOIN tbl_embarque      USING (posto)
					JOIN tbl_embarque_item USING (embarque)
					JOIN tbl_peca        USING (peca)
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_peca.fabrica
					LEFT JOIN tbl_faturamento   ON tbl_embarque_item.embarque = tbl_faturamento.embarque AND tbl_faturamento.fabrica in ($telecontrol_distrib)
					LEFT JOIN tbl_faturamento_destinatario ON tbl_faturamento.faturamento = tbl_faturamento_destinatario.faturamento
					LEFT JOIN tbl_etiqueta_servico ON tbl_embarque.embarque = tbl_etiqueta_servico.embarque
					LEFT JOIN tbl_frete_transportadora ON tbl_frete_transportadora.embarque = tbl_embarque.embarque
					WHERE $condicao
					AND   tbl_embarque.distribuidor = $login_posto
					group by tbl_posto_fabrica.codigo_posto, 
									tbl_posto.cnpj ,
									tbl_posto.fone,
									tbl_posto.posto, 
									tbl_posto.nome, 
									tbl_posto.cidade,
									tbl_posto.estado, 
									tbl_peca.peca, 
									tbl_peca.referencia, 
									tbl_peca.descricao, 
									tbl_embarque.embarque, 
									tbl_embarque.data, tbl_faturamento.nota_fiscal, 
									tbl_faturamento.emissao , 
									tbl_etiqueta_servico.etiqueta,
									tbl_faturamento.conhecimento,
									tbl_etiqueta_servico.peso,
									tbl_frete_transportadora.peso,
									tbl_frete_transportadora.codigo_rastreio
									ORDER BY tbl_posto.nome, tbl_peca.referencia LIMIT 500";
		$res = pg_exec ($con,$sql);

		if (pg_num_rows($res) == 0) {
			
			if (strlen(trim($nome_destinatario)) > 0) {
				$condicao = " tbl_posto.nome = '$nome_destinatario'";
			}

			if (strlen(trim($cep_destinatario)) > 0) {
				$condicao = " tbl_posto.cep = '$cep_destinatario'";	
			}

			if (strlen(trim($data_final)) > 0 && strlen(trim($data_inicial)) > 0) {
				if (empty($condicao)) {
					$condicao = " tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
				} else {
					$condicao .= " AND tbl_faturamento.emissao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'";
				}
			}

			$sql = "SELECT tbl_posto_fabrica.codigo_posto, 
							tbl_posto.cnpj ,
							tbl_posto.fone,
							tbl_posto.posto, 
							tbl_posto.nome, 
							tbl_posto.cidade,
							tbl_posto.estado, 
							tbl_peca.peca, 
							tbl_peca.referencia, 
							tbl_peca.descricao, 
							sum(tbl_embarque_item.qtde) as qtde, 
							tbl_embarque.embarque, 
							to_char (tbl_embarque.data,'DD/MM/YYYY') AS embarque_data, tbl_faturamento.nota_fiscal, 
							to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS nf_emissao , 
							CASE WHEN tbl_etiqueta_servico.etiqueta IS NOT NULL 
								 THEN tbl_etiqueta_servico.etiqueta 
								 WHEN tbl_frete_transportadora.codigo_rastreio IS NOT NULL
								 THEN tbl_frete_transportadora.codigo_rastreio
								 ELSE tbl_faturamento.conhecimento 
							END as etiqueta,
							COALESCE(tbl_etiqueta_servico.peso, tbl_frete_transportadora.peso) as peso
					FROM tbl_posto
					JOIN tbl_embarque      USING (posto)
					JOIN tbl_embarque_item USING (embarque)
					JOIN tbl_peca        USING (peca)
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_peca.fabrica
					LEFT JOIN tbl_faturamento   ON tbl_embarque_item.embarque = tbl_faturamento.embarque AND tbl_faturamento.fabrica in ($telecontrol_distrib)
					LEFT JOIN tbl_faturamento_destinatario ON tbl_faturamento.faturamento = tbl_faturamento_destinatario.faturamento
					LEFT JOIN tbl_etiqueta_servico ON tbl_embarque.embarque = tbl_etiqueta_servico.embarque
					LEFT JOIN tbl_frete_transportadora ON tbl_frete_transportadora.embarque = tbl_embarque.embarque
					WHERE $condicao
					AND   tbl_embarque.distribuidor = $login_posto
					group by tbl_posto_fabrica.codigo_posto, 
									tbl_posto.cnpj ,
									tbl_posto.fone,
									tbl_posto.posto, 
									tbl_posto.nome, 
									tbl_posto.cidade,
									tbl_posto.estado, 
									tbl_peca.peca, 
									tbl_peca.referencia, 
									tbl_peca.descricao, 
									tbl_embarque.embarque, 
									tbl_embarque.data, tbl_faturamento.nota_fiscal, 
									tbl_faturamento.emissao , 
									tbl_etiqueta_servico.etiqueta,
									tbl_faturamento.conhecimento,
									tbl_etiqueta_servico.peso
									ORDER BY tbl_posto.nome, tbl_peca.referencia LIMIT 500";
			$res = pg_exec ($con,$sql);
		}

		echo "</div>";
		echo "<table style='min-width: 100% !IMPORTANT' class='table table-striped table-bordered table-hover table-fixed' > ";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$posto = pg_fetch_result ($res,$i,posto);
			if ($posto_ant <> pg_fetch_result ($res,$i,posto) ) {
				echo "<thead>";
				echo "<tr class='titulo_coluna'>";

				echo "<th colspan='9' align='center'>";
				echo pg_fetch_result ($res,$i,cnpj)." - ";
				echo pg_fetch_result ($res,$i,nome);
				echo " Fone: ";
				echo pg_fetch_result ($res,$i,fone);
				echo "</th>";

				echo "</tr>";


				echo "<tr class='titulo_coluna'>";

				echo "<th nowrap>Embarque</th>";
				echo "<th nowrap>Data embarque</th>";
				echo "<th nowrap>Pedido / OS</th>";
				echo "<th nowrap>Peça</th>";
				echo "<th nowrap>Descrição</th>";
				echo "<th nowrap>Pedida</th>";
				echo "<th nowrap>Nota Fiscal</th>";
				echo "<th nowrap>Rastreio</th>";
				echo "<th nowrap>Peso</th>";
				echo "</tr></thead>";

				$posto_ant = pg_fetch_result ($res,$i,posto);
				$peca_ant = "";
			}

			if ($peca_ant <> pg_fetch_result ($res,$i,peca) ) {

				$peca           = pg_fetch_result ($res,$i,peca);
				$embarque       = pg_fetch_result ($res,$i,'embarque');

				echo "</td>";
				echo "</tr>";

				$sql_po = "SELECT distinct tbl_pedido_item.pedido,sua_os, os,tbl_pedido.fabrica, tbl_embarque_item.qtde AS qtde_embarque_item
						FROM tbl_embarque_item
						JOIN tbl_pedido_item USING(pedido_item)
						JOIN tbl_pedido USING(pedido)
						LEFT JOIN tbl_os_item USING(os_item)
						LEFT JOIN tbl_os_produto USING(os_produto)
						LEFT JOIN tbl_os USING(os)
						WHERE tbl_embarque_item.embarque = $embarque
						AND tbl_embarque_item.peca = $peca";
				$res_po = pg_query($con,$sql_po);
				for($j=0;$j<pg_num_rows($res_po);$j++) {
				echo "<tr style='font-size:12px' bgcolor='$cor'> ";

				echo "<td>";
				echo pg_fetch_result ($res,$i,'embarque');
				echo "</td>";

				echo "<td>";
				echo pg_fetch_result ($res,$i,'embarque_data');
				echo "</td>";

				echo "<td nowrap>";
					$pedido = pg_fetch_result($res_po,$j,'pedido')	;
					$sua_os = pg_fetch_result($res_po,$j,'sua_os')	;
					$fabrica = pg_fetch_result($res_po,$j,'fabrica')	;
					$os = pg_fetch_result($res_po,$j,'os')	;

					echo "<a href='../pedido_finalizado.php?pedido=$pedido&lu_fabrica=$fabrica' target='_blank'>$pedido</a>";
					if(!empty($os)) echo " - <a href='../os_press.php?os=$os&login_posto=$posto&distribuidor=4311' target='_blank'>$sua_os</a>";
					echo "<br>";
				echo "</td>";

				echo "<td nowrap>";
				echo pg_fetch_result ($res,$i,'referencia');
				echo "</td>";

				echo "<td nowrap>";
				echo pg_fetch_result ($res,$i,'descricao');
				echo "</td>";

				echo "<td class='tac'>";
				echo pg_fetch_result ($res_po,$j,'qtde_embarque_item');
				echo "</td>";

				$peca_ant = pg_fetch_result ($res,$i,'peca');

				echo "<td nowrap>";
				echo pg_fetch_result ($res,$i,'nota_fiscal');
				echo "-";
				echo pg_fetch_result ($res,$i,'nf_emissao');
				echo "</td>";

				echo "<td>";
				echo pg_fetch_result ($res,$i,'etiqueta');
				echo "</td>";

				echo "<td>";
				echo pg_fetch_result ($res,$i,'peso');
				echo "</td>";
				echo "</tr>";
				}
			}
		}

		if (pg_num_rows ($res)==0){
			echo "<tr>";
			echo "<td colspan='9'>";
			echo "<p>Não foi encontrado nenhuma pendência</p>";
			echo "</td>";
			echo "</tr>";
		}

		echo "</table>";

		if (pg_num_rows ($res) > 0) {
?>
			<div class='row-fluid'>
				<div class='span4'></div>
				<div class='span4 tac'>
					<p><b>Resultados limitados a 500.</b></p>
				</div>
			</div>
<?php
		}
	}else{
		 echo '<div class="alert alert-error">
			<h4>Nenhum Registro Encontrado</h4>
		</div>';
	}
}
include "rodape.php";


