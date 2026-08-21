<?php


$hora_do_dia=date("H");

/*uso de condicionais, poderíamos utilizar o switch também*/

if (($hora_do_dia >=6) && ($hora_do_dia <12)) echo "<b> Bom Dia </b>  ";
if (($hora_do_dia >=12) && ($hora_do_dia <18)) echo "<b> Boa Tarde </b>";
if (($hora_do_dia >=18) && ($hora_do_dia <=24)) echo "<b> Boa Noite </b>";
if (($hora_do_dia >00) && ($hora_do_dia <6)) echo "<b> Boa Madrugada </b>";

?>  
