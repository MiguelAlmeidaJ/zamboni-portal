<?php
include_once('config/session.php');
include_once('config/ddsAcesso.php');

$dds = isset($_GET["hsh"]) ? explode('#', deCript($_GET["hsh"],COBI_KEY)) : array();
$dds = array_map('trim', $dds);
if(count($dds) < 3 || !preg_match('/^[0-9A-Z]+$/i', $dds[0]) || !preg_match('/^[0-9]{1,3}$/', $dds[1]) || !preg_match('/^[A-Z]{4}$/', $dds[2])){
	logFalha('NULL', 'Tentou acessar boleto com dados invalidos.');
	header('Location: index.php');
	exit;
}


if($dds[1] != NULL)
{
	if(isset($_SESSION['ZA_ACSS']) && $_SESSION['ZA_ACSS'] == 'acss')
	{
		//30-05-2022 - Aprimorar sistema de Boletos
	    /*
		[237] -> Bradesco - RED SA - TradeMaster
		[399] -> HSBC
		[001] -> Banco do Brasil
		[341] -> Itaú
		[104] -> Caixa
		[745] -> Citibank
		[033] -> Santander
		[655] -> Banco Votorantim
		[707] -> Daycoval
		[756] -> Sicoob
		
		// 08/08/2024 - Adição de novos layouts
		[237] -> Bradesco/C6 e Bradesco/Sifra
		[422] -> Safra
		[336] -> C6
		[033] -> Santander/Sofisa
		[318] -> BMG (não testado)
		
		*/
		
		
		// Bancos com convênio nas duas empresas
		$bcos_comuns = array('001', '1', '237', '341', '104', '655', '033', '336', '422', '707', '756');
		// Bancos com convêncio só na Zamboni
		$bcos_sozamb = array('399', '745');
		// Bancos com convêncio só na Mixcerto
		$bcos_somixc = array('707', '756');
		// As empresas
		$empresas = array('MIXC', 'ZAMB');
		
		
		// Tenta acessar as empresas e os bancos comuns, se não der
		if(in_array($dds[2], $empresas) && in_array($dds[1], $bcos_comuns)){

			switch($dds[1])
			{
				case '001':
				  case '1':
						if(strlen($dds[0]) == 17)
						{
							$conv = substr($dds[0], 0, 7);
						}
				 // $imgobs ='obs_brasil_'. $conv .'-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobBrasil.php';
				  break;

				  case '237':
				 // $imgobs ='obs_bra_237-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobBradesco.php';
				  break;

				  case '104':
				 // $imgobs = 'obs_cx_104-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobCaixa.php';
				  break;
					
				  case '341':
				  //$imgobs = 'obs_it_341-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobItau.php';
				  break;
					
				  case '655':
				  //$imgobs = 'obs_vot_655-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobVotorantim.php';
				  break;
					
				  case '033':
				  //$imgobs = 'obs_sant_033-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobSantander.php';
				  break;

				  case '707':
				  $imgobs ='';
				  $file = 'cobDaycoval.php';
				  break;

				  case '756':
				  $imgobs = '';
				  $file = 'cobSicoob.php';
				  break;
				
				  case '422':
				  $imgobs = NULL;
				  $file = 'cobSafra.php';
				  break;

					
				  case '336':
				  $imgobs = NULL;
				  $file = 'cobC6.php';
				  break;

				  default:
				  header('Location: index.php');
			}
			
		// Tenta acessar a ZAMB e os bancos só da ZAMB	
		}elseif($dds[2] == 'ZAMB' && in_array($dds[1], $bcos_sozamb)){
			
			switch($dds[1])
			{
				  case '399':
				  //$imgobs ='obs_hsb_399-'.$dds[2].'.png';
				  $imgobs = NULL;
				  $file = 'cobHSBC.php';
				  break;

				  case '745':
				  $imgobs = '';
				  $file = 'cobCiti.php';
				  break;

				  default:
				  header('Location: index.php');
			}
		
		// Tenta acessar a MIXC e os bancos só da MIXC
		}elseif($dds[2] == 'MIXC' && in_array($dds[1], $bcos_somixc)){
			
			switch($dds[1])
			{
				  case '707':
				  $imgobs ='';
				  $file = 'cobDaycoval.php';
				  break;

				  case '756':
				  $imgobs = '';
				  $file = 'cobSicoob.php';
				  break;

				  default:
				  header('Location: index.php');
			}
		
		}else{
			header('Location: index.php');
		}
		
		$local = DIR_SITE_BASE .'include/'. $file;
		
		
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Zamboni Comercial Ltda.</title>
<script src="js/jquery.js" type="text/javascript"></script>
<script type="text/javascript">
$(function(){
	$('#Load').show();
	$(window).load(function(){	
		$.ajax({
			url: '<?= $local; ?>',
			dataType: "html",
			data: {hsh: '<?= $_GET["hsh"]; ?>', iobs: '<?= $imgobs; ?>'},
			type: "GET",
			complete: function(){
				$('#Load').hide();
			},
			success: function(data, texStatus){
				console.log('data');
				$('#Tela').html(data);
			},
			error: function(xhr, er){
				$('#Tela').html('<p>Error '+ xhr.status +' - '+ xhr.statusText +'<br />Tipo de erro: '+ er +'</p>');
			}			
		});
	});
});

</script>
</head>
<body>
<style type="text/css" media="all">
#Load{display: none; height:300px; width: 800px; margin:0 auto; text-align:center;padding:180px 0 0; font-family:Arial, sans-serif; font-size:14px; color:#333;}
#Load img{float:left;}
#Load div{width:200px; margin:0 auto;}
#Load p{ font-family:Arial, sans-serif; font-size:14px; color:#333; width: 180px; margin-left: 15px; padding-top: 5px;}
</style>
<style type="text/css" media="all">
	#Tela{width: 670px; margin: 0 auto; /*height:200px; background-color:#CCC;*/}
</style>
<div id="Load">
	<div><img src="img/load.gif" alt="Carregando..." title="Carregando..." /><p>Carregando o T&iacute;tulo...</p></div>
</div>
<div id="Tela"></div>
</body>
</html>
<?php 
	}
	else
    {
		logFalha('NULL', 'Tentou acessar boleto sem o CNPJ/CPF e senha.');
        header('Location: index.php');
	}
}
else
{
	logFalha('NULL', 'Tentou acessar boleto sem os dados do documento.');
	header('Location: index.php');
}
?>


