<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if($ajax=='codigo_barra'){
	$sql = "SELECT produto_rg
			FROM   tbl_produto_rg
			WHERE  posto                = $login_posto
			AND    data_digitacao::DATE = CURRENT_DATE
			AND    data_digitacao_termino IS NULL";
	$res1 = @pg_exec($con,$sql);
	
	if(@pg_numrows($res1)>0){

		$produto_rg = @pg_result($res1,0,0);

	}


	$sql = "SELECT produto,referencia,descricao
			FROM   tbl_produto
			JOIN   tbl_linha   USING(linha)
			WHERE  codigo_barra = '$codigo_barra'
			AND    fabrica      = 45";
	$res2 = pg_exec($con,$sql);


	if(pg_numrows($res2)>0){
		$produto    = @pg_result($res2,0,produto);
		$referencia = @pg_result($res2,0,referencia);
		$descricao  = @pg_result($res2,0,descricao);

		$resultado = "<span style='font-size:60px;'>$descricao</span>";

		if ($dh = opendir("imagens_produto/")) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				$Xreferencia = str_replace(" ", "_",$referencia);
				$Xreferencia = explode("-", $referencia);
				$Xreferencia = $Xreferencia[0];
				if (strpos($filename,$Xreferencia) !== false){
					$contador++;
					//$peca_referencia = ntval($peca_referencia);
					$po = strlen($Xreferencia);
					if(substr($filename, 0,$po)==$Xreferencia){
						$file_final = $filename;
						$img = "<img src='imagens_produto/$file_final'/>";

						$tem_foto = "SIM";

					}
				}
			}
		}
		$sql = "SELECT
				(
					SELECT
						count(*) 
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
					AND   PR.troca_obrigatoria IS TRUE
				) AS troca_obrigatoria,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
				) AS cb_achado,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					WHERE RI.produto_rg        = $produto_rg
					AND   RI.produto           IS NULL
				) AS cb_perdido ";
		$res2 = @pg_exec($con,$sql);
		$troca_obrigatoria = @pg_result($res2,0,0);
		$cb_achado         = @pg_result($res2,0,1);
		$cb_perdido        = @pg_result($res2,0,2);

		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;background:#FFFFB3;'>Produtos com Troca Obrigatória:<br> $troca_obrigatoria</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#C1FFC1;'>Produtos com código barra encontrados:<br> $cb_achado</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#FFD9D9;'>Produtos com código barra NÃO encontrados:<br> $cb_perdido</div><br>";

		if($tem_foto<>'SIM'){
			$img = "<img src='imagens/produto_sem_foto.gif' align='middle'>\n";
		}


	}else $resultado .= "<span style='font-size:70PX;color:#FF0000;text-align:center;'>NÃO ENCONTRADO</SPAN>";

	echo "ok|$resultado|$img|$quadro";
	exit;
}


if ($acao == "gravar" AND $ajax == "sim") {

//	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$rg           = strtoupper(trim($_POST['rg']));
	$codigo_barra = strtoupper(trim($_POST['codigo_barra']));

	if(strlen($codigo_barra)==0) $msg_erro .= "Código de Barra não pode estar vazio<br>";
	if(strlen($codigo_barra)==0) $msg_erro .= "RG não pode estar vazio<br>";

	if(strlen($msg_erro)==0){
		//Verificar se o recebimento foi finalizado
		$sql = "SELECT produto_rg
				FROM   tbl_produto_rg
				WHERE  posto                = $login_posto
				AND    data_digitacao::DATE = CURRENT_DATE
				AND    data_digitacao_termino IS NULL";
		$res1 = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		//se o recebimento não foi finalizado pega o ID do Lote
		if(@pg_numrows($res1)>0){
			$produto_rg = @pg_result($res1,0,0);
		}else{
			//se o recebimento está finalizado cria uma nova ID para o Lote
			$sql =	"INSERT INTO tbl_produto_rg (
						posto             ,
						revenda           ,
						fabrica
					) VALUES (
						$login_posto,
						307,
						45
					)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
				$res = @pg_exec($con,"SELECT CURRVAL ('seq_produto_rg')");
				$produto_rg = pg_result($res,0,0);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {

		//Verifica se o Produto do RG tem OS em aberto.
		$sql = "SELECT  RI.fabrica
				FROM tbl_produto_rg      RG
				JOIN tbl_produto_rg_item RI USING(produto_rg)
				WHERE RG.posto = $login_posto
				AND   RI.rg    = '$rg'
				AND   RI.data_devolucao IS NULL";
		$res1 = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if(@pg_numrows($res1)>0){
			$fabrica         = pg_result($res1,0,fabrica);

			//Verifica se a OS está na tbl_os
			$sql = "SELECT os,sua_os FROM tbl_os 
					WHERE fabrica    = 45
					AND   posto      = $login_posto
					AND   rg_produto = '$rg'";
			$res2 = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$msg_erro .= "Existe uma OS aberta com o RG $rg<br>";
			if(pg_numrows($res2)>0) {
				$os     = pg_result($res2,0,os);
				$sua_os = pg_result($res2,0,sua_os);
				$msg_erro .= "OS <a href='../login_unico.php?id=$fabrica&os=$os'>$sua_os</a>";
			}
		}

		//Verifica se o código de Barra tem um único produto correspondente
		if(strlen($codigo_barra)>0){
			$sql = "SELECT produto,referencia,descricao
					FROM   tbl_produto
					JOIN   tbl_linha   USING(linha)
					WHERE  codigo_barra = '$codigo_barra'
					AND    fabrica      = 45";
			$res2 = @pg_exec($con,$sql);
			if(pg_numrows($res2)==1) $produto = @pg_result($res2,0,produto);
			else                     $produto = "null";
			
		}

		if(strlen($msg_erro)==0){
			//Insere o produto 
			$sql =	"INSERT INTO tbl_produto_rg_item (
						produto_rg  ,
						rg          ,
						codigo_barra,
						produto     ,
						fabrica     ,
						posto
					) VALUES (
						$produto_rg    ,
						'$rg'          ,
						'$codigo_barra',
						$produto       ,
						45             ,
						$login_posto
					)";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}


	if (strlen($msg_erro) == 0) {
		//$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso<br>$resultado|$produto_rg|";
	}else{
		//$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro";
	}
	exit;
}













if ($acao == "mostrar" AND $ajax == "sim") {

	$sql = "SELECT produto_rg
			FROM   tbl_produto_rg
			WHERE  posto                = $login_posto
			AND    data_digitacao::DATE = CURRENT_DATE
			AND    data_digitacao_termino IS NULL";
	$res1 = @pg_exec($con,$sql);

	if(@pg_numrows($res1)>0){

		$produto_rg = @pg_result($res1,0,0);
		$sql = "SELECT  RI.produto_rg_item                                           ,
					RI.codigo_barra                                              ,
					RI.rg                                                        ,
					RI.produto                                                   ,
					RI.serie                                                     ,
					RI.defeito_reclamado                                         ,
					RI.fabrica                                                   ,
					PR.referencia                           AS produto_referencia,
					PR.descricao                            AS produto_descricao ,
					TO_CHAR(RI.data_devolucao,'dd/mm/YYYY') AS data
			FROM tbl_produto_rg_item  RI 
			LEFT JOIN tbl_produto          PR USING(produto) 
			WHERE produto_rg = $produto_rg";
			$res = @pg_exec($con,$sql);
		$resultado .= "<table class='TabelaRevenda'  cellspacing='3' cellpadding='3' width='98%'>";
		$resultado .= "<thead>";
		$resultado .= "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
		$resultado .= "<td><b>Código Barra</b></td>";
		$resultado .= "<td><b>RG</b></td>";
		$resultado .= "<td><b>Referência do Produto</b></td>";
		$resultado .= "<td><b>Descrição do Produto</b></td>";
		$resultado .= "<td><b>Ação</b></td>";
		$resultado .= "</tr>";
		$resultado .= "</thead>";
		$resultado .= "<tbody>";

		for($i=0;$i<@pg_numrows($res);$i++) {
			$produto_rg_item    = pg_result($res,$i,produto_rg_item);
			$codigo_barra       = pg_result($res,$i,codigo_barra);
			$rg                 = pg_result($res,$i,rg);
			$data               = pg_result($res,$i,data);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$defeito_reclamado  = pg_result($res,$i,defeito_reclamado);
			$serie              = pg_result($res,$i,serie);
			$fabrica            = pg_result($res,$i,fabrica);

			$sql = "SELECT nota_fiscal FROM tbl_lote_revenda
					WHERE lote    = '$produto_rg'
					AND   posto   = $cook_posto
					AND   revenda = $revenda
					AND   fabrica = $fabrica";
			$res2 = @pg_exec($con,$sql);
			$lote = @pg_result($res2,0,0);

			if($cor<>'#FFFFFF') $cor = '#FFFFFF';
			else                $cor = '';

			$resultado .= "<tr bgcolor='$cor' >";
			$resultado .= "<td>$codigo_barra&nbsp;</td>";
			$resultado .= "<td>&nbsp;&nbsp;$rg</td>";
			$resultado .= "<td>$produto_referencia</td>";
			$resultado .= "<td>$produto_descricao</td>";
			$resultado .= "<td><a href='rg_recebimento.php?excluir=$produto_rg_item'><img src='imagens/icone_deletar.png'></a></td>";
			$resultado .= "</tr>";
		}

		$resultado .= "<tr>";
		$resultado .=  "<td colspan='5'><input type='button' name='btn_acao'  value='Finalizar Recebimento' onClick=\"if (this.value!='Finalizar Recebimento'){ alert('Aguarde');}else {this.value='Aguarde...';window.location='rg_recebimento.php?finaliza' ;}\" style=\"width: 150px;\"></td>";
		$resultado .= "</tr>";

		$resultado .= "</tbody>";
		$resultado .= "</table>";

		$sql = "SELECT
				(
					SELECT
						count(*) 
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
					AND   PR.troca_obrigatoria IS TRUE
				) AS troca_obrigatoria,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
				) AS cb_achado,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					WHERE RI.produto_rg        = $produto_rg
					AND   RI.produto           IS NULL
				) AS cb_perdido ";
		$res2 = @pg_exec($con,$sql);
		$troca_obrigatoria = @pg_result($res2,0,0);
		$cb_achado         = @pg_result($res2,0,1);
		$cb_perdido        = @pg_result($res2,0,2);

		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;background:#FFFFB3;'>Produtos com Troca Obrigatória:<br> $troca_obrigatoria</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#C1FFC1;'>Produtos com código barra encontrados:<br> $cb_achado</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#FFD9D9;'>Produtos com código barra NÃO encontrados:<br> $cb_perdido</div><br>";

	}
	echo "ok|$resultado|$quadro";
	exit;
}

?>
