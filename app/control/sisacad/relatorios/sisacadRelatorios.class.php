<?php
/**
 * sisacadConfiguracao
 * @author  Fernando de Pinho Araújo
 */
class sisacadRelatorios extends TPage
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
    var $servico  = 'Relatório Financeiro';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado       = false;    //Controle de correção de chamado
    //Nomes registrados em banco de configuração e armazenados na array config
    /*private $cfg_carga_horaria_maxima     = 'carga_horaria_maxima';
    private $cfg_valor_maximo             = 'maximo_pago_mes';
    private $cfg_aula_validada            = 'pagar_aula_validada';*/
   
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
        $financeiro = new TButton('financeiro');
        $curso      = new TButton('curso');
        $nivel      = new TButton('nivel');
        $titulo     = new TButton('titulo');
        $valores    = new TButton('valores');
        $orgaos     = new TButton('orgaos');
        $config     = new TButton('config');
        //Labels
        $financeiro->setLabel('Financeiro');
        $curso->setLabel('Cursos');
        $nivel->setLabel('Níveis');
        $titulo->setLabel('Títulos');
        $valores->setLabel('Valores');
        $orgaos->setLabel('Órgãos');
        $config->setLabel('Funcionamento');
        //Icones
        $financeiro->setImage('fa:money gray');
        $curso->setImage('fa:flag-checkered red');
        $nivel->setImage('fa:percent gray');
        $titulo->setImage('fa:graduation-cap gray');
        $valores->setImage('fa:usd green');
        $orgaos->setImage('fa:university gray');
        $config->setImage('fa:gears red');
        //PopUps
        $financeiro->popover = 'true';
        $financeiro->popside = 'bottom';
        $financeiro->poptitle = 'Relatórios Financeiros';
        $financeiro->popcontent = 'Gera Relatórios financeiros para fins de pagamento';
        
        $curso->popover = 'true';
        $curso->popside = 'bottom';
        $curso->poptitle = 'Relatórios de Curso';
        $curso->popcontent = 'Gera Relatórios relativos aos cursos';
        
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
        
        //Classe dos botões
        $financeiro->class = 'btn btn-info btn-lg';
        $curso->class = 'btn btn-info btn-lg';
        $nivel->class = 'btn btn-info btn-lg';
        $titulo->class = 'btn btn-info btn-lg';
        $valores->class = 'btn btn-info btn-lg';
        $orgaos->class = 'btn btn-info btn-lg';
        $config->class = 'btn btn-info btn-lg';
        //Scripts
        $financeiro->addFunction("__adianti_load_page('index.php?class=sisacadRelatorioFinanceiro');");
        $curso->addFunction("__adianti_load_page('index.php?class=sisacadRelatorioCurso');");
        $nivel->addFunction("__adianti_load_page('index.php?class=nivel_pagamentoList');");
        $titulo->addFunction("__adianti_load_page('index.php?class=titularidadeList');");
        $valores->addFunction("__adianti_load_page('index.php?class=valores_pagamentoList');");
        $orgaos->addFunction("__adianti_load_page('index.php?class=orgaosorigemList');");
        $config->addFunction("__adianti_load_page('index.php?class=sisacadConfiguraFuncionamento');");
        
        $documento = new TButton('documento');
        $documento->setLabel('Doc. Gerados');
        $documento->setImage('fa:file-text-o gray');
        $documento->popover = 'true';
        $documento->popside = 'bottom';
        $documento->poptitle = 'Gestão de Documentos Gerados';
        $documento->popcontent = 'Visualiza e permite alterações pontuais em documentos Gerados.';
        $documento->class = 'btn btn-info btn-lg';
        $documento->addFunction("__adianti_load_page('index.php?class=controle_geracaoList');");
        
        //Horizontal Box-01        
        $hbox1 = new THBox;
        $hbox1->addRowSet( $financeiro,$documento,$curso,$nivel,$titulo,$valores,$orgaos );
        $frame1 = new TFrame;
        $frame1->setLegend('Relatórios Básicos');
        $frame1->add($hbox1);
        
        $hbox2 = new THBox;
        $hbox2->addRowSet( $config );
        $frame3 = new TFrame;
        $frame3->setLegend('Relatórios Diversos');
        $frame3->add($hbox2);
        
        //Notificações
        // creates a table
        $table = new TTable;
        
        // creates a label with the title
        $title4 = new TLabel('4 Meses atrás');
        $title4->setFontSize(12);
        $title4->setFontFace('Arial');
        $title4->setFontColor('black');
        $title4->setFontStyle('b');
        
        $title3 = new TLabel('3 Meses atrás');
        $title3->setFontSize(12);
        $title3->setFontFace('Arial');
        $title3->setFontColor('black');
        $title3->setFontStyle('b');
        
        
        $title2 = new TLabel('2 Meses atrás');
        $title2->setFontSize(12);
        $title2->setFontFace('Arial');
        $title2->setFontColor('black');
        $title2->setFontStyle('b');
        
        $title1 = new TLabel('Mês passado');
        $title1->setFontSize(12);
        $title1->setFontFace('Arial');
        $title1->setFontColor('black');
        $title1->setFontStyle('b');
        
        $table-> border = '1';
        $table-> cellpadding = '4';
        $table-> style = 'border-collapse:collapse; text-align: center;';
        
        //4 meses atras
        $this->quatro = new TQuickGrid('quatro');
        $this->quatro->setHeight( 170 );
        $this->quatro->makeScrollable();
        $this->quatro->disableDefaultClick();
        $this->quatro->addQuickColumn('OPM', 'sigla', 'center');
        $this->quatro->createModel();
        
        $this->tres = new TQuickGrid('tres');
        $this->tres->setHeight( 170 );
        $this->tres->makeScrollable();
        $this->tres->disableDefaultClick();
        $this->tres->addQuickColumn('OPM', 'sigla', 'center');
        $this->tres->createModel();
        
        $this->dois = new TQuickGrid('dois');
        $this->dois->setHeight( 170 );
        $this->dois->makeScrollable();
        $this->dois->disableDefaultClick();
        $this->dois->addQuickColumn('OPM', 'sigla', 'center');
        $this->dois->createModel();
        
        $this->um_mes = new TQuickGrid('um_mes');
        $this->um_mes->setHeight( 170 );
        $this->um_mes->makeScrollable();
        $this->um_mes->disableDefaultClick();
        $this->um_mes->addQuickColumn('OPM', 'sigla', 'center');
        $this->um_mes->createModel();
        
        // adds a row to the table
        $row=$table->addRowSet($title4,$title3,$title2,$title1);
        $row=$table->addRowSet($this->quatro,$this->tres,$this->dois,$this->um_mes);
        
        $hbox2 = new THBox;
        $hbox2->addRowSet( $table );
        $frame2 = new TFrame;
        $frame2->setLegend('Escalas Não Fechadas');
        $frame2->add($hbox2);
        
        $vbox->style = 'width: 90%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'sisacadRelatorios'));
        $vbox->add($frame1);
        //$vbox->add($frame3);
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
        for ($i = 1; $i <= 4; $i++) 
        {
            switch ($i)
            {
                case 1:
                    $this->um_mes->clear();
                    break;
                case 2:
                    $this->dois->clear();
                    break;
                case 3:
                    $this->tres->clear();
                    break;
                case 4:
                    $this->quatro->clear();
                    break;
            }
            
            $escalas = self::buscaEscalas($i); 
            if ($escalas)
            {
                foreach ($escalas as $escala)
                {
                    $item = new StdClass;
                    $item->sigla = $escala['sigla'];
                    switch ($i)
                    {
                        case 1:
                            $row = $this->um_mes->addItem( $item );
                            break;
                        case 2:
                            $row = $this->dois->addItem( $item );
                            break;
                        case 3:
                            $row = $this->tres->addItem( $item );
                            break;
                        case 4:
                            $row = $this->quatro->addItem( $item );
                            break;
                    }
                    $row->onmouseover='';
                    $row->onmouseout='';
                }
            }
            else
            {
                $item = new StdClass;
                $item->sigla = 'Nenhum Escala Aberta';
                switch ($i)
                {
                    case 1:
                        $row = $this->um_mes->addItem( $item );
                        break;
                    case 2:
                        $row = $this->dois->addItem( $item );
                        break;
                    case 3:
                        $row = $this->tres->addItem( $item );
                        break;
                    case 4:
                        $row = $this->quatro->addItem( $item );
                        break;
                }
                $row->onmouseover='';
                $row->onmouseout='';
            }
        }
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
