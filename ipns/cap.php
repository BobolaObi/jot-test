<?
$_GET['qCap'] = str_replace(" ", "+", $_GET['qCap']);  
print join("", file("http://v2.jotform.com/ipns/cap.php?anum=".$_GET['anum']."&qCap=".$_GET['qCap'])); 
?>
