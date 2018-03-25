<?php
/*
 * TRelatorioOP - Cria relatório de operações para ser mostrado em mensagens
 * Versão: 1.0
 * Date: 14/04/2017
 * Author: Fernando de Pinho Araújo <o.megapinho@gmail.com>
 * Changelog:
 * - Versão: 1.0 
 */
class TRelatorioOP 
{
/*------------------------------------------------------------------------------
 *    Iniciando Variáveis
 *------------------------------------------------------------------------------*/
 
     public $mSucesso;
     public $mFalha;
     
     public $corSucesso;
     public $corFalha;
     
     protected $mensagem ;
     protected $mCount;
/*------------------------------------------------------------------------------
 *    Construtor da Classe
 *------------------------------------------------------------------------------*/
     public function __construct()
     {
        $this->mSucesso = " Sucesso na Operação";
        $this->mFalha   = " Falha na Operação";
         
        $this->corSucesso = "green";
        $this->corFalha   = "red";
        $this->mensagem   = array();
        $this->mCount     = 0;  
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Adiciona Mensagem
 *------------------------------------------------------------------------------*/
     public function addMensagem ($texto, $status=TRUE)
     {
         if ($status!=null)
         {
             $cor = ($status) ? $this->corSucesso : $this->corFalha;
             $txt = ($status) ? $this->mSucesso   : $this->mFalha;
             $ret = $texto.' <strong><font color="'.$cor.'">'.$txt.'</font></strong>';
         }
         else
         {
             $ret = $texto;
         }
         $this->mensagem[]=$ret;
         $this->mCount++;
         
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Publica Mensagem
 *    @ $mostra = false (retorna a variavel $show)
 *    @         = info ou error (muda o tipo de mensagem)
 *------------------------------------------------------------------------------*/
     public function publicaRelatorio ($mostra = false)
     {
         $rels = $this->mensagem;
         $show = '';
         foreach ($rels as $rel)
         {
             if (!empty($show))
             {
                 $show.="<br>";
             }
             $show.= $rel;
         }
         if ($mostra)
         {
             new TMessage ($mostra,$show);
         }
         else
         {
             return $show;
         }
     }//Fim Módulo
 
 

    
}//Fim Classe

