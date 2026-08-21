<?
   if(!isset($_GET["app"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>
<?
ob_start();
session_start();
session_cache_expire(5); 
$nota   = $_GET["nota"];
$cgc    = $_GET["cnpj"];

$str = $nota;
$nfe = ltrim($str, '-0');



//$cliente = $_GET["cliente"];

$qry = mssql_query("SELECT DS_ARQUIVO_XML,NR_DOCUMENTO_FISCAL FROM sgc..nfe_nf (nolock) where NR_DOCUMENTO_FISCAL = $nfe and NR_CNPJ_CPF_DESTINO = $cgc");
$tipo = mssql_result($qry,0,"DS_ARQUIVO_XML");

while ($danfe = mssql_fetch_row($qry )){
$ndanfe = $danfe[1];
}

header("Content-Disposition: attachment; filename=zamboni_nfe_$ndanfe.xml");
echo $tipo;

/////////////////////////////////////////////////////////////
ob_end_flush();
session_destroy(); 
session_unset(); 
?>
