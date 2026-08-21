<?php

session_start(); 

session_destroy(); 
session_unset(); 

setcookie("zamboni", "");

echo "<script>alert('Obrigado e Volte Sempre!');top.location.href='index.php';</script>"; /*aqui você pode por alguma coisa falando que ele saiu ou fazer como eu, coloquei redirecionar para uma certa página*/


?>
