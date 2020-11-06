<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

require_once __DIR__.'/../classes/api/Client.php';
use api\Client;

$client = Client::makeTelecontrolClient("auditor","auditor");
$logs = array();

if(isset($_GET['limit']) && is_numeric($_GET['limit'])){
	$limit = (int)$_GET['limit'];
}
else{
	$limit = 50;
}

if(isset($_GET['table']) && !empty($_GET['table']) && isset($_GET['id']) && !empty($_GET['id'])){
	$table = $_GET['table'];
	$primaryKey  = $_GET['id'];
	$client->urlParams = array(
		'aplication' => '02b970c30fa7b8748d426f9b9ec5fe70',
		'table' => $table,
		'primaryKey' => $primaryKey,
		'limit' => $limit,
	);
	$admins = array();
	try{
	
		$auditor = $client->get();
		foreach ($auditor as $auditorLine) {
			$admins[] = (int)$auditorLine['data']['user'];
			$log = array();
			$log['admin'] = (int)$auditorLine['data']['user'];
			$log['time'] = (float)$auditorLine['data']['created'];
			$log['ip'] = $auditorLine['data']['ip_access'];
			$log['table'] = $auditorLine['data']['table'];
			$log['before'] = $auditorLine['data']['content']['antes'];
			$log['after'] = $auditorLine['data']['content']['depois'];
			$log['beforeDiff'] = array_diff($log['before'],$log['after']);
			$log['afterDiff'] = array_diff($log['after'],$log['before']);
			foreach (array('before','after','beforeDiff','afterDiff') as $keyName) {
				if(isset($log[$keyName]['senha']))
					$log[$keyName]['senha'] = '********';
			}
			$logs[] = $log;
		}
		$admins = buscaAdmin($admins);
	}
	catch(Exception $ex){
	}
}


function buscaAdmin($adminIds){
	global $con,$login_fabrica;
	if(!is_array($adminIds))
		$adminIds = $adminIds;
	$in = '$'.implode(',$',range(2,count($adminIds)+1));
	$sql = 'SELECT admin,nome_completo FROM tbl_admin WHERE fabrica = $1 AND admin IN ('.$in.') LIMIT 1;';	
	$params = array_merge(array((int)$login_fabrica),$adminIds);
	$result = pg_query_params($con,$sql,$params);
	if($result === false)
		throw new Exception(pg_last_error($con));
	$admins = pg_fetch_all($result);
	$adminMap = array();
	foreach ($admins as $admin) {
		$adminMap[$admin['admin']] = $admin['nome_completo'];
	}
	return $adminMap;
}

function prettyKey($origKey){
	$explode = preg_split('@[_]+@',$origKey,-1,PREG_SPLIT_NO_EMPTY);
	return htmlentities(implode(' ',array_map('ucfirst',$explode)));
}

function printArray($array){
?>
	<ul>
	<?php
	foreach ($array as $key => $value):
	?>	
		<li>
			<div>
				<span>
					<?php echo prettyKey($key); ?>
				</span>
				:
				<span>
					<?php echo $value; ?>
				</span>
			</div>
		</li>
	<?php
	endforeach;
	?>
	</ul>
<?php
}

?>
<html>
	<head>
		<meta charset="iso-8859-1" />
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
	    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
	    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
	    <link href="css/tooltips.css" type="text/css" rel="stylesheet" />
	    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
	    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

	    <!--[if lt IE 10]>
	  	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
		<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
		<![endif]-->

	    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
	    <style type="text/css">
	    	html,body {
	    		margin:none !important;
	    		padding:none !important;
	    		border:none !important;
	    		height: 0 !important;
	    		width: 100% !important;
	    	}
	    </style>
	    <script type="text/javascript">
	    	$(function(){
	    		if(window.location == window.parent.location)
	    			return;
	    		$(window.parent.document).ready(function(){
	    			var parent = $(window.parent.document);
		    		var myUrl = (window.location.href).split('#')[0];
		    		var ownerUrl = (window.parent.location.href).split('#')[0];
		    		var mySplitedUrl = myUrl.split('/');
		    		var ownerSplitedUrl = ownerUrl.split('/');
		    		var myUrlEnd = mySplitedUrl[mySplitedUrl.length-1];
		    		var iframes = parent.find("iframe[src$='"+myUrlEnd+"']");
		    		var height = $('#content').height();
		    		console.debug(iframes);
		    		iframes.each(function(){
		    			var min = $(this).css('min-height').replace('px','');
		    			var max = $(this).css('max-height').replace('px','');
		    			var h = height < min ? min : height;
		    			var h = h > max ? max : h;
		    			if(h < max){
		    				$(this).css('overflow-y','hidden');
		    				$(this).attr('scrolling','no');
		    			}
		    			$(this).height(h);
		    		});
	    		});	    		
	    	});
	    </script>
	</head>
	<body>
		<?php if(!empty($logs)) : ?>
		<table id="content" class="table table-striped table-bordered table-hover table-fixed" style="margin:0 auto;">
			<thead>
				<tr class="titulo_tabela">
					<th colspan="4">Logs de Alterações</th>
				</tr>
				<tr class="titulo_coluna">
					<th>Usuário</th>
					<th>Horário</th>
					<th>Antes</th>
					<th>Depois</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($logs as $log): ?>
				<tr>
					<td>
						<?php echo htmlentities($admins[$log['admin']]); ?>
					</td>
					<td>
						<?php echo htmlentities(date('d-m-Y H:i:s',$log['time'])); ?>
					</td>
					<td>
						<?php printArray($log['beforeDiff']); ?>
					</td>
					<td>
						<?php printArray($log['afterDiff']); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
			<div id="content" class="containter" >
				<div class="alert">
					Este registro ainda não possui Logs
				</div>
			</div>
		<?php endif; ?>
	</body>
</html>

