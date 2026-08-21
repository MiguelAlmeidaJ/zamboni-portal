<?php header("Content-Type: text/html; charset=iso-8859-1",true) ?>
<?

    if (!isset($_GET["login"]))  // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
     {
    header("Location: index.php");  // redireciona para o site original
    exit;
     }
?>

<?
if (!isset($_POST["dia"])) {
				$dia = date("d");
				$mes = date("m");
				$ano = date("Y");
				$Date = "$ano$mes$dia";

}Else{
				$dia = $_POST["dia"];
				$mes = $_POST["mes"];
				$ano = $_POST["ano"];
				$Date = "$ano$mes$dia";

}

$Dataf = "$dia/$mes/$ano";
$login = $_GET["login"];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Agenda Zamboni</title>
<style type="text/css">
<!--
.style6 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px;}
.style8 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px;}
.style13 {font-weight: bold}
-->
</style>
</head>

<body>
 <form name="form1" method="post" action="<? echo "indexagenda.php?login=$login"; ?>">
   <table width="100%"  border="0" cellspacing="0" cellpadding="0">
     <tr>
       <td colspan="3" bgcolor="#E6E6E6"><div align="center"><strong><span class="style8">Selecione uma data: </span></strong></div>
         <div align="right">         </div>
       <div align="center">         </div></td>
     </tr>
     <tr>
       <td colspan="3" bgcolor="#E6E6E6"><table width="11%"  border="0" align="center" cellpadding="2" cellspacing="1">
         <tr>
           <td width="8%" bgcolor="#E6E6E6" class="style13">
              <div align="center">
                   <select name="dia" class="style6">
                     <option value="01" selected>01</option>
                     <option value="02">02</option>
                     <option value="03">03</option>
                     <option value="04">04</option>
                     <option value="05">05</option>
                     <option value="06">06</option>
		     <option value="07">07</option>
                     <option value="08">08</option>
                     <option value="09">09</option>
                     <option value="10">10</option>
                     <option value="11">11</option>
                     <option value="12">12</option>
                     <option value="13">13</option>
                     <option value="14">14</option>
                     <option value="15">15</option>
                     <option value="16">16</option>
                     <option value="17">17</option>
                     <option value="18">18</option>
                     <option value="19">19</option>
                     <option value="20">20</option>
                     <option value="21">21</option>
                     <option value="22">22</option>
                     <option value="23">23</option>
                     <option value="24">24</option>
                     <option value="25">25</option>
                     <option value="26">26</option>
                     <option value="27">27</option>
                     <option value="28">28</option>
                     <option value="29">29</option>
                     <option value="30">30</option>
                     <option value="31">31</option>
                   </select>
              </div></td>
           <td width="5%" bgcolor="#E6E6E6" class="style13">
              <div align="center">
                   <select name="mes" class="style6">
                     <option value="01">Jan</option>
                     <option value="02">Fev</option>
                     <option value="03">Mar</option>
                     <option value="04">Abr</option>
                     <option value="05">Mai</option>
                     <option value="06">Jun</option>
                     <option value="07">Jul</option>
                     <option value="08">Ago</option>
                     <option value="09">Set</option>
                     <option value="10">Out</option>
                     <option value="11">Nov</option>
                     <option value="12" selected>Dez</option>
                   </select>
              </div></td>
           <td width="6%" bgcolor="#E6E6E6" class="style13"><select name="ano" class="style6" id="ano">
             <option value="2010" selected>2010</option>
             <option value="2011">2011</option>
                                            </select></td>
           <td width="12%" bgcolor="#E6E6E6" class="style13">
              <div align="center">
                <input name="Submit" type="submit" class="style6" value="Consultar">
              </div></td>
         </tr>
       </table></td>
     </tr>
   </table>
    <span class="style6">    </span>
 </form>
  <table width="100%"  border="0" cellspacing="0" cellpadding="0">
   <tr>
     <td class="style6">Agenda do Dia: <?  echo "$Dataf " ;  ?>
	   <? 
	 include "config.php";
	 $top10 = mssql_query("SELECT  convert(char,Data,103) as Data,
	 					Substring(Substring(Ltrim(Rtrim(Convert(char, Hora) )), 1, Len(Hora) - 2) +
		               ':' + Right( Ltrim(RTrim(Convert(char, Hora) )), 2),1,6) as HoraInicio,
	            		Substring(Substring(Ltrim(Rtrim(Convert(char, Hora_Fim) )), 1, Len(Hora_Fim) - 2) +
		               ':' + Right( Ltrim(RTrim(Convert(char, Hora_Fim) )), 2),1,6) as HoraTermino,
	 					Assunto,
						Nom_Completo 
from			agenda..Agenda_Zamboni as A (nolock)
join			agenda..Proprietarios_Agenda as P (nolock)
on				a.Proprietario = p.Cod_Proprietario
where			p.Usuario_Login = '$login'
And				a.Status = 'A'
and                     a.Data = '$Date'
order by 		hora") ;
	 
	 $pinta = mssql_query("select top 1000 row_number() over (order by data)  as linha from            agenda..agenda_zamboni (nolock)");


$bgcolor_topo="#BF000A";
echo "<font face='verdana' size='1'><table width='100%'  border='0' cellspacing='1' cellpadding='1'>
       <tr>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='1' color='#FFFFFF'>Data</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='1' color='#FFFFFF'>Hora Inicio</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='1' color='#FFFFFF'>Hora Fim</center></font></td>
        <td bgcolor=$bgcolor_topo height='20'><center><font face='verdana' size='1' color='#FFFFFF'>Assunto</font></center></td>";
while ($dados = mssql_fetch_row($top10)) {

$pintando = mssql_fetch_row($pinta);

//pintando linha
$numero = $pintando[0];
$resultado = ($numero % 2) ? '1' : '2';
if ($resultado == '1'){
$bgcolor="#CCCCCC";
} else {
$bgcolor="#FFFFFF";
}


echo "<tr>
        <td bgcolor=$bgcolor><center><font face='verdana' size='1'>" .$dados[0]. "</center></font></td>
        <td bgcolor=$bgcolor><right>
        <div align='right'><center><font face='verdana' size='1'>" .$dados[1]. "</center></font></div></td>
        <td bgcolor=$bgcolor><center><font face='verdana' size='1'>" .$dados[2]. "</center></font></td>
        <td bgcolor=$bgcolor><left><font face='verdana' size='1'>" .$dados[3]. "</font></left></td>";



}
echo "</table></font>";
	 
	 ?>	&nbsp;<a href="index.php">Sair </a></td>
   </tr>
 </table>
</body>
</html>
