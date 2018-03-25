<?php
class TServidoresUpdate 
{
    //Variáveis
    var $status     = "D";
    var $militares  = array();
    var $opm        = array();
    
    
/*-------------------------------------------------------------------------------
 *        Função Construtora
 *-------------------------------------------------------------------------------*/
    public function __construct()
    {
        set_time_limit (3600);
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Cria array com os RGs que serão atualizados
 *-------------------------------------------------------------------------------*/
    public function getMilitares ($param=null)
    {
        try 
        {
            TTransaction::open('sicad');
            $conn = TTransaction::get();
            $sql = "SELECT id,rgmilitar FROM efetivo.servidor";
            $sql.= ($this->opm!='') ? " WHERE unidadeid = ".$this->opm : '';
            if ($status == 'A')
            {
                $sql.= ($this->opm!='') ? " AND " : " WHERE ";
                $sql.= " status = 'ATIVO' ";
            }
            elseif ($this->status=='I')
            {
                $sql.= ($this->opm!='') ? " AND " : " WHERE ";
                $sql.= " status != 'ATIVO' ";
            }  
            $pms = $conn->prepare($sql);
            $pms->execute();
            $results = $pms->fetchAll();
            TTransaction::close();
            $ppmms = array();
            foreach ($results as $result)
            {
                $ppmms[] = array('id'=>$result['id'],'rgmilitar'=>$result['rgmilitar']);
            }
            $this->militares = $ppmms;
            return true;
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar Militares..."); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            $this->militares = array();
            return false;
        }   
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Busca os dados dos militares para atualizar
 *-------------------------------------------------------------------------------*/
    public function upDatePms ($param=null)
    {
        $report = new TRelatorioOP();
        $CI = new TSicadDados();
        $militares = $this->militares;
        $report->mSucesso = ' Não foi atualizado.';
        $report->mFalha   = ' Foi devidadmente atualizado.';
        $conta = 0;
        $tempoInicio = microtime(true);
        //print_r($militares);//return;
        foreach ($militares as $militar)
        {
            if (is_array($militar))//Dado não pode ser vazio
            {
                //echo $militar['rgmilitar'];
                $cadastro = $CI->dados_servidor($militar['rgmilitar']);
                //print_r($cadastro);
                if (!$cadastro || empty($cadastro))
                {
                    $report->addMensagem('- O PM RG '.$militar['rgmilitar'],false);
                }
                else
                {
                    try 
                    {
                        TTransaction::open('sicad');
                        //Transfere os dados
                        $object = new servidor($militar['id']);  // get a object
                        $cadastro = array_change_key_case($cadastro,CASE_LOWER);//Converte a Key da array para caixa baixa
                        $cadastro['dtnascimento']   = $CI->time_To_Date_SICAD($cadastro['dtnascimento']);
                        $cadastro['dtexpedicaocnh'] = $CI->time_To_Date_SICAD($cadastro['dtexpedicaocnh']);
                        $cadastro['dtvalidadecnh']  = $CI->time_To_Date_SICAD($cadastro['dtvalidadecnh']);
                        $cadastro['dtpromocao']     = $CI->time_To_Date_SICAD($cadastro['dtpromocao']);
                        //Campos distoados
                        $cadastro['orgaoexpedicaorg']=$cadastro['orgaoexpediçãorg'];
                        $cadastro['ufexpedicaorg']=$cadastro['ufexpediçãorg'];
                        $object->fromArray( (array) $cadastro); // load the object with data
                        $dependentes = $cadastro['dependentes'];
                        if ($dependentes)
                        {
                            foreach($dependentes as $dependente)
                            {
                                if ($dependente)
                                {
                                    $filho = new dependente();
                                    $dependente = array_change_key_case($dependente,CASE_LOWER);
                                    $filho->fromArray( (array) $dependente);
                                    $filho->servidor_id = $object->id;
                                    $filho->boletiminclusao = self::boletim($dependente['boletiminclusao']);
                                    $filho->boletimexclusao = self::boletim($dependente['boletimexclusao']);
                                    $filho->dtnascimento = $CI->time_To_Date_SICAD($dependente['dtnascimento']);
                                    $object->addDependente($filho);
                                }
                            }
                        }
                        if ($cadastro['endereco'])
                        {
                            $endereco = array_change_key_case($cadastro['endereco'],CASE_LOWER);//Carregas os dados de endereço
                            $object->logradouro   = $endereco['logradouro'];
                            $object->numero       = $endereco['numero'];
                            $object->quadra       = $endereco['quadra'];
                            $object->lote         = $endereco['lote'];
                            $object->complemento  = $endereco['complemento'];
                            $object->bairro       = $endereco['bairro'];
                            $object->codbairro    = $endereco['codbairro'];
                            $object->municipio    = $endereco['municipio'];
                            $object->codmunicipio = $endereco['codmunicipio'];
                            $object->uf           = $endereco['estado'];
                            $object->cep          = $endereco['cep'];
                        }
                        $object->store();
                        TTransaction::close();
                        $conta ++;
                    }
                    catch (Exception $e) // in case of exception
                    {
                        new TMessage('error', $e->getMessage()."<br>Erro ao atualizar dados."); // shows the exception error message
                        $report->addMensagem('- O PM RG '.$militar['rgmilitar'],false);
                        TTransaction::rollback(); // undo all pending operations
                    }
                }
            }
            if ((int)($conta/2000)==($conta/2000))
            {
                new TMessage('info',$conta.' Atualizados...<br>Tecle OK para prosseguir');
                
                
            }
        }//Fim Foreach
        $fer = new TFerramentas;
        $report->addMensagem('Foram atualizado '.$conta.' Militares',null);
        $tempoFinal = microtime(true);
        $tempoGasto = $tempoFinal - $tempoInicio;
        $report->addMensagem("Tempo Estimado Gasto na Operação de ".$fer->tempo_descrito($tempoGasto),null);
        $report->publicaRelatorio('info');
    }// Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Retorno Boletim
 *------------------------------------------------------------------------------*/
    public function boletim ($param)
    {
        if (is_array($param))
        {
            $ci = new TSicadDados();
            $bol = ($param['numero'])  ? $param['numero']    : '';
            $ano = ($param['ano'])     ? $param['ano']       : '';
            $opm = ($param['unidade']) ? $param['unidade']   : '';
            $tip = ($param['tipo'])    ? $param['tipo']      : '';
            $dat = ($param['data'])    ? $ci->time_To_Date_SICAD($param['data'])  : '';
            return 'BOL('.$tip.') nº'.$bol.'/'.$ano.'-'.$opm.' de '.$dat;
        }
        else
        {
            return '';
        }
       
    }//Fim Módulo
}//Fim Classe
