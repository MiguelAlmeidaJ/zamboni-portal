<?php

 
    //REMETENTE --> ESTE EMAIL TEM QUE SER VALIDO DO DOMINIO
     //====================================================
    $email_remetente = "naoresponda@zamboni.com.br"; // deve ser um email do seu dominio (ex: suaconta@seudominio.com.br)
    //====================================================
 
 
    //Configurações do email, ajustar conforme necessidade
    //====================================================
    $email_destinatario = "eder.chaves@zamboni.com.br"; // qualquer email pode receber os dados
    $email_reply = "eder.alem@gmail.combr";
    $email_assunto = "Contato formmail";
    //====================================================
 
 
    //Variaveis de POST, Alterar somente se necessário
    //====================================================
    $nome = 'Eder'; //$_POST['nome'];
    $email = 'eder.chaves@zamboni.com.br'; //$_POST['email'];
    $telefone = '22222222'; //$_POST['telefone'];
     $mensagem = 'teste mensagem'; // $_POST['mensagem'];
    //====================================================
 
    //Monta o Corpo da Mensagem
    //====================================================
    $email_conteudo = "Nome = $nome \n"; 
    $email_conteudo .= "Email = $email \n"; 
    $email_conteudo .=  "Telefone = $telefone \n";
    $email_conteudo .=  "Mensagem = $mensagem \n";
     //====================================================
 
    //Seta os Headers (Alterar somente caso necessário)
    //====================================================
    $email_headers = implode ( "\n",array ( "From: $email_remetente", "Reply-To: $email_reply", "Subject: $email_assunto","Return-Path:  $email_remetente","MIME-Version: 1.0","X-Priority: 3","Content-Type: text/html; charset=UTF-8" ) );
    //====================================================
 
 
    //Enviando o email
    //====================================================
    mail ($email_destinatario, $email_assunto, nl2br($email_conteudo), $email_headers);
        echo "</b>E-Mail enviado com sucesso!</b>"; 
  
?>