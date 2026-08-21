<?
   if(!isset($_COOKIE["zamboni"]))   // a trava para verificar se passou ou nao pelo link certo esta sendo feita aqui
   {
   header("Location: index.php");  // redireciona para o site original
   exit;
    }
?>
<?

function anti_injection($sql)
{
// remove palavras que contenham sintaxe sql
$seg = preg_replace(sql_regcase("/(from|select|insert|delete|where|drop table|show tables|#|\*|--|\\\\)/"),"",$sql);
$seg = trim($seg);//limpa espaços vazio
$seg = strip_tags($seg);//tira tags html e php
$seg = addslashes($seg);//Adiciona barras invertidas a uma string
return $seg;

}

$string = $_GET["M"] ;
$decodificadacpf = base64_decode($string);
$cgc = $decodificadacpf;
$tabela = 'sgc..nfe_novo';

ob_start();
session_start();
session_cache_expire(5); 
$nota   = $_GET["nota"];
//$cgc    = $_GET["cgc"];

//$cliente = $_GET["cliente"];

$qry = mssql_query("SELECT XML,NUMERO_DOCUMENTO FROM sgc..nfe_novo (nolock) where NUMERO_DOCUMENTO = $nota and CNPJ_CPF_DESTINATARIO = $cgc");
$tipo = mssql_result($qry,0,"XML");

while ($danfe = mssql_fetch_row($qry )){
$ndanfe = $danfe[1];
}

header("Content-Disposition: attachment; filename=nfe_$ndanfe.xml");
echo $tipo;

/////////////////////////////////////////////////////////////
ob_end_flush();
session_destroy(); 
session_unset(); 
?>
