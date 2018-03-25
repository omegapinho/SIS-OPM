<?php
/**
 * bdhRelatorioForm Form
 * @author  <your name here>
 */
class sisacadRelatorioCurso extends TPage
{
    protected $form; // form
    
    protected $lista_opm;
    private $lista;
    private $lista_slc;

/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Relatório Curso';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado       = false;    //Controle de correção de chamado
   
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
        // creates the form
        $this->form = new TQuickForm('form_relatorio');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('SIS-ACADÊMICO - Relatório de Cursos');
        // create the form fields
        $criteria = new TCriteria;
        $criteria->add(new TFilter('oculto','!=','S'));
        $orgao         = new TDBCombo('orgao','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $dt_inicio     = new TDate('dt_inicio');
        $dt_fim        = new TDate('dt_fim');
        $relatorio     = new TCombo('relatorio');

        $carga_horaria = new TEntry('carga_horaria');
        $natureza = new TCombo('natureza');
        $tipo_curso = new TCombo('tipo_curso');
        $nivel_pagamento_id = new TDBCombo('nivel_pagamento_id','sisacad','nivel_pagamento','id','nome','nome');
        $oculto = new TCombo('oculto');

        $assinante     = new TCombo('assinante');

        //Mascara
        $dt_fim->setMask('dd/mm/yyyy');
        $dt_inicio->setMask('dd/mm/yyyy');
        $carga_horaria->setMask('99999999999');
        
        //Valores dos Itens
        $relatorio->addItems(array (1=>"Cursos em Andamento e suas Turmas",
                                    2=>"Cursos e suas disciplinas de ementa",
                                    3=>"Cursos e seus instrutores designados por turma",
                                    4=>"Curso e o progresso das disciplinas de suas turmas"),true);
        $relatorio->setValue(1);
        $natureza->addItems($fer->lista_natureza_curso());
        $tipo_curso->addItems($fer->lista_tipos_curso());
        $oculto->addItems($fer->lista_sim_nao());
        $assinante->addItems($fer->getAssinantes($this->listas['valores'])); //Pega a lista já preparada com as opms
        
        //Dicas
        $relatorio->setTip('Escolha um tipo de Relatório para executar.');
        
        // add the fields
        $this->form->addQuickField($lbl = new TLabel('Tipo de Relatório'), $relatorio,  300 );
        $lbl->setFontColor('red');
        $this->form->addQuickField('Instituição Interessada', $orgao, 400 );
        $this->form->addQuickField('Natureza do Curso', $natureza, 400 );
        $this->form->addQuickField('Tipo de Curso', $tipo_curso, 400 );
        $this->form->addQuickField('Nível do Curso', $nivel_pagamento_id, 400 );
        $this->form->addQuickField('Carga Horária Mínima', $carga_horaria,  80 );
        $this->form->addQuickField('Data de Início', $dt_inicio, 100 );
        $this->form->addQuickField('Data Final', $dt_fim, 100 );
        $this->form->addQuickField('Lista Cursos Encerrados?', $oculto, 100 );
        $this->form->addQuickField('Assinante', $assinante, 300 );
        
        //Ações
        //$troca = new TAction(array($this, 'onChangeTempo'));//Troca o tipo de serviço e seus dados
        
        //Botões de Serviço
        $run = new TButton('view');
        $run->setAction(new TAction(array($this, 'onView')));
        $run->setLabel('Visualiza Relatório');
        $run->setImage('fa:eye black');
        $run->setTip('Executa os filtros e monta um relatório!');
        $run->class = 'btn btn-success';

        $ret = new TButton('return');
        $ret->setAction(new TAction(array($this, 'onReturn')));
        $ret->setLabel('Volta aos Relatórios');
        $ret->setImage('fa:backward black');
        $ret->class = 'btn btn-warning';
        
        /*$prt = new TButton('run');
        $prt->setAction(new TAction(array($this, 'onExec')));
        $prt->setLabel('Executa Pagamento');
        $prt->setImage('fa:dollar black');
        $prt->setTip('Executa o faturamento registrando o pagamento das aulas.');
        $prt->class = 'btn btn-danger';*/

        $this->form->add($run);
        //$this->form->add($prt);
        $this->form->add($ret);
        $this->form->setFields(array($run,$ret,$relatorio,$orgao,$carga_horaria,$tipo_curso,$nivel_pagamento_id,
                                        $oculto,$natureza,$dt_fim,$dt_inicio,$assinante));        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'sisacadRelatorios'));
        $container->add($this->form);

        parent::add($container);
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
        TApplication::loadPage('sisacadRelatorios');
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Executa o relatório
 * @param  => dt_inicio e dt_fim são o intervalo do relatório
 *         => orgao é o orgão de vínculo do professor
 *         => cpf_professor é, para o caso de um extrato individual, a indentificação do professor
 *         => relatorio é o tipo do relatorio sendo padrão o tipo 1
 *---------------------------------------------------------------------------------------*/
    public function ExecutaRelatorio($param)
    {
        $data         = $this->form->getData();
        $report       = new TSisacadCursoReport();
        $fer          = new TFerramentas();
        
        $dt_inicio    = TDate::date2us($param['dt_inicio']);
        $dt_fim       = TDate::date2us($param['dt_fim']);
        $orgao        = $param['orgao'];
        $tipo         = (empty($param['relatorio'])) ? 1 : $param['relatorio'];
        $assinante    = (!empty($param['assinante']) && $param['assinante']!='0') ? $param['assinante'] : null;
        $oculto       = (empty($param['oculto'])) ? 'T' : $param['oculto'];
        $nivel        = (empty($param['nivel_pagamento_id'])) ? 'T' : $param['nivel_pagamento_id'];
        $natureza     = (empty($param['natureza'])) ? 'T' : $param['natureza'];
        $tipo_curso   = (empty($param['tipo_curso'])) ? 'T' : $param['tipo_curso'];
        $carga_hora   = (empty($param['carga_horaria'])) ? 0 : $param['carga_horaria'];    
                             
        //var_dump($this->config[$this->cfg_aula_validada]);exit;
        //Cria e configura o Relatório

        $report->dt_inicio        = $dt_inicio;
        $report->dt_fim           = $dt_fim;
        $report->carga_horaria    = $carga_hora;//Já está armazenado no $report
        $report->tipo             = $tipo;
        $report->orgao            = $orgao;
        $report->natureza         = $natureza;
        $report->tipo_curso       = $tipo_curso;
        $report->nivel            = $nivel;
        $report->oculto           = $oculto;
        $report->assinatura       = $fer->getAssinatura($assinante);//Envia quem está assinando o relatório (usuário atual)

        /*$cab = 'Saldo a pagar ';
        if (!empty($dt_inicio) && !empty($dt_fim))
        {
            $cab.='entre '.$dt_inicio.' e '.$dt_fim;
        }
        else if(!empty($dt_inicio) && empty($dt_fim))
        {
            $cab.='a partir de '.$dt_inicio;    
        }
        else if (empty($dt_inicio) && !empty($dt_fim))
        {
            $cab.='até '.$dt_fim;
        }
        /*if (!empty($param['cpf_professor']))
        {
            $posto = (!empty($cpf['posto'])) ? $cpf['posto'] : '';
            $cab.=' do professor '.$posto . ' '.$cpf['nome'];
        }
        else
        { 
            //$cab .= ' do(a) '.$this->get_Orgao($orgao);
        }*/
        $report->unidade = '';//$cab;            //Entra com a unidade e dados pedidos se for o caso
        $men = '<center>'.$report->mainMensal().'</center>';
        //var_dump( $men);
        $this->form->setData($data);
        $window = TWindow::create($cab, 1200, 600);
        $window->add($men);
        $window->show();
    
        return false;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onView($param)
    {
        $param['executa_gravacao'] = 'N';
        $this->ExecutaRelatorio($param);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onExec($param)
    {
        // define the delete action
        $action = new TAction(array($this, 'Exec'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion('Deseja executar o pagamento? <br>'.
                      'Essa ação implica em registrar todos os pagamentos sendo de díficil conserto.', $action);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function Exec($param)
    {
        $param['executa_gravacao'] = 'S';
        $this->ExecutaRelatorio($param);
    }//Fim Módulo
}//Fim Classe
