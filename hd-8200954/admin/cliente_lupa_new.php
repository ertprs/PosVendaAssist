<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
if ($areaAdmin === true ) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$parametro = $_REQUEST["parametro"];

$valor     = utf8_decode(trim($_REQUEST["valor"]));

$contratual = utf8_decode(trim($_REQUEST["contratual"]));

$callcenter = utf8_decode(trim($_REQUEST["callcenter"]));

$nacionalidade = $_REQUEST['nacionalidade'];

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				$.dataTableLupa();
			});
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
			<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
				<div class="span1"></div>
				<div class="span4">
					<input type="hidden" name="posicao" class="span12" value='<?=$posicao?>' />
					<select name="parametro" >
						<option value="cpf" <?=($parametro == "cpf") ? "SELECTED" : ""?> >CPF/CNPJ</option>
						<option value="nome" <?=($parametro == "nome") ? "SELECTED" : ""?> >NOME</option>
					</select>
				</div>
				<div class="span4">
					<input type="text" name="valor" class="span12" value="<?=$valor?>" />
				</div>
				<div class="span2">
					<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();">Pesquisar</button>
				</div>
				<div class="span1"></div>
			</form>
			</div>
			<?

			if (strlen($valor) >= 3) {
				switch ($parametro) {
					case 'cpf':
						$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
						$whereAdc = " tbl_cliente.cpf = '".preg_replace("/[\.\-\/]/", "", $valor)."' ";
						break;

					case 'nome':
						$whereAdc = " UPPER(tbl_cliente.nome) ILIKE UPPER('%{$valor}%') ";
						break;

					case 'codigo':
						$whereAdc = " UPPER(tbl_cliente.codigo_cliente) ILIKE UPPER('%{$valor}%') ";
						break;
				}

				if (isset($whereAdc)) {

					$cond_grupo_cliente_descrica = "";
					$inner_grupo_cliente = "";

					if(in_array($login_fabrica, array(171))){
						$cond_grupo_cliente_descrica = " ,tbl_grupo_cliente.descricao AS grupo_cliente_descricao";
						$inner_grupo_cliente = " INNER JOIN tbl_grupo_cliente ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente AND tbl_grupo_cliente.fabrica = $login_fabrica";
					} else if (in_array($login_fabrica, [85]) && $contratual == 't') {
						$left_grupo_cliente = " LEFT JOIN tbl_grupo_cliente ON tbl_grupo_cliente.grupo_cliente = tbl_cliente.grupo_cliente AND tbl_grupo_cliente.fabrica = $login_fabrica";
						$cond_grupo_cliente = " 
							AND tbl_grupo_cliente.grupo_cliente = (
								SELECT tbl_grupo_cliente.grupo_cliente 
								FROM tbl_grupo_cliente
								WHERE tbl_grupo_cliente.fabrica = {$login_fabrica}
								AND UPPER(tbl_grupo_cliente.descricao) = 'GARANTIA CONTRATUAL'
							)";
					}

					if ($login_fabrica == 148 && $nacionalidade == 'exterior') {
						
						$cond_grupo_cliente .= " AND tbl_cidade.estado = 'EX' ";
					}

					if ($login_fabrica == 190) {
						
						$cond_grupo_cliente .= " AND tbl_cliente.fabrica = {$login_fabrica}";
					}

					$sql = "SELECT tbl_cliente.cliente,
								tbl_cliente.nome,
								tbl_cliente.cpf,
								tbl_cliente.cep,
								tbl_cliente.bairro,
								tbl_cliente.endereco,
								tbl_cliente.numero,
								tbl_cliente.complemento,
								tbl_cliente.fone,
								tbl_cliente.email,
								tbl_cliente.codigo_cliente,
								tbl_cliente.cidade,
								tbl_cidade.nome as nome_cidade,
								tbl_cidade.estado
								{$cond_grupo_cliente_descrica}
							FROM tbl_cliente
							JOIN tbl_cidade ON tbl_cliente.cidade = tbl_cidade.cidade
							{$inner_grupo_cliente}
							{$left_grupo_cliente}
							WHERE {$whereAdc}
							{$cond_grupo_cliente}
							ORDER BY tbl_cliente.nome ";

					$res = pg_query($con, $sql);

					$rows = pg_num_rows($res);
					if ($rows > 0) {

					?>
					<div id="border_table">
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<?php
									if (in_array($login_fabrica, [85]) && $contratual == 't') { ?>
											<th>Código Cliente</th>
											<th>Nome Cliente</th>
										<?php
										} else { ?>
											<th>Cliente nome</th>
											<th>CPF/CNPJ</th>
										<?php
										} ?>
									
									<? if(in_array($login_fabrica, array(171))){ ?>
										<th>Grupo</th>
									<? } ?>
								</tr>
							</thead>
							<tbody>
								<?php
								for ($i = 0 ; $i <$rows; $i++) {
									$cliente     = pg_fetch_result($res, $i, 'cliente');
									$nome        = pg_fetch_result($res, $i, 'nome');
									$cpf         = pg_fetch_result($res, $i, 'cpf');
									$cep         = pg_fetch_result($res, $i, 'cep');
									$bairro      = pg_fetch_result($res, $i, 'bairro');
									$endereco    = pg_fetch_result($res, $i, 'endereco');
									$numero      = pg_fetch_result($res, $i, 'numero');
									$complemento = pg_fetch_result($res, $i, 'complemento');
									$telefone    = pg_fetch_result($res, $i, 'fone');
									$email       = pg_fetch_result($res, $i, 'email');
									$nome_cidade = pg_fetch_result($res, $i, 'nome_cidade');
									$cidade      = pg_fetch_result($res, $i, 'cidade');
									$estado      = pg_fetch_result($res, $i, 'estado');
									$cod_cliente = pg_fetch_result($res, $i, 'codigo_cliente');

									$r = array(
										"cliente"     => $cliente                    ,
										"nome"        => utf8_encode($nome)  ,
										"cpf"         => utf8_encode($cpf)  ,
										"cep"         => utf8_encode($cep)  ,
										"bairro"      => utf8_encode($bairro)  ,
										"endereco"    => utf8_encode($endereco)  ,
										"numero"      => utf8_encode($numero)  ,
										"complemento" => utf8_encode($complemento)  ,
										"telefone"    => utf8_encode($telefone)  ,
										"email"       => utf8_encode($email)  ,
										"nome_cidade" => utf8_encode($nome_cidade) ,
										"cidade"      => utf8_encode($cidade),
										"estado"      => utf8_encode($estado),
										"codigo_cliente" => utf8_encode($cod_cliente)
									);

									if(in_array($login_fabrica, array(171))){
										$grupo_cliente_descricao = pg_fetch_result($res, $i, 'grupo_cliente_descricao');
										$r["grupo_cliente"] = utf8_encode($grupo_cliente_descricao);
									}

									if (in_array($login_fabrica, [85]) && $callcenter == "t" && $contratual == "t") {
										$funcao_retorno = "retorna_cliente_contratual";
									} else {
										$funcao_retorno = "retorna_cliente";
									}


									echo "<tr onclick='window.parent.".$funcao_retorno."(".json_encode($r)."); window.parent.Shadowbox.close();' >";
										if (in_array($login_fabrica, [85]) && $contratual == 't') {
											echo "<td class='cursor_lupa'>".utf8_encode($cod_cliente)."</td>";
											echo "<td class='cursor_lupa'>".strtoupper($nome)."</td>";
										} else {
											echo "<td class='cursor_lupa'>".strtoupper($nome)."</td>";
											echo "<td class='cursor_lupa'>".utf8_encode($cpf)."</td>";
										}
										if(in_array($login_fabrica, array(171))){
											echo "<td class='cursor_lupa'>".utf8_encode($grupo_cliente_descricao)."</td>";
										}
									echo "</tr>";
								}
								?>
							</tbody>
						</table>
						</div>
					<?php
					} else {
						echo '
					<div class="alert alert_shadobox">
					    <h4>Nenhum resultado encontrado</h4>
					</div>';
					}
				}
			} else {
				echo '
					<div class="alert alert_shadobox">
					    <h4>Informe toda ou parte da informação para pesquisar!</h4>
					</div>';
			}
			?>
	</div>
	</body>
</html>
