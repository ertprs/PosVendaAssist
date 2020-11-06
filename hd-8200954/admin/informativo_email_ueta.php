<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';


$msg_erro = array();
$prefixo_link = "http://posvenda.telecontrol.com.br/assist/admin/";

$informativo = strlen($informativo) == 0 && isset($_GET["informativo"]) ? $_GET["informativo"] : $informativo;
$informativo = strlen($informativo) == 0 && isset($_POST["informativo"]) ? $_POST["informativo"] : $informativo;
?>
<style type="text/css">
    .tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #CCCCCC;
}
</style>

<?
if (strlen($informativo) > 0) {
	try {
		$informativo = intval($informativo);
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_informativo
		
		WHERE
		informativo={$informativo}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		if (pg_num_rows($res) == 0) {
			throw new Exception("Informativo não encontrado");
		}
		
		$dados_informativo = pg_fetch_array($res);
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_informativo_modulo
		
		WHERE
		tbl_informativo_modulo.informativo={$informativo}
		";
		$res = pg_query($con, $sql);
		$n_modulos = pg_num_rows($res);
		
		for($i = 0; $i < $n_modulos; $i++) {
			$dados_modulo = pg_fetch_array($res);
			$dados_modulo["altura"] = intval($dados_modulo["altura"]);
			
			$sql = "
			SELECT
			*
			
			FROM
			tbl_informativo_modulo_texto
			
			WHERE
			tbl_informativo_modulo_texto.informativo_modulo={$dados_modulo["informativo_modulo"]}
			AND tbl_informativo_modulo_texto.texto IS NOT NULL
			AND tbl_informativo_modulo_texto.texto <> ''
			
			ORDER BY
			ordem,
			informativo_modulo_texto
			";
			$res_textos = pg_query($con, $sql);
			$n_textos = pg_num_rows($res_textos);

			$estilo_modulo = array();
			$estilo_modulo[] = "width: 600px";
			
			if (strlen($dados_modulo["imagem_fundo"]) > 0 && file_exists($dados_modulo["imagem_fundo"])) {
				//$estilo_modulo[] = "background-image: url({$prefixo_link}{$dados_modulo["imagem_fundo"]})";
				$imagem_fundo = "background='{$prefixo_link}{$dados_modulo["imagem_fundo"]}'";
			}
			else  {
				$imagem_fundo = "";
			}
			
			if (strlen($dados_modulo["imagem_direita"]) > 0 && file_exists($dados_modulo["imagem_direita"])) {
				$imagem_direita = "<img src='{$prefixo_link}{$dados_modulo["imagem_direita"]}' style='float: right; margin-left: 5px;' />";
			}
			else {
				$imagem_direita = "";
			}
			
			if (strlen($dados_modulo["imagem_esquerda"]) > 0 && file_exists($dados_modulo["imagem_esquerda"])) {
				$imagem_esquerda = "<img src='{$prefixo_link}{$dados_modulo["imagem_esquerda"]}' style='float: left; margin-right: 5px;' />";
			}
			else {
				$imagem_esquerda = "";
			}
			
			if (strlen($dados_modulo["link"]) > 0) {
				$abre_link = "<a href='{$dados_modulo["link"]}' target='_blank'>";
				$fecha_link = "</a>";
			}
			else {
				$abre_link = "";
				$fecha_link = "";
			}
			
			if ($dados_modulo["altura"] > 0) {
				$estilo_modulo[] = "height: {$dados_modulo["altura"]}px";
			}
			
			if (strlen($dados_modulo["borda"]) > 0) {
				$estilo_modulo[] = "border: 1px solid {$dados_modulo["borda"]}";
			}
			else {
				$estilo_modulo[] = "border: none";
			}
			
			$estilo_modulo = implode("; ", $estilo_modulo);
			
			echo "
			{$abre_link}
			<table style='{$estilo_modulo}' {$imagem_fundo}>
				<tr>
					<td>
						{$imagem_esquerda}{$imagem_direita}
			";
			
			for ($j = 0; $j < $n_textos; $j++) {
				$dados_texto = pg_fetch_array($res_textos);
				$dados_texto["texto"] = nl2br($dados_texto["texto"]);
				
				$estilo_texto = array();
				$estilo_texto[] = "display:inline";
				
				if (strlen($dados_texto["fonte"]) > 0) {
					$estilo_texto[] = "font-family: {$dados_texto["fonte"]}";
				}
				
				if (strlen($dados_texto["tamanho"]) > 0) {
					$estilo_texto[] = "font-size: {$dados_texto["tamanho"]}pt";
				}
				
				if (strlen($dados_texto["cor"]) > 0) {
					$estilo_texto[] = "color: {$dados_texto["cor"]}";
				}
				
				if (strlen($dados_texto["alinhamento"]) > 0) {
					$estilo_texto[] = "text-align: {$dados_texto["alinhamento"]}";
				}
				
				$estilo_texto = implode("; ", $estilo_texto);
				
				echo "<p style='{$estilo_texto}'>{$dados_texto["texto"]}</p><br>";
			}

			echo "
					</td>
				</tr>
			</table>
			{$fecha_link}
			";
		}

		$sql = "SELECT
		tbl_informativo.data_inicial,
		tbl_informativo.data_final

		from
		tbl_informativo

		where 
		informativo={$informativo}";
		$resultado = pg_query($con, $sql);
		//pego as datas inicial e final do informativo e deixo concatenado para efetuar a busca
		while ($row = pg_fetch_row($resultado)) 
		{
			$informativo_data_inicial = "'" . $row[0] . "'";
			$informativo_data_final	  = "'" . $row[1] . "'";
		}
		
		$sql = "SELECT
		tbl_comunicado.comunicado,
		tbl_comunicado.descricao,
		tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
		array(SELECT tbl_produto.referencia || ' - ' || tbl_produto.descricao FROM tbl_comunicado_produto JOIN tbl_produto ON tbl_comunicado_produto.produto=tbl_produto.produto AND tbl_comunicado_produto.comunicado=tbl_comunicado.comunicado) AS produtos
		FROM tbl_comunicado
		
		LEFT JOIN tbl_produto ON tbl_comunicado.produto=tbl_produto.produto
		 
		WHERE	
		tbl_comunicado.tipo='Informativo tecnico'
		AND tbl_comunicado.ativo
		AND tbl_comunicado.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final}
		";
		$resultado = pg_query($con, $sql);
		
		while ($row = pg_fetch_row($resultado)) 
		{
			$total = strlen($row[3]);
			$row[3] = substr($row[3], 2, $total);  //retira os dois primeiros caracteres
			$total = strlen($row[3]);
			$row[3] = substr($row[3], 0, $total-2);//retira os dois ultimos caracteres
			$row[3] = str_replace('","', '<br>', $row[3]);
			$row[3] = $row[3] . $produto;			//concatena com produto
			if(file_exists("../comunicados/{$comunicado}.pdf")) //verifica se arquivo existe
			{
				$anexo = "../comunicados/{$comunicado}.pdf";//joga o link do arquivo no anexo
			}
			echo "Comunicado: $row[0] Descrição: $row[1] Produto: $row[2] Produtos: $row[3] Anexo: $anexo";
		}

		//coloca o outro sql aqui
		/*$sql = "
		SELECT 
		tbl_credenciamento.data, 
		tbl_posto.nome, 
		CASE WHEN tbl_tipo_posto.descricao ILIKE '%locadora%' THEN 'LOCADORA' 
		ELSE 'POSTO AUTORIZADO' END AS tipo 

		FROM 
		tbl_credenciamento 
		JOIN tbl_posto_fabrica ON tbl_credenciamento.posto=tbl_posto_fabrica.posto AND tbl_credenciamento.fabrica=tbl_posto_fabrica.fabrica 
		JOIN tbl_posto ON tbl_posto_fabrica.posto=tbl_posto.posto 
		JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto=tbl_tipo_posto.tipo_posto 

		WHERE 
		tbl_credenciamento.fabrica={$login_fabrica} 
		AND tbl_credenciamento.status='CREDENCIADO' 
		AND (SELECT COUNT(*) FROM tbl_credenciamento AS tbl_credenciamento_interna WHERE fabrica=tbl_credenciamento.fabrica AND posto=tbl_credenciamento.posto) = 1 
		AND tbl_credenciamento.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final} 

		ORDER BY 
		tbl_posto.nome ASC; ";
		}
		
		$resultado = pg_query($con, $sql);
		
		while ($row = pg_fetch_row($resultado)) 
		{
			echo "Nome: $row[1] Tipo: $row[2]";
		}*/
		
		$sql ="SELECT
		COUNT(*),
		tbl_posto.estado 

		FROM
		tbl_hd_chamado
		JOIN tbl_posto ON tbl_hd_chamado.posto=tbl_posto.posto 

		WHERE 
		tbl_hd_chamado.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final} 

		GROUP BY 
		tbl_posto.estado";
		
		$resultado = pg_query($con, $sql);
		$somanorte 		 = 0;
		$somacentrooeste = 0;
		$somanordeste 	 = 0;
		$somasudeste 	 = 0;
		$somasul 		 = 0;
		$norte 		 	 = array('AC','AM','RO','RR','AP','PA','TO');
		$centrooeste 	 = array('MT','MS','GO','DF');
		$nordeste 	 	 = array('MA','PI','BA','CE','PE','RN','PB','AL','SE');
		$sudeste 	 	 = array('SP','RJ','MG','ES');
		$sul 		 	 = array('PR','SC','RS');
		$i = 0;
		
		while ($row = pg_fetch_row($resultado)) 
		{
			if (in_array($row[1], $norte)) //se o estado estive dentro da variavel ele vai somando
			{
				$somanorte = $somanorte + $row[0];
			}
			
			if (in_array($row[1], $centrooeste)) 
			{
				$somacentrooeste = $somacentrooeste + $row[0];
			}
			
			if (in_array($row[1], $nordeste)) 
			{
				$somanordeste = $somanordeste + $row[0];
			}
			
			if (in_array($row[1], $sudeste)) 
			{
				$somasudeste = $somasudeste + $row[0];
			}
			
			if (in_array($row[1], $sul)) 
			{
				$somasul = $somasul + $row[0];
			}
		}?>
		<table align="center" width="700" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
			<td>Região Norte:</td>
			<td>Região Centro Oeste:</td>
			<td>Região Nordeste:</td>
			<td>Região Sudeste:</td>
			<td>Região Sul:</td>
		</tr>
		<?
		$i++;
        $cor = ($i % 2) ? "#FFFFCC" : "#CCCCCC";
		//total por regiao norte, nordeste, sul, sudeste, centro oeste
        ?>  
        <tr>
            <td><? echo "$somanorte" ?></td>
            <td><? echo "$somacentrooeste"?></td>
            <td><? echo "$somanordeste"?></td>
			<td><? echo "$somasudeste"?></td>
            <td><? echo "$somasul"?></td>
        </tr>
		</table>
		<?
	
		}		
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar informativo" : "") . $e->getMessage();
	}
}
else {
	$msg_erro[] = "Informativo não informado, impossível continuar";
}

		$sql ="SELECT COUNT(*), tbl_posto.estado 
			FROM tbl_hd_chamado JOIN tbl_posto ON tbl_hd_chamado.posto=tbl_posto.posto
			WHERE tbl_hd_chamado.data BETWEEN {$informativo_data_inicial} AND {$informativo_data_final}
			GROUP BY tbl_posto.estado";			
		
		$resultado = pg_query($con, $sql);
		$resultado2 = pg_query($con, $sql);
		$rows = pg_affected_rows($resultado);
		
		$j = 0;
		?>
		<table align="center" width="700" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
		<?
		while ($row = pg_fetch_row($resultado))
		{		
		?>
			<td width="50"><? echo "$row[1]" ?></td>
		<?  } ?>
		</tr>
		</table>
		
		<table align="center" width="700" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
		<?
		while ($row = pg_fetch_row($resultado2))
		{		
		?>
			<td width="50"><? echo "$row[0]" ?></td>
		<? } ?>
		</tr>
		</table>
		
		<?
		if(is_array($msg_erro) > 0) {
		$msg_erro = implode('<br>', $msg_erro);
		echo "<link href='../telecontrol_oo.css' rel='stylesheet' type='text/css'>";
		echo "<center>";
		echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
}