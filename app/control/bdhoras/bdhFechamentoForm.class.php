<?php
/**
 * bdhFechamentoForm Form
 * @author  <your name here>
 */
class bdhFechamentoForm extends TPage
{
    protected $form; // form
    private $lista;
    private $lista_slc;
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_OPM');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Banco de Horas - Fechamento de Escalas');

        // create the form fields
        $mes = new TCombo('mes');
        $ano = new TCombo('ano');
        $fecha_inativo = new TCombo('fecha_inativo');
        $fecha_OPM     = new TCombo('fecha_OPM');
        $lista_opm = new TSelect ('lista_opm');
        $selecao   = new TSelect ('selecao');
        
        //Valores dos Itens
        $fer = new TFerramentas();
        $mes->addItems($fer->lista_meses());
        $ano->addItems($fer->lista_anos());
        $item = array ("S"=>"SIM","N"=>"NÃO");
        $fecha_inativo->addItems($item);
        $fecha_inativo->setValue('N');
        $fecha_OPM->addItems($item);
        $fecha_OPM->setValue('S');
        $ano->setValue(date('Y'));
        $mes->setValue(date('m'));
        //Tamanho
        $mes->setSize(120);
        $ano->setSize(80);        
        //Tips
        $fecha_inativo->setTip('Marque SIM se não interessa em fechar as escalas dos militares inativos.<br>' . 
                               'Ao marcar essa opção como NÃO, eu fecharei/listarei as unidades que tem qualquer <br> ' .
                               'escala aberta de militares ativos ou não.');
        $fecha_OPM->setTip('Marque SIM se quizer fechar todas escalas geradas nessa OPM.<br>'.
                            'Deste modo, mesmo que o militar tenha sido transferido de unidade e a nova escala<br>' .
                            'não tenha sido enviada pela nova, será fechada com a identificação antiga.<br>' .
                            'Marcando esta opção, fecho também as escalas que a OPM foi informante como no caso dos adidos.');
        

        $lista_opm->setSize(300,300);
        $selecao->setSize(300,300);
        $fecha_inativo->setSize(80);
        $fecha_OPM->setSize(80);
        
        //Botões de Serviço
        $add    = new TButton('add_opm');
        $del    = new TButton('del_opm');
        $cls    = new TButton('clear');
        $run    = new TButton('close');
        $ret    = new TButton('return');
        $filtro = new TButton('filtro');
        
        //Ações
        $add->setAction(new TAction(array($this, 'onAdd_OPM')));
        $del->setAction(new TAction(array($this, 'onSub_OPM')));
        $cls->setAction(new TAction(array($this, 'onClear_OPM')));
        $run->setAction(new TAction(array($this, 'onClose')));
        $ret->setAction(new TAction(array($this, 'onReturn')));
        $filtro->setAction(new TAction(array($this, 'onChangeTempo')));
        
        //Labels
        $add->setLabel('Adiciona');
        $del->setLabel('Remove');
        $cls->setLabel('Limpa');
        $run->setLabel('Fecha Escala');
        $ret->setLabel('Volta ao Gerenciador');
        $filtro->setLabel('Filtra OPMs');
        
        //Icones
        $add->setImage('fa:plus green');
        $del->setImage('fa:minus red');
        $cls->setImage('fa:file-o blue');
        $run->setImage('fa:calendar-check-o white');
        $ret->setImage('fa:backward black');
        $filtro->setImage('fa:search blue');
        
        //Classes
        $run->class = 'btn btn-info';
        $ret->class = 'btn btn-warning';
        $filtro->class = 'btn btn-info';
        
        //Inclui em Tabela de Comandos
        $cmds = new TTable();
        $cmds-> style = 'width: 100%; text-align: center;';
        $cmds->addRowSet($add);
        $cmds->addRowSet($del);
        $cmds->addRowSet($cls);
        $cmds->addRowSet($run);
        $cmds->addRowSet($ret);
        //Frames e caixas
        $frame_tempo = new TFrame();
        $frame_tempo->setLegend('Período a Considerar');
        $hbox   = new THBox;
        $hbox->addRowSet(new TLabel ('Mês'),$mes);
        $hbox->addRowSet(new TLabel ('Ano'),$ano);
        $hbox->addRowSet(new TLabel('Fecha/Lista só os PMs Ativos?'),$fecha_inativo);
        $hbox->addRowSet(new TLabel('Fecha Toda OPM?'),$fecha_OPM);
        $hbox->addRowSet(new TLabel (''),$filtro);
        $frame_tempo->add($hbox);

        $vbox1 = new TVBox;
        $frame1 = new TFrame(300,300);
        $frame1->setLegend('Listagem');
        $frame1->add($lista_opm);
        $frame1->style = "width: 300px; display: table-cell; vertical-align: top;";
        $vbox1->add($frame1);
        
        $vbox2 = new TVBox;
        $frame2 = new TFrame(200,300);
        $frame2->setLegend('Comandos');
        $frame2->add($cmds);
        $frame2->style = "width: 200px; display: table-cell; vertical-align: top;";
        $vbox2->add($frame2);
        
        $vbox3 = new TVBox;
        $frame3 = new TFrame(300,300);
        $frame3->setLegend('Listagem');
        $frame3->add($selecao);
        $frame3->style = "width: 300px; display: table-cell; vertical-align: top;";
        $vbox3->add($frame3);
        
        $frame4 = new TFrame;
        //$frame2->setLegend('Listagens');
        $frame4->style = "width: 100%; display: table-cell; vertical-align: top;";
        $frame4->add($vbox1);
        $frame4->add($vbox2);
        $frame4->add($vbox3);

        $this->form->setFields(array($mes,$ano,$add,$del,$cls,$run,$ret,$fecha_inativo,$fecha_OPM,$filtro));        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'bdhManagerForm'));
        $container->add($frame_tempo);
        $container->add($frame4);
        $this->form->add($container);        
        self::showSelecao();

        parent::add($this->form);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('sicad'); // open a transaction
            $this->form->validate(); // validate form data
            
            $object = new OPM;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Executa a leitura das escalas em aberto conforme Mês/Ano
 *---------------------------------------------------------------------------------------*/
    public function buscaEscalas ($mes,$ano, $fecha_inativo = 'N')
    {
         try
         {
            //$data=$this->form->getData();
            $mes = ($mes) ? $mes : date('m')-1;
            $ano = ($ano) ? $ano : date('Y');
            if ($mes==0)
            {
                $ano = $ano -1;
                $mes = 12;
            }
            if ($fecha_inativo == 'N')
            {
                $ser_status = "";
            }
            else
            {
                $ser_status = " AND servidor.status = 'ATIVO'";
            }
            TTransaction::open('sicad');
            $datainicio = mktime(0, 0, 0, $mes  , 1, $ano);
            $datafim = mktime(23, 59, 59, $mes+1, 0, $ano);
            $sql = "SELECT DISTINCT opm.id as id,opm.sigla as nome, servidor.rgmilitar as rgmilitar " . 
                    "FROM bdhoras.historicotrabalho, g_geral.opm, efetivo.servidor  ".
                    "WHERE historicotrabalho.opm_id = opm.id AND historicotrabalho.status = 'P' AND ".
                    "historicotrabalho.datainicio BETWEEN '".date('Y-m-d',$datainicio)." 00:00:00' AND '".date('Y-m-d',$datafim)." 23:59:59' ".
                    "AND historicotrabalho.rgmilitar = servidor.rgmilitar" . $ser_status .";";
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $opms = $res->fetchAll();
            TTransaction::close();
            $ret = array();
            foreach ($opms as $opm)
            {
                $ret [$opm['id']] = $opm['nome'];
            }
            asort($ret);
            TSession::setValue(__CLASS__.'_lista_opm',$ret);
            return $ret;
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar dados de Unidades que não Escala.<br>".$sql);
            TTransaction::rollback();
            return false;
         }        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Executa a leitura das escalas em aberto conforme Mês/Ano
 *---------------------------------------------------------------------------------------*/
    static function buscaEscalasEstatico ($mes,$ano, $fecha_inativo = 'N')
    {
         try
         {
            //$data=$this->form->getData();
            $mes = ($mes) ? $mes : date('m')-1;
            $ano = ($ano) ? $ano : date('Y');
            if ($mes==0)
            {
                $ano = $ano -1;
                $mes = 12;
            }
            if ($fecha_inativo != 'S')
            {
                $ser_status = "";
            }
            else if ($fecha_inativo == 'S')
            {
                $ser_status = " AND servidor.status = 'ATIVO'";
            }
            TTransaction::open('sicad');
            $datainicio = mktime(0, 0, 0, $mes  , 1, $ano);
            $datafim = mktime(23, 59, 59, $mes+1, 0, $ano);

            $sql = "SELECT DISTINCT opm.id as id,opm.sigla as nome, servidor.rgmilitar as rgmilitar " . 
                    "FROM bdhoras.historicotrabalho, g_geral.opm, efetivo.servidor  ".
                    "WHERE historicotrabalho.opm_id = opm.id AND historicotrabalho.status = 'P' AND ".
                    "historicotrabalho.datainicio BETWEEN '".date('Y-m-d',$datainicio)." 00:00:00' AND '".date('Y-m-d',$datafim)." 23:59:59' ".
                    "AND historicotrabalho.rgmilitar = servidor.rgmilitar" . $ser_status .";";
                    
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $opms = $res->fetchAll();
            TTransaction::close();
            $ret = array();
            foreach ($opms as $opm)
            {
                $ret [$opm['id']] = $opm['nome'];
            }
            asort($ret);
            TSession::setValue(__CLASS__.'_lista_opm',$ret);
            return $ret;
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar dados de Unidades que não Escala.<br>".$sql);
            TTransaction::rollback();
            return false;
         }        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Busca Unidades com Escala Aberta conforme troca-se o Mês/Ano
 *---------------------------------------------------------------------------------------*/
    public static function onChangeTempo ($param)
    {
        //var_dump($param);
        if (array_key_exists('mes',$param))
        {
            $mes = $param['mes'];
        }
        else
        {
            return;
        }
        if (array_key_exists('ano',$param))
        {
            $ano = $param['ano'];
        }
        else
        {
            return;
        }
        if (array_key_exists('fecha_inativo',$param))
        {
            $fecha_inativo = $param['fecha_inativo'];
        }
        else
        {
            $fecha_inativo = 'N';
        }
        $list = self::buscaEscalasEstatico($mes,$ano, $fecha_inativo);
        if (empty($list))
        {
            $list['0'] = 'Nenhuma Escala Aberta';
        }
        TDBSelect::reload('form_OPM', 'lista_opm', (array) $list);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReload ($param)
    {

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Coloca uma OPM da seleção
 *---------------------------------------------------------------------------------------*/
    public function onAdd_OPM($param)
    {
        $data = $this->form->getData();
        $selecao = TSession::getValue(__CLASS__.'_selecao');
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $param['lista_opm'] = (isset($param['lista_opm'])) ? $param['lista_opm'] : false;
        if (!is_array($lista_opm))//Se lista_slc não existe ou não é array...inicia
        {
            $lista_opm = array('0'=>'Nenhuma OPM Selecionada');
        }
        if (!is_array($selecao))//Se lista_slc não existe ou não é array...inicia
        {
            $selecao = array('0'=>'Nenhuma OPM Selecionada');
        }
        if (!$param['lista_opm'] || empty($param['lista_opm']))
        {
            new TMessage ('info','É necessário selecionar pelo menos uma OPM primeiro.');
        }
        else
        {
            $OPMs=$param['lista_opm'];
            if ($OPMs)
            {
                foreach ($OPMs as $OPM)
                {
                    $selecao [$OPM] = $lista_opm[$OPM];//add militar
                }
                if (array_key_exists('0',$selecao))//Remove elemento zero, se existir
                { 
                    unset($selecao['0']);
                }
            }
        }
        asort($selecao);
        TSession::setValue(__CLASS__.'_selecao',$selecao);
        $data = new StdClass;
        $data->mes = $param['mes'];
        $data->ano = $param['ano'];
        $data->fecha_inativo = $param['fecha_inativo'];
        TForm::sendData('form_OPM',$data);
        TDBSelect::reload('form_OPM', 'selecao', $selecao);
        TDBSelect::reload('form_OPM', 'lista_opm', $lista_opm);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *   Tira uma OPM da seleção
 *---------------------------------------------------------------------------------------*/
    public function onSub_OPM($param)
    {
        $selecao = TSession::getValue(__CLASS__.'_selecao');
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $param['selecao'] = (isset($param['selecao'])) ? $param['selecao'] : false;
        if (!is_array($lista_opm))//Se lista_slc não existe ou não é array...inicia
        {
            $lista_opm = array('0'=>'Nenhuma OPM Selecionada');
        }
        if (!is_array($selecao))//Se lista_slc não existe ou não é array...inicia
        {
            $selecao = array('0'=>'Nenhuma OPM Selecionada');
        }
        if (!$param['selecao'] || empty($param['selecao']))
        {
            new TMessage ('info','É necessário selecionar pelo menos uma OPM primeiro.');
        }
        else
        {
            $OPMs=$param['selecao'];
            if ($OPMs)
            {
                foreach ($OPMs as $OPM)
                {
                    unset($selecao [$OPM]);//del militar
                }
                $selecao = (!is_array($selecao) || count($selecao)==0) ? array('0'=>'Nenhuma OPM Selecionada') : $selecao;
            }
        }
        //$this->form->setData( $data ); //Atualiza form
        asort($selecao);
        TSession::setValue(__CLASS__.'_selecao',$selecao);
        $data = new StdClass;
        $data->mes = $param['mes'];
        $data->ano = $param['ano'];
        $data->fecha_inativo = $param['fecha_inativo'];
        TForm::sendData('form_OPM',$data);
        TDBSelect::reload('form_OPM', 'selecao', $selecao);
        TDBSelect::reload('form_OPM', 'lista_opm', $lista_opm);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Limpa Seleção de OPMs
 *---------------------------------------------------------------------------------------*/
    public function onClear_OPM( $param )
    {
        $data = new StdClass;
        $data->mes = $param['mes'];
        $data->ano = $param['ano'];
        $data->fecha_inativo = $param['fecha_inativo'];
        $data->fecha_OPM = $param['fecha_OPM'];
        $selecao = array('0'=>'- Nenhuma OPM Selecionada -');
        TSession::setValue(__CLASS__.'_selecao',$selecao);
        TForm::sendData('form_OPM',$data);
        TDBSelect::reload('form_OPM', 'selecao',$selecao);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onClose($param)
    {
        $selecao = TSession::getValue(__CLASS__.'_selecao');
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $lista = array();
        foreach ($selecao as $key=>$sel)
        {
            if (array_key_exists($key,$lista_opm))
            {
                $lista [] = $key;
            }
        }
        if (count($lista)==0)
        {
            new TMessage('info',"Não existe nenhuma escala para fechar para as OPMs selecionadas.");
            return;
        }
        $y = date('Y');
        $m = date('m');
        $ano = $param['ano'];
        $mes = $param['mes'];
        if (empty($ano) || empty($mes))
        {
            new TMessage('info',"Sem definir o Mês e o Ano não posso fechar escala nenhuma.");
            return;
        }
        if ($ano>$y || ($ano==$y && $mes>=$m))
        {
            new TMessage('info',"Não é possível fecha uma escala futura.");
            return;
        }
        $inativo  = ($param['fecha_inativo']=='S') ? 'S' : 'N';
        $toda_OPM = ($param['fecha_OPM']=='S')     ? 'S' : 'N';
        $ci       = new TBdhGerador();
        $ret      = $ci->main_fechar($lista,$lista_opm,"CLOSE",$param['mes'],$param['ano'],$inativo,$toda_OPM);
        if ($ret)
        {
            $lista_opm = self::buscaEscalas($param['mes'],$param['ano']);
        }
        $data = new StdClass;
        $data->mes = $param['mes'];
        $data->ano = $param['ano'];
        $data->fecha_inativo = $param['fecha_inativo'];
        $data->fecha_OPM = $param['fecha_OPM'];
        TForm::sendData('form_OPM',$data);
        TSession::setValue(__CLASS__.'_lista_opm',$lista_opm);
        TDBSelect::reload('form_OPM', 'selecao', $selecao);
        TDBSelect::reload('form_OPM', 'lista_opm', $lista_opm);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReturn($param)
    {
        TApplication::loadPage('bdhManagerForm');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    static function showSelecao($param=null)
    {
        $selecao = TSession::getValue(__CLASS__.'_selecao');
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        if (!is_array($lista_opm))//Se lista_slc não existe ou não é array...inicia
        {
            $lista_opm = array('0'=>'Nenhuma Escala Aberta');
        }
        if (!is_array($selecao))//Se lista_slc não existe ou não é array...inicia
        {
            $selecao = array('0'=>'Nenhuma OPM Selecionada');
        }
        if ($param['ano'])
        {
            
            $data = new StdClass;
            $data->mes = $param['mes'];
            $data->ano = $param['ano'];
            $data->fecha_inativo = $param['fecha_inativo'];
            TForm::sendData('form_OPM',$data);
        }
        TSession::setValue(__CLASS__.'_lista_opm',$lista_opm);
        TSession::setValue(__CLASS__.'_selecao',$selecao);
        TDBSelect::reload('form_OPM', 'selecao', $selecao);
        TDBSelect::reload('form_OPM', 'lista_opm', $lista_opm);
    }//Fim Módulo
}//Fim Classe
