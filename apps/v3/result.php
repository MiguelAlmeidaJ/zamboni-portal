<?

   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>

<?
$aux = time();
$senha = substr(md5($aux),0,10);
$test = $senha;

require_once('configuracao.php');  
$cgc = $_POST["cgc"];
$pagina = 'result.php';

$string = $cgc ;
$codificada = base64_encode($string);
//echo $codificada;



/*
if(isset($_GET["NOM_FANTAS"]))  //se estiver criado a variavel nao executa isso
{

	$NOM_FANTAS = $_GET["NOM_FANTAS"];
	if ( isset($_GET["cgc"]))
		{
		$CGC = $_GET["cgc"];
		$NOM_FANTAS = $_GET["NOM_FANTAS"];
		}


				 if ( isset($_GET["Cliente"]))
				 	{
					 //$cliente = $_GET["cliente"];
					 //$nf = $_GET["N_Fiscal"];
					 $consulta = 'N_Fiscal';
					 //$cliente  = $nf;
					}
				else
				 	{
					 $consulta = 'Cliente';
					 //$cliente = $_GET["Cliente"]; 
					 $CGC = $_GET["cgc"];
					 //$NOM_FANTAS = $_GET["NOM_FANTAS"];
					}
} /// fim
*/

$dat1 = $_POST["dat1"];
$dat2 = $_POST["dat2"];
$dat3 = $_POST["dat3"];
$dat4 = $_POST["dat4"];
$dat5 = $_POST["dat5"];
$dat6 = $_POST["dat6"];


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

<body >

<form action=$pagina method='post' name='result'>";
?>
<style type="text/css">
<!--
.style1 {font-size: 12}
-->
</style>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
//-->
</script>


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
              <td height='20'><div align='left'><span class='style1'> &nbsp;&nbsp;</span><span class='style5'>
              <? include "cumprimento.php ";
              
				?>
               </span></div></td>
              <td height='20'><div align="center"><a href="#" class="style1" onClick="MM_openBrWindow('<? echo "cadastro_email.php?cgc=$codificada&testar=$test"; ?>','','width=450,height=300')">Cadastre Aqui para Receber Xml em seu E-mail </a></div></td>
              <td width='74'><div align='right' class='style7'><a href='logout.php' class='style5'>Sair</a>&nbsp;&nbsp;</div></td>
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

          <table width='100%'  border='0' cellspacing='0' cellpadding='0'>
            <tr>
              <td><table width='100%'  border='0' cellspacing='0' cellpadding='1'>
                <tr>
                  <td width='75%' colspan='2' valign='top' bgcolor='#F4F4F4'><div align='left'>
                    <table width='100%'  border='0' cellspacing='2' cellpadding='2'>
                        <tr>
                          <td width="27%" valign="top"><div align='left'></div>                            <div align='left'>
						  </div>
                          <div align='left'>
                            <? require_once("menu.php"); ?>
                          </div></td>
                          <td width="73%" valign="top"><? require_once("top100.php"); ?></td>
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
</form>

</body>
</html>
