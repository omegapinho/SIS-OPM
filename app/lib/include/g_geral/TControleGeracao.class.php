<?php
/*
 * TControleGeracao - Controla geração de Documentos/Relatórios válidos
 * Versão: 1.0
 * Date: 04/12/2017
 * Author: Fernando de Pinho Araújo <o.megapinho@gmail.com>
 * Changelog:
 * - Versão: 1.0 
 */
class TControleGeracao 
{
/*------------------------------------------------------------------------------
 *    Iniciando Variáveis
 *------------------------------------------------------------------------------*/
 
     public $mudanca = array();
     public $status  = array();
     public $usuario;
     public $dt_inicial;
	 public $motivo;

/*------------------------------------------------------------------------------
 *    Construtor da Classe
 *------------------------------------------------------------------------------*/
     public function __construct()
     {
        $this->mudanca = array(1=>'CRIAR',2=>'STATUS',3=>'USUARIO');
        $this->status  = array(1=>'GERADO',5=>'RETIFICADO',13=>'CANCELADO');
        $this->usuario = TSession::getValue('login');
        $this->dt_inicial = date('Y-m-d');
     }//Fim Módulo
/*------------------------------------------------------------------------------
 * Cria Numero de controle_aula
 * não precisa parametros retornado o próximo número disponível  
 *------------------------------------------------------------------------------*/
     public function geraControle ()
     {
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                
                $ctrl                 = new controle_geracao ();
                $ctrl->status         = 1;                 //Status 1   Gerado
                $ctrl->dt_inicial     = $this->dt_inicial; //Cria com a data atual
                $ctrl->usuario        = $this->usuario;    //Grava o usuário atual
                $ctrl->dt_atualizacao = $this->dt_inicial; //Como está criando, salva a mesma data atual
                $ctrl->store();                      //Salva Controle
                //Cria o primeiro item do historico
                $hst                      = new historico_geracao();
                $hst->dt_historico        = $this->dt_inicial;          //Data atual
                $hst->mudanca             = 1;                          //Tipo de mudança = criação
                $hst->valor_anterior      = '-';
                $hst->valor_atual         = '-';
                $hst->usuario_atualizador = $this->usuario;
                $hst->controle_geracao_id = $ctrl->id;
                $hst->motivo              = 'CRIAR';
                $hst->store();
                TTransaction::close(); // close the transaction

        }
        catch (Exception $e) // in case of exception 
        {
            TTransaction::rollback(); // undo all pending operations
            return false;            
        }
        return $ctrl->id;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Altera algo no Controle
 *    @ $key  = id do Controle a Mudar
 *    @ $tipo = O que será mudado
 *    @ $novo = Novo valor
 *------------------------------------------------------------------------------*/
     public function alteraControle ($key,$tipo, $novo)
     {
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                
                $ctrl                 = new controle_geracao ($key);
                switch ($tipo)
                {
                    case 2://Muda o Status
                        $velho = $ctrl->status;
                        $ctrl->status = $novo;
                        break;
                    case 3://Muda o Usuário
                        $velho = $ctrl->usuario;
                        $ctrl->usuario = $novo;
                        break;
                    case 4:
                        
                        break;
                }
                $ctrl->dt_atualizacao = $this->dt_inicial;         //Salva a data de atualização

                $ctrl->store();                                    //Salva Controle
                //Cria historico da alteração
                $hst                      = new historico_geracao();
                $hst->dt_historico        = $this->dt_inicial;          //Data atual
                $hst->mudanca             = $tipo;                      //Tipo de mudança = conforme pedido
                
                $hst->valor_anterior      = $velho;                     //Valor mudado
                $hst->valor_atual         = $novo;                      //Novo valor
                $hst->usuario_atualizador = $this->usuario;             //Quem estava operando a mudança
                $hst->controle_geracao_id = $ctrl->id;                  //Controle que foi alterado
                $hst->motivo              = $this->motivo;              //Acrescenta o motivo (se existir)



                $hst->store();
                TTransaction::close(); // close the transaction

        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            return false;            
        }
        return $ctrl->id;
     }//Fim Módulo
/*------------------------------------------------------------------------------
 * Pesquisa Numero de controle_aula
 * @ $param = id a pesquisar
 *------------------------------------------------------------------------------*/
     public function buscaControle ($param)
     {
        $ret = false;
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                
                $ctrl                 = new controle_geracao ($param);
                if (!empty($ctrl))
                {
                    $ret = ($ctrl->status != 13) ? true : false; //Verifica se há algo a cancelar ainda
                }
                TTransaction::close(); // close the transaction

        }
        catch (Exception $e) // in case of exception 
        {
            TTransaction::rollback(); // undo all pending operations
        }
        return $ret;
     }//Fim Módulo
}//Fim Classe

