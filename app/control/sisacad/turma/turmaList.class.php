<?php
/**
 * turmaList Listing
 * @author  <your name here>
 */
class turmaList extends TPage
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
        // creates the form
        $this->form = new TQuickForm('form_search_turma');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Gerenciamento de Atividades de Turma - Listagem');

        TSession::setValue('curso_militar',null);//Limpa variável de seção..

        // create the form fields
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('oculto','!=','S'));
        
        $curso_id = new TDBCombo('curso_id','sisacad','curso','id','nome','nome',$criteria);
        $nome = new TEntry('nome');
        $sigla = new TEntry('sigla');
        
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('uf','=','GO'));
        $cidade = new TDBCombo('cidade','sicad','cidades','nome','nome','nome',$criteria);
        $opm_id = new TDBCombo('opm_id','sisacad','OPM','id','nome','nome');
        $tipo_turma = new TCombo('tipo_turma');
        $oculto = new TCombo('oculto');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $tipo_turma->addItems($fer->lista_tipos_curso());

        // add the fields
        $this->form->addQuickField('Curso Vinculado', $curso_id,  400 );
        $this->form->addQuickField('Nome da Turma', $nome,  400 );
        $this->form->addQuickField('Sigla', $sigla,  200 );
        $this->form->addQuickField('Cidade Sede', $cidade,  400 );
        $this->form->addQuickField('OPM Responsável', $opm_id,  400 );
        $this->form->addQuickField('Tipo de Turma', $tipo_turma,  400 );
        $this->form->addQuickField('Encerrada?', $oculto,  120 );
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('turma_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find')      , new TAction(array($this, 'onSearch')), 'fa:search');
        if ($this->nivel_sistema>80)
        {
            $this->form->addQuickAction(_t('New')            ,  new TAction(array('turmaForm', 'onEdit')), 'bs:plus-sign green');
            $this->form->addQuickAction('abre Matrícula'     ,  new TAction(array('turmasmatriculandoList', 'onReload')), 'fa:user-plus red');
            $this->form->addQuickAction('Atribui Turma a OPM',  new TAction(array('turmaOPMList', 'onReload')), 'fa:sitemap blue');
        }
        $this->form->addQuickAction('Ver Progresso' ,  new TAction(array($this,'onProgresso')), 'fa:bar-chart black');
        $this->form->addQuickAction('Aplicação de Provas'    ,  new TAction(array('avaliacao_turmaList','onReload')), 'fa:bookmark red');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_curso_id = new TDataGridColumn('curso_id', 'Curso Vinculado', 'center');
        $column_nome = new TDataGridColumn('nome', 'Nome da Turma', 'center');
        $column_sigla = new TDataGridColumn('sigla', 'Sigla', 'center');
        $column_cidade = new TDataGridColumn('cidade', 'Cidade Sede', 'center');
        $column_opm_id = new TDataGridColumn('opm_id', 'OPM', 'center');
        $column_tipo_turma = new TDataGridColumn('tipo_turma', 'Tipo de Turma', 'center');
        $column_oculto = new TDataGridColumn('oculto', 'Encerrada?', 'center');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_curso_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_sigla);
        $this->datagrid->addColumn($column_opm_id);
        $this->datagrid->addColumn($column_cidade);
        $this->datagrid->addColumn($column_tipo_turma);
        $this->datagrid->addColumn($column_oculto);

        // creates the datagrid column actions
        $order_curso_id = new TAction(array($this, 'onReload'));
        $order_curso_id->setParameter('order', 'curso_id');
        $column_curso_id->setAction($order_curso_id);
        
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_sigla = new TAction(array($this, 'onReload'));
        $order_sigla->setParameter('order', 'sigla');
        $column_sigla->setAction($order_sigla);
        
        $order_opm_id = new TAction(array($this, 'onReload'));
        $order_opm_id->setParameter('order', 'opm_id');
        $column_opm_id->setAction($order_opm_id);
        
        $order_cidade = new TAction(array($this, 'onReload'));
        $order_cidade->setParameter('order', 'cidade');
        $column_cidade->setAction($order_cidade);
        
        $order_tipo_turma = new TAction(array($this, 'onReload'));
        $order_tipo_turma->setParameter('order', 'tipo_turma');
        $column_tipo_turma->setAction($order_tipo_turma);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('turmaForm', 'onEdit'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
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
        if ($this->nivel_sistema>80)
        {
            $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        }
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
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
            $object = new turma($key); // instantiates the Active Record
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
        
        // clear session filters
        TSession::setValue('turmaList_filter_curso_id',   NULL);
        TSession::setValue('turmaList_filter_nome',   NULL);
        TSession::setValue('turmaList_filter_sigla',   NULL);
        TSession::setValue('turmaList_filter_opm_id',   NULL);
        TSession::setValue('turmaList_filter_cidade',   NULL);
        TSession::setValue('turmaList_filter_tipo_turma',   NULL);
        TSession::setValue('turmaList_filter_oculto',   NULL);

        if (isset($data->curso_id) AND ($data->curso_id)) {
            $filter = new TFilter('curso_id', '=', "$data->curso_id"); // create the filter
            TSession::setValue('turmaList_filter_curso_id',   $filter); // stores the filter in the session
        }


        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('turmaList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->sigla) AND ($data->sigla)) {
            $filter = new TFilter('sigla', 'like', "%{$data->sigla}%"); // create the filter
            TSession::setValue('turmaList_filter_sigla',   $filter); // stores the filter in the session
        }

        if (isset($data->opm_id) AND ($data->opm_id)) {
            $filter = new TFilter('opm_id', '=', "$data->opm_id"); // create the filter
            TSession::setValue('turmaList_filter_opm_id',   $filter); // stores the filter in the session
        }

        if (isset($data->cidade) AND ($data->cidade)) {
            $filter = new TFilter('cidade', '=', "$data->cidade"); // create the filter
            TSession::setValue('turmaList_filter_cidade',   $filter); // stores the filter in the session
        }


        if (isset($data->tipo_turma) AND ($data->tipo_turma)) {
            $filter = new TFilter('tipo_turma', '=', "$data->tipo_turma"); // create the filter
            TSession::setValue('turmaList_filter_tipo_turma',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('turmaList_filter_oculto',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('turma_filter_data', $data);
        
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
            
            // creates a repository for turma
            $repository = new TRepository('turma');
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
            

            if (TSession::getValue('turmaList_filter_curso_id')) {
                $criteria->add(TSession::getValue('turmaList_filter_curso_id')); // add the session filter
            }


            if (TSession::getValue('turmaList_filter_nome')) {
                $criteria->add(TSession::getValue('turmaList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('turmaList_filter_sigla')) {
                $criteria->add(TSession::getValue('turmaList_filter_sigla')); // add the session filter
            }

            if (TSession::getValue('turmaList_filter_opm_id')) {
                $criteria->add(TSession::getValue('turmaList_filter_opm_id')); // add the session filter
            }

            if (TSession::getValue('turmaList_filter_cidade')) {
                $criteria->add(TSession::getValue('turmaList_filter_cidade')); // add the session filter
            }


            if (TSession::getValue('turmaList_filter_tipo_turma')) {
                $criteria->add(TSession::getValue('turmaList_filter_tipo_turma')); // add the session filter
            }


            if (TSession::getValue('turmaList_filter_oculto')) {
                $criteria->add(TSession::getValue('turmaList_filter_oculto')); // add the session filter
            }
            
            $fer = new TFerramentas();
            $ci  = new TSicadDados();
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário

            if ($this->opm_operador==false)                     //Carrega OPM do usuário
            {
                //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
                $this->opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
            }
            if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
            {
                $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
                $this->listas        = $ci->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
                $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
                $this->config_load = true;                               //Informa que configuração foi carregada
            }
            if ($this->nivel_sistema<=80)//Gestores e/Operadores
            {
                $criteria->add( new TFilter('opm_id','IN',$this->listas['lista'] ), TExpression::OR_OPERATOR);
                $query = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
                $criteria->add (new TFilter ('id','IN',$query), TExpression::OR_OPERATOR);
            }

            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                $fer = new TFerramentas();
                // interate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->oculto = $fer->lista_sim_nao($object->oculto);
                    $object->curso_id = $object->get_curso()->sigla;
                    $object->tipo_turma = $fer->lista_tipos_curso($object->tipo_turma);
                    $object->opm_id = $object->opm->sigla;
                    $this->datagrid->addItem($object);
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
    }
    
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
            $object = new turma($key, FALSE); // instantiates the Active Record
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
                    $object = new turma;
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
 *  Rotina: Verifica o progresso de uma turma
 *---------------------------------------------------------------------------------------*/
    public function onProgresso ($param = null)
    {
        $criteria = new TCriteria();

        if ($this->nivel_sistema<=80)//Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query1), TExpression::OR_OPERATOR);
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query2), TExpression::OR_OPERATOR);
        }
        $criteria->add (new TFilter ('oculto','!=','S'));

        $turma_id = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        
        //var_dump($param);
        
        //Tamanho
        $turma_id->setSize(300);
        
        //Mascaras

        //Trava
        //$id->setEditable(false);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( $lbl = new TLabel('Turma: '), $turma_id );
        $lbl->setFontColor('red');

        
        $form->setFields(array($turma_id));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'Progresso'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Verifica o gráfico do progresso da turma', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Gera o progresso da Turma numa tela
 *------------------------------------------------------------------------------*/
    public function Progresso ($param)
    {

        //var_dump($param);
        $html = new THtmlRenderer('app/resources/google_bar_chart.html');
        $dados = $this->getDadosTurma($param);
        $legenda = array();
        $eixo    = array();
        
        if (!empty($dados))
        {
            $legenda[] = 'Disciplinas';
            $eixo[]    = 'Carga Horária em Percentual';
            foreach ($dados as $dado)
            {
                $legenda[] = $dado->disciplina_id;
                $eixo[]    = $dado->carga_percent;
            }
        }
        
        $data = array();
        $data[] = $legenda;
        $data[] = $eixo;
       
        $panel = new TPanelGroup('Gráfico de Colunas da Turma ' . $dados[0]->turma_nome );
        $panel->add($html);
        
        // replace the main section variables
        $html->enableSection('main', array('data'   => json_encode($data),
                                           'width'  => '100%',
                                           'height'  => '300px',
                                           'title'  => 'Progresso Percentual das disciplinas',
                                           'ytitle' => 'Percentual', 
                                           'xtitle' => 'Disciplinas'));
        
       
        // show the input dialog
        
        $window = TWindow::create('Gráfico do Progresso de turma', 0.8, 0.8);
        $window->add($panel);
        $window->show();

    }//Fim  Módulo
/*------------------------------------------------------------------------------
 *  Carrega dados para estatística
 *------------------------------------------------------------------------------*/
    public function getDadosTurma ($param)
    {
        try
        {
            $lista = false;
            $fer = new TFerramentas();
            $ci  = new TSicadDados();
            $acad = new TSisacad();
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário
            
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
               
            // creates a repository for materia
            $repository = new TRepository('materia');

            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            
            $criteria->add($filter = new TFilter('turma_id', '=', $param['turma_id'])); // add the session filter

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            if ($objects)
            {
                // iterate the collection of active records
                $lista = array();
                foreach ($objects as $object)
                {
                    // Atualiza os dados do objeto
                    $ch                    = $acad->getCargaHoraria($object->id);
                    $object->carga_total   = $ch;
                    $percent               = ($ch * 100) / $object->carga_horaria;
                    $object->carga_percent = $percent;
                    $object->turma_id      = $object->get_turma()->sigla;
                    $object->turma_nome    = $object->get_turma()->nome;
                    $object->disciplina_id = $object->get_disciplina()->nome;

                    $lista[]               = $object;
                }
            }
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage() . '<br>'. $criteria->dump());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
}//Fim Classe
