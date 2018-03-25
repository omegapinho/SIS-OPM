<?php
/**
 * sisacadConfiguracao
 * @author  Fernando de Pinho Araújo
 */
class sisacadConfiguracao extends TPage
{
    private $form;
    protected $quatro;
    protected $tres;
    protected $dois;
    protected $um_mes;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Turmas';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

/*------------------------------------------------------------------------------
 * Carrega configurações
 *------------------------------------------------------------------------------*/
        $fer   = new TFerramentas();                                            //Ferramentas diversas
        $sicad = new TSicadDados();                                             //Ferramentas SICAD
        $profile = TSession::getValue('profile');                               //Profile da Conta do usuário
        if (!$this->nivel_sistema || $this->config_load == false)               //Carrega OPMs que tem acesso
        {
            $this->opm_operador  = $sicad->get_OPM();                           //Carrega OPM do Usuário
            $this->nivel_sistema = $fer->getnivel (get_class($this));           //Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OPMsUsuario();                   //Carrega Listas de OPMs
            $this->config        = $fer->getConfig($this->sistema);             //Carrega config
            TSession::setValue('SISACAD_CONFIG', $this->config);                //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                                          //Informa que configuração foi carregada
        }
        
        $vbox = new TVBox;
        
        //Botões de Serviço
        $disciplina = new TButton('disciplina');
        $incidente  = new TButton('incidente');
        $nivel      = new TButton('nivel');
        $titulo     = new TButton('titulo');
        $valores    = new TButton('valores');
        $orgaos     = new TButton('orgaos');
        $config     = new TButton('config');
        $docs       = new TButton('docs');
        //Labels
        $disciplina->setLabel('Disciplinas');
        $incidente->setLabel('Incidentes');
        $nivel->setLabel('Níveis');
        $titulo->setLabel('Títulos');
        $valores->setLabel('Valores');
        $orgaos->setLabel('Órgãos');
        $config->setLabel('Funcionamento');
        $docs->setLabel('Documentos');
        //Icones
        $disciplina->setImage('fa:book gray');
        $incidente->setImage('fa:balance-scale red');
        $nivel->setImage('fa:percent gray');
        $titulo->setImage('fa:graduation-cap gray');
        $valores->setImage('fa:usd green');
        $orgaos->setImage('fa:university gray');
        $config->setImage('fa:gears red');
        $docs->setImage('fa:copy red');
        //PopUps
        $disciplina->popover = 'true';
        $disciplina->popside = 'bottom';
        $disciplina->poptitle = 'Disciplinas/Matérias';
        $disciplina->popcontent = 'Define as disciplinas (matérias) que estarão disponíveis aos cursos e turmas.';
        
        $incidente->popover = 'true';
        $incidente->popside = 'bottom';
        $incidente->poptitle = 'Tipos de Incidente Pedagógicos';
        $incidente->popcontent = 'Define quais tipos de faltas podem ocorrer para ausência de docente.';
        
        $nivel->popover = 'true';
        $nivel->popside = 'bottom';
        $nivel->poptitle = 'Tipos de Níveis para Pagamento';
        $nivel->popcontent = 'Define os níveis renumerados pela AC2.';
        
        $titulo->popover = 'true';
        $titulo->popside = 'bottom';
        $titulo->poptitle = 'Titularidades';
        $titulo->popcontent = 'Define a nomeclatura e nível de Títulos dos docentes.';
        
        $valores->popover = 'true';
        $valores->popside = 'bottom';
        $valores->poptitle = 'Planta de Valores por Aula';
        $valores->popcontent = 'Define a Planta de valores das respectivas aulas/titularidade';

        $orgaos->popover = 'true';
        $orgaos->popside = 'bottom';
        $orgaos->poptitle = 'Órgãos de Origem';
        $orgaos->popcontent = 'Define os órgãos de Origem bem como os Postos/Graduações e/ou Funções locais';
        
        $config->popover = 'true';
        $config->popside = 'bottom';
        $config->poptitle = 'Configuração de Funcionamento';
        $config->popcontent = 'Define como o sistema irá se comportar em certas ocasiões';

        $docs->popover = 'true';
        $docs->popside = 'bottom';
        $docs->poptitle = 'Define tipos de documentos';
        $docs->popcontent = 'Define o Tipo de Documentação e onde será cadastrada';
        
        //Classe dos botões
        $disciplina->class = 'btn btn-info btn-lg';
        $incidente->class = 'btn btn-info btn-lg';
        $nivel->class = 'btn btn-info btn-lg';
        $titulo->class = 'btn btn-info btn-lg';
        $valores->class = 'btn btn-info btn-lg';
        $orgaos->class = 'btn btn-info btn-lg';
        $config->class = 'btn btn-info btn-lg';
        $docs->class = 'btn btn-info btn-lg';
        //Scripts
        $disciplina->addFunction("__adianti_load_page('index.php?class=disciplinaList');");
        $incidente->addFunction("__adianti_load_page('index.php?class=tipo_incidenteList');");
        $nivel->addFunction("__adianti_load_page('index.php?class=nivel_pagamentoList');");
        $titulo->addFunction("__adianti_load_page('index.php?class=titularidadeList');");
        $valores->addFunction("__adianti_load_page('index.php?class=valores_pagamentoList');");
        $orgaos->addFunction("__adianti_load_page('index.php?class=orgaosorigemList');");
        $config->addFunction("__adianti_load_page('index.php?class=sisacadConfiguraFuncionamento');");
        $docs->addFunction("__adianti_load_page('index.php?class=tipo_docList');");
        
        //Básicos
        $hbox1 = new THBox;
        $hbox1->addRowSet( $disciplina,$incidente,$nivel,$titulo,$valores,$orgaos,$docs );
        $frame1 = new TFrame;
        $frame1->setLegend('Definições Básicas');
        $frame1->add($hbox1);
        
        //Configuração de Funcionamento
        $hbox2 = new THBox;
        $hbox2->addRowSet( $config );
        $frame3 = new TFrame;
        $frame3->setLegend('Configuração de Funcionamento');
        $frame3->add($hbox2);
        
        $vbox->style = 'width: 90%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'sisacadConfiguracao'));
        $vbox->add($frame1);
        $vbox->add($frame3);
        parent::add($vbox);
        
    }//Fim __construct
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReport ()
    {
        TApplication::loadPage('');
    }
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onAppend ()
    {
        TApplication::loadPage('bdhLancamentoForm');
    }
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onFechar ($param=null)
    {
        TApplication::loadPage('');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function buscaEscalas ($param)
    {
         try
         {
            TTransaction::open('sicad');
            $datainicio = mktime(0, 0, 0, date('m')-$param , 1 , date('Y'));
            $datafim = mktime(23, 59, 59, date('m')-($param-1), 0, date('Y'));
            //echo 'início ' . date('Y-m-d',$datainicio);            echo ' fim ' . date('Y-m-d',$datafim);
            $sql = "SELECT DISTINCT opm.id,opm.nome,opm.sigla FROM bdhoras.historicotrabalho, g_geral.opm ".
                    "WHERE historicotrabalho.opm_id = opm.id AND historicotrabalho.status = 'P' AND ".
                    "historicotrabalho.datainicio BETWEEN '".date('Y-m-d',$datainicio)." 00:00:00' AND '".date('Y-m-d',$datafim)." 23:59:59';";
            $conn = TTransaction::get();
            $res = $conn->prepare($sql);
            $res->execute();
            $repository = $res->fetchAll();
            //var_dump($sql);

            TTransaction::close();
            return $repository;
         }
         catch (Exception $e)
         {
            new TMessage('error', $e->getMessage()."<br>Erro ao buscar dados de Unidades que não Escala.<br>".$sql);
            TTransaction::rollback();
            return false;
         }        
    }//Fim Módulo 
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onReload($param)
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
        
}//Fim Classe
