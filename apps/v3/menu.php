<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
$aux = time();
$senha = substr(md5($aux),0,10);
$test = $senha;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Untitled Document</title>
<style type="text/css">
<!--
.style2 {        font-size: 10px;
        font-family: Verdana, Arial, Helvetica, sans-serif;
}
.style7 {font-size: 10px}
.style8 {font-size: 10px; font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; }
.style9 {
	font-size: 12px;
	font-family: Verdana, Arial, Helvetica, sans-serif;
}
-->
</style>
</head>

<body>
<table width='100%'  border='0' cellpadding='1' cellspacing='0'>
  <tr>
    <td width='27%' height='20' bgcolor='#BF000A'><div align='center' class='style2'><strong><font size="2">Pesquisa: </font></strong></div></td>
  </tr>
  <tr>
    <td height='20' bgcolor='#FFFFFF'><div align='center'><span class='style2'>&nbsp;&nbsp;Data Inicial:</span> </div></td>
  </tr>
  <tr>
    <td bgcolor='#FFFFFF'><div align='center'><span class='style2'>
        <select name='dat1'>
          <option selected>Dia</option>
          <option value='01'>01</option>
          <option value='02'>02</option>
          <option value='03'>03</option>
          <option value='04'>04</option>
          <option value='05'>05</option>
          <option value='06'>06</option>
          <option value='07'>07</option>
          <option value='08'>08</option>
          <option value='09'>09</option>
          <option value='10'>10</option>
          <option value='11'>11</option>
          <option value='12'>12</option>
          <option value='13'>13</option>
          <option value='14'>14</option>
          <option value='15'>15</option>
          <option value='16'>16</option>
          <option value='17'>17</option>
          <option value='18'>18</option>
          <option value='19'>19</option>
          <option value='20'>20</option>
          <option value='21'>21</option>
          <option value='22'>22</option>
          <option value='23'>23</option>
          <option value='24'>24</option>
          <option value='25'>25</option>
          <option value='26'>26</option>
          <option value='27'>27</option>
          <option value='28'>28</option>
          <option value='29'>29</option>
          <option value='30'>30</option>
          <option value='31'>31</option>
        </select>
        /
        <select name='dat2'>
          <option selected>M&ecirc;s</option>
          <option value='01'>01</option>
          <option value='02'>02</option>
          <option value='03'>03</option>
          <option value='04'>04</option>
          <option value='05'>05</option>
          <option value='06'>06</option>
          <option value='07'>07</option>
          <option value='08'>08</option>
          <option value='09'>09</option>
          <option value='10'>10</option>
          <option value='11'>11</option>
          <option value='12'>12</option>
        </select>
        /
        <select name='dat3'>
          <option selected>Ano</option>
		  <option value="2018">2018</option>
        </select>
    </span></div></td>
  </tr>
  <tr>
    <td height='20' bgcolor='#FFFFFF'><div align='center' class='style2'>Data Final: </div></td>
  </tr>
  <tr>
    <td bgcolor='#FFFFFF'><div align='center'><span class='style2'>
        <select name='dat4'>
          <option selected>Dia</option>
          <option value='01'>01</option>
          <option value='02'>02</option>
          <option value='03'>03</option>
          <option value='04'>04</option>
          <option value='05'>05</option>
          <option value='06'>06</option>
          <option value='07'>07</option>
          <option value='08'>08</option>
          <option value='09'>09</option>
          <option value='10'>10</option>
          <option value='11'>11</option>
          <option value='12'>12</option>
          <option value='13'>13</option>
          <option value='14'>14</option>
          <option value='15'>15</option>
          <option value='16'>16</option>
          <option value='17'>17</option>
          <option value='18'>18</option>
          <option value='19'>19</option>
          <option value='20'>20</option>
          <option value='21'>21</option>
          <option value='22'>22</option>
          <option value='23'>23</option>
          <option value='24'>24</option>
          <option value='25'>25</option>
          <option value='26'>26</option>
          <option value='27'>27</option>
          <option value='28'>28</option>
          <option value='29'>29</option>
          <option value='30'>30</option>
          <option value='31'>31</option>
        </select>
        /
        <select name='dat5'>
          <option selected>M&ecirc;s</option>
          <option value='01'>01</option>
          <option value='02'>02</option>
          <option value='03'>03</option>
          <option value='04'>04</option>
          <option value='05'>05</option>
          <option value='06'>06</option>
          <option value='07'>07</option>
          <option value='08'>08</option>
          <option value='09'>09</option>
          <option value='10'>10</option>
          <option value='11'>11</option>
          <option value='12'>12</option>
        </select>
        /
        <select name='dat6'>
          <option selected>Ano</option>
<option value="2018">2018</option>
        </select>
		
		
    </span></div></td>
  </tr>
  <tr>
    <td height='30' bgcolor='#FFFFFF'><div align='center'>
	
		 <input type='hidden' name='cgc' value=<?  echo $cgc; ?>>
		  <input type='hidden' name='testar' value=<?  echo $test; ?>>
        <input name='consultar' type='submit' class='style7' value='Consultar'>
    </div></td>
  </tr>
  <tr>
    <td height='10' bgcolor='#FFFFFF'><div align='center'>
     
       
    
    </div></td>
  </tr>
  <tr>
    <td height='20' bgcolor='#BF000A'><div align='center' class="style2"><strong><font size="2">Utilit&aacute;rios</font></strong></div></td>
  </tr>
  <tr>
    <td height='50' bgcolor='#FFFFFF'><div align="left">      &nbsp;&nbsp;&nbsp;<a href='http://www.nfe.fazenda.gov.br/portal/listaSubMenu.aspx?Id=/fwLvLUSmU8=' target='_blank'><span class="style9">Visualizador Arquivo XML</span><br>
        </a>
        
         &nbsp;&nbsp;&nbsp;<a href='http://portalnfe.fazenda.mg.gov.br/consultas.html' target='_blank' class="style9">Sefaz MG</a><br>
  
         <span class="style9">&nbsp;&nbsp;&nbsp;</span><a href="http://www.sefaz.rs.gov.br/NFE/NFE-COM.aspx" target="_blank"><span class="style9">Sefaz RJ </span></a>&nbsp;<br>
    </div></td>
  </tr>
  <tr>
    <td height='20' bgcolor='#BF000A'><div align='center' class='style8'><font size="2">SAC </font></div></td>
  </tr>
  <tr>
    <td bgcolor='#FFFFFF'><div align="center"><span class='style2'><br>
      Interior: 0800 282 8111<br>
      <br>
        Grande Rio: 4002 8111 </span>
		<br><a href=http://servicos.zamboni.com.br/atendimento.php target=blank><center><img src=chat.png border =0></center></a>
		</div></td>
		
  </tr>
  <tr>
    <td height='0' bgcolor='#FFFFFF'>&nbsp;</td>
  </tr>
  <tr>
    <td bgcolor='#FFFFFF'>&nbsp;</td>
  </tr>
</table>
</body>
</html>
