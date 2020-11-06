<script>
$(function () {
	$("a[name=prod_ve]").attr("name", "prod_ve_brother");
	$("a[name=prod_ve_brother]").click(function () {
	    var comunicado = $(this).attr("rel");
	    
	    var tipo = $('#'+comunicado).val();
	    //Array para validar tipos no qual não vai aparecer vista expandida
		var noVistaExpandida = ['zip', 'rar'];

	    $.ajaxSetup({
	        async: true
	    });

	    $.blockUI({ message: "Aguarde..." });

	    $.get(
        "verifica_s3_comunicado.php",
        {tipo: tipo, comunicado: comunicado, fabrica:"<?=$fabrica_comunicado?>"},
        function(data) {
        	var extensao = data.substring(data.length-3, data.length);
            if (data.length > 0) {
            	if ($.inArray(extensao, noVistaExpandida) == -1) {
            		Shadowbox.init();

	                Shadowbox.open({
	                    content: data,
	                    player:  'iframe',
	                    title:   'Vista Explodida',
	                });
            	} else {
			window.open(data, '_blank');
		}               
            } else {
                alert("Arquivo não encontrado!");
            }

            $.unblockUI();
        })
    });
});
</script>
<?php
//--==================== Firmware ====================--\\
$sql = "SELECT COUNT(comunicado) AS firmware
		FROM tbl_comunicado
		WHERE ativo IS NOT FALSE
		AND tipo = 'Firmware'
		AND (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND fabrica    = $fabrica_comunicado";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$Firmware  = trim(pg_result($res,0,'firmware'));
}

$orTipo = ($login_fabrica == 203) ? "AND (tipo = 'ITB Informativo Técnico Brother' OR tipo ILIKE '%ITB%')" : "AND tipo = 'ITF Informativo Técnico FARCOMP'";

//--==================== ITF Informativo Técnico FARCOMP ====================--\\
$sql = "SELECT COUNT(comunicado) AS itf
		FROM tbl_comunicado
		WHERE ativo IS NOT FALSE
		$orTipo
		AND (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND fabrica    = $fabrica_comunicado";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$ITF  = trim(pg_result($res,0,'itf'));
}
//--==================== Utilitários BROTHER ====================--\\
$sql = "SELECT COUNT(comunicado) AS Utilitarios
		FROM tbl_comunicado
		WHERE ativo IS NOT FALSE
		AND tipo = 'Utilitários BROTHER'
		AND (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND fabrica    = $fabrica_comunicado";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$Utilitarios  = trim(pg_result($res,0,'Utilitarios'));
}
//--==================== Print Data INK JET ====================--\\
$sql = "SELECT COUNT(comunicado) AS Print
		FROM tbl_comunicado
		WHERE ativo IS NOT FALSE
		AND tipo = 'Print Data INK JET'
		AND (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
		AND fabrica    = $fabrica_comunicado";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$Print  = trim(pg_result($res,0,'Print'));
}

?>
<!--Firmware-->
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#efefef'>
		<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >Firmware</td>
	</tr>
	<tr bgcolor = '#efefef'>
		<td colspan='2' height='5'></td>
	</tr>
	<tr bgcolor = '#efefef'>
		<td valign='top' class='menu'>
			<?php

				$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
										tbl_familia.descricao                                ,
										tbl_linha.linha                                      ,
										tbl_linha.nome
						FROM    tbl_comunicado
						JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
						JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
						LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
						WHERE   tbl_linha.fabrica    = $fabrica_comunicado
						AND     tbl_comunicado.ativo IS NOT FALSE
						AND     tbl_comunicado.tipo = 'Firmware'
						AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
						AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
						".$sqlPostoLinha."
							UNION
							SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome
							FROM    tbl_comunicado
							JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
							JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
							JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
							LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
							WHERE   tbl_linha.fabrica    = $fabrica_comunicado
							AND     tbl_comunicado.ativo IS NOT FALSE
							AND     tbl_comunicado.tipo = 'Firmware'
							AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
							AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
							".$sqlPostoLinha."
							ORDER BY nome, descricao";
							#echo nl2br($sql);
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$linha_anterior = "";
					echo "<dl>";
					for ($i = 0 ; $i < pg_numrows($res); $i++) {

						$descricao  = trim(pg_result($res,$i,'descricao'));
						$familia    = trim(pg_result($res,$i,'familia'));
						$nome       = trim(pg_result($res,$i,'nome'));
						$linha      = trim(pg_result($res,$i,'linha'));

						if ($linha_anterior <> $linha) {
							echo "<br /><dt>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=Firmware&linha=$linha'>$nome</a><br /></dt>";
						}
						echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=Firmware&linha=$linha&familia=$familia'>$descricao</a><br /></dd>";
						$linha_anterior = $linha;
					}
				} else {
					echo "<br /><dt>&nbsp;&nbsp;<b>»</b> Nenhum Cadastrado<br /></dt>";
				}

			?>
			<br />
		</td>
	<td rowspan='2'class='detalhes' width='150'>Escolha a família que deseja consultar.</td>
	</tr>
</table>
<!--ITF Informativo Técnico FARCOMP-->
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#fafafa'>
		<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' ><?= ($login_fabrica == 203) ? "ITB Informativo Técnico Brother" : "ITF Informativo Técnico FARCOMP" ?> </td>
	</tr>
	<tr bgcolor = '#fafafa'>
		<td colspan='2' height='5'></td>
	</tr>
	<tr bgcolor = '#fafafa'>
		<td valign='top' class='menu'>
			<?php
				$orTipo = ($login_fabrica == 203) ? "AND (tbl_comunicado.tipo = 'ITB Informativo Técnico Brother' OR tbl_comunicado.tipo ILIKE '%ITB Informativo T%'" : "AND     tbl_comunicado.tipo = 'ITF Informativo Técnico FARCOMP'";
				$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
										tbl_familia.descricao                                ,
										tbl_linha.linha                                      ,
										tbl_linha.nome
						FROM    tbl_comunicado
						JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
						JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
						LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
						WHERE   tbl_linha.fabrica    = $fabrica_comunicado
						AND     tbl_comunicado.ativo IS NOT FALSE
						$orTipo
						AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
						AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
						".$sqlPostoLinha."
							UNION
							SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome
							FROM    tbl_comunicado
							JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
							JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
							JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
							LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
							WHERE   tbl_linha.fabrica    = $fabrica_comunicado
							AND     tbl_comunicado.ativo IS NOT FALSE
							$orTipo
							AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
							AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
							".$sqlPostoLinha."
							ORDER BY nome, descricao";

				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$linha_anterior = "";
					echo "<dl>";

					$txtTipo = ($login_fabrica == 203) ? "ITB Informativo Técnico Brother" : "ITF Informativo Técnico FARCOMP";

					for ($i = 0 ; $i < pg_numrows($res); $i++) {

						$descricao  = trim(pg_result($res,$i,'descricao'));
						$familia    = trim(pg_result($res,$i,'familia'));
						$nome       = trim(pg_result($res,$i,'nome'));
						$linha      = trim(pg_result($res,$i,'linha'));

						if ($linha_anterior <> $linha) {
							echo "<br /><dt>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=$txtTipo&linha=$linha'>$nome</a><br /></dt>";
						}
						echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=$txtTipo&linha=$linha&familia=$familia'>$descricao</a><br /></dd>";
						$linha_anterior = $linha;
					}
				} else {
					echo "<br /><dt>&nbsp;&nbsp;<b>»</b> Nenhum Cadastrado<br /></dt>";
				}

			?>
			<br />
		</td>
	<td rowspan='2'class='detalhes' width='150'>Escolha a família que deseja consultar.</td>
	</tr>
</table>
<!--Utilitários BROTHER-->
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#efefef'>
		<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >Utilitários BROTHER</td>
	</tr>
	<tr bgcolor = '#efefef'>
		<td colspan='2' height='5'></td>
	</tr>
	<tr bgcolor = '#efefef'>
		<td valign='top' class='menu'>
			<?php

				$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
										tbl_familia.descricao                                ,
										tbl_linha.linha                                      ,
										tbl_linha.nome
						FROM    tbl_comunicado
						JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
						JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
						LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
						WHERE   tbl_linha.fabrica    = $fabrica_comunicado
						AND     tbl_comunicado.ativo IS NOT FALSE
						AND     tbl_comunicado.tipo = 'Utilitários BROTHER'
						AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
						AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
						".$sqlPostoLinha."
							UNION
							SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome
							FROM    tbl_comunicado
							JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
							JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
							JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
							LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
							WHERE   tbl_linha.fabrica    = $fabrica_comunicado
							AND     tbl_comunicado.ativo IS NOT FALSE
							AND     tbl_comunicado.tipo = 'Utilitários BROTHER'
							AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
							AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
							".$sqlPostoLinha."
							ORDER BY nome, descricao";

				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$linha_anterior = "";
					echo "<dl>";
					for ($i = 0 ; $i < pg_numrows($res); $i++) {

						$descricao  = trim(pg_result($res,$i,'descricao'));
						$familia    = trim(pg_result($res,$i,'familia'));
						$nome       = trim(pg_result($res,$i,'nome'));
						$linha      = trim(pg_result($res,$i,'linha'));

						if ($linha_anterior <> $linha) {
							echo "<br /><dt>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=Utilitários BROTHER&linha=$linha'>$nome</a><br /></dt>";
						}
						echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=Utilitários BROTHER&linha=$linha&familia=$familia'>$descricao</a><br /></dd>";
						$linha_anterior = $linha;
					}
				} else {
					echo "<br /><dt>&nbsp;&nbsp;<b>»</b> Nenhum Cadastrado<br /></dt>";
				}
			?>
			<br />
		</td>
	<td rowspan='2'class='detalhes' width='150'>Escolha a família que deseja consultar.</td>
	</tr>
</table>
<!--Print Data INK JET-->
<table width="700" border="0" cellspacing="0" cellpadding="0" align='center'>
	<tr bgcolor = '#fafafa'>
		<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>
		<td  class="chapeu" colspan='2' >Print Data INK JET</td>
	</tr>
	<tr bgcolor = '#fafafa'>
		<td colspan='2' height='5'></td>
	</tr>
	<tr bgcolor = '#fafafa'>
		<td valign='top' class='menu'>
			<?php

				$sql = "SELECT DISTINCT tbl_familia.familia                                  ,
										tbl_familia.descricao                                ,
										tbl_linha.linha                                      ,
										tbl_linha.nome
						FROM    tbl_comunicado
						JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
						JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
						LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
						WHERE   tbl_linha.fabrica    = $fabrica_comunicado
						AND     tbl_comunicado.ativo IS NOT FALSE
						AND     tbl_comunicado.tipo = 'Print Data INK JET'
						AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
						AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
						".$sqlPostoLinha."
							UNION
							SELECT DISTINCT tbl_familia.familia                                  ,
							tbl_familia.descricao                                ,
							tbl_linha.linha                                      ,
							tbl_linha.nome
							FROM    tbl_comunicado
							JOIN    tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
							JOIN    tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto
							JOIN    tbl_linha   ON tbl_produto.linha   = tbl_linha.linha
							LEFT JOIN    tbl_familia ON tbl_produto.familia = tbl_familia.familia
							WHERE   tbl_linha.fabrica    = $fabrica_comunicado
							AND     tbl_comunicado.ativo IS NOT FALSE
							AND     tbl_comunicado.tipo = 'Print Data INK JET'
							AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
							AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
							".$sqlPostoLinha."
							ORDER BY nome, descricao";

				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$linha_anterior = "";
					echo "<dl>";
					for ($i = 0 ; $i < pg_numrows($res); $i++) {

						$descricao  = trim(pg_result($res,$i,'descricao'));
						$familia    = trim(pg_result($res,$i,'familia'));
						$nome       = trim(pg_result($res,$i,'nome'));
						$linha      = trim(pg_result($res,$i,'linha'));

						if ($linha_anterior <> $linha) {
							echo "<br /><dt>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=Print Data INK JET&linha=$linha'>$nome</a><br /></dt>";
						}
						echo "<dd>&nbsp;&nbsp;<b>»</b> <a href='info_tecnica_visualiza.php?tipo=Print Data INK JET&linha=$linha&familia=$familia'>$descricao</a><br /></dd>";
						$linha_anterior = $linha;
					}
				} else {
					echo "<br /><dt>&nbsp;&nbsp;<b>»</b> Nenhum Cadastrado<br /></dt>";
				}
			?>
			<br />
		</td>
	<td rowspan='2'class='detalhes' width='150'>Escolha a família que deseja consultar.</td>
	</tr>
</table>
