<?
    $typing = $_GET["typing"];

    for ($i = 1; $i <= 8; $i++)
    {
?>
<div onselect="this.text.value = 'Member No.<?= $i ?>';$('studentID').value = '<?= $i ?>'">
        <span class="informal">No.<?= $i ?></span>
        Member <?= $i ?>
</div>
<?
    }
?>
