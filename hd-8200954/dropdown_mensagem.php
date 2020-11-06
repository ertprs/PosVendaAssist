<style type="text/css">


div#info2 {
	position: fixed;
	text-align: right;
	font-weight: bold;
	top: 50px;
	right:70px;
	height: 30px;
    line-height: 12px;
	width: 180px;
	/*background: white;*/
	/*border-radius: 0 0 6px 6px;*/
	/* border: 1px solid #ccb; */
	border-width: 0;
	margin: 0;
	padding: 0;
	/*box-shadow: 0 2px 3px #444;*/
	overflow: hidden;
	transition: width, height .3s, .5s;
	/*background: #eeeeee;*/ /* Old browsers */
	/*background: -ms-linear-gradient(top, #dcdcdc 0%,#dcdcdc 100%); *//* ie10+ */
	/*background: linear-gradient(to to, #dcdcdc, #eee); *//* w3c */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#DCDCDC', endColorstr='#DCDCDC',GradientType=0 ); /* IE6-9 */
	z-index: 100;
}


div#info {
	position: fixed;
	top: 50px;
	left: 20px;
	height: 24px;
    line-height: 12px;
	width: 180px;
	background: white;
	border-radius: 0 0 6px 6px;
	/* border: 1px solid #ccb; */
	border-width: 0;
	margin: 0;
	padding: 0;
	box-shadow: 0 2px 3px #444;
	overflow: hidden;
	transition: width, height .3s, .5s;
	background: #eeeeee; /* Old browsers */
	background: -ms-linear-gradient(top, #dcdcdc 0%,#dcdcdc 100%); /* ie10+ */
	background: linear-gradient(to to, #dcdcdc, #eee); /* w3c */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#DCDCDC', endColorstr='#DCDCDC',GradientType=0 ); /* IE6-9 */
	z-index: 100;
}
	div#info:hover  {
		height: 250px;
		width: 450px;
		box-shadow: 2px 2px 4px #444;
		overflow-y: auto;
	}

	div#info h2 {
		background-color: orange;
		color: black;
		font-size: 12px;
		font-weight: bold;
		margin: 0;
        padding: 0;
		text-align:center;
		width: 100%;
		height: 24px;
		line-height: 2em;
	}
	div#info p{
		margin: 1ex 1em;
		font-size: 11px;
		width: 95%;
		text-align:justify;
	}
</style>
<?php
	if ($login_fabrica == 1) {
		$sql_posto_fabrica = "SELECT digita_os, pedido_faturado, pedido_em_garantia, categoria, tipo_posto,reembolso_peca_estoque from tbl_posto_fabrica where posto = $login_posto and fabrica = $login_fabrica";
		$res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
		if(pg_num_rows($res_posto_fabrica) > 0 ){
			$digita_os              = pg_fetch_result($res_posto_fabrica, 0, 'digita_os');
			$pedido_faturado        = pg_fetch_result($res_posto_fabrica, 0, 'pedido_faturado');
			$pedido_em_garantia     = pg_fetch_result($res_posto_fabrica, 0, 'pedido_em_garantia');
			$categoria              = pg_fetch_result($res_posto_fabrica, 0, 'categoria');
			$tipo_posto             = pg_fetch_result($res_posto_fabrica, 0, 'tipo_posto');
			$reembolso_peca_estoque = pg_fetch_result($res_posto_fabrica, 0, 'reembolso_peca_estoque');

            $sql_cond1=" tbl_comunicado.pedido_em_garantia     IS NULL ";
            $sql_cond2=" OR tbl_comunicado.pedido_faturado        IS NULL ";
            $sql_cond3=" OR tbl_comunicado.digita_os              IS NULL ";
            $sql_cond4=" OR tbl_comunicado.reembolso_peca_estoque IS NULL ";

			if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia     IS TRUE ";
			if ($pedido_faturado == "t")        $sql_cond2 =" OR tbl_comunicado.pedido_faturado        IS TRUE ";
			if ($digita_os == "t")              $sql_cond3 =" OR tbl_comunicado.digita_os              IS TRUE ";
			if ($reembolso_peca_estoque == "t") $sql_cond4 =" OR tbl_comunicado.reembolso_peca_estoque IS TRUE ";

			$sql_cond_total="AND ($sql_cond1 $sql_cond2 $sql_cond3 $sql_cond4) ";

		}


		$condicao_black = " AND (tbl_comunicado.destinatario_especifico = '$categoria' or tbl_comunicado.destinatario_especifico = '')
			AND (tbl_comunicado.tipo_posto = '$tipo_posto'  or tbl_comunicado.tipo_posto is null) ";
	}

	$sql = "SELECT *
			  FROM tbl_comunicado
			 WHERE tipo    =  'Comunicado Inicial'
			   AND fabrica =  $login_fabrica
			   AND posto   IS NULL
			   AND ativo   IS TRUE
			   $condicao_black
			   $sql_cond_total
			   AND linha   IS NULL
		  ORDER BY comunicado DESC LIMIT 1";
	$res2 = pg_exec ($con,$sql);

	if (pg_numrows($res2) == 0) {
		$sql = "SELECT *
				  FROM tbl_comunicado
				 WHERE tipo    =  'Comunicado Inicial'
				   AND fabrica =  $login_fabrica
				   AND posto   =  $login_posto
				   AND ativo   IS TRUE
				   $condicao_black
				   $sql_cond_total
			  ORDER BY comunicado DESC LIMIT 1";
		$res2 = pg_exec ($con,$sql);

		if (pg_numrows($res2) == 0) {
			$sql = "SELECT *
					  FROM tbl_comunicado
					  JOIN tbl_posto_linha ON tbl_comunicado.linha  = tbl_posto_linha.linha
										  AND tbl_posto_linha.posto = $login_posto
                                          AND tbl_posto_linha.ativo IS TRUE
					 WHERE tipo    = 'Comunicado Inicial'
					   AND fabrica = $login_fabrica
					   AND tbl_comunicado.posto IS NULL
					   AND tbl_comunicado.ativo IS TRUE
					   $condicao_black
					   $sql_cond_total
                  ORDER BY comunicado DESC
                     LIMIT 1";
			$res2 = pg_exec ($con,$sql);

		}
	}

	if (pg_numrows($res2) > 0) {
		$comunicado = pg_fetch_result($res2, 0, 'comunicado');
		$titulo     = pg_fetch_result($res2, 0, 'descricao');
		$texto      = pg_fetch_result($res2, 0, 'mensagem');
		$extensao   = pg_fetch_result($res2, 0, 'extensao');
		$tipo_com   = pg_fetch_result($res2, 0, 'tipo');
	}

	if (!empty($extensao)) {
		if ($S3_sdk_OK) {
			include_once S3CLASS;
			$tipoS3   = (strpos(anexaS3::TIPOS_VE, $tipo_com) === false) ? 'co' : 've';
			$s3       = new anexaS3($tipoS3, (int) $login_fabrica, $comunicado);
			$fileLink = $s3->url;

			if (is_null($fileLink))
				unset($fileLink);
			unset($s3);
		} else {
			$fileLink = "comunicados/$comunicado.$extensao";
			if (!file_exists($fileLink))
				unset($fileLink);
		}
	}

?>

<div id='info'>
	<h2>COMUNICADO IMPORTANTE</h2>
	<p><center><span style="font-size:13px; font-weight:bold; color:#09F;"><?=$titulo?></center></p>
	<p><?=$texto?></p>
	<? if(isset($fileLink)){ ?>
			<p>
				<center><a href="<?=$fileLink?>" target="_blank" style="color:#FF0000"><u>Veja Mais</u></a></center>
			</p>
	<? } ?>
	<br />
	<p style='margin-top: 0.5ex;text-align:right'>
		<b><?php echo strtoupper($login_fabrica_nome); ?></b><br />
	</p>
</div>
<?php
if (pg_num_rows($resTela) > 0 && $login_fabrica == 1) {
    $descricao = pg_fetch_result($resTela, 0, 'descricao');
    $mensagem  = str_replace("&nbsp;", "", strip_tags(trim(pg_fetch_result($resTela, 0, 'mensagem')),
    	"<br><br/><p><i><b><strong><em><span><h1><h2><h3><h4>"));
?>
<style>
	#comunicado_tela {
		width: 800px;
		margin: auto !important;
		padding: 1px;
	}

	#comunicado_tela p {
		line-height: 28px;
		margin: 6px !important;
	}

	#comunicado_title {
		font-size: 16px;
	}

	#comunicado_mensagem {
		text-align: center;
		text-indent: 0pt !important;
	}

	.MsoNormal {
		text-indent: 0pt !important;
	}
</style>

<div id="comunicado_tela">
		<?=$cabecalho->alert(
	        "
	        	<center>
	        		<div id='comunicado_title'>
		        		<b>
		        			IMPORTANTE!
		        			<br>
		            		$descricao
		            	</b>
	            	</div>
	            </center>
	            <br>
	            <div id='comunicado_mensagem'>
	            	$mensagem
	            </div>
	        ",
	        "warning"
	    );?>
    <br>
</div>

<?php }
