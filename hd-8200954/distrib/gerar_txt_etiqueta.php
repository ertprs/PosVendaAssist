<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

$msg_erro = '';
$nome_campo = array();
if(!empty($_POST['btn_acao'])){
	$sql_juncao = "SELECT DISTINCT ON (a.embarque) a.embarque,0 as ordem
				INTO TEMP temp_embarque_juncao
				FROM tbl_embarque_item a 
				JOIN tbl_embarque_item b using(embarque)
				WHERE a.embarcado::date > b.liberado::date 
					AND a.liberado IS NOT NULL;
				";
	$res_juncao = pg_query($con,$sql_juncao);

	$sql = "SELECT
			ordem,
			tbl_embarque_item.embarque,
			tbl_peca.referencia,
			tbl_peca.descricao, 
			tbl_posto_estoque_localizacao.localizacao,
			tbl_produto.descricao AS produto_descricao,
			tbl_embarque_item.embarque_item AS codigo_barras,
			case when tbl_pedido.origem_cliente then tbl_hd_chamado_extra.nome else tbl_os.consumidor_nome end as consumidor_nome,
			case when tbl_pedido.origem_cliente then tbl_hd_chamado_extra.hd_chamado::text else tbl_os.sua_os end as sua_os,
			tbl_posto.nome AS posto_nome, tbl_embarque_item.qtde,tbl_embarque.fabrica
		INTO TEMP embarque_etiqueta
		FROM tbl_embarque_item
		JOIN tbl_embarque ON tbl_embarque_item.embarque = tbl_embarque.embarque
		JOIN (
			SELECT embarque,ordem
			FROM temp_embarque_juncao
		UNION
		SELECT distinct c.embarque, 
			1 as ordem 
		FROM tbl_embarque_item c
		JOIN tbl_embarque_item d using(embarque)
		WHERE c.embarcado::date = d.embarcado::date 
			AND c.liberado IS NOT NULL
			AND c.embarque NOT IN (
				SELECT embarque FROM temp_embarque_juncao
			)

 		) emb ON emb.embarque = tbl_embarque_item.embarque

		JOIN tbl_peca ON tbl_embarque_item.peca = tbl_peca.peca
		AND tbl_peca.fabrica = tbl_embarque.fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_embarque.posto
		LEFT JOIN tbl_pedido_item USING (pedido_item)
		LEFT JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
		AND tbl_pedido.fabrica = tbl_embarque.fabrica
		LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_embarque.distribuidor = tbl_posto_estoque_localizacao.posto AND tbl_embarque_item.peca = tbl_posto_estoque_localizacao.peca
		LEFT JOIN tbl_os_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
		AND tbl_os_item.fabrica_i = tbl_embarque.fabrica
		LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os 
		AND tbl_os.fabrica = tbl_embarque.fabrica
		LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
		AND tbl_produto.fabrica_i = tbl_embarque.fabrica
		WHERE tbl_embarque.distribuidor in ($login_posto)
		AND   tbl_embarque_item.liberado IS NOT NULL
		AND   tbl_embarque_item.impresso ISNULL 
		order by ordem,tbl_embarque.fabrica,tbl_embarque_item.embarque,tbl_posto_estoque_localizacao.localizacao,tbl_peca.referencia; 

		SELECT distinct * from embarque_etiqueta order by ordem, fabrica, embarque, localizacao, referencia
";

	$res = pg_query($con,$sql);


	$resultado = pg_fetch_all($res) ;

	if($resultado){
			$arquivo= "xls/etiqueta.tx2";
			if(file_exists($arquivo)) exec("rm $arquivo");

			$file = fopen($arquivo,'w');
			foreach($resultado[0] as $key => $value) {
					if($key == 'fabrica') continue;
					$nome_campo[] = $key;
			}
			fwrite($file,implode("\t",$nome_campo));
			fwrite($file,"\n");

			foreach($resultado as $key => $value) {
					unset($value['fabrica']);
					fwrite($file,implode("\t",$value));
					fwrite($file,"\n");
			}
			fclose($file);
			if(file_exists($arquivo)){
					echo "<script> window.open('$arquivo','_blank')</script>";
			}
	}else{
		$msg_erro = "Nenhum embarque liberado";
	}
}
if(!empty($_POST['btn_acao2'])){

		$sql = "SELECT	distinct	tbl_embarque_item.embarque,
				tbl_posto.nome AS posto_nome,
				tbl_fabrica.nome as fabrica_nome,
				case when tbl_embarque.garantia is true then 'GARANTIA' else 'FATURADO' end as tipo_embarque
				FROM tbl_embarque_item
			JOIN tbl_embarque USING (embarque)
			JOIN tbl_peca USING (peca)
			JOIN tbl_posto ON tbl_posto.posto = tbl_embarque.posto
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_embarque.fabrica
			LEFT JOIN tbl_pedido_item USING (pedido_item)
			LEFT JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
			LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_embarque.distribuidor = tbl_posto_estoque_localizacao.posto AND tbl_embarque_item.peca = tbl_posto_estoque_localizacao.peca
			LEFT JOIN tbl_os_item USING (os_item)
			LEFT JOIN tbl_os_produto USING (os_produto)
			LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
			LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_embarque.distribuidor in ($login_posto)
			AND   tbl_embarque_item.liberado IS NOT NULL
			AND   tbl_embarque_item.impresso ISNULL 
			order by tbl_embarque_item.embarque,posto_nome;";
		$res = pg_query($con,$sql);
		$resultado = pg_fetch_all($res) ;
		if($resultado){
				$arquivo= "xls/etiqueta.tx2";
				if(file_exists($arquivo)) exec("rm $arquivo");

				$file = fopen($arquivo,'w');
				foreach($resultado[0] as $key => $value) {
						$nome_campo[] = $key;
				}
				fwrite($file,implode("\t",$nome_campo));
				fwrite($file,"\n");

				foreach($resultado as $key => $value) {
						fwrite($file,implode("\t",$value));
						fwrite($file,"\n");
				}
				fclose($file);
				if(file_exists($arquivo)){
						echo "<script> window.open('$arquivo','_blank')</script>";
				}
		}else{
			$msg_erro = "Nenhum embarque liberado";
		}
}

 include 'menu.php' ?>
<HTML>
<HEAD>
<TITLE> GERAR ARQUIVO BARTENDER </TITLE>
</head>
<BODY>
<br>
<?=$msg_erro;?>
<br>
<form name='frm_gerar' action='<? echo $PHP_SELF ?>' method='post'>
<TABLE BORDER="0" align="center" cellspacing='2' cellpadding='0' style='font-family: verdana; font-size: 10px;'>
<TR>
	<TD colspan='2' height='20' bgcolor='eeeeee'>
		<b><center>GERAR ARQUIVO PARA BARTENDER</center></b>
	</TD>
</TR>

<TR>
	<TD>
		CERTIFICA QUE JÁ LIBEROU AS PEÇAS QUE DESEJA IMPRIMIR ANTES DE GERAR ARQUIVO
	</TD>
</TR>
<TR>
	<TD align='center'>
		<INPUT TYPE='submit' value='Gerar itens para imprimir no Bartender' name='btn_acao'>
		<INPUT TYPE='submit' value='Gerar embarque e posto' name='btn_acao2'>
	</TD>
</TR>

</TABLE>

</form>

</BODY>
</HTML>
