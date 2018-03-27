<?php
class TSisacadFinanceiroReport
{
//Declaração de Variáveis
    protected $cabecalho;
    protected $corpo;
    protected $valores;

    public $dt_inicio;
    public $dt_fim;
    public $cpf;             //CPFs que serão Relatados
    public $assinatura;      //Nome de quem abriu o relatório
    public $orgao;           //Órgão destinado
    public $tipo = 1;        //Tipo de Relatório que será feito
    public $valor_maximo;    //Valor máximo que será pago por Pessoa
    public $aula_validada;   //Ativa/Desativa o filtro para aulas validadas
    public $executa_gravacao;//Grava o pagamento liberando para próxima folha.
    public $mes_ref;
    public $ano_ref;
    public $controle_geracao;//Controle de Geração de Relatório
    public $numero_ctrl;     //Controle de Geração de Relatório (estorno)
    public $retificado;      //Se corrigir só alguns professores será tipo retificar
    public $motivo;          //Para quando se corrige.

/*-------------------------------------------------------------------------------
 *        Função Construtora
 *-------------------------------------------------------------------------------*/
    public function __construct()
    {
        $this->valores = array();
        $this->cabecalho = '';
        $this->corpo     = '';
        $this->controle_geracao = 'VIEW';

        //set_time_limit (3600);
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Busca Professores que tenham saldo ou que serão estornados
 *        retorna array com os dados para professor (id,nome,postgrad e cpf)
 *        poderá ter data de início e/ou data fim
 *        poderá ser de todos Professores com vínculo a um órgão específico ou
 *        individual
 *-------------------------------------------------------------------------------*/
     public function get_ListaProfessores($param = array())
     {
        $campos = 'professor.id,professor.nome,professor.cpf,professor.quadro,orgaosorigem.sigla as orgaosorigem,postograd.sigla as postograd';
        if ($this->tipo == 1)//Relatório de Pagamento de Saldos
        {
            $query =  'SELECT DISTINCT professor_id FROM sisacad.professorcontrole_aula '.
                      'WHERE aulas_saldo > 0 AND aulas_pagas != aulas_saldo ';
            if ($this->aula_validada == 'S')
            {
                $query .= " AND validado ='S' "; 
            }
            if (!empty($param['dt_inicio']) && empty($param['dt_fim']))
            {
                $query .= " AND data_aula >='" . $param['dt_inicio'] . "'";
            }
            else if (empty($param['dt_inicio']) && !empty($param['dt_fim']))
            {
                $query .= " AND data_aula <='" . $param['dt_fim'] . "'";
            }
            else if (!empty($param['dt_inicio']) && !empty($param['dt_fim']))
            {
                $query .= " AND data_aula BETWEEN '" . $param['dt_inicio'] . "' AND '" . $param['dt_fim']  . "'";
            }
    
        }
        else if ($this->tipo ==2) //Relatório de Estorno de Saldos
        {
            $query =  "SELECT DISTINCT professor_id FROM sisacad.professorcontrole_aula ".
                      "WHERE historico_pagamento LIKE '%[CTR=".$this->numero_ctrl."!%'" ;
        }
        
        if(!empty($param['cpf']))
        {
            $dados = explode(',',$param['cpf']);
            $valores = "'" . implode("','",$dados) . "'";
            $valores  = (!empty($valores)) ? $valores : false;
        } 

        if (!empty($valores))
        {
            $query2 = 'SELECT DISTINCT ' . $campos . ' FROM sisacad.professor, g_geral.postograd, g_geral.orgaosorigem '.
                      'WHERE professor.id IN (' . $query . ") AND professor.cpf IN (" . $valores . ")" . 
                      ' AND professor.postograd_id = postograd.id AND professor.orgaosorigem_id = orgaosorigem.id';
        }
        else if (!empty($param['orgao']) && $this->tipo == 1)
        {
            $query2 = 'SELECT DISTINCT ' . $campos . ' FROM sisacad.professor, g_geral.postograd, g_geral.orgaosorigem '.
                      'WHERE professor.id IN (' . $query . ") AND professor.orgaosorigem_id = '" . $param['orgao'] . "'" .
                      ' AND professor.postograd_id = postograd.id AND professor.orgaosorigem_id = orgaosorigem.id';
        }
        else
        {
            $query2 = 'SELECT DISTINCT ' . $campos . ' FROM sisacad.professor, g_geral.postograd, g_geral.orgaosorigem '.
                      'WHERE professor.id IN (' . $query . ')' . ' AND professor.postograd_id = postograd.id AND professor.orgaosorigem_id = orgaosorigem.id';
        }
        $query2 .= ' ORDER BY professor.nome';
        $dados = $this->runQuery($query2);
        $this->cpf = $dados;
        return (!empty($dados)) ? true : false; // Só notifica se tem ou não professores com saldo/Restituição
     }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Executor de querys
  * @ $sql deve ser uma query preparada para ser aplicada
  *-------------------------------------------------------------------------------*/
     public function runQuery($sql)
     {
        try
        {
            TTransaction::open('sicad');
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $retorno = $res->fetchAll(PDO::FETCH_NAMED);
            TTransaction::close();
            return $retorno;
        }
        catch (Exception $e) 
        { 
            new TMessage('error', $e->getMessage().'<br>Erro ao buscar dados.<br>'.$sql); 
            TTransaction::rollback();
            return false;
        }
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Cabeçalho da PM para os relatórios
  *-------------------------------------------------------------------------------*/
     public function cabecalhoRelatorio()
     {
        $cmd = $this->scriptPrint();
        $r = $cmd['codigo']. "<div id='relatorio'>".
                                '<table width="90%"><tr><td width="10%">'.
                                ' <img src="app/images/pmgo_ico.gif" alt="LOGO PMGO" height="60" width="60"> '.
                                '<td width="80%">'.
                                "<center>ESTADO DE GOIÁS<br>".
                                "SECRETARIA DE ESTADO DA SEGURANÇA PÚBLICA E ADMINISTRAÇÃO PENITENCIÁRIA<br>".
                                "POLÍCIA MILITAR DO ESTADO DE GOIÁS<br>".
                                "<strong>COMANDO DA ACADEMIA DE POLÍCIA MILITAR</strong><br><center>".
                                '<td width="10%">'.
                                ' <img src="app/images/pmgo_ico.gif" alt="LOGO PMGO" height="60" width="60"> '.
                                '</td></tr></table>';
        $this->cabecalho = $r;
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Cabeçalho da tabela Saldos
  *-------------------------------------------------------------------------------*/
     public function cabecalhoSaldo()
     {
        $ano = $this->ano;
        $mes = $this->mes;
        $cmd = $this->scriptPrint();
        $r = $cmd['codigo']. "<div id='relatorio'>
                                <center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS<br><br>".
                                "RELATÓRIO DE SALDO MENSAL<br>".
                                "<br><strong>".strtoupper($this->unidade)."</strong></center><br>".
                                "<center><table border=1 cellspacing=0 cellpadding=1 style='font-size:10px; color:#000;'>";
        $this->cabecalho = $r;
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        título da tabela
  * @$param['mes'] = mês do relatório
  * @$cor          = cor de fundo do título
  *-------------------------------------------------------------------------------*/
     public function titulosParaRelatorio($param = null , $cor = 'ddd')
     {
        $r  = '<table border=1 cellspacing=0 cellpadding=1 style="font-size:10px; color:#000;">';
        $r .= '<thead>';
        $r .= '<tr bgcolor="#'. $cor .'">';
        switch ($this->tipo)
        {
            case 1:
                $r .= '<td colspan="8"><center><font color="#CC3333"><strong>'.
                'PLANILHA SIMPLIFICADA DE HORAS/AULAS AC-2 MINISTRADAS NA ACADEMIA DE POLÍCIA MILITAR A SEREM INCLUÍDAS NA '.
                'FOLHA DE PAGAMENTO DO MÊS DE ' . mb_strtoupper($this->mes_ref,'UTF-8') . ' DE ' . $this->ano_ref.
                '<br>REFERÊNCIA Nº ' . $this->controle_geracao . '</strong></font></center></td>';
                break;
            case 2:
                $r .= '<td colspan="8"><center><font color="#CC3333"><strong>'.
                'PLANILHA SIMPLIFICADA DE HORAS/AULAS AC-2 MINISTRADAS NA ACADEMIA DE POLÍCIA MILITAR A SEREM ESTORNADAS. '.
                '<br>REFERÊNCIA Nº ' . $this->numero_ctrl . '</strong></font></center></td>';
                break;
            case 3:
                break;
            case 4:
                break;
            default:
            
                break;
        }
        $r .= '</tr></th>';
        return $r;
    }//Fim módulo
 /*-------------------------------------------------------------------------------
  *        tabula os itens nas colulas com cabeçalho
  * @ $param = array ('itens'=>'valor');
  * @     
  *-------------------------------------------------------------------------------*/
     public function cabecalhoTabelaRelatorio($param = null)
     {
        $r = '<tr>';
        foreach ($param as $p)
        {
            $r .= '<th width="'. $p['tamanho']. '"style="text-align: center;">' . $p['nome'] . '</th>';
        }
        $r .= "</tr>";
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        tabula os itens nas colulas
  * @ $param = array ('itens'=>'valor');
  * @     
  *-------------------------------------------------------------------------------*/
     public function tabulaTabelaRelatorio($param = null)
     {
        $r = '<tr>';
        foreach ($param as $p)
        {
            $r .= '<td style="text-align: center;">' . $p['nome'] . '</td>';
        }
        $r .= "</tr>";
        return $r;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Executa os diversos calculos para pagamento de um professo
  * @ $param = id do professor
  * @     
  *-------------------------------------------------------------------------------*/
     public function get_aulasPagamento($param = null , $salvar = false)
     {
        $fer    = new TFerramentas();
        $lista  = false;
        $salvar = $this->executa_gravacao;
        $controle_id = $this->controle_geracao;
        try
        {
            TTransaction::open('sisacad');
            $repository = new TRepository('professorcontrole_aula');
            
            $criteria = new TCriteria;
            $criteria->setProperties(array('order'=>'data_aula','direction'=>'asc'));
            
            $criteria->add(new TFilter('professor_id','=',$param));
            $criteria->add(new TFilter('aulas_saldo','>',0));
            $criteria->add(new TFilter('aulas_pagas','!=',"NOESC:aulas_saldo"));
            $objects = $repository->load($criteria, FALSE);
            if (!empty($objects))
            {
                $ids_usados = array();
                $maximo = $this->valor_maximo;
                $teto = false;
                $a_pagar = 0;
                $hh_trab = 0;
                $dt_trab = '';
                foreach($objects as $object)
                {
                    if ($teto == false)
                    {
                        if ($object->validado == 'S' && $this->aula_validada == 'S')
                        {
                            $v_aula = $object->valor_aula * ($object->aulas_saldo - $object->aulas_pagas);
                            //Se não se atingiu o teto e se há um teto limitador
                            if ($maximo > 0 && $a_pagar + $v_aula <= $maximo)//Pagamento integral das aulas do dia
                            {
                                $ids_usados[] = $object->controle_aula_id;          //Define as Ids usadas
                                $a_pagar = $a_pagar + $v_aula;                      //Soma o valor desta aula ao acumulado
                                $hh_trab = $hh_trab + ($object->aulas_saldo - $object->aulas_pagas);//Somas as aulas já trabalhadas
                                $dt_trab .= ((!empty($dt_trab)) ? ', ' : '') . TDate::date2br($object->data_aula);//Registra mais um dia trabalhado
                                //É pra salvar (executar) o pagamento.
                                if ($salvar == 'S')
                                {
                                    $aula                      = new professorcontrole_aula($object->id);//Busca o controle do professor
                                    $diferenca                 = $aula->aulas_saldo - $aula->aulas_pagas;//Tira o que já foi pago do saldo
                                    echo $aula->aulas_saldo . ' - ' . $aula->aulas_pagas . ' = ' . $diferenca . '<br>';
                                    $aula->aulas_pagas         = $aula->aulas_saldo;  //Zera o saldo igualando aulas pagas
                                    $aula->data_pagamento      = date('Y-m-d');       //Data de Hoje com ultima a ser paga
                                    $aula->data_quitacao       = date('Y-m-d');       //Como quitou, fecha tb hoje

                                    
                                    //Relaciona no historico_pagamento a folha de controle e as aulas pagas
                                    $aula->historico_pagamento = $aula->historico_pagamento . 
                                                                 '[CTR=' . $controle_id . 
                                                                 '!CH-' . $diferenca . ']'; //Confere a diferença
                                    $aula->controle_geracao_id = $controle_id;        //Grava N. Controle
                                    $aula->store();//Grava
                                    //var_dump($aula);
                                }
                            }
                            else//Pagamento parcial das aulas
                            {
                                $teto   = true;                                   //Marca que o teto já foi atingido
                                $aula   = new professorcontrole_aula($object->id);//Busca o controle do professor
                                $aulapg = $aula->aulas_pagas;                     //Pega as aulas já pagas no controle
                                //Calcula quantas aulas ainda poderão ser pagas deste controle
                                for ($a = ($object->aulas_saldo - $object->aulas_pagas); $a >=0 ; $a--)
                                {
                                    $v_aula = $object->valor_aula * $a;//Verifica se há alguma aula pra somar
                                    //Verifica a quantidade não estoura o máximo
                                    if ($a > 0 && $a_pagar + $v_aula <= $maximo)
                                    {
                                        $ids_usados[] = $object->controle_aula_id;//Define as Ids usadas
                                        $a_pagar = $a_pagar + $v_aula;            //Soma o valor desta aula ao acumulado
                                        $hh_trab = $hh_trab + $a;                 //Somas as aulas já trabalhadas
                                        $dt_trab .= ((!empty($dt_trab)) ? ', ' : '') . TDate::date2br($object->data_aula);//Registra mais um dia trabalhado
                                        //É pra salvar (executar) o pagamento.
                                        if ($salvar == 'S')
                                        {
                                            $aula->aulas_pagas         = $aula->aulas_pagas + $a;
                                            //Verifica se pagou todo o controle
                                            if($aula->aulas_pagas >= $aula->aulas_saldo)
                                            {
                                                $aula->aulas_pagas     = $aula->aulas_saldo;//Aulas pagas não pode passar o Saldo 
                                                $aula->data_quitacao   = date('Y-m-d');//Registra a quitação de hoje
                                            }
                                            $aula->data_pagamento      = date('Y-m-d');//Registra o ultimo pagamento
                                            $aula->controle_geracao_id = $controle_id;   //Grava N. Controle

                                        }
                                        $a = 0;//Força a saída do laço FOR
                                    }
                                }//Laço FOR
                                //Verifica se é para salvar e se houve aulas a pagar
                                if ($salvar == 'S' && $aula->aulas_pagas > 0)
                                {
                                    //Relaciona no historico_pagamento a folha de controle e as aulas pagas
                                    $aula->historico_pagamento = $aula->historico_pagamento . 
                                                                 '[CTR=' . $controle_id . 
                                                                 '!CH-' . ($aula->aulas_pagas - $aulapg) . ']';
                                    $aula->store();//Grava
                                    //var_dump($aula);
                                }
                            }//Fim do if - Pagamento Parcial das aulas
                        }//Fim do if - Verifica Validado
                    }//Fim da verificação do teto 
                }//Fim do Foreach
            }//Fim verificação se há objetos
            //Cria o Array de Retorno
            $lista = array();
            $lista['valor']   =  $fer->formataDinheiro($a_pagar);
            $lista['hh_trab'] = $hh_trab; 
            $lista['dt_trab'] = $this->clearDataIgual($dt_trab);
            //Busca os Documentos de autorização
            setlocale(LC_CTYPE, 'pt_BR.iso-8859-1');
            if (!empty($ids_usados))
            {
                $repository = new TRepository('professormateria');
                $criteria = new TCriteria;            
                $query1 = "(SELECT DISTINCT materia_id FROM sisacad.controle_aula WHERE controle_aula.id IN (".implode(',',$ids_usados).") )";
                $criteria->add (new TFilter ('materia_id','IN',$query1));
                $criteria->add (new TFilter ('professor_id','=',$param));
                $objects = $repository->load($criteria, FALSE);
                $doc = '';
                if (!empty($objects))
                {
                    foreach ($objects as $object)
                    {
                        $ato = mb_strtoupper($object->ato_autorizacao,'UTF-8');
                        if (!empty($ato))
                        {
                            $tags = explode(',',str_replace(" ","",$doc));
                            if (!in_array(str_replace(" ","",$ato),$tags))
                            {
                                $doc .= ((!empty($doc)) ? ', ' : '') . $ato ;
                            }
                        }
                    } 
                }
            }
            else
            {
                $doc = 'NC';
            }
            $lista['doc'] = $doc;
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Remove os Estornos já ocorridos
  * @ $historico = string
  * @     
  *-------------------------------------------------------------------------------*/
    public function removeEstorno($historico = null)
    {
        if (strpos($historico,'[RET=') === false)
        {
            return $historico;
        }
        do
        {
            $in = strpos($historico,'[RET=');//Acha inicio da chave de estorno
            $fi = strpos($historico,']',$in);//Acha o final da chave de estorno
            $st = substr($historico, $in, ($fi - $in));//retira a substring
            $historico = str_replace($st,'',$historico);
        } while (strpos($historico,'[RET=') != false);
        return $historico;
    }
 /*-------------------------------------------------------------------------------
  *        Rotina para recalculo do saldo da folha 19
  * @ $param = array ('itens'=>'valor');
  * @     
  *-------------------------------------------------------------------------------*/
    public function recalculaSaldo($param = null)
    {
        if (is_array($param))
        {
            $historico   = $param['historico'];  //String com o historico_pagamento
            $saldo       = $param['saldo'];      //Saldo de aulas não pagas
            $ch_estorno  = $param['ch_estorno']; //O que está previsto para devolver
            $controle_id = $param['controle_id'];//O código Gerador
            $id          = $param['id'];         //O id do professorcontrole_aula
        }
        //Verifica se existe mais de um pagamento para professorcontrole_aula
        $tot = substr_count($historico,'[CTR=');
        if ($tot == 1)//Se há apenas um pagamento no histórico, retorna o valor já contabilizado
        {
             $ret  = $ch_estorno;//Carga Horária paga no N.Controle
             $soma = $ret;       //Carga Horária já usada por outros numeros de controle
        }
        else
        {
            echo $id . ' -- Saldo ' . $saldo . ' - Histórico '  . $historico . ' - ';
            $fer = new TFerramentas;
            //Remove os conteúdos já estornados
            $historico = $this->removeEstorno($historico);
            //Monta array com pagamentos para saber o que já foi debitado de fato
            $res = explode('[CTR=',$historico);
            $est = array();
            foreach($res as $re)
            {
                $div = explode('!CH-',$re);//Separa o identificador e a carga horária
                if (is_array($div))
                {
                    //Se encontrou identificador e CH continue
                    if (array_key_exists(0,$div) && array_key_exists(1,$div))
                    {
                        //monta um array usando o identificador $fer->soNumeros($div[0]) e guardando a CH $fer->soNumeros($div[1])
                        $est[$fer->soNumeros($div[0])] = $fer->soNumeros($div[1]);
                        //var_dump($est);
                    }
                }
                
            }
            $soma     = 0;//Carga Horária já usada por outros numeros de controle
            $pg_final = true;
            foreach($est as $key => $e)//Calcula os valores pagos anteriormente
            {
                if ($key != $controle_id)//Pula o N.Controle que estamos buscando
                {
                    $soma = $soma + $e;
                }
                if ($key > $controle_id)
                {
                    //Se o houver numero de controle maior, então não é onde houve problema
                    $pg_final = false;
                }
                
            }
            if ($pg_final)
            {
                //Retorna a diferença dos valores pagos (reais) com o saldo de aulas
                $ret = $saldo - $soma;
            }
            else
            {
                //Retorna a carga horária relatada como paga uma vez que o pagamento não foi o que zerou o saldo
                $ret = $ch_estorno;
            }
            echo 'TOTAL ' . $ret . '<br>';
        }
        $lista = array('estorno'=>$ret,'pago'=>$soma);
        return $lista;
        
    }//Fim Módulo
     /*-------------------------------------------------------------------------------
  * Executa os Estornos
  * @ $param = id do professor
  * @     
  *-------------------------------------------------------------------------------*/
     public function get_aulasEstorno($param = null , $salvar = false)
     {
        $fer    = new TFerramentas();
        $lista  = false;
        $salvar = $this->executa_gravacao;
        $controle_id = $this->numero_ctrl;
        try
        {
            TTransaction::open('sisacad');
            $repository = new TRepository('professorcontrole_aula');
            
            $criteria = new TCriteria;
            $criteria->setProperties(array('order'=>'data_aula','direction'=>'asc'));
            
            $criteria->add(new TFilter('professor_id','=',$param));
            $criteria->add(new TFilter('historico_pagamento','LIKE', '%[CTR='.$controle_id.'!%'));
            $objects = $repository->load($criteria, FALSE);
            if (!empty($objects))
            {
                $ids_usados = array();
                $maximo = $this->valor_maximo;
                $teto = false;
                $a_pagar = 0;
                $hh_trab = 0;
                $dt_trab = '';
                foreach($objects as $object)
                {
                    //Pega a quantidade de horas a estornar do campo historico
                    $ctr_ini = strpos($object->historico_pagamento,'[CTR='.$controle_id.'!CH-');
                    if (false !== $ctr_ini)//Há horas a estornar
                    {
                        $ch_ini  = strpos($object->historico_pagamento,'!CH-',$ctr_ini);//Marca o fim da string 
                        $next    = strpos($object->historico_pagamento,'[CTR=',$ch_ini);//Olha se tem mais pagamento
                        if (false === $next)//Não há mais pagamento após este
                        {
                            $tam     = (strpos($object->historico_pagamento,']',$ch_ini)) - $ch_ini;//Evita pegar além do necessário
                            $hora    = substr($object->historico_pagamento,$ch_ini,$tam);//Pega a hora
                        }
                        else//Há outro pagamento no histórico, pegar só um pedaço
                        {
                            $hora    = substr($object->historico_pagamento,$ch_ini, ($next - $ch_ini));
                        }
                        $ch_estorno = $fer->soNumeros($hora);
                        $er_estorno = $ch_estorno;//Armazena o $ch_estorno original pois pode ter que ser corrigido
                        
                        //Se numero de controle <= 19 verifica se houve erro na soma da carga horária paga
                        if ($object->aulas_saldo == $object->aulas_pagas && $controle_id <= 19)
                        {
                            $valores = self::recalculaSaldo(array('historico'=>$object->historico_pagamento,
                                                                      'saldo'=>$object->aulas_saldo,
                                                                      'ch_estorno'=>$ch_estorno,
                                                                      'controle_id'=>$controle_id,
                                                                      'id'=>$object->id));
                            $ch_estorno = $valores['estorno'];
                            $cor_pago   = $valores['pago'];
                        }
                        //Se há estorno a considerar
                        if ($ch_estorno > 0)
                        {
                            $ids_usados[] = $object->controle_aula_id;      //Define as Ids usadas
                            //Limita o Estorno...nem maior que as aulas dadas nem menor que zero
                            if ($ch_estorno > $object->aulas_saldo)
                            {
                                $ch_estorno = $object->aulas_saldo;
                            }
                            else if ($ch_estorno < 0)
                            {
                                $ch_estorno = 0;
                            }
                            $v_aula = $object->valor_aula * ($ch_estorno);
                            $a_pagar = $a_pagar + $v_aula;                  //Soma o valor desta aula ao acumulado
                            $hh_trab = $hh_trab + $ch_estorno;
                            $dt_trab .= ((!empty($dt_trab)) ? ', ' : '') . TDate::date2br($object->data_aula);
                            if ($salvar == 'S')
                            {
                                $aula                      = new professorcontrole_aula($object->id);
                                if ($controle_id <= 19 && (isset($cor_pago) && ($aula->aulas_pagas - $ch_estorno) != $cor_pago))
                                {
                                    $aula->aulas_pagas         = $aula->aulas_pagas - $cor_pago; //Mudança 2018-03-23 - 
                                }
                                else
                                {
                                    $aula->aulas_pagas         = $aula->aulas_pagas - $ch_estorno;
                                }
                                //Mudança 2018-03-23, para evitar aulas_pagas menor que zero
                                if ($aula->aulas_pagas < 0)
                                {
                                    $aula->aulas_pagas = 0;
                                }
                                $aula->data_pagamento      = '';
                                $aula->data_quitacao       = '';
                                $aula->historico_pagamento = str_replace('[CTR=' . $controle_id . '!CH-' . $er_estorno . ']',
                                                                         '[RET=' . $controle_id . '|HS-' . $ch_estorno . ']',
                                                                         $aula->historico_pagamento);
                                //Ponto Controle
                                $aula->store();
                            }
                        }
                        else if ($ch_estorno == 0) //remove para o caso de se achar um histórico com zero
                        {
                            $aula                      = new professorcontrole_aula($object->id);
                            //Confere o saldo pago e atualiza se houver diferença
                            if ($controle_id <= 19 && (isset($cor_pago) && ($aula->aulas_pagas - $ch_estorno) != $cor_pago))
                            {
                                $aula->aulas_pagas         = $cor_pago;
                            }
                            $aula->historico_pagamento = str_replace('[CTR=' . $controle_id . '!CH-' . $er_estorno . ']',
                                                                     '',
                                                                     $aula->historico_pagamento);
                            //Ponto Controle
                            $aula->store();
                        }
                    }
                }//Fim do Foreach
            }//Fim verificação se há objetos
            $lista = array();
            $lista['valor']   =  $fer->formataDinheiro($a_pagar);
            $lista['hh_trab'] = $hh_trab; 
            $lista['dt_trab'] = $this->clearDataIgual($dt_trab);
            //Busca os Documentos de autorização
            setlocale(LC_CTYPE, 'pt_BR.iso-8859-1');
            if (!empty($ids_usados))
            {
                $repository = new TRepository('professormateria');
                $criteria = new TCriteria;            
                $query1 = "(SELECT DISTINCT materia_id FROM sisacad.controle_aula WHERE controle_aula.id IN (".implode(',',$ids_usados).") )";
                $criteria->add (new TFilter ('materia_id','IN',$query1));
                $criteria->add (new TFilter ('professor_id','=',$param));
                $objects = $repository->load($criteria, FALSE);
                $doc = '';
                if (!empty($objects))
                {
                    foreach ($objects as $object)
                    {
                        $ato = mb_strtoupper($object->ato_autorizacao,'UTF-8');
                        if (!empty($ato))
                        {
                            $tags = explode(',',str_replace(" ","",$doc));//str_replace(" ","",$string)
                            if (!in_array(str_replace(" ","",$ato),$tags))
                            {
                                $doc .= ((!empty($doc)) ? ', ' : '') . $ato ;
                            }
                        }
                    } 
                }
            }
            else
            {
                $doc = 'NC';
            }
            $lista['doc'] = $doc;
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
    
 /*-------------------------------------------------------------------------------
  *        Principal do Relatório Mensal da OPM
  *-------------------------------------------------------------------------------*/
    public function mainMensal ($param=null)
    {
        $fer = new TFerramentas();
        $professores = $this->cpf;
        $campos = array (
                        array('nome'  =>'ORD'            ,'tamanho'=>'30px'),
                        array('nome'  =>'DATA TRABALHADA','tamanho'=>'150px'),
                        array('nome'  =>'DOC/ORIGEM'     ,'tamanho'=>'120px'),
                        array('nome'  =>'SERVIDOR'       ,'tamanho'=>'150px'),
                        array('nome'  =>'CPF'            ,'tamanho'=>'80px'),
                        array('nome'  =>'CARGO'          ,'tamanho'=>'60px'),
                        array('nome'  =>'HH TRAB'        ,'tamanho'=>'30px'),
                        array('nome'  =>'VALOR'          ,'tamanho'=>'50px')
                        );
        switch ($this->tipo)
        {
            case 1://Relatório de Saldos a pagar
            
                if ($this->executa_gravacao == 'S')
                {
                    $grv    = new TControleGeracao();
                    $this->controle_geracao = $grv->geraControle();   //Pega Numero de Controle
                    if($this->controle_geracao == false)
                    {
                        new TMessage('erro','Não foi Gerado o número de Controle');
                        return false;
                    }
                }
                $relatorio  = $this->cabecalhoRelatorio();
                $relatorio .= $this->titulosParaRelatorio('1');
                $relatorio .= $this->cabecalhoTabelaRelatorio($campos);
                $ord = 1;
                $total_pago = 0;
                foreach ($professores as $key=>$professor)
                {
                    $saldos = $this->get_aulasPagamento($professor['id']);
                    $campos = array (
                                    array('nome'  =>$ord),
                                    array('nome'  =>$saldos['dt_trab']),
                                    array('nome'  =>$saldos['doc']),
                                    array('nome'  =>$professor['nome']),
                                    array('nome'  =>$professor['cpf']),
                                    array('nome'  =>$professor['postograd'].' '.$professor['quadro']),
                                    array('nome'  =>$saldos['hh_trab']),
                                    array('nome'  =>$saldos['valor'])
                                    );
                    
                    $relatorio .= $this->tabulaTabelaRelatorio($campos);
                    $ord ++;
                    if (!empty($saldos['valor']))
                    {
                        $total_pago = $total_pago + ($fer->soNumeros($saldos['valor']) / 100);
                    }
                    
                }
                $relatorio .= '<td colspan=7 style="text-align: right;"><strong>TOTAL</strong></td>';
                $relatorio .= '<td style="text-align: right;"><strong>' . $fer->formataDinheiro($total_pago) .'</strong></td>';
                
               
                $cmd = $this->scriptPrint();
                $relatorio.= "</center></table>".$this->assinatura."</div>".$cmd['botao'];
                break;
            case 2://Estorno de Saldos pagos
                if ($this->executa_gravacao == 'S')
                {
                    $grv         = new TControleGeracao();
                    $grv->motivo = $this->motivo; 
                    //Muda o Status do Controle para retificado
                    //Ponto Controle
                    $grv->alteraControle($this->numero_ctrl, 2 , (($this->retificado == 'S') ? 5 : 13) );
                }
                $relatorio  = $this->cabecalhoRelatorio();
                $relatorio .= $this->titulosParaRelatorio('1');
                $relatorio .= $this->cabecalhoTabelaRelatorio($campos);
                $ord = 1;
                $total_pago = 0;
                foreach ($professores as $key=>$professor)
                {
                    $saldos = $this->get_aulasEstorno($professor['id']);
                    $campos = array (
                                    array('nome'  =>$ord),
                                    array('nome'  =>$saldos['dt_trab']),
                                    array('nome'  =>$saldos['doc']),
                                    array('nome'  =>$professor['nome']),
                                    array('nome'  =>$professor['cpf']),
                                    array('nome'  =>$professor['postograd'].' '.$professor['quadro']),
                                    array('nome'  =>$saldos['hh_trab']),
                                    array('nome'  =>$saldos['valor'])
                                    );
                    
                    $relatorio .= $this->tabulaTabelaRelatorio($campos);
                    $ord ++;
                    if (!empty($saldos['valor']))
                    {
                        $total_pago = $total_pago + ($fer->soNumeros($saldos['valor']) / 100);
                    }
                    
                }
                $relatorio .= '<td colspan=7 style="text-align: right;"><strong>TOTAL</strong></td>';
                $relatorio .= '<td style="text-align: right;"><strong>' . $fer->formataDinheiro($total_pago) .'</strong></td>';
                
               
                $cmd = $this->scriptPrint();
                $relatorio.= "</center></table>".$this->assinatura."</div>".$cmd['botao'];
                
                break;
            
        }
        return $relatorio;
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Script de impressão
  *-------------------------------------------------------------------------------*/
    public function scriptPrint ($param=null)
    {
        $script_print = '<script>
                            function impressao(elementodiv)
                            {
                                var ficha = document.getElementById(elementodiv);
                                var ventimp = window.open(" ", "popimpr");
                                ventimp.document.write( "<html><head><STYLE TYPE=\"text/css\">table{font-size:12px;} .folha { page-break-after: always; } </STYLE><title>IMPRESSORA</title></head><body>",ficha.innerHTML ,"</body></html>" );
                                ventimp.document.close();
                                ventimp.print();
                                ventimp.close();
                            }
                            </script>';
        $button_print = "<input type=\"button\" value = \"Imprimir\" id=\"impressora\" onclick=\"impressao('relatorio')\" title=\"IMPRIMIR\">";
        return array('codigo'=>$script_print,'botao'=>$button_print);
    }//Fim Módulo
 /*-------------------------------------------------------------------------------
  *        Remove datas iguais
  *-------------------------------------------------------------------------------*/
    public function clearDataIgual ($param=null)
    {
        $ret = '';
        $datas = explode(',',$param);
        $ret .= $datas[0];
        foreach ($datas as $data)
        {
            if (strpos($data ,$ret) === false)
            {
                $ret .= ',' . $data;
            }
        }
        return $ret;
    }//Fim Módulo
 }//Fim Classe
