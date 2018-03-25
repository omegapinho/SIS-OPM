<?php
/**
 * bdhRelatorioForm Form
 * @author  <your name here>
 */
class sisacadRelatorioFinanceiro extends TPage
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
    var $servico  = 'Relatório Financeiro';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado       = false;    //Controle de correção de chamado
    //Nomes registrados em banco de configuração e armazenados na array config
    private $cfg_carga_horaria_maxima     = 'carga_horaria_maxima';
    private $cfg_valor_maximo             = 'maximo_pago_mes';
    private $cfg_aula_validada            = 'pagar_aula_validada';
   
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
        $this->form->setFormTitle('SIS-ACADÊMICO - Relatório Financeiro');
        // create the form fields
        $criteria = new TCriteria;
        $criteria->add(new TFilter('oculto','!=','S'));
        $orgao         = new TDBCombo('orgao','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $cpf_professor = new TText('cpf_professor');
        $dt_inicio     = new TDate('dt_inicio');
        $dt_fim        = new TDate('dt_fim');
        $relatorio     = new TCombo('relatorio');
        $mes_ref       = new TCombo('mes_ref');
        $ano_ref       = new TCombo('ano_ref');
        $assinante     = new TCombo('assinante');
        $numero_ctrl   = new TEntry('numero_ctrl');

        //Mascara
        $dt_fim->setMask('dd/mm/yyyy');
        $dt_inicio->setMask('dd/mm/yyyy');
        $numero_ctrl->setMask('99999999999');
        
        //Valores dos Itens
        $relatorio->addItems(array (1=>"Pagamento Regular de Saldo",2=>"Estorno de Pagamento Regular de Saldo"),true);
        $relatorio->setValue(1);
        $mes_ref->addItems($fer->lista_meses());
        $ano_ref->addItems($fer->lista_anos());
        $assinante->addItems($fer->getAssinantes($this->listas['valores'])); //Pega a lista já preparada com as opms
        //Ações
        //$numero_ctrl->setEditable(false);
        $action = new TAction(array($this, 'onChangeRelatorio'));
        //$relatorio->setChangeAction($action);
        
        $tmes = date('m')-1;
        $tano = date('Y');
        if ($tmes==0)
        {
            $tano = $tano -1;
            $tmes = 12;
        }
        $ano_ref->setValue($tano);
        $mes_ref->setValue($tmes);
        
        //Dicas
        $relatorio->setTip('Escolha um tipo de Relatório para executar.');
        
        // add the fields
        $this->form->addQuickField($lbl = new TLabel('Tipo de Relatório/Estorno'), $relatorio,  300 );
        $lbl->setFontColor('red');
        $this->form->addQuickField('Órgão de Origem', $orgao, 400 );
        $this->form->addQuickField('CPF(s) de Professor(es)', $cpf_professor,  100 );
        $this->form->addQuickField('Data de Início', $dt_inicio, 100 );
        $this->form->addQuickField('Data Final', $dt_fim, 100 );
        $this->form->addQuickField('Mês de Referência', $mes_ref, 120 );
        $this->form->addQuickField('Ano de Referência', $ano_ref, 80 );
        $this->form->addQuickField('Assinante', $assinante, 300 );
        $this->form->addQuickField($lbl = new TLabel('Número de Controle'), $numero_ctrl, 120 );
        $lbl->setFontColor('red');
        
        //Tamanho
        $cpf_professor->setSize(400,80);
        
        //Botões de Serviço
        $run = new TButton('view');
        $run->setAction(new TAction(array($this, 'onView')));
        $run->setLabel('Visualiza');
        $run->setImage('fa:eye red');
        $run->setTip('Apenas visualiza o que será pago/estornado. NÃO GRAVA A AÇÃO DE PAGAMENTO/ESTORNO!');
        $run->class = 'btn btn-success';

        $ret = new TButton('return');
        $ret->setAction(new TAction(array($this, 'onReturn')));
        $ret->setLabel('Volta aos Relatórios');
        $ret->setImage('fa:backward white');
        $ret->class = 'btn btn-info';
        
        $prt = new TButton('run');
        $prt->setAction(new TAction(array($this, 'onExec')));
        $prt->setLabel('Executa');
        $prt->setImage('fa:dollar green');
        $prt->setTip('Executa o PAGAMENTO/ESTORNO das aulas.');
        $prt->class = 'btn btn-danger';

        $this->form->add($run);
        $this->form->add($prt);
        $this->form->add($ret);
        $this->form->setFields(array($run,$prt,$ret,$relatorio,$orgao,$cpf_professor,$dt_fim,$dt_inicio,$assinante));        
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
        $data = TSession::getValue('temp_estorno');
        if (!empty($data))
        {
            $data['executa_gravacao'] = 'S';
            $data['motivo']           = (strlen($param['motivo']) > 10) ? $param['motivo'] : null;
            TSession::setValue('temp_estorno' , null);
            $param = $data;
            TForm::sendData('form_relatorio',(object) $data);
        }
        try
        {
            $data         = $this->form->getData();
            $report       = new TSisacadFinanceiroReport();
            $fer          = new TFerramentas();
            $controle     = new TControleGeracao();
            
            //Define o que será feito
            $tipo                 = (empty($param['relatorio'])) ? 1 : $param['relatorio'];       
            $report->tipo         = $tipo;
            //numero do documento Gerado (Número de Controle dos documentos)
            $numero               = (empty($param['numero_ctrl'])) ? null : $fer->soNumeros($param['numero_ctrl']);   
            if (null == $numero && ($tipo == 2))
            {
                throw new Exception('Número de Referência de Relatório Gerado não foi informado.');
            }
            else if (($tipo == 2)  && $controle->buscaControle($numero) == false)
            {
                throw new Exception('O Número de Referência não existe ou já não há o que cancelar no mesmo.');
            }
            if ($tipo == 2 && empty($param['motivo']) && $param['executa_gravacao'] == 'S')
            {
                throw new Exception('A declaração dos motivos de Estorno são inexistentes ou insuficientes.');
            }
            //exit;
            $report->numero_ctrl  = $numero;
            //Troca valores conforme tipo de relatório
            if ($tipo == 1)
            {
                $dt_inicio            = (!empty($param['dt_inicio'])) ? TDate::date2us($param['dt_inicio']) : '';
                $dt_fim               = (!empty($param['dt_fim']))    ? TDate::date2us($param['dt_fim'])    : '';
                $orgao                = (!empty($param['orgao']))     ? $param['orgao']                     : '';
                $report->retificado   = 'N';
                $report->motivo       = '';
            }
            else if ($tipo ==2)
            {
                $dt_inicio            = '';
                $dt_fim               = '';
                $orgao                = '';
                $report->retificado   = (!empty($param['cpf_professor'])) ? 'S' : 'N';
                $report->motivo       = $param['motivo'];
            }
            $aula_validada        = (!empty($this->config[$this->cfg_aula_validada])) 
                                        ? $this->config[$this->cfg_aula_validada] 
                                        : 'N';
            $report->aula_validada    = $aula_validada;       //Ativa/Desativa pagar somente aula validada
            $cpf                      = (!empty($param['cpf_professor'])) 
                                        ? $report->get_ListaProfessores(array('cpf'=>$param['cpf_professor'],'orgao'=>null,'dt_inicio'=>$dt_inicio,'dt_fim'=>$dt_fim)) 
                                        : $report->get_ListaProfessores(array('cpf'=>null,'orgao'=>$orgao,'dt_inicio'=>$dt_inicio,'dt_fim'=>$dt_fim));
                                        //Busca o(s) professor(es) conforme definições de filtro
    
            $valor_maximo             = (!empty($this->config[$this->cfg_valor_maximo])) 
                                        ? $this->config[$this->cfg_valor_maximo] 
                                        : 0;
            $executa_gravacao         = (!empty($param['executa_gravacao'])) 
                                        ? $param['executa_gravacao'] 
                                        : 'N';
            $assinante                = (!empty($param['assinante']) && $param['assinante']!='0') ? $param['assinante'] : null;

            if (empty($cpf))
            {
                throw new Exception('Não há professores com saldo a pagar.');
            }
            //Cria e configura o Relatório
            $report->dt_inicio        = $dt_inicio;
            $report->dt_fim           = $dt_fim;
            //$report->cpf            = $cpf;//Já está armazenado no $report
            $report->orgao            = $orgao;
            $report->assinatura       = $fer->getAssinatura($assinante);//Envia quem está assinando o relatório (usuário atual)
            $report->valor_maximo     = $valor_maximo;        //Envia o valor máximo pago por mês (zero se nao tiver limite)
            $report->executa_gravacao = $executa_gravacao;  
            $report->ano_ref          = (!empty($param['ano_ref'])) ? $param['ano_ref'] : date('Y');
            $report->mes_ref          = $fer->lista_meses((!empty($param['mes_ref'])) ? $param['mes_ref'] : date('m'));     
            $cab = 'Saldo a pagar ';
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
            $report->unidade = $cab;            //Entra com a unidade e dados pedidos se for o caso
            $men = '<center>'.$report->mainMensal().'</center>';
            if ($tipo == 1)
            {
                $this->form->setData($data);
            }
            else
            {
                //$std = (object) $param;
                //TForm::sendData('form_relatorio',$std);
                $this->form->setData($data);
            }
            //exit;
            $window = TWindow::create($cab, 1200, 600);
            $window->add($men);
            $window->show();
        }
        catch (Exception $e) // in case of exception 
        {
            $this->form->setData($data);
            $this->onChangeRelatorio($param);
            new TMessage('info',$e->getMessage());
        }
        
        return false;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onView($param)
    {
        $param['executa_gravacao'] = 'N';
        $param['motivo'] = null;
        $this->ExecutaRelatorio($param);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function onExec($param)
    {
        // define the delete action
        
        if ($param['relatorio'] == 1)
        {
            $action = new TAction(array($this, 'Exec'));
            $action->setParameters($param); // pass the key parameter ahead
            
            // shows a dialog to the user
            new TQuestion('Deseja executar o pagamento/estorno? <br>'.
                          'Essa ação implica em registrar todos os pagamentos/estornos.<br>'.
                          'Para o Estorno os valores voltam a planta de pagamentos saindo no próximo relatório.', $action);
        }
        else if ($param['relatorio'] == 2)
        {
            $data = $this->form->getData();
            TSession::setValue('temp_estorno' , $param);
            $motivo = new TText('motivo');
            $motivo->setSize(300,60);
            $form = new TForm('input_form');
            $form->style = 'padding:20px';
            
            $table = new TTable;
            $table->addRowSet( $lbl = new TLabel('Motivo do Estorno: '), $motivo );
            $lbl->setFontColor('red');
    
            
            $form->setFields(array($motivo));
            $form->add($table);
            
            // show the input dialog
            $action = new TAction(array($this, 'ExecutaRelatorio'));
            $action->setParameter('stay-open', 1);
            $this->form->setData($data);
            new TInputDialog('Relate o Motivo do Estorno', $form, $action, 'Confirma');
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function ExecEstorno($param)
    {
        $form = $this->form->getData();
        $data = TSession::getValue('temp_estorno');
        TSession::setValue('temp_estorno' , null);
        
        $data['executa_gravacao'] = 'S';
        $data['motivo']           = (strlen($param['motivo']) > 10) ? $param['motivo'] : null;
        $this->form->setData($form);
        $this->ExecutaRelatorio($data);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public function Exec($param)
    {
        $param['executa_gravacao'] = 'S';
        $param['motivo'] = null;
        $this->ExecutaRelatorio($param);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  
 *---------------------------------------------------------------------------------------*/
    public static function onChangeRelatorio($param)
    {
        return;
        if ((is_array($param)  && !array_key_exists('relatorio',$param)) || (!is_array($param)))
        {
            return false;
        }
        else
        {
            $relatorio = $param['relatorio'];
        }
        switch ($relatorio)
        {
            case 2:
                TEntry::enableField('form_relatorio', 'numero_ctrl');
                
                TCombo::disableField('form_relatorio', 'orgao');
                TCombo::disableField('form_relatorio', 'mes_ref');
                TCombo::disableField('form_relatorio', 'ano_ref');
                TCombo::disableField('form_relatorio', 'assinante');
                //TDate::disableField('form_relatorio', 'dt_inicio');
                //TDate::disableField('form_relatorio', 'dt_fim');
                break;
            default:
                TEntry::disableField('form_relatorio', 'numero_ctrl');
                
                TCombo::enableField('form_relatorio', 'orgao');
                TCombo::enableField('form_relatorio', 'mes_ref');
                TCombo::enableField('form_relatorio', 'ano_ref');
                TCombo::enableField('form_relatorio', 'assinante');
                //TDate::enableField('form_relatorio', 'dt_inicio');
                //TDate::enableField('form_relatorio', 'dt_fim');
                break;
        }
        //return true;
        
    }//Fim Módulo
    
}//Fim Classe
