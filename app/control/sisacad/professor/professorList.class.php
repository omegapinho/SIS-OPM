<?php
/**
 * professorList Listing
 * @author  <your name here>
 */
class professorList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Professor';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado = false;          //Controle de correção de chamado
   
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
        $this->form = new TQuickForm('form_search_professor');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem de professores');


        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');

        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','!=','S'));
        $orgao_origem = new TDBCombo('orgaosorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $rg           = new TEntry('rg');
        $postograd    = new TDBCombo('postograd_id','sisacad','postograd','id','nome','nome',$criteria);


        $data_nascimento = new TDate('data_nascimento');
        $sexo = new TCombo('sexo');
        $oculto = new TCombo('oculto');
        $titulos = new TCombo('titulos');
        $minha_opm = new TCombo('minha_opm');
        
        $criteria = new TCriteria();
        if ($this->nivel_sistema<=80)//Lista as disciplinas somente da unidade dos Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            
            $query3 = "(SELECT DISTINCT curso_id FROM sisacad.turma WHERE id IN " . $query1 . 
                        " OR id IN " . $query2 . " )";
            $query4 = "(SELECT DISTINCT disciplina_id FROM sisacad.materias_previstas WHERE curso_id IN " . $query3 . ")";
            $criteria->add (new TFilter ('id','IN',$query4));
        }
        $criteria->add (new TFilter ('oculto','!=','S'));
        $disciplina_id = new TDBCombo('disciplina_id','sisacad','disciplina','id','nome','nome',$criteria);

        //Tamanhos
        $id->setSize(50);
        $nome->setSize(300);
        $orgao_origem->setSize(200);
        $postograd->setSize(180);
        $rg->setSize(120);
        $data_nascimento->setSize(100);
        $sexo->setSize(140);
        $oculto->setSize(80);
        $disciplina_id->setSize(400);
        $titulos->setSize(80);
        $minha_opm->setSize(80);
        
        //Valores
        $sexo->addItems($fer->lista_sexo());
        $oculto->addItems($fer->lista_sim_nao());
        $titulos->addItems($fer->lista_sim_nao());
        $minha_opm->addItems($fer->lista_sim_nao());
        
        //Mascara
        $data_nascimento->setMask('dd-mm-yyyy');
        $rg->setMask('999999999');
        $id->setMask('999999999');
        
        //Ações
        $change_action_posto = new TAction(array($this, 'onChangeAction_posto'));//Popula as cidades com a troca da UF
        $orgao_origem->setChangeAction($change_action_posto);

        // add the fields
        $table = new TTable();
        $table->addRowSet(array(new TLabel('ID'),$id,new TLabel('nome'),$nome,new TLabel('RG'),$rg));
        $table->addRowSet(array(new TLabel('Órgão de Origem'),$orgao_origem,new TLabel('Cargo'),$postograd));
        $table->addRowSet(array(new TLabel('D.N.'),$data_nascimento,new TLabel('Sexo'),$sexo));
        $table->addRowSet(array(new TLabel('Inativo?'),$oculto,new TLabel('Lista só quem tem Título(s)'),$titulos, 
                                new TLabel('Somente quem gerencio?'),$minha_opm));
        $table->addRowSet(array(new TLabel('Disciplina'),$disciplina_id));
        
        $frame = new TFrame();
        $frame->setLegend('Filtros');
        $frame->add($table);
        $this->form->add($frame);
        
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($orgao_origem);
        $this->form->addField($postograd);
        $this->form->addField($rg);
        $this->form->addField($data_nascimento);
        $this->form->addField($sexo);
        $this->form->addField($oculto);
        $this->form->addField($titulos);
        $this->form->addField($minha_opm);
        $this->form->addField($disciplina_id);
        
        //Botões        
        $onSearch = new TButton('onSearch');
        $onSearch->setLabel(_t('Find'));
        $onSearch->setImage('fa:search');
        $onSearch->class = 'btn btn-info btn-lg';
        $onSearch->popover = 'true';
        $onSearch->popside = 'bottom';
        $onSearch->poptitle = 'Busca';
        $onSearch->popcontent = 'Busca os docentes que atendam aos filtros';
        $onSearch->setAction(new TAction(array($this, 'onSearch')));

        $onNew = new TButton('onNew');
        $onNew->setLabel(_t('New'));
        $onNew->setImage('bs:plus-sign gray');
        $onNew->class = 'btn btn-info btn-lg';
        $onNew->popover = 'true';
        $onNew->popside = 'bottom';
        $onNew->poptitle = 'Novo Professor';
        $onNew->popcontent = 'Cadastra um novo professor';
        $onNew->setAction(new TAction(array('professorForm', 'onEdit')));     
        
        $onPrt = new TButton('onPrt');
        $onPrt->setLabel(_t('New'));
        $onPrt->setImage('bs:print black');
        $onPrt->class = 'btn btn-info btn-lg';
        $onPrt->popover = 'true';
        $onPrt->popside = 'bottom';
        $onPrt->poptitle = 'Imprime Listagem de professores';
        $onPrt->popcontent = 'Imprime uma listagem dos professores com base no(s) filtro(s) atual(is).';
        $onPrt->setAction(new TAction(array($this, 'onImprime')));  
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('professor_filter_data') );
        $frame->add($onSearch);
        $frame->add($onNew);
        $frame->add($onPrt);
        $this->form->addField($onSearch);
        $this->form->addField($onNew);
        $this->form->addField($onPrt);
        if ($this->nivel_sistema>80)
        {
            $onImport = new TButton('onImport');
            $onImport->setLabel('Importar');
            $onImport->setImage('fa:download red');
            $onImport->class = 'btn btn-info btn-lg';
            $onImport->popover = 'true';
            $onImport->popside = 'bottom';
            $onImport->poptitle = 'Importa Dados';
            $onImport->popcontent = 'Importa dados de uma tabela csv para o sistema';
            $onImport->setAction(new TAction(array($this, 'onImporte')));
            
            $onExport = new TButton('onExport');
            $onExport->setLabel('Exportar');
            $onExport->setImage('fa:upload green');
            $onExport->class = 'btn btn-info btn-lg';
            $onExport->popover = 'true';
            $onExport->popside = 'bottom';
            $onExport->poptitle = 'Exporta';
            $onExport->popcontent = 'Exporta dos dados dos professores para uma tabela csv(compatível com excel)';
            $onExport->setAction(new TAction(array($this, 'onExporte')));
            $frame->add($onImport);
            $frame->add($onExport);
            $this->form->addField($onImport);
            $this->form->addField($onExport);
            
        } 
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';

        // creates the datagrid columns
        $column_check           = new TDataGridColumn('check', '', 'center');
        $column_id              = new TDataGridColumn('id', 'ID', 'rigth');
        $column_rg              = new TDataGridColumn('rg', 'RG', 'rigth');
        $column_nome            = new TDataGridColumn('nome', 'Nome', 'center');
        $column_postograd       = new TDataGridColumn('postograd_id', 'Posto/Graduação', 'center');
        $column_orgao_origem    = new TDataGridColumn('orgaosorigem_id', 'Órgão de Origem', 'center');
        $column_data_nascimento = new TDataGridColumn('data_nascimento', 'D.N.', 'left');
        $column_sexo            = new TDataGridColumn('sexo', 'Sexo', 'center');
        $column_oculto          = new TDataGridColumn('oculto', 'Inativo?', 'center');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_rg);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_postograd);
        $this->datagrid->addColumn($column_orgao_origem);
        $this->datagrid->addColumn($column_data_nascimento);
        $this->datagrid->addColumn($column_sexo);
        $this->datagrid->addColumn($column_oculto);

        // creates the datagrid column actions
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);

        $order_rg = new TAction(array($this, 'onReload'));
        $order_rg->setParameter('order', 'rg');
        $column_rg->setAction($order_rg);
        
        $order_postograd = new TAction(array($this, 'onReload'));
        $order_postograd->setParameter('order', 'postograd');
        $column_postograd->setAction($order_postograd);
        
        $order_orgao_origem = new TAction(array($this, 'onReload'));
        $order_orgao_origem->setParameter('order', 'orgao_origem');
        $column_orgao_origem->setAction($order_orgao_origem);
        
        $order_data_nascimento = new TAction(array($this, 'onReload'));
        $order_data_nascimento->setParameter('order', 'data_nascimento');
        $column_data_nascimento->setAction($order_data_nascimento);
        
        $order_sexo = new TAction(array($this, 'onReload'));
        $order_sexo->setParameter('order', 'sexo');
        $column_sexo->setAction($order_sexo);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);

        // define the transformer method over image
        $column_data_nascimento->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });

        //Cria grupo de ações para a data Grid
        $action_group = new TDataGridActionGroup('Ações', 'fa:gear black');
        $action_group->addHeader('Serviços Gerais');
       
        // add the actions to the datagrid
        $this->datagrid->addActionGroup($action_group);
        
        // create EDIT action
        //$action_edit = new TDataGridAction(array('professorForm', 'onEdit'));
        $action_edit = new TDataGridAction(array($this, 'onEditProntuario'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        //$action_group->addAction($action_edit);
        
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(false);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('fa:trash-o red fa-lg');
        $action_del->setField('id');
        
        if ($this->nivel_sistema>80)
        { 
            $this->datagrid->addAction($action_del);
            //$action_group->addAction($action_del);
        }

        // create Título action
        $action_cert = new TDataGridAction(array($this, 'onEditEscolaridade'));
        $action_cert->setUseButton(false);
        $action_cert->setButtonClass('btn btn-default');
        $action_cert->setLabel('Cadastra a Titularidade');
        $action_cert->setImage('fa:certificate green fa-lg');
        $action_cert->setField('id');
        //$this->datagrid->addAction($action_cert);
        $action_group->addAction($action_cert);

        // create Área de Interesse action
        $action_int = new TDataGridAction(array('disciplinaProfessorForm', 'onEdit'));
        $action_int->setUseButton(false);
        $action_int->setButtonClass('btn btn-default');
        $action_int->setLabel('Vincula Área de interesse');
        $action_int->setImage('fa:exclamation yellow fa-lg');
        $action_int->setField('id');
        //$this->datagrid->addAction($action_int);
        
        // create documentos action
        $action_doc = new TDataGridAction(array($this, 'onDocumento'));
        $action_doc->setUseButton(false);
        $action_doc->setButtonClass('btn btn-default');
        $action_doc->setLabel('Carrega Documentação');
        $action_doc->setImage('fa:download blue fa-lg');
        $action_doc->setField('id');
        //$this->datagrid->addAction($action_doc);
        $action_group->addAction($action_doc);
       
        // create Vincula Turmas action
        $action_tur = new TDataGridAction(array('professorVinculoTurmaForm', 'onEdit'));
        $action_tur->setUseButton(false);
        $action_tur->setButtonClass('btn btn-default');
        $action_tur->setLabel('Designar o professor para turmas/disciplinas');
        $action_tur->setImage('fa:book gray fa-lg');
        $action_tur->setField('id');
        //$this->datagrid->addAction($action_tur);
        $action_group->addAction($action_tur);
        
        // create Visualiza Saldo
        $action_sal = new TDataGridAction(array($this, 'onSaldos'));
        $action_sal->setUseButton(false);
        $action_sal->setButtonClass('btn btn-default');
        $action_sal->setLabel('Verifica os Saldos do professor');
        $action_sal->setImage('fa:money green fa-lg');
        $action_sal->setField('id');
        //$this->datagrid->addAction($action_tur);
        $action_group->addAction($action_sal);
        
        // notificação
        //OBS: Deve ser o último item do actiongroup
        $action_not = new TDataGridAction(array($this, 'onNotifica'));
        $action_not->setUseButton(false);
        $action_not->setButtonClass('btn btn-default');
        $action_not->setLabel('Notifica problema no professor');
        $action_not->setImage('fa:bell red fa-lg');
        $action_not->setField('id');
        
        // Vincula professor a opm auxiliar
        $action_opm = new TDataGridAction(array('professorOpmForm', 'onEdit'));
        $action_opm->setUseButton(false);
        $action_opm->setButtonClass('btn btn-default');
        $action_opm->setLabel('Vincula a uma OPM auxiliar');
        $action_opm->setImage('fa:cubes black fa-lg');
        $action_opm->setField('id');

        if ($this->nivel_sistema>80)
        {
            $action_group->addAction($action_opm);
        }
        
        if ($this->nivel_sistema<=80)
        { 
            //$this->datagrid->addAction($action_not);
            //$action_group->addSeparator();
            //$action_group->addHeader('Serviços do Operador');
            $action_group->addAction($action_not);
        }

        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        $this->datagrid->disableDefaultClick();
        
        // put datagrid inside a form
        $this->formgrid = new TForm;
        $this->formgrid->add($this->datagrid);
        
        // creates the delete collection button
        $this->deleteButton = new TButton('delete_collection');
        $this->deleteButton->setAction(new TAction(array($this, 'onDeleteCollection')), AdiantiCoreTranslator::translate('Delete selected'));
        $this->deleteButton->setImage('fa:remove red');
        $this->formgrid->addField($this->deleteButton);
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($gridpack);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    /**
     * Inline record editing
     * @param $param Array containing:
     *              key: object ID value
     *              field name: object attribute to be updated
     *              value: new attribute content 
     */
    public function onInlineEdit($param)
    {
        try
        {
            // get the parameter $key
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new professor($key); // instantiates the Active Record
            $object->{$field} = $value;
            $object->store(); // update the object in the database
            TTransaction::close(); // close the transaction
            
            $this->onReload($param); // reload the listing
            new TMessage('info', "Record Updated");
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Register the filter in the session
     */
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        $data->nome = mb_strtoupper($data->nome,'UTF-8');
        
        // clear session filters
        TSession::setValue('professorList_filter_id',   NULL);
        TSession::setValue('professorList_filter_nome',   NULL);
        TSession::setValue('professorList_filter_rg',   NULL);
        TSession::setValue('professorList_filter_postograd',   NULL);
        TSession::setValue('professorList_filter_orgao_origem',   NULL);
        TSession::setValue('professorList_filter_data_nascimento',   NULL);
        TSession::setValue('professorList_filter_sexo',   NULL);
        TSession::setValue('professorList_filter_oculto',   NULL);
        TSession::setValue('professorList_filter_titulos',   NULL);
        TSession::setValue('professorList_filter_minha_opm',   NULL);
        TSession::setValue('professorList_filter_disciplina_id',   NULL);


        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('professorList_filter_id',   $filter); // stores the filter in the session
        }
        
        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter("sem_acentos(nome)", 'like', "NOESC:sem_acentos('%". $data->nome."%')"); // create the filter
            TSession::setValue('professorList_filter_nome',   $filter); // stores the filter in the session
            //var_dump($filter);
        }

        if (isset($data->rg) AND ($data->rg)) {
            $filter = new TFilter('rg', '=', "$data->rg"); // create the filter
            TSession::setValue('professorList_filter_rg',   $filter); // stores the filter in the session
        }

        if (isset($data->postograd_id) AND ($data->postograd_id)) {
            $filter = new TFilter('postograd_id', '=', "$data->postograd_id"); // create the filter
            TSession::setValue('professorList_filter_postograd',   $filter); // stores the filter in the session
        }


        if (isset($data->orgaosorigem_id) AND ($data->orgaosorigem_id)) {
            $filter = new TFilter('orgaosorigem_id', '=', "$data->orgaosorigem_id"); // create the filter
            TSession::setValue('professorList_filter_orgao_origem',   $filter); // stores the filter in the session
        }


        if (isset($data->data_nascimento) AND ($data->data_nascimento)) {
            $filter = new TFilter('data_nascimento', '=', "$data->data_nascimento"); // create the filter
            TSession::setValue('professorList_filter_data_nascimento',   $filter); // stores the filter in the session
        }


        if (isset($data->sexo) AND ($data->sexo)) {
            $filter = new TFilter('sexo', '=', "$data->sexo"); // create the filter
            TSession::setValue('professorList_filter_sexo',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('professorList_filter_oculto',   $filter); // stores the filter in the session
        }
        
        if (isset($data->titulos) AND ($data->titulos)) 
        {
            $sql = "(SELECT professor_id FROM sisacad.escolaridade)";
            if ($data->titulos == 'N')
            {
                $filter = new TFilter('id', 'NOT IN', $sql); // create the filter   
            }
            else if ($data->titulos == 'S')
            {
                $filter = new TFilter('id', 'IN', $sql); // create the filter
            }
            TSession::setValue('professorList_filter_titulos',   $filter); // stores the filter in the session
            
        }

        if (isset($data->minha_opm) AND ($data->minha_opm)) {
            $profile = TSession::getValue('profile');
            $tipo = ($data->minha_opm == 'S') ? 'IN' : 'NOT IN';
            if ($this->nivel_sistema>80)           //Adm e Gestor
            {
                $filter = null;
            }
            else if ($this->nivel_sistema>=50 )     //Nível Operador (carrega OPM e subOPMs)
            {
                //Se não há lista de OPM, carrega só a OPM do usuário
                $lista = ($this->listas['valores']!='') ? $this->listas['lista'] : array($profile['unidade']['id']);
                $filter = new TFilter ('opm_id',$tipo,$lista);
                //echo $criteria->dump() . ' OPM ';var_dump($lista);
            }
            else if ($this->nivel_sistema<50)       //nível de visitante (só a própria OPM)
            {
                $filter = new TFilter ('opm_id',$tipo,array($this->opm_operador));
            }
            TSession::setValue('professorList_filter_minha_opm',   $filter); // stores the filter in the session
        }
        
        if (isset($data->disciplina_id) AND ($data->disciplina_id)) 
        {
            $sql1 = "(SELECT id FROM sisacad.materia WHERE disciplina_id = " . $data->disciplina_id . ")";
            $sql2 = "(SELECT professor_id FROM sisacad.professormateria WHERE materia_id IN " . $sql1 . " )";
            $filter = new TFilter('id', 'IN', $sql2); // create the filter   
            TSession::setValue('professorList_filter_disciplina_id',   $filter); // stores the filter in the session
            
        }


        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('professor_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for professor
            $repository = new TRepository('professor');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            
            //Monta Critérios para escolhas dos professores conforme opms
            //echo $this->nivel_sistema.'---'.$this->opm_operador;
            
            if (TSession::getValue('professorList_filter_id')) {
                $criteria->add(TSession::getValue('professorList_filter_id')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_nome')) {
                $criteria->add(TSession::getValue('professorList_filter_nome')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_rg')) {
                $criteria->add(TSession::getValue('professorList_filter_rg')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_postograd')) {
                $criteria->add(TSession::getValue('professorList_filter_postograd')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_orgao_origem')) {
                $criteria->add(TSession::getValue('professorList_filter_orgao_origem')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_data_nascimento')) {
                $criteria->add(TSession::getValue('professorList_filter_data_nascimento')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_sexo')) {
                $criteria->add(TSession::getValue('professorList_filter_sexo')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_oculto')) {
                $criteria->add(TSession::getValue('professorList_filter_oculto')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_titulos')) {
                $criteria->add(TSession::getValue('professorList_filter_titulos')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_minha_opm')) {
                $criteria->add(TSession::getValue('professorList_filter_minha_opm')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_disciplina_id')) {
                $criteria->add(TSession::getValue('professorList_filter_disciplina_id')); // add the session filter
            }  
            //var_dump($criteria->dump());
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                $fer = new TFerramentas();
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->oculto = $fer->lista_sim_nao($object->oculto);
                    $object->sexo   = $fer->lista_sexo($object->sexo);
                    $object->orgaosorigem_id = $object->orgaosorigem->sigla;
                    $object->postograd_id = $object->postograd->nome;
                    
                    $titulos    = escolaridade::where('professor_id','=',$object->id)->load();
                    $interesses = professordisciplina::where('professor_id','=',$object->id)->load();

                    $row = $this->datagrid->addItem($object);
                    $row->popover = 'true';
                    $row->popside = 'top';
                    $row->poptitle = $object->postograd_id.' '.$object->nome;
                    $tip='';
                    if (count($titulos)>0)
                    {
                        $tip .= '<tr><td><center>Título</center></td><td><center>Área de Conhecimento</center></td></tr>';
                        foreach ($titulos as $titulo)
                        {
                            $nome = $titulo->titularidade->nome;
                            $grad = $titulo->nome_graduacao;
                            $tip .= '<tr><td>'.$nome.'</td><td>'.$grad.'</tr>';
                        }
                    }
                    else
                    {
                        $tip .= '<tr><td colspan=2>Professor sem títularidade</td></tr>';
                    }
                    $titulo_tab = "<center><table class='popover-table'>" . $tip ."</table></center>";
                    $tip = '';
                    if (count($interesses)>0)
                    {
                        $tip = '<tr><td colspan=2><center>Área de Interesse</center></td></tr>';
                        foreach ($interesses as $interesse)
                        {
                            //new professordisciplina();
                            $nome = $interesse->disciplina->nome;
                            $tip .= '<tr><td colspan=2>'.$nome.'</td></tr>';
                        }
                    }
                    else
                    {
                        $tip .= '<tr><td colspan=2>Sem áreas de interesse vinculadas</td></tr>';
                    }
                    $titulo_int = "<center><table class='popover-table'>" . $tip ."</table></center>";

                    $tip = '<tr><td><center>Tipo</center></td><td><center>Contato</center></td></tr>';
                    
                    $email = (!empty($object->email)) ? $object->email : ' Contato Não cadastrado';
                    $fone  = (!empty($object->telefone)) ? $object->telefone : ' Contato Não cadastrado';
                    $cel   = (!empty($object->celular)) ? $object->celular : ' Contato Não cadastrado'; 
                    
                    $tip .= '<tr><td><center>Email:</center></td><td><center>' . $email .'</center></td></tr>';
                    $tip .= '<tr><td><center>Fone:</center></td><td><center>' . $fone .'</center></td></tr>';
                    $tip .= '<tr><td><center>Celular:</center></td><td><center>' . $cel .'</center></td></tr>';
                    
                    $titulo_cont = "<center><table class='popover-table'>" . $tip ."</table></center>";
                    
                    
                    $tot  = '<tr><td><center>Títulos</center></td><td><center>Interesses</center></td><td><center>Contatos</center></td></tr>';
                    $tot .= '<tr><td><center>' . 
                            $titulo_tab .'</center></td><td><center>' . 
                            $titulo_int . '</center></td><td><center>' . 
                            $titulo_cont . '</center></td></tr>'; 
                    
                    $row->popcontent = "<center><table class='popover-table'>" . $tot ."</table></center>";;

                    
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }//Fim Módulo
    
    /**
     * Ask before deletion
     */
    public function onDelete($param)
    {
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
        try
        {
            $key=$param['key']; // get the parameter $key
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new professor($key, FALSE); // instantiates the Active Record
            $object->delete(); // deletes the object from the database
            TTransaction::close(); // close the transaction
            $this->onReload( $param ); // reload the listing
            new TMessage('info', AdiantiCoreTranslator::translate('Record deleted')); // success message
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Ask before delete record collection
     */
    public function onDeleteCollection( $param )
    {
        $data = $this->formgrid->getData(); // get selected records from datagrid
        $this->formgrid->setData($data); // keep form filled
        
        if ($data)
        {
            $selected = array();
            
            // get the record id's
            foreach ($data as $index => $check)
            {
                if ($check == 'on')
                {
                    $selected[] = substr($index,5);
                }
            }
            
            if ($selected)
            {
                // encode record id's as json
                $param['selected'] = json_encode($selected);
                
                // define the delete action
                $action = new TAction(array($this, 'deleteCollection'));
                $action->setParameters($param); // pass the key parameter ahead
                
                // shows a dialog to the user
                new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
            }
        }
    }
    
    /**
     * method deleteCollection()
     * Delete many records
     */
    public function deleteCollection($param)
    {
        // decode json with record id's
        $selected = json_decode($param['selected']);
        
        try
        {
            TTransaction::open('sisacad');
            if ($selected)
            {
                // delete each record from collection
                foreach ($selected as $id)
                {
                    $object = new professor;
                    $object->delete( $id );
                }
                $posAction = new TAction(array($this, 'onReload'));
                $posAction->setParameters( $param );
                new TMessage('info', AdiantiCoreTranslator::translate('Records deleted'), $posAction);
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


    /**
     * Transform datagrid objects
     * Create the checkbutton as datagrid element
     */
    public function onBeforeLoad($objects, $param)
    {
        // update the action parameters to pass the current page to action
        // without this, the action will only work for the first page
        $deleteAction = $this->deleteButton->getAction();
        $deleteAction->setParameters($param); // important!
        
        $gridfields = array( $this->deleteButton );
        
        foreach ($objects as $object)
        {
            $object->check = new TCheckButton('check' . $object->id);
            $object->check->setIndexValue('on');
            $gridfields[] = $object->check; // important
        }
        
        $this->formgrid->setFields($gridfields);
    }

    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca postos/graduação e função
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_posto($param)
    {
        if (array_key_exists('orgaosorigem_id',$param))
        {
            //if(empty($param['orgaosorigem_id']))
            //{
                //return;
            //}
            $key = $param['orgaosorigem_id'];
        }
        $lista = array ('-'=>'Sem definição cadastrada');
        try
        {
            TTransaction::open('sisacad'); // open a transaction
            if ($key != '')
            {
                $repository = new TRepository('orgaosorigem');
                $criteria = new TCriteria;
                $filter = new TFilter('id', '=', $key); // create the filter
                $criteria->add($filter); // add the session filter
                
                // load the objects according to criteria
                $objects = $repository->load($criteria, true);
                foreach ($objects as $object)
                {
                    $id = $object->id;
                }
            }
            $repository = new TRepository('postograd');
            $criteria = new TCriteria;
            if ($key == '')
            {
                $filter = new TFilter('orgaosorigem_id', '!=', 0); // create the filter
            }
            else
            {
                $filter = new TFilter('orgaosorigem_id', '=', $id); // create the filter
            }
            $ord = array ('order'=>'id','direction'=>'asc');
            $criteria->setProperties($ord); // order, offset
            $criteria->add($filter); // add the session filter

            $postos = $repository->load($criteria, true);



            
            if (!empty($postos))
            {
                $lista = array();
                $lista[] = ('');
                foreach ($postos as $posto)
                {
                    $lista[$posto->id] = $posto->nome . '(' . $posto->orgaosorigem->sigla . ')'; 
                }
            }
            TTransaction::close(); // close the transaction
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
        TDBCombo::reload('form_search_professor', 'postograd_id', $lista);

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Importa professor
 *---------------------------------------------------------------------------------------*/
    public function onImporte ($param = null)
    {
        $arquivo = new TFile('arquivo');
        
        //Tamanho
        $arquivo->setSize(300);
       
        $arquivo->setProperty('accept','application/csv');//Aceitar somente PDF

        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( $lbl = new TLabel('Arquivo: '), $arquivo );
        $lbl->setFontColor('red');      
        
        $form->setFields(array($arquivo));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'onConfirm'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Importar dados de professor', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Rotina: Cadastra professor
 *------------------------------------------------------------------------------*/
    public static function onConfirm( $param )
    {
        //var_dump($param);
        //$cfg = TSession::getValue('SISACAD_CONFIG');
        if ((isset($param['arquivo']) && $param['arquivo'])) // validate required field
        {
            $fer = new TFerramentas();
            $file = 'tmp/'.$param['arquivo'];
            $linhas = $fer->csv_in_array($file,";", "\"", true );
            $report = new TRelatorioOP();
            $report->mSucesso = ' Cadastrado com sucesso.';
            $report->mFalha   = ' não foi Cadastrado. Verifique o CFP/RG se está correto ou se não repete.';
            set_time_limit ( 180 );
            try
            {
                // open a transaction with database
                TTransaction::open('sisacad');
                foreach ($linhas as $linha)
                {
                    $loc = professor::where('cpf','=',$linha['CPF'])->load();
                    if (empty($loc) || empty($linha['CPF']))
                    {   
                        $master = new professor;
                        $master->nome     = mb_strtoupper($linha['INSTRUTOR'],'UTF-8');
                        $master->rg       = $linha['RG'];
                        $master->cpf      = $linha['CPF'];
                        $master->lattes   = $linha['LATTES'];
                        $master->telefone = $linha['TELEFONE'];
                        $master->email    = $linha['EMAIL'];
                        $master->orgaosorigem_id = 1;
                        $master->postograd_id    = 0;

                        $master->store(); // save master object
                    }
                    else
                    {
                        $men = 'O professor '.$linha['INSTRUTOR'].' CPF '.$linha['CPF'] . ' já foi cadastrado uma vez';
                        $report->addMensagem($men,false);
                    }
                }
                TTransaction::close(); // close the transaction
                // reload form and session items
                $report->publicaRelatorio('info');
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations
            }
            TApplication::loadPage('professorList','onReload');
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Rotina: Salva o professor
 *------------------------------------------------------------------------------*/
    public static function SaveProfessor ($param)
    {
        $result = true;
        try
        {
            // open a transaction with database
            TTransaction::open('sisacad');
            
            $loc = professor::where('cpf','=',$param['cpf'])->load();
            if (empty($loc))
            {   
                $master = new professor;
                $master->fromArray( (array) $param);
                $master->store(); // save master object
            }
            else
            {
                $result = false;
            }
            TTransaction::close(); // close the transaction
            // reload form and session items
            //new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            //new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            $result = false;
        }
        return $result;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Exporta Professores
 *---------------------------------------------------------------------------------------*/
    public function onExporte ($param = null)
    {
        $objeto = new TCsvManager;
        $fer    = new TFerramentas;
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for professor
            $repository = new TRepository('professor');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'nome';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            //$criteria->setProperty('limit', $limit);
            
            //Monta Critérios para escolhas dos professores conforme opms
            //echo $this->nivel_sistema.'---'.$this->opm_operador;
            
            if (TSession::getValue('professorList_filter_id')) {
                $criteria->add(TSession::getValue('professorList_filter_id')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_nome')) {
                $criteria->add(TSession::getValue('professorList_filter_nome')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_rg')) {
                $criteria->add(TSession::getValue('professorList_filter_rg')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_postograd')) {
                $criteria->add(TSession::getValue('professorList_filter_postograd')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_orgao_origem')) {
                $criteria->add(TSession::getValue('professorList_filter_orgao_origem')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_data_nascimento')) {
                $criteria->add(TSession::getValue('professorList_filter_data_nascimento')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_sexo')) {
                $criteria->add(TSession::getValue('professorList_filter_sexo')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_oculto')) {
                $criteria->add(TSession::getValue('professorList_filter_oculto')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_titulos')) {
                $criteria->add(TSession::getValue('professorList_filter_titulos')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_minha_opm')) {
                $criteria->add(TSession::getValue('professorList_filter_minha_opm')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_disciplina_id')) {
                $criteria->add(TSession::getValue('professorList_filter_disciplina_id')); // add the session filter
            }
            
            //var_dump($criteria->dump());
            // load the objects according to criteria
            $professores    = $repository->load($criteria, FALSE);
            //$tabela = null;
            #Executa os selects
            /*$query = 'SELECT professor.id,professor.nome,quote_ident(professor.cpf),postograd.nome as postograd ' .
                     'FROM sisacad.professor, g_geral.postograd WHERE ((professor.postograd_id != 0 and professor.postograd_id = postograd.id) OR' .
                     ' (professor.postograd_id = 0 and postograd.id = 0)) ';*/
            /*$query = 'SELECT DISTINCT ON (professor.id) professor.id AS id, professor.nome AS nome, 
                      professor.cpf AS cpf, postograd.sigla AS postograd, opm.sigla AS opm,
                      orgaosorigem.sigla AS orgaosorigem,professor.email AS email, professor.lattes AS lattes, 
                      professor.data_nascimento AS data_nascimento, professor.sexo AS sexo
                    FROM 
                      sisacad.professor, g_geral.postograd, 
                      g_geral.orgaosorigem, g_geral.opm 
                    WHERE 
                      professor.opm_id = opm.id AND
                      professor.orgaosorigem_id = orgaosorigem.id AND
                      professor.postograd_id = postograd.id';
            $professores = $fer->runQuery($query);*/
            
            $lista = array();
            foreach ($professores as $professor)
            {
                $subquery = 'SELECT max(titularidade.nivel) as nível, titularidade.nome as titulo 
                                FROM sisacad.escolaridade,   sisacad.titularidade 
                                WHERE escolaridade.professor_id = ' . $professor->id . ' AND 
                                escolaridade.titularidade_id = titularidade.id GROUP BY titularidade.nivel, titularidade.nome ORDER BY nivel DESC;';
                $prof = array();
                $titulo                  = $fer->runQuery($subquery);
                $prof['id']              = $professor->id;
                $prof['nome']            = $professor->nome;
                $prof['cpf']             = (!empty($professor->cpf))    ? $fer->mascara(str_pad($professor->cpf , 11, '0', STR_PAD_LEFT) , '###.###.###-##') : '--';
                $prof['postograd']       = $professor->postograd->sigla;
                $prof['opm']             = $professor->opm->sigla;
                $prof['orgaosorigem']    = $professor->orgaosorigem->sigla;
                $prof['email']           = (!empty($professor->email))  ? $professor->email : '--';
                $prof['lattes']          = (!empty($professor->lattes)) ? $professor->lattes : '--';
                $prof['data_nascimento'] = (!empty($professor->data_nascimento)) ? TDate::date2br($professor->data_nascimento) : '--';
                $prof['sexo']            = (!empty($professor->sexo))   ? $fer->lista_sexo($professor->sexo) : '--';
                $prof['titulo']          = (!empty($titulo)) ? $titulo['0']['titulo'] : '--';
                $lista[]                 = $prof;
            }
            //var_dump($lista);
            #gera cabeçalho
            $cabecalho = array("id","nome","cpf","postograd","opm","orgaosorigem","email",
                                "lattes","data_nascimento","sexo","titulo");
            #cria instancia de objeto da classe
            $export = $objeto->csv (";", $cabecalho, $lista,"tmp/", "professores");
            $objeto->salvar();
            $file = 'tmp/professores.csv';
            TPage::openFile($file);
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
        }

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Formulário para a Carga de Documentos
 *------------------------------------------------------------------------------*/
    public function onDocumento ($param)
    {
         if (!$param)
         {
             new TMessage('info','Nenhum Professor selecionado!!!');
         }
         else if ($this->onCheckEdit($param))
         {
              $data = new stdClass;
              $data->id = $param['key'];
              TSession::setValue('professor',$data);
              TApplication::loadPage('documentos_professorList');
         }
         else
         {
             new TMessage('info','Usuário sem Permissão para incluir documentos para este professor!!!');
         }
    }
/*------------------------------------------------------------------------------
 *  Verifica se o professor pode ser editado pelo usuário
 *------------------------------------------------------------------------------*/
    public function onCheckEdit ($param)
    {
         $result = false;
         if (!empty($param))
         {
            $key = $param['key'];
            if ($this->nivel_sistema>80)
            {
                $result = true;
            }
            else
            {
                try
                {
                    // open a transaction with database
                    TTransaction::open('sisacad');
                    $loc = new professor($key);
                    if (!empty($loc))
                    {
                        if (in_array($loc->opm_id,$this->listas['lista']))
                        {
                            $result = true;
                        }
                    }
                    //var_dump($this->listas['valores']);
                    $loc = professor_opm::where ('opm_id','IN',$this->listas['lista'])->
                                             where ('professor_id','=',$key)->load();
                    if (!empty($loc))
                    {
                        $result = true;
                    }
                    
                    TTransaction::close(); // close the transaction
                }
                catch (Exception $e) // in case of exception
                {
                    TTransaction::rollback(); // undo all pending operations
                }
            }
         }
         return $result;
    }//Fim do Módulo
/*------------------------------------------------------------------------------
 *  Verifica se usuário pode prosseguir na edição
 *------------------------------------------------------------------------------*/
    public function onEditProntuario ($param)
    {
         if (!$param)
         {
             new TMessage('info','Nenhum Professor selecionado!!!');
         }
         else if ($this->onCheckEdit($param))
         {
            TApplication::loadPage('professorForm','onEdit',array('key'=>$param['key']));
         }
         else
         {
             new TMessage('info','Usuário sem Permissão para Editar este professor!!!');
         }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica se usuário pode prosseguir na edição e inclusão de Títulos
 *------------------------------------------------------------------------------*/
    public function onEditEscolaridade ($param)
    {
         if (!$param)
         {
             new TMessage('info','Nenhum Professor selecionado!!!');
         }
         else if ($this->onCheckEdit($param))
         {
            TApplication::loadPage('professorEscolaridadeForm','onEdit',array('key'=>$param['key']));
         }
         else
         {
             new TMessage('info','Usuário sem Permissão para Editar a Titularidade este professor!!!');
         }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Cria notificação
 *---------------------------------------------------------------------------------------*/
    public function onNotifica ($param = null)
    {
        $id      = new THidden('id');
        $tipo    = new TCombo('tipo');
        $texto   = new TText('texto');
        //var_dump($param);
        //Tamanho
        $texto->setSize(300, 120);
        $tipo->setSize(300);
        
        //Valores
        $id->setValue($param['id']);
        $tipos = array('INTERESSE'=>'ATRIBUIR ÁREA DE INTERESSE','DADOS'=>'DADOS PESSOAIS ERRADOS','VINCULO'=>'FALTA VINCULO EM TURMA');
        $tipo->addItems($tipos);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( new TLabel('Tipo de Problema: '), $tipo );
        $table->addRowSet( new TLabel('Descrição do Problema: '), $texto );
        $table->addRowSet( '', $id );
        
        $form->setFields(array($tipo,$texto,$id));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'Notifica'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Notificação de Problema', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public function Notifica ($param)
    {

        //var_dump($param);exit;
        if (!empty($param['tipo']))
        {
            try
            {
                TTransaction::open('sisacad');
                
                //var_dump($data);
                $ma          = new TMantis;
                $profile     = TSession::getValue('profile');
                $servidor    = $ma->FindServidor($profile['login']);
                $sistema     = $ma->FindSistema('SISACAD');
                $parameters  = json_encode(
                               array ('key'=>$param['id'],
                                     'class_to'=>'professorList',
                                     'method_to'=>'onReload'));
                
                $texto = 'Comunicação de Problema com ' . $param['tipo'] .' de Professor(a)';
                $info = array (
                    'relator_id'    => $servidor->id,
                    'operador_id'   => $servidor->id,
                    'duplicata_id'  => 0,
                    'prioridade'    => 50,
                    'gravidade'     => 30,
                    'status'        => 10,
                    'resolucao'     => 0,
                    'destino_id'    => 1,
                    'resumo'        => $texto,
                    'categoria_id'  => 5,
                    'sistema_id'    => $sistema->id,
                    'grupo_id'      => 90,
                    'servidor_id'   => 0,
                    'data_inicio'   => date('Y-m-d'),
                    'data_fim'      => '',
                    'data_atual'    => date('Y-m-d'),
                    'oculto'        => 'N',
                    'json'          => $parameters,
                    'acesso'        => 10);//Acesso publico
                //$ma->chamado       = $info;
                //$ret               = $ma->criaChamado();
                $chamado = new incidentes;
                $chamado->fromArray($info);
                $chamado->store();
                $ret = $chamado->id;
                
                //Melhorar essa nota conforme o tipo de problema
                $nota = (!empty($param['texto'])) ? ' notifica algum problema no(a) professor(a) ' .
                                                    ' conforme descreve abaixo:<br>' . $param['texto'] :
                                                    ' notifica algum problema no(a) professor(a). ';
                $professor = new professor($param['id']);
                $posto     = $professor->get_postograd();
                $posto     = ($posto) ? $posto->nome : '';
                $nome_prof = $posto . ' ' . $professor->nome;
                
                switch ($param['tipo'])
                {
                    case 'INTERESSE':
                        $nota  = 'O(A) servidor(a) '.$servidor->nome .' relata alguma dificuldade em definir ' . 
                                 'área de interesse ao professor(a) ' . $nome_prof;
                        $nota  .= (!empty($param['texto'])) ? ' conforme o próprio relata: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Formulário de Interesse do Docente';
                        $acao   = 'class=disciplinaProfessorForm&method=onEdit&key=' . $param['id'] . '&chamado=' . $ret;
                    
                        break;
                    case 'DADOS':
                        $nota  = 'O(A) servidor(a) '.$servidor->nome .' relata que há dados pessoais do '.
                                 'professor(a) ' . $nome_prof . ' incorretos';
                        $nota  .= (!empty($param['texto'])) ? ' conforme o próprio relata: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Formulário de Edição de Docente';
                        $acao   = 'class=professorForm&method=onEdit&key=' . $param['id'] . '&chamado=' . $ret;
                        break;
                    case 'VINCULO':
                        $nota  = 'O(A) servidor(a) '.$servidor->nome .' relata dificuldades em vincular o(a) '.
                                 'professor(a) ' . $nome_prof . ' em uma turma';
                        $nota  .= (!empty($param['texto'])) ? ' conforme o próprio relata: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Formulário de Designação de Docente em turma';
                        $acao   = 'class=professorVinculoTurmaForm&method=onEdit&key=' . $param['id'] . '&chamado=' . $ret;
                        break;                                                
                }

                
                //Mudar o redirecionamento conforme o tipo de problema
                //Para cada tipo de problema reescrever a tela de forma que faça o registro no Mantis
                SystemNotification::register( 3, $nota, $fazer, $acao,'Correção', 'fa fa-pencil-square-o blu',$sistema->id,80);
                $action = new TAction(array($this, 'onReload'));
                $action->setParameter('key', $param['id']);
                new TMessage('info','Administradores notificados.',$action);
                TTransaction::close();

    
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
                TTransaction::rollback();
            }
        }
        else
        {
            $action = new TAction(array($this, 'onReload'));
            $action->setParameter('key', $param['id']);
            new TMessage('error','Deve-se escolher um tipo de problema para notificar.',$action);
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function onImprime ($param)
    {
        $tipo    = new TCombo('tipo');
        //var_dump($param);
        //Tamanho
        $tipo->setSize(300);
        
        //Valores
        $tipos = array('TURMA/INTERESSE'=>'Lista Áreas de Interesses e Turma vinculadas','DADOS'=>'Dados Pessoais',
                       'SALDO_SIMPLES'=>'Lista com aulas e valores','SALDO_DETALHES'=>'Lista movimentação de docência com aulas e valores');
        $tipo->addItems($tipos);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( new TLabel('Tipos de Listagem: '), $tipo );

        $form->setFields(array($tipo));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array('professorList', 'Imprime'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Escolha qual tipo de Listagem de Professores deseja?', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function Imprime ($param)
    {
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for professor
            $repository = new TRepository('professor');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'nome';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            //$criteria->setProperty('limit', $limit);
            
            //Monta Critérios para escolhas dos professores conforme opms
            //echo $this->nivel_sistema.'---'.$this->opm_operador;
            
            if (TSession::getValue('professorList_filter_id')) {
                $criteria->add(TSession::getValue('professorList_filter_id')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_nome')) {
                $criteria->add(TSession::getValue('professorList_filter_nome')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_rg')) {
                $criteria->add(TSession::getValue('professorList_filter_rg')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_postograd')) {
                $criteria->add(TSession::getValue('professorList_filter_postograd')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_orgao_origem')) {
                $criteria->add(TSession::getValue('professorList_filter_orgao_origem')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_data_nascimento')) {
                $criteria->add(TSession::getValue('professorList_filter_data_nascimento')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_sexo')) {
                $criteria->add(TSession::getValue('professorList_filter_sexo')); // add the session filter
            }


            if (TSession::getValue('professorList_filter_oculto')) {
                $criteria->add(TSession::getValue('professorList_filter_oculto')); // add the session filter
            }

            if (TSession::getValue('professorList_filter_titulos')) {
                $criteria->add(TSession::getValue('professorList_filter_titulos')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_minha_opm')) {
                $criteria->add(TSession::getValue('professorList_filter_minha_opm')); // add the session filter
            }
            
            if (TSession::getValue('professorList_filter_disciplina_id')) {
                $criteria->add(TSession::getValue('professorList_filter_disciplina_id')); // add the session filter
            }
            
            //var_dump($criteria->dump());
            // load the objects according to criteria
            $objects    = $repository->load($criteria, FALSE);
            $tabela = null;
            $cabecalho = '';
            if ($objects)
            {
                // 
                $fer = new TFerramentas();
                $lista = array();
                $nc    = '<font color="red"><strong>NC</strong></font>';
                //Aumenta o prazo de busca conforme o quantidade de professores + 50% de tempo
                $quantidade = count($objects);
                $tempo = (int)((60 * ($quantidade / 500)) * 1.5); 
                set_time_limit($tempo);
                switch ($param['tipo'])
                {
                    //Monta Relatório de professores listados com interesses, títulos e turmas designados
                    case 'TURMA/INTERESSE':
                        $cabecalho = '<h4>Listagem Por Contato, Titulos, Intereses e Turmas</h4>';
                        $head = array('Identificação','Contato/Endereço','Titularidade','Áreas de Atuação','Turmas Designadas');
                        foreach ($objects as $object)
                        {
                            $dado = array();
                            //Dados básicos
                            $posto                 = $object->postograd->nome; //new postograd($object->postograd_id);
                            $opm                   = $object->opm->sigla;
                            $professor             = $posto . ((!empty($object->quadro)) ? ' ' . $object->quadro  : '') . ' ' . 
                                                     $object->nome . ((!empty($opm)) ? ' (' . $opm . ')' : '');
                            
                            $dado['identificacao'] = $professor;
                            
                            //Contatos e Endereço
                            $end = array();
                            $end[] = 'Logradouro: ' . ((!empty($object->logradouro)) ? $object->logradouro : $nc) .
                                                      ((!empty($object->quadra)) ? ' Qd:' . $object->quadra : '') .
                                                      ((!empty($object->lote)) ? ' Lt:' . $object->lote : '') .
                                                      ((!empty($object->numero)) ? ' Nº:' . $object->numero : '') .
                                                      ((!empty($object->bairro)) ? ', ' . $object->bairro : '') .
                                                      ((!empty($object->cidade)) ? ' - ' . $object->cidade : '') .
                                                      ((!empty($object->uf_residencia)) ? '-' . $object->uf_residencia : '');
                            
                            $end[] = 'Email: ' . ((!empty($object->email)) ? $object->email : $nc);
                            $end[] = 'Celular: ' . ((!empty($object->celular)) ? $object->celular : $nc);
                            $end[] = 'Telefone: ' . ((!empty($object->telefone)) ? $object->telefone : $nc); 
                            
                            $dado['contatos'] = $fer->geraListaHTML($end,array('lst'=>'"list-style-type: disc;"'));
                            
                            //Formata datos de títulos
                            $titulos = escolaridade::where('professor_id','=',$object->id)->load();
                            $lista_d = array();
                            if (count($titulos)>0)
                            {
                                foreach ($titulos as $titulo)
                                {
                                    $tit = $titulo->titularidade->nome . (!empty($titulo->nome_graduacao) ? ' - ' .$titulo->nome_graduacao : '');
                                    $lista_d[] = $tit;
                                }
                            }
                            else
                            {
                                $lista_d[] = $nc;
                            }
                            $dado['titulos'] = $fer->geraListaHTML($lista_d,array('lst'=>'"list-style-type: disc;"'));
                            
                            //Formata dados de áreas de interesses
                            $interesses = professordisciplina::where('professor_id','=',$object->id)->load();
                            $lista_d = array();
                            if (count($interesses)>0)
                            {
                                foreach ($interesses as $interesse)
                                {
                                    $lista_d[] = $interesse->disciplina->nome;
                                }
                            }
                            else
                            {
                                $lista_d[] = $nc;
                            }
                            $dado['interesses'] = $fer->geraListaHTML($lista_d,array('lst'=>'"list-style-type: disc;"'));
                            
                            //Formata dados de turmas
                            $turmas = professormateria::where('professor_id','=',$object->id)->load(); 
                            $lista_d = array();
                            if (count($interesses)>0)
                            {
        
                                foreach ($turmas as $turma)
                                {
                                    //var_dump($turma);
                                    $t_disciplina = $turma->materia->nome;
                                    $t_turma      = $turma->materia->turma->sigla;
                                    $turma_info   = $t_turma . ' - ' . $t_disciplina; 
                                    $lista_d[] = $turma_info;
                                }
                            }
                            else
                            {
                                $lista_d[] = $nc;
                            }
                            $dado['turmas'] =$fer->geraListaHTML($lista_d,array('lst'=>'"list-style-type: disc;"'));
                        $lista[] = $dado;
                        }
                        $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                    'bordercolor="black" width="100%" '.
                                                    'style="border-collapse: collapse;"',
                                                    'cab'=>'style="background: lightblue; text-align: center;"',
                                                    'cel'=>'style="background: blue;"'));
                        break;
                    //Lista os professores com seus dados pessoais e formas de contato
                    case 'DADOS':
                        $cabecalho = '<h4>Listagem Por Dados Pessoais e Contatos</h4>';
                        $head = array('Identificação','Dados Pessoais/Documentação','Residência/Contatos');
                        
                        foreach ($objects as $object)
                        {
                            $dado = array();
                            
                            //Dados básicos
                            $posto                 = $object->postograd->nome; //new postograd($object->postograd_id);
                            $opm                   = $object->opm->sigla;
                            $professor             = $posto . ((!empty($object->quadro)) ? ' ' . $object->quadro  : '') . ' ' . 
                                                     $object->nome . ((!empty($opm)) ? ' (' . $opm . ')' : '');
                            
                            $dado['identificacao'] = $professor;
                            
                            //Dados Pessoais e documentação
                            $doc = array();
                            $doc[] = 'ID:'    . $object->id;
                            $doc[] = 'Sexo: ' . ((!empty($object->sexo)) ? $fer->lista_sexo($object->sexo) : $nc);
                            $doc[] = 'D.N.: ' . ((!empty($object->data_nascimento)) ? TDate::date2br($object->data_nascimento) : $nc);
                            
                            $doc[] = 'CPF: '  . ((!empty($object->cpf)) ? $object->cpf : $nc) .
                                                ((strlen($object->cpf)!=11) ? '(!)' : '');
                            $doc[] = 'RG: '   . ((!empty($object->rg)) ? $object->rg  : $nc) .
                                    ((!empty($object->orgao_expeditor)) ? '-' . $object->orgao_expeditor : '') .
                                    ((!empty($object->uf_expeditor))    ? '/' . $object->uf_expeditor    : '');  
                            $doc[] = 'Lattes:' . ((!empty($object->lattes)) ? $object->email : $nc);
                            
                            $dado['documentacao'] = $fer->geraListaHTML($doc,array('lst'=>'"list-style-type: disc;"'));
                            
                            //Contatos e Endereço
                            $end = array();
                            $end[] = 'Logradouro: ' . ((!empty($object->logradouro)) ? $object->logradouro : $nc) .
                                                      ((!empty($object->quadra)) ? ' Qd:' . $object->quadra : '') .
                                                      ((!empty($object->lote)) ? ' Lt:' . $object->lote : '') .
                                                      ((!empty($object->numero)) ? ' Nº:' . $object->numero : '') .
                                                      ((!empty($object->bairro)) ? ', ' . $object->bairro : '') .
                                                      ((!empty($object->cidade)) ? ' - ' . $object->cidade : '') .
                                                      ((!empty($object->uf_residencia)) ? '-' . $object->uf_residencia : '');
                            
                            $end[] = 'Email: ' . ((!empty($object->email)) ? $object->email : $nc);
                            $end[] = 'Celular: ' . ((!empty($object->celular)) ? $object->celular : $nc);
                            $end[] = 'Telefone: ' . ((!empty($object->telefone)) ? $object->telefone : $nc); 
                            
                            $dado['contatos'] = $fer->geraListaHTML($end,array('lst'=>'"list-style-type: disc;"'));
                            
                            $lista[] = $dado;
                        }
                        
                        $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                    'bordercolor="black" width="100%" '.
                                                    'style="border-collapse: collapse;"',
                                                    'cab'=>'style="background: lightblue; text-align: center;"',
                                                    'cel'=>'style="background: blue;"'));
                        break;
                    //Lista os professores computando aulas, saldos e valores
                    case 'SALDO_SIMPLES':
                        $cabecalho = '<h4>Listagem por Aulas e Valores</h4>';
                        $head = array('Identificação','Aulas Ministradas','Aulas Recebidas','R$ Recebidas','Aulas a Receber','R$ a Receber');
                        $total_pago  = 0;
                        $total_saldo = 0;
                        foreach ($objects as $object)
                        {
                            
                            $dado = self::getSaldoProfessor(array('id'=>$object->id));
                            //Corrige alinhamento
                            $dado['professor'] = '<p style = "text-align: left;">' . $dado['professor'] . '</p>';
                            //Converte o valor formatado para inteiro novamente
                            $v = $fer->soNumeros($dado['recebido']);
                            $v = $v / 100;
                            $s = $fer->soNumeros($dado['a_receber']);
                            $s = $s / 100;
                            $total_pago  = $total_pago  + (float) $v;
                            $total_saldo = $total_saldo + (float) $s;
                            $lista[] = $dado;
                        }
                        $lista[] = array('professor'=>'Total','aulas_total'=>'-','aulas_recebidas'=>'-',
                                         'recebido'=>$fer->formataDinheiro($total_pago),'aulas_a_receber'=>'-',
                                         'a_receber'=>$fer->formataDinheiro($total_saldo));
                        $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                                    'bordercolor="black" width="100%" '.
                                                                    'style="border-collapse: collapse;"',
                                                                    'cab'=>'style="background: lightblue; text-align: center;"',
                                                                    'row'=>'style="text-align: right;"'));



                        break;
                    case 'SALDO_DETALHES':
                        $cabecalho = '<h4>Listagem Por Detalhamento de Aulas e Valores por Disciplina</h4>';
                        $head = array('Identificação','Movimentações da Docência');
                        $lista = array();
                        $total_pago  = 0;
                        $total_saldo = 0;
                        $subhead =  array('Título','Apresentação','Disciplina','Turma','C.H.',
                                          'Aulas','% do Curso', 'R$ recebido','R$ A receber');
                        foreach ($objects as $object)
                        {
                            $titulos = self::getTitulos();
                            $dado = self::getSaldoDisciplinasProfessor(array('id'=>$object->id) , $titulos);
                            //Corrige alinhamento
                            $professor = '<p style = "text-align: left;">' . $dado['professor'] . '</p>';
                            if ($dado['lista'] != false)
                            {
                                $info      = $fer->geraTabelaHTML($dado['lista'],$subhead,array('tab'=>'border="1px" '.
                                                                        'bordercolor="black" width="100%" '.
                                                                        'style="border-collapse: collapse;"',
                                                                        'cab'=>'style="background: grey; text-align: center;"'));
                            }
                            else
                            {
                                $info = '<p style = "text-align: center;"> --- Sem Lançamentos --- </p>';
                            }
                            $lista[] = array($professor,$info);
                            $total_pago   = $total_pago  + $dado['recebido'];
                            $total_saldo  = $total_saldo + $dado['a_receber'];
                            //echo $dado['recebido'];

                        }
                        //Totalizando tudo
                        $total = array(array($fer->formataDinheiro($total_pago),
                                       $fer->formataDinheiro($total_saldo)));
                        $info      = $fer->geraTabelaHTML($total,array('Total R$ Recebido','Total R$ a Pagar'),
                                                                array('tab'=>'border="1px" '.
                                                                'bordercolor="black" width="100%" '.
                                                                'style="border-collapse: collapse; background: lightyellow; ' . 
                                                                'text-align: right;"'));
                        $lista[] = array('Totalização',$info);//Adiciona no fim da tabela
                        $tabela = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                                    'bordercolor="black" width="100%" '.
                                                                    'style="border-collapse: collapse;"',
                                                                    'cab'=>'style="background: lightblue; text-align: center;"',
                                                                    'row'=>'style="text-align: right;"'));
                        break;
                    case '':
                    
                        break;
                }

            }
            // close the transaction
            TTransaction::close();

        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
        if (!empty($tabela))
        {
            $rel = new TBdhReport();
            $bot = $rel->scriptPrint();
            $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                   '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                   '<h4>RELAÇÃO DE PROFESSORES</h4>'. $cabecalho . '<br></center>';
            $botao = '<center>'.$bot['botao'].'</center>';
            $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
            $window = TWindow::create('Listagem dos Professores', 1400, 500);
            $window->add($tabela);
            $window->show();
        }
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getTitulos ($param = null)
    {
        $lista = false;
        try
        {
            TTransaction::open('sisacad');
            
            $titulos = titularidade::where ('oculto','!=','S')->load();
            if ($titulos)
            {
                $lista = array();
                foreach ($titulos as $titulo)
                {
                    $lista[$titulo->id] = array ('id'=>$titulo->id,'nome'=>$titulo->nome,'nivel'=>$titulo->nivel);
                }
            }
            
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
        }
        return $lista;
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getDisciplinas ($param = null)
    {
        $lista = false;
        //var_dump($param);
        try
        {
            TTransaction::open('sisacad');
            
            $disciplinas = professormateria::where ('professor_id','=',$param)->load();
            if ($disciplinas)
            {
                $lista = array();
                foreach ($disciplinas as $disciplina)
                {
                    $lista[] = array ('materia_id'=>$disciplina->materia_id,'nome'=>$disciplina->materia->disciplina->nome,
                                      'turma'=>$disciplina->materia->turma->sigla,
                                      'carga_horaria'=>$disciplina->materia->carga_horaria);
                }
            }
            
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
        }
        return $lista;
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function onSaldos ($param)
    {
        $lista = self::getSaldoProfessor($param);
        $fer = new TFerramentas();
        $head = array('Identificação','Aulas Ministradas','Aulas Recebidas','R$ Recebidas','Aulas a Receber','R$ a Receber');
        $tabela = $fer->geraTabelaHTML(array($lista),$head,array('tab'=>'border="1px" '.
                                                    'bordercolor="black" width="100%" '.
                                                    'style="border-collapse: collapse;"',
                                                    'cab'=>'style="background: lightblue; text-align: center;"',
                                                    'row'=>'style="text-align: right;"'));
        if (!empty($tabela))
        {
            $rel = new TBdhReport();
            $bot = $rel->scriptPrint();
            $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                   '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                   '<h4>MOVIMENTAÇÃO FINANCEIRA</h4><br><br></center>';
            $botao = '<center>'.$bot['botao'].'</center>';
            $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
            $window = TWindow::create('Saldos e Valores do(a) professor(a)' . $lista['professor'], 0.8, 0.8);
            $window->add($tabela);
            $window->show();
        }
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getSaldoProfessor ($param)
    {
        $lista = false;
        $prof  = false;
        $fer   = new TFerramentas();
        try
        {
            TTransaction::open('sisacad');
            //Carrega dados do professor
            $professor = new professor($param['id']);
            $prof = $professor->postograd->nome.' '.$professor->nome . '(' . ($professor->orgaosorigem->sigla) . ')';
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
            return $lista;
        }
        
        //Busca saldos de aulas            
        $sql1      = 'SELECT SUM(aulas_saldo) FROM sisacad.professorcontrole_aula WHERE professor_id = ' . $param['id'];
        $sql2      = 'SELECT SUM(aulas_pagas) FROM sisacad.professorcontrole_aula WHERE professor_id = ' . $param['id'];
        $sql       = 'SELECT DISTINCT (' . $sql1 . ') as tot_aulas, (' . 
                                           $sql2 . ') as tot_pago FROM sisacad.professorcontrole_aula';
        //echo $prof . ' - sql de saldos de aula ' . $sql . '<br>' ;
        $s   = $fer->runQuery($sql);
        if (!empty($s))
        {
            $saldos = $s[0];
            $saldos['tot_aulas'] = (!empty($saldos['tot_aulas'])) ? (int) $saldos['tot_aulas'] : 0;
            $saldos['tot_pago']  = (!empty($saldos['tot_pago']))  ? (int) $saldos['tot_pago']  : 0;
        }
        else
        {
            $saldos['tot_aulas'] = 0;
            $saldos['tot_pago']  = 0;
        }

        //Busca valores a receber
        $sql       = 'SELECT ((aulas_saldo - aulas_pagas) * valor_aula) as a_receber FROM sisacad.professorcontrole_aula WHERE  professor_id = ' . $param['id'] .
                     ' AND valor_aula > 0 AND aulas_saldo != aulas_pagas';
        //echo 'sql de valores a receber ' . $sql . '<br>' ;
        $a_receber = $fer->runQuery($sql);
        $v_total = 0;
        if (!empty($a_receber))
        {
            foreach($a_receber as $valor)
            {
                $v_total = $v_total + $valor['a_receber'];
            }
        }
        $v_total = $fer->formataDinheiro($v_total);
        //Busca valores a recebidos
        $sql       = 'SELECT (aulas_pagas * valor_aula) as recebido FROM sisacad.professorcontrole_aula WHERE  professor_id = ' . $param['id'] .
                     ' AND (valor_aula > 0 AND aulas_pagas > 0)';
        //echo 'sql de valores a recebidos ' . $sql . '<br>' . '-----------------------------------------------------<br>';
        $recebido = $fer->runQuery($sql);
        $r_total = 0;
        if (!empty($recebido))
        {
            foreach($recebido as $valor)
            {
                $r_total = $r_total + $valor['recebido'];
            }
        }
        $r_total = $fer->formataDinheiro($r_total);

        //Os valores ficaram trocados, tot_pago <==>(tot_aulas - tot_pago), r_total <==> v_total
        //Foram trocado de posição na $lista mas observar no resto do trabalho em caso de alterações.
        $lista = array ('professor'=>$prof,
                        'aulas_total'=>$saldos['tot_aulas'],
                        'aulas_recebidas'=>$saldos['tot_pago'],
                        'recebido'=>$r_total,
                        'aulas_a_receber'=>($saldos['tot_aulas'] - $saldos['tot_pago']),
                        'a_receber'=>$v_total);

        return $lista;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function onSaldosCompletos ($param)
    {
        $titulos = self::getTitulos();
        $lista   = self::getSaldoDisciplinasProfessor($param , $titulos);//Retorno de array ('identificacao',$tabela)
        $fer     = new TFerramentas();
        $head    = array('Identificação','Dados da Movimentação da Docência');
        $tabela  = $fer->geraTabelaHTML($lista,$head,array('tab'=>'border="1px" '.
                                                     'bordercolor="black" width="100%" '.
                                                     'style="border-collapse: collapse;"',
                                                     'cab'=>'style="background: lightblue; text-align: center;"',
                                                     'row'=>'style="text-align: right;"'));
        if (!empty($tabela))
        {
            $rel = new TBdhReport();
            $bot = $rel->scriptPrint();
            $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                   '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                   '<h4>MOVIMENTAÇÃO DE DOCÊNCIA</h4><br><br></center>';
            $botao = '<center>'.$bot['botao'].'</center>';
            $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela . '</div>' . $botao;
            $window = TWindow::create('Movimentação da Docência do(a) professor(a)' . $lista['professor'], 0.8, 0.8);
            $window->add($tabela);
            $window->show();
        }
        
    }//Fim Módulo

/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public static function getSaldoDisciplinasProfessor ($param , $titulos)
    {
        $fer     = new TFerramentas();
        $lista = false;
        $prof  = false;
        $tot_a_receber = 0;
        $tot_recebido  = 0;
        try
        {
            TTransaction::open('sisacad');
            $fer       = new TFerramentas();
            
            //Carrega dados do professor
            $professor = new professor($param['id']);
            $prof = $professor->postograd->nome.' '.$professor->nome . '(' . ($professor->orgaosorigem->sigla) . ')';
            $disciplinas = self::getDisciplinas($param['id']);
            //O query tem que listas pelas ids do controle_aula que o professor tem listado pelas disciplinas 
            //var_dump($disciplinas);echo '<br>';//exit;
            //Busca saldos de aulas
            //var_dump($titulos);
            foreach($titulos as $titulo)
            {
                //echo $titulo['nome'] . '<br>';
                if (!empty($disciplinas))
                {
                    foreach ($disciplinas as $disciplina)
                    {
                        $sql1      = 'SELECT SUM(aulas_saldo) AS tot_aulas FROM sisacad.professorcontrole_aula,sisacad.controle_aula ' .
                                     'WHERE controle_aula.id = professorcontrole_aula.controle_aula_id AND professor_id = ' . $param['id'] . ' ' .
                                     'AND controle_aula.materia_id = ' . $disciplina['materia_id'] . ' ' .
                                     'AND professorcontrole_aula.titularidade_id = ' . $titulo['id'] ;
                        $sql2      = 'SELECT SUM(aulas_pagas) as tot_pagas FROM sisacad.professorcontrole_aula,sisacad.controle_aula ' .
                                     'WHERE controle_aula.id = professorcontrole_aula.controle_aula_id AND professor_id = ' . $param['id'] . ' ' .
                                     'AND controle_aula.materia_id = ' . $disciplina['materia_id'] . ' ' .
                                     'AND professorcontrole_aula.titularidade_id = ' . $titulo['id'] ;
                        $dadas   = $fer->runQuery($sql1);//Aulas ministradas 
                        if (!empty($dadas[0]['tot_aulas']))// Se houve aulas, continue a calcular
                        { 
                            $pagas   = $fer->runQuery($sql2);//Aulas pagas                    
                            $aulas_dadas = (!empty($dadas[0]['tot_aulas'])) ? $dadas[0]['tot_aulas'] : 0 ;
                            $aulas_pagas = (!empty($pagas[0]['tot_pagas'])) ? $pagas[0]['tot_pagas'] : 0 ;
                            
                            $tit = escolaridade::where ('professor_id','=',$param['id'])->
                                                 where ('titularidade_id','=',$titulo['id'])->
                                                 orderBy('data_apresentacao','desc')->load();
                                                 
                            $data_apresentacao  = (!empty($tit)) ? TDate::date2br($tit[0]->data_apresentacao) : $nc; 
                            $titular = $titulo['nome'];
                            $materia = $disciplina['nome'];
                            $turma   = $disciplina['turma'];
                            $ch      = $disciplina['carga_horaria'];
                            
                            $sql       = 'SELECT DISTINCT professorcontrole_aula.id as id,((aulas_saldo - aulas_pagas) * valor_aula) as a_receber ' . 
                                         'FROM sisacad.professorcontrole_aula, sisacad.controle_aula WHERE  ' . 
                                         'professorcontrole_aula.controle_aula_id = controle_aula.id AND ' .
                                         'professor_id = ' . $param['id'] . ' ' .
                                         'AND controle_aula.materia_id = ' . $disciplina['materia_id'] . ' ' .
                                         'AND professorcontrole_aula.titularidade_id = ' . $titulo['id'] . ' ' .
                                         'AND (aulas_saldo > 0 AND aulas_saldo != aulas_pagas)';
                            //echo '<br>A receber ' . $sql . '<br>';
                            $a_receber = $fer->runQuery($sql);
                            $v_total = 0;
                            if (!empty($a_receber))
                            {
                                //echo 'A Receber ';
                                //var_dump($a_receber);
                                foreach($a_receber as $valor)
                                {
                                    $v_total = $v_total + (double) $valor['a_receber'];
                                    //echo $valor['a_receber'] . ' + ';
                                }
                                //echo '<br>';
                            }
                            
                            $sql       = 'SELECT DISTINCT professorcontrole_aula.id as id, (aulas_pagas * valor_aula) as recebido ' .
                                         'FROM sisacad.professorcontrole_aula, sisacad.controle_aula WHERE  ' . 
                                         'professorcontrole_aula.controle_aula_id = controle_aula.id AND ' .
                                         'professor_id = ' . $param['id'] . ' ' .
                                         'AND controle_aula.materia_id = ' . $disciplina['materia_id'] . ' ' .
                                         'AND professorcontrole_aula.titularidade_id = ' . $titulo['id'] . ' ' .
                                         'AND (aulas_saldo > 0 AND aulas_pagas > 0)';
                            //echo '<br>Recebido ' . $sql . '<br>';
                            $recebido = $fer->runQuery($sql);
                            $r_total = 0;
                            if (!empty($recebido))
                            {
                                //echo 'Recebido ';
                                //var_dump($recebido);
                                foreach($recebido as $valor)
                                {
                                    $r_total = $r_total + (double) $valor['recebido'];
                                    //echo $valor['recebido'] . ' + ';
                                }
                                //echo '<br>';
                            }
                            $tot_a_receber = $tot_a_receber + $v_total;
                            $tot_recebido  = $tot_recebido  + $r_total;
                            $dados = array('titulo'=>$titular,'data_apresentacao'=>$data_apresentacao,
                                           'disciplina'=>$materia,'turma'=>$turma,'carga_horaria'=>$ch,
                                           'aulas_dadas'=>$aulas_dadas,'pencentual'=>number_format((($aulas_dadas/$ch)*100),2,',','')  . '%',
                                           'recebido'=>$fer->formataDinheiro($r_total),'a_receber'=>$fer->formataDinheiro($v_total));
                            //var_dump($dados);
                            //echo '<br>'. $materia .' - Recebido ' . $tot_recebido . ", A Receber " . $tot_a_receber; 
                            $lista[] = $dados;
    
                        }//Fim verificação de objeto vazio
                        
                    }//Fim Foreach disciplinas
                }//Fim verifica disciplinas não vazias
                
            }//Fim Foreach titulos
            if ($tot_a_receber == 0 && $tot_recebido == 0)
            {
                $lista = false;
            }
            else
            {
                $lista[] = array('titulo'=>'','data_apresentacao'=>'',
                                   'disciplina'=>'','turma'=>'','carga_horaria'=>'',
                                   'aulas_dadas'=>'','pencentual'=>'Sub-Total',
                                   'recebido'=>$fer->formataDinheiro($tot_recebido),'a_receber'=>$fer->formataDinheiro($tot_a_receber));
            }
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
            TTransaction::rollback();
        }
        return array('professor'=>$prof,'lista'=>$lista,'recebido'=>$tot_recebido,'a_receber'=>$tot_a_receber);
    }//Fim Módulo
    public function onCorrecao ($param)
    {
          $data = new stdClass;
          $data->id      = $param['key'];
          $data->chamado = $param['chamado'];
          if (isset($param['chamado']))
          {
              if (!empty($param['chamado']))
              {
                  $chamado = new TMantis();
                  $chamado->fechaChamado(array('key'=>$param['chamado']));
              }
          }
          TSession::setValue('professor',$data);
          TApplication::loadPage('documentos_professorList');
    }
}//Fim da Classe
