<?php
class TSisacadCursoReport
{
//Declaração de Variáveis
    protected $cabecalho;
    protected $corpo;
    protected $valores;

    public $dt_inicio;
    public $dt_fim;
    public $cursos;
    public $assinatura;      //Nome de quem abriu o relatório
    public $orgao;           //Órgão Interessado
    public $tipo;        //Tipo de Relatório que será feito
    public $nivel;           //Nível do curso
    public $tipo_curso;      //tipo de curso
    public $natureza;        //Natureza do curso
    public $carga_horaria;
    public $oculto;
    public $unidade;

/*-------------------------------------------------------------------------------
 *        Função Construtora
 *-------------------------------------------------------------------------------*/
    public function __construct()
    {
        $this->valores = array();
        $this->cabecalho = '';
        $this->corpo     = '';
        $this->tipo      = 1;
        $this->cursos    = array();

        //set_time_limit (3600);
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *        Busca Professores que tenham saldo
 *        retorna array com os dados para professor (id,nome,postgrad e cpf)
 *        poderá ter data de início e/ou data fim
 *        poderá ser de todos Professores com vínculo a um órgão específico ou
 *        individual
 *-------------------------------------------------------------------------------*/
     public function get_ListaCursos($param = array())
     {
        try
        {
            TTransaction::open('sicad');



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
                '</strong></font></center></td>';
                break;
            case 2:
            
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
        $lista  = false;
        $salvar = $this->executa_gravacao;
        try
        {
            TTransaction::open('sisacad');
            $repository = new TRepository('professorcontrole_aula');
            
            $criteria = new TCriteria;
            $criteria->setProperties(array('order'=>'data_aula','direction'=>'asc'));
            
            $criteria->add(new TFilter('professor_id','=',$param));
            $criteria->add(new TFilter('aulas_saldo','>',0));
            /*if ($this->aula_validada == 'S')
            {
                $criteria->add(new TFilter('validado','=','S'));
            }*/
            $criteria->add(new TFilter('aulas_pagas','!=',"NOESC:aulas_saldo"));
            //echo "<br><br>".$criteria->dump();
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
                            if ($maximo > 0 && $a_pagar + $v_aula <= $maximo)
                            {
                                $ids_usados[] = $object->controle_aula_id;                        //Define as Ids usadas
                                $a_pagar = $a_pagar + $v_aula;                      //Soma o valor desta aula ao acumulado
                                $hh_trab = $hh_trab + ($object->aulas_saldo - $object->aulas_pagas);
                                $dt_trab .= ((!empty($dt_trab)) ? ', ' : '') . TDate::date2br($object->data_aula);
                                if ($salvar == 'S')
                                {
                                    $aula = new professorcontrole_aula($object->id);
                                    $aula->aulas_pagas = $aula->aulas_saldo;
                                    //echo 'Pagas'.$aula->aulas_pagas;
                                    $aula->data_pagamento = date('Y-m-d');
                                    $aula->data_quitacao  = date('Y-m-d');
                                    $aula->store();
                                }
                            }
                            else
                            {
                                $teto = true;                                       //Marca que o teto já foi atingido
                                for ($a = ($object->aulas_saldo - $object->aulas_pagas); $a >=0 ; $a--)
                                {
                                    $v_aula = $object->valor_aula * $a;             //Verifica se há alguma aula pra somar
                                    if ($a > 0 && $a_pagar + $v_aula <= $maximo)
                                    {
                                        $ids_usados[] = $object->controle_aula_id;
                                        $a_pagar = $a_pagar + $v_aula;
                                        $hh_trab = $hh_trab + $a;
                                        $dt_trab .= ((!empty($dt_trab)) ? ', ' : '') . TDate::date2br($object->data_aula);
                                        if ($salvar == 'S')
                                        {
                                            $aula = new professorcontrole_aula($object->id);
                                            $aula->aulas_pagas = $aula->aulas_pagas + $a;
                                            if($aula->aulas_pagas >= $aula->aulas_saldo)
                                            {
                                                $aula->aulas_pagas   = $aula->aulas_saldo;
                                                $aula->data_quitacao = date('Y-m-d');
                                            }
                                            $aula->data_pagamento = date('Y-m-d');
                                            $aula->store();
                                        }
                                        $a = 0;
                                    }
                                }
                            }
                        }
                    }//Fim da verificação do teto 
                }//Fim do Foreach
            }//Fim verificação se há objetos
            $lista = array();
            $lista['valor']   =  number_format($a_pagar,2,'.','');
            $lista['hh_trab'] = $hh_trab; 
            $lista['dt_trab'] = $dt_trab;
            //Busca os Documentos de autorização
            if (!empty($ids_usados))
            {
                $repository = new TRepository('professormateria');
                $criteria = new TCriteria;            
                $query1 = "(SELECT DISTINCT materia_id FROM sisacad.controle_aula WHERE controle_aula.id IN (".implode(',',$ids_usados).") )";
                //$query2 = "(SELECT DISTINCT turma_id FROM sisacad. WHERE controle_aula.id IN (".implode(',',$ids_usados).") )";
                $criteria->add (new TFilter ('materia_id','IN',$query1));
                $criteria->add (new TFilter ('professor_id','=',$param));
                //echo "<br><br> -- >".$criteria->dump();
                $objects = $repository->load($criteria, FALSE);
                //var_dump($objects);
                $doc = '';
                if (!empty($objects))
                {
                    foreach ($objects as $object)
                    {
                        $doc .= ((!empty($doc)) ? ', ' : '') . $object->ato_autorizacao;
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
        $professores = $this->cpf;
        switch ($this->tipo)
        {
            case 1://Cria relatório do Tipo: Mensal da OPM
                $relatorio  = $this->cabecalhoRelatorio();
                $relatorio .= $this->titulosParaRelatorio('1');
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
                $relatorio .= $this->cabecalhoTabelaRelatorio($campos);
                $ord = 1;
                foreach ($professores as $key=>$professor)
                {
                    $saldos = $this->get_aulasPagamento($professor['id']);
                    //var_dump($saldos);exit;
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
                }
                
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
 }//Fim Classe
