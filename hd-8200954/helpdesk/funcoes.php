<?php


function prioridade($atendente) {
	global $con;

	$sql = " select hd_chamado, data, tbl_backlog_item.prioridade from tbl_hd_chamado left join tbl_backlog_item using(hd_chamado) where status not in ('Resolvido', 'Cancelado','Novo')  and atendente =$atendente and tbl_backlog_item.prioridade ~ '[0-9]' order by prioridade , hd_chamado ;";
	$res = pg_query($con,$sql);

	for($i=0;$i<pg_num_rows($res);$i++) {
			$prioridade = pg_fetch_result($res,$i,'prioridade');
			$hd_chamado = pg_fetch_result($res,$i,'hd_chamado');

			$j = $i+1;

			if(!empty($prioridade) and $j <> $prioridade) {
				$sql = "UPDATE tbl_backlog_item SET prioridade='$j' where hd_chamado = $hd_chamado";
				$ress = pg_query($con,$sql);
			}
	}

}


function sendMessage($messaggio) {

	$token = "1254817924:AAEgxRhvp2xpVp7pYbTFkpAAxPuTbjwmyYQ";
	$chatID = "-1001403778946";

    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chatID;
    $url = $url . "&text=" . urlencode($messaggio);
    $ch = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
