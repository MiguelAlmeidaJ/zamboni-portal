<?

   if(!isset($_GET["c"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>

<?
$aux = time();
$senha = substr(md5($aux),0,10);
$test = $senha;


$string = $_GET["c"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;

$string = $cgc ;
$codificada = base64_encode($string);
//echo $codificada;

require_once('configuracao.php');  



echo "<html>
<head>

<title>Portal NFe Zamboni</title>
<script language='JavaScript' type='text/JavaScript'>
<!--
function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+'.location=''+selObj.options[selObj.selectedIndex].value+''');
  if (restore) selObj.selectedIndex=0;
}
//-->
</script>
<style type='text/css'>
<!--
.style1 {
        font-size: 12px;
        font-weight: bold;
        font-family: Verdana, Arial, Helvetica, sans-serif;
}
.style2 {
        font-size: 10px;
        font-family: Verdana, Arial, Helvetica, sans-serif;
}
.style5 {
        font-family: Verdana, Arial, Helvetica, sans-serif;
        font-size: 12px;
}
.style7 {font-size: 10px}
a:link {
	text-decoration: none;
}
a:visited {
	text-decoration: none;
}
a:hover {
	text-decoration: underline;
}
a:active {
	text-decoration: none;
}
.style8 {font-size: 10px; font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; }
body {
	background-color: #CCCCCC;
}
-->
</style>
</head>

<body  >";
?>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
</script>
<style type="text/css">
<!--
.style1 {font-size: 12}
.style2 {font-size: 12pt; font-family: Verdana, Arial, Helvetica, sans-serif; }
-->
</style>




<table width='775'  border='1' align='center' cellpadding='0' cellspacing='0' bordercolor="#FFFFFF">
  <tr>
    <td><table width='100%'  border='0' cellspacing='0' cellpadding='0'>
      <tr>
        <td bgcolor="#FFFFFF"><? include "topo.php" ?></td>
      </tr>
      <tr>
        <td height='0' valign='top' bgcolor='#F4F4F4'><div align='center'>
          <table width='100%'  border='0' cellspacing='0' cellpadding='0'>
            <tr>
              <td height='20' colspan='2'><div align='left'><span class='style1'> &nbsp;&nbsp;</span><span class='style5'>
          
               </span></div></td>
              <td width='354'><div align='right' class='style7'>
               
              </div></td>
             
            </tr>
            <tr>
              <td width='262' bgcolor='#CCCCCC'>
            </tr>
            <tr>
              <td bgcolor='#CCCCCC'>
            </tr>
            <tr>
              <td bgcolor='#CCCCCC'>
            </tr>
            <tr>
              <td bgcolor='#CCCCCC'>
            </tr>
            <tr>
              <td bgcolor='#CCCCCC'>
            </tr>
            <tr>
              <td bgcolor='#CCCCCC'>
            </tr>
            <tr>
              <td bgcolor='#CCCCCC'>
            </tr>
          </table>
   <? 
   mssql_query("UPDATE sgc..cliente_xml SET status_confirmacao = 'S' WHERE cgc = '$cgc'");
    echo" <meta http-equiv='refresh' content='5;URL=index.php'>";
      ?>
    Processo confirmado!   
   <table width='100%'  border='0' cellspacing='0' cellpadding='0'>
            <tr>
              <td><table width='100%'  border='0' cellspacing='0' cellpadding='1'>
                <tr>
                  <td width='75%' colspan='2' valign='top' bgcolor='#F4F4F4'><div align='left'>
                    <table width='100%'  border='0' cellspacing='2' cellpadding='2'>
                        <tr><br>
						
                        </tr>
                      </table>
                    </div>                    </td>
                  </tr>
              </table></td>
            </tr>
          </table>
          </div></td>
      </tr>
    </table></td>
  </tr>
</table>
<div align="center">
<?    include "fim.php"; ?>
<br>
</div>


</body>
</html>
