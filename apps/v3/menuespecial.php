<?


    if (!isset($_GET["cgc"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
     {
    header("Location: index.php");  // redireciona para o site original
    exit;
     }
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
.style11 {font-family: Verdana, Arial, Helvetica, sans-serif}
-->
</style>
</head>

<body>
<table width='100%'  border='0' cellpadding='1' cellspacing='0'>
  <tr>
    <td width='27%' height='20' colspan="2" bgcolor='#BF000A'><div align='center' class='style2'><strong><font size="2">Pesquisa: </font></strong></div></td>
  </tr>
  <tr>
    <td height='20' colspan="2" bgcolor='#FFFFFF'><div align='center'><span class='style2'>&nbsp;&nbsp;Data Inicial:</span> </div></td>
  </tr>
  <tr>
    <td colspan="2" bgcolor='#FFFFFF'><div align='center'><span class='style2'>
        <select name='dat1'>
          <option value="0" selected>Dia</option>
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
          <option value='2009'>2009</option>
          <option value='2010'>2010</option>
        </select>
    </span></div></td>
  </tr>
  <tr>
    <td height='20' colspan="2" bgcolor='#FFFFFF'><div align='center' class='style2'>Data Final: </div></td>
  </tr>
  <tr>
    <td colspan="2" bgcolor='#FFFFFF'><div align='center'><span class='style2'>
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
          <option value='2009'>2009</option>
          <option value='2010'>2010</option>
        </select>
    </span></div></td>
  </tr>
  <tr>
    <td height='30' colspan="2" bgcolor='#FFFFFF'><div align='center'>
        <input name='consultar' type='submit' class='style7' value='Consultar'>
    </div></td>
  </tr>
  <tr>
    <td height='5' colspan="2" bgcolor='#BD0008'><div align='center'><span class='style11'><strong><font size="2">Baixar outro Cnpj</font></strong></span>
      
     
    </div></td>
  </tr>
  <tr>
    <td height='5' bgcolor='#FFFFFF'><div align="right"><span class='style2'>
        <select name='cnpjlist' id="cnpjlist">
          <option selected>Selecione outro Cnpj</option>
          <option value="04779685000177">04779685000177</option>
          <option value="04779685000258">04779685000258</option>
          <option value="04779685000339">04779685000339</option>
          <option value="04779685000410">04779685000410</option>
          <option value="04779685000509">04779685000509</option>
          <option value="04779685000681">04779685000681</option>
          <option value="04779685000762">04779685000762</option>
          <option value="04779685000924">04779685000924</option>
          <option value="04779685001068">04779685001068</option>
          <option value="04779685001149">04779685001149</option>
          <option value="04779685001220">04779685001220</option>
          <option value="04779685003001">04779685003001</option>
          <option value="04779685002897">04779685002897</option>
          <option value="04779685002978">04779685002978</option>
          <option value="04779685002030">04779685002030</option>
          <option value="04779685001300">04779685001300</option>
          <option value="04779685003192">04779685003192</option>
          <option value="04779685001491">04779685001491</option>
          <option value="04779685001572">04779685001572</option>
          <option value="04779685001904">04779685001904</option>
          <option value="04779685001653">04779685001653</option>
          <option value="04779685002200">04779685002200</option>
          <option value="04779685001815">04779685001815</option>
          <option value="04779685001734">04779685001734</option>
          <option value="04779685002463">04779685002463</option>
          <option value="04779685002110">04779685002110</option>
          <option value="04779685003273">04779685003273</option>
          <option value="04779685002625">04779685002625</option>
          <option value="04779685002706">04779685002706</option>
          <option value="04779685003354">04779685003354</option>
          <option value="04779685002382">04779685002382</option>
          <option value="04779685003435">04779685003435</option>
          <option value="04779685003605">04779685003605</option>
          <option value="04779685002544">04779685002544</option>
          <option value="04779685003516">04779685003516</option>
          <option value="04779685000843">04779685000843</option>
        </select>
    </span></div></td>
    <td height="30" bgcolor='#FFFFFF'><span class='style2'>
      <input name='consultarc' type='submit' class='style7' value='Consultar'>
    </span></td>
  </tr>
  <tr>
    <td height='20' colspan="2" bgcolor='#BF000A'><div align='center' class="style2"><strong><font size="2">Utilit&aacute;rios</font></strong></div></td>
  </tr>
  <tr>
    <td height='50' colspan="2" bgcolor='#FFFFFF'><div align="left">      &nbsp;&nbsp;&nbsp;<a href='http://www.nfe.fazenda.gov.br/portal/visualizador.aspx' target='_blank'><span class="style9">Visualizador Arquivo XML</span><br>
        </a>
        
         &nbsp;&nbsp;&nbsp;<a href='http://nfe.fazenda.mg.gov.br/consulta/' target='_blank' class="style9">Sefaz MG</a><br>
  
         <span class="style9">&nbsp;&nbsp;&nbsp;</span><a href="http://www.sefaz.rs.gov.br/NFE/NFE-COM.aspx" target="_blank"><span class="style9">Sefaz RJ </span></a>&nbsp;<br>
    </div></td>
  </tr>
  <tr>
    <td height='20' colspan="2" bgcolor='#BF000A'><div align='center' class='style8'><font size="2">SAC Zamboni</font></div></td>
  </tr>
  <tr>
    <td colspan="2" bgcolor='#FFFFFF'><div align="center"><span class='style2'><br>
      Interior: 0800 282 8111<br>
      <br>
        Grande Rio: 4002 8111 </span></div></td>
  </tr>
  <tr>
    <td height='0' colspan="2" bgcolor='#FFFFFF'>&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" bgcolor='#FFFFFF'>&nbsp;</td>
  </tr>
</table>
</body>
</html>
