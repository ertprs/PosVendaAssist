<?
$hoje = strtotime('now');
if ($hoje < strtotime('2019-09-04 23:00:00')
    and strpos($PHP_SELF, 'chamado_lista.php') > 0    ) {

			?>
			<script type="text/javascript">
				var modal = null;
				function showTeleZap(){
					modal = Shadowbox.open({
					content: "<div style='width:400px;height:495px'><img src='../admin/imagens/telezapmarketing.png' width='545' height='695'></div>",
						player: "html",
						width: 550,
						height: 700,
						options: {
							onClose: function(){				        	
								var today = new Date();
								today = today.getDate();
								var checkLocal = localStorage.setItem("modalTeleZap",today);
							}		
						}
					 });
				 }

				$(function(){				
					Shadowbox.init();
					$(document).on("ShadowboxInit", function() {					
						 var today = new Date();
						 today = today.getDate();

						 var checkLocal = localStorage.getItem("modalTeleZap");
						 if(checkLocal == null){
							showTeleZap();
						 }else{
							if(checkLocal != today){
								showTeleZap();
							}						
						 }
					});
				});
			</script>
			<?php
}
?>




<?php if (BS3 === true or $_COOKIE['menu']=='novo'/* or in_array($login_admin, array(1375, 6835))*/): ?>
    </div>
    <div id="rodape">
      <div class="well bg-warning text-muted text-center">
        &copy; Telecontrol Networking Ltda - <? echo date("Y"); ?>
        <div class="pull-right">
          <a  href="http://www.telecontrol.com.br" target="_blank"  style='text-decoration: none ; color: #000000 ;font-size: 10px' >www.telecontrol.com.br</a><br>
          <font color='#fefefe'>Deus é o Provedor</font><br>
        </div>
      </div>
    </div>
	<script type='text/javascript' src='js/rotinas/check_online_admin.js' ></script>
    <style>
    body {
      padding-bottom: 80px;
    }
    #rodape {
      position: fixed;
      bottom: -22px;
      width: 100%;
    }
    </style>
  </body>
</html>
<?php else: ?>
<p> </p>

<hr style='border-collapse: collapse' color='#d2e4fc' width='90%'>
<hr style='border-collapse: collapse' color='#FFFFcc' width='90%'>
	<table width="90%" align='center'>
		<tr>
			<td></td>
			<td align='right' style='font-family: arial ; font-size: 10px'>
				Telecontrol Networking Ltda - <? echo date("Y"); ?><br />
				<a  href="http://www.telecontrol.com.br" target="_blank"  style='text-decoration: none ; color: #000000 ;font-size: 10px' >www.telecontrol.com.br</a><br>
				<font color='#fefefe'>Deus é o Provedor</font><br>
			</td>
			</tr>
	</table>
	<script type='text/javascript' src='js/rotinas/check_online_admin.js' ></script>
</body>
</html>
<?php
endif;
pg_close($con);

