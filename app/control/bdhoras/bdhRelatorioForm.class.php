<?php
/**
 * bdhRelatorioForm Form
 * @author  <your name here>
 */
class bdhRelatorioForm extends TPage
{
    protected $form; // form
    protected $lista_opm;
    
    private $lista;
    private $lista_slc;

    var $sistema  = 'Banco de Horas';  //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Relatório';       //Nome da página de serviço.
    
    protected $nivel_sistema = false;  //Registra o nível de acesso do usuário
    protected $config        = array();//Array com configuração
    protected $config_load   = false;  //Informa que a configuração está carregada
    
    private $opm_operador = false;     // Unidade do Usuário
    private $listas = false;           // Lista de valores e array de OPM
    
    //Nomes registrados em banco de configuração e armazenados na array config
    private $cfg_horas_max     = 'horas_semanais';
    
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
        $this->form->setFormTitle('Banco de Horas - Relatório de Escalas');

        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if ($this->opm_operador==false)                     //Carrega OPM do usuário
        {
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
            $this->opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        }
        if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
        {
            $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
            $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                               //Informa que configuração foi carregada
        }
        // create the form fields
        $mes       = new TCombo('mes');
        $ano       = new TCombo('ano');
        $opm       = new TCombo('opm');
        $relatorio = new TCombo('relatorio');
        $so_ativo  = new TCombo('so_ativo');
        $so_OPM    = new TCombo('so_OPM');
        
        // add the fields
        $this->form->addQuickField('Mês', $mes, 120 );
        $this->form->addQuickField('Ano', $ano,  80 );
        $this->form->addQuickField('OPM', $opm, 400 );
        $this->form->addQuickField('Tipo de Relatório', $relatorio,  300 );
        $this->form->addQuickField('Quanto ao Status, Listo? ', $so_ativo,  200 );
        $this->form->addQuickField('Lista só o Efetivo atual da OPM?', $so_OPM,  80 );
        
        //Valores dos Itens
        $fer = new TFerramentas();
        $mes->addItems($fer->lista_meses());
        $ano->addItems($fer->lista_anos());
        $item = array (1=>"Relatório Mensal da OPM",2=>"Relatório de Saldo mensal da OPM");//,"S"=>"Relatório de Saldo da OPM");
        $relatorio->addItems($item);
        $relatorio->setValue(1);
        $item = array ("A"=>"Ativos","I"=>"Inativos","D"=>"Ativos e Inativos");
        $so_ativo->addItems($item);
        $so_ativo->setValue('A');
        $item = array ("S"=>"SIM","N"=>"NÃO");
        $so_OPM->setValue('S');
        $so_OPM->addItems($item);
        $omes = TSession::getValue(__CLASS__.'_mes');
        if (empty ($omes))
        {
            $tmes = date('m')-1;
            $tano = date('Y');
            if ($tmes==0)
            {
                $tano = $tano -1;
                $tmes = 12;
            }
        }
        else
        {
            $tano = TSession::getValue(__CLASS__.'_ano');
            $tmes = TSession::getValue(__CLASS__.'_mes');
        }
        $ano->setValue($tano);
        $mes->setValue($tmes);
        TSession::setValue(__CLASS__.'_mes',$tmes);
        TSession::setValue(__CLASS__.'_ano',$tano);
        $aopm = TSession::getValue(__CLASS__.'_opm');
        if (!empty ($aopm))
        {
            $opm->setValue(TSession::getValue(__CLASS__.'_opm'));
        }
        
        //Dicas
        $relatorio->setTip('Escolha um tipo de Relatório para executar.');
        $so_ativo->setTip('Deseja Relatar só PMs ativos, inativos ou ativos e inativos ? <br>Escolha conforme a necessidade');
        $so_OPM->setTip('Caso deseje filtrar os militares que não estão mais na OPM, escolha SIM onde usarei como '.
                        'base de pesquisa<br>somente os militares que tiveram escala no mês e ano escolhidos.<br>'.
                        'Se escolher no NÃO, listatei nos relatórios todos militares que estejam lotados atualmente na OPM,<br>'.
                        'assim, alguns podem não ter escala.');
        //Ações
        $troca = new TAction(array($this, 'onChangeTempo'));//Troca o tipo de serviço e seus dados
        $mes->setChangeAction($troca);
        $ano->setChangeAction($troca);
        
        //Botões de Serviço
        $run = new TButton('run');
        $ret = new TButton('return');
        
        //Ações

        $run->setAction(new TAction(array($this, 'onSend')));
        $ret->setAction(new TAction(array($this, 'onReturn')));
        
        //Labels
        $run->setLabel('Visualiza Relatório');
        $ret->setLabel('Volta ao Gerenciador');
        
        //Icones
        $run->setImage('fa:eye black');
        $ret->setImage('fa:backward black');
        
        //Classes
        $run->class = 'btn btn-success';
        $ret->class = 'btn btn-warning';

        $this->form->add($run);
        $this->form->add($ret);
        $this->form->setFields(array($run,$ret,$relatorio,$opm,$mes,$ano,$so_OPM,$so_ativo));        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'bdhManagerForm'));
        $container->add($this->form);

        parent::add($container);
        self::showCarrega();
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Executa a leitura das escalas em aberto conforme Mês/Ano
 *---------------------------------------------------------------------------------------*/
    public static function buscaEscalas ($mes=false,$ano=false)
    {
         try
         {
            //$data=$this->form->getData();
            $omes = TSession::getValue(__CLASS__.'_mes');
            if (!$mes && empty($omes))
            {
                $mes = date('m')-1;
            }
            else if (!$mes)
            {
                $mes = TSession::getValue(__CLASS__.'_mes');
            }
            $oano = TSession::getValue(__CLASS__.'_ano');
            if (!$ano && empty($oano))
            {
                $ano = date('Y');
            }
            else if (!$ano)
            {
                $ano = TSession::getValue(__CLASS__.'_ano');
            }
            if ($mes===0)
            {
                $ano = $ano -1;
                $mes = 12;
            }
            //echo "busca escala - ano " . $ano . "   -  mês ". $mes . "<br>";
            TTransaction::open('sicad');
            $datainicio = mktime(0, 0, 0, $mes  , 1, $ano);
            $datafim = mktime(23, 59, 59, $mes+1, 0, $ano);
            $sql = "SELECT DISTINCT opm.id as id,opm.sigla as nome FROM bdhoras.historicotrabalho, g_geral.opm ".
                    "WHERE historicotrabalho.opm_id = opm.id AND ".
                    "historicotrabalho.datainicio BETWEEN '".date('Y-m-d',$datainicio)." 00:00:00' AND '".date('Y-m-d',$datafim)." 23:59:59';";
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
            //print_r($ret);
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
        TSession::setValue(__CLASS__.'_mes',$mes);
        TSession::setValue(__CLASS__.'_ano',$ano);
        $list = self::buscaEscalas($mes,$ano);
        if (empty($list))
        {
            $list['0'] = 'Nenhuma Escala Aberta';
            asort($ret);
            TSession::setValue(__CLASS__.'_lista_opm',$list);
        }
        TDBCombo::reload('form_OPM', 'opm', (array) $list);
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
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReturn($param)
    {
        TApplication::loadPage('bdhManagerForm');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onSend($param)
    {
        $data      = $this->form->getData();
        $fer       = new TFerramentas();
        $meses     = $fer->lista_meses();
        $lista_opm = TSession::getValue(__CLASS__.'_lista_opm');
        $mes       = $param['mes'];
        $ano       = $param['ano'];
        $opm       = $param['opm'];
        $so_ativo  = $param['so_ativo'];
        $so_OPM    = $param['so_OPM'];
        $tipo      = (empty($param['relatorio'])) ? 1 : $param['relatorio'];
        $so_ativo  = ($so_ativo!='') ? $so_ativo : 'D';
        $so_OPM    = ($so_OPM=='N') ? 'N' : 'S';
        if (empty($mes) || empty($ano) || empty ($opm))
        {
            new TMessage('info',"Preencha os dados corretamente. <br>Mês, ano e a OPM são obrigatório definir.");
            return;
        }
        TSession::setValue(__CLASS__.'_opm',$opm);//Salva Valor da OPM
        //Cria e configura o Relatório
        $report = new TBdhReport();
        $report->mes = $mes;
        $report->ano = $ano;
        $report->opm = $opm;
        $report->tipo = $tipo;
        $report->so_ativo = $so_ativo;
        $report->so_OPM   = $so_OPM;
        $report->assinatura   = $fer->getAssinatura();
        $report->qntDias      = $fer->qntDiasMes($mes,$ano);
        $report->horas_semanais = (!empty($this->config[$this->cfg_horas_max])) ? $this->config[$this->cfg_horas_max] : 40;
        
        
        $cab = 'Referente ao MÊS de '.$meses[$param['mes']].' de '.$param['ano'].' da Unidade: '.
                $lista_opm[$param['opm']];
        //var_dump( $lista_opm);
        //echo $param['opm'];
        $report->unidade = $cab;
        //$report->mainMensal();
        $men = '<center>'.$report->mainMensal().'</center>';
        //echo $men;
        $this->form->setData($data);
        $window = TWindow::create($cab, 1200, 600);
        $window->add($men);
        $window->show();
        
        return false;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    static function showCarrega ()
    {
        $list = self::buscaEscalas();
        if (empty($list))
        {
            $list['0'] = 'Nenhuma Escala Aberta';
            asort($ret);
            TSession::setValue(__CLASS__.'_lista_opm',$list);
        }
        TDBCombo::reload('form_OPM', 'opm', (array) $list);
    }
        
}//Fim Classe
