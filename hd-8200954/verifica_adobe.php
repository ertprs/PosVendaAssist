<style>
	#adobeText a {
		color: #D82828 !important;
	}

	#adobeText a:hover {
		text-decoration: underline;
	}

	#adobeText {
		font-size: 12px;
	}
</style>
<script>
	$(window).load(function(){
		setInterval(function() {
			if ($("#downloadAdobe").css("visibility") == "visible") {
				$("#downloadAdobe").css("visibility", "hidden");
			} else {
				$("#downloadAdobe").css("visibility", "visible");
			}
		}, 600);
	});
</script>

<div style="margin-top: 10px; margin-bottom: 10px; color: #D82828;">	
	<center>
	<span id="adobeText">
		<?=traduz('Para visualizar vistas explodidas, comunicados, esquemas elétricos e manuais, instale a última versão do')?> <a href='http://get.adobe.com/br/reader/' target='_blank' ><b>Adobe Reader</b></a>.<br />
<?=traduz('Caso a vista explodida se mostre apagada, tente abrí-la com o programa')?> <a href="https://www.foxitsoftware.com/pdf-reader/" target="new" style="font-weight:bold;color:#D82828;text-decoration:none">Foxit Reader</a>.
	</span>
	<br /><br />
	<div style="height: 40px; width: 162px;">
		<a href="http://get.adobe.com/br/reader/" target="_blank" >
			<div style="cursor: pointer; height: 40px; width: 162px;" >
				<img id="downloadAdobe" src="http://www.botuvera.com.br/wp-content/uploads/2010/02/baixar_adobe_reader.gif" style="height: 40px; width: 162px;" />
			</div>
		</a>
	</div>
</center>
</div>

