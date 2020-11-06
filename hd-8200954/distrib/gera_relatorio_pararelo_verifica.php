<?
if(file_exists($relatorio_anterior)){
	?>
		<script>
			window.location = "<?=$PHP_SELF?>?include=1";
		</script>
	<?
	exit;
}
?>