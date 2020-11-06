<?

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

	$typing = $_GET["typing"];

	if (!isset($_GET["page"]))
		$page = 0;
	else 
		$page = $_GET["page"];

    $pageSize = 8;
?>
	<table width="100%" cellpadding="2" cellspacing="0">
<?
    for ($i = 1; $i <= $pageSize; $i++)
    {
?>
	<tr onselect="this.text.value = 'Member No.<?= $page * $pageSize + $i ?>';">
    	<td><b>Member </b></td>
		<td>No.<?= $page * $pageSize + $i ?></td>
		<td><img src="image.png"></td>
	</tr>
<?
    }
?>
	</table>
<?	

    if ($page > 0)
    {
       echo "<a href='?page=" . ($page - 1) . "' style='float:left' class='page_up'>Prev</a>";
    }

    echo "<a href='?page=" . ($page + 1) .  "' style='float:right'  class='page_down'>Next</a>";

?>