<?php
/**
 * alunoList Listing
 * @author  <your name here>
 */
class alunoList extends TPage
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
        $this->form = new TQuickForm('form_search_aluno');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Gestão do Corpo de Alunos - Listagem de Matrícula');
        
        $fer = new TFerramentas();
        TSession::setValue('turma_militar',null);

        // create the form fields
        $aluno     = new TEntry('aluno');
        $status    = new TCombo('status');
        $cpf       = new TEntry('cpf');
        $resultado = new TCombo('resultado');
        $restricao = new TCombo('restricao');
        
        // create the form fields
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

        //Valores
        $status->addItems($fer->lista_status_aluno());
        $resultado->addItems($fer->lista_resultado_aluno());
        $restricao->addItems($fer->lista_status_saude());
        

        // add the fields
        $this->form->addQuickField('Nome do Aluno', $aluno,  400 );
        $this->form->addQuickField('CPF', $cpf,  160 );
        $this->form->addQuickField('Status', $status,  140 );
        $this->form->addQuickField('Saúde', $restricao,  140 );
        $this->form->addQuickField('Resultado', $resultado,  140 );
        $this->form->addQuickField('Turma', $turma_id,  400 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('aluno_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('alunoForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->makeScrollable ();
        $this->datagrid->setHeight(240);
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_turma_id = new TDataGridColumn('turma_id', 'Turma', 'center',120);
        $column_cpf = new TDataGridColumn('cpf', 'Identificação', 'center',510);
        $column_status = new TDataGridColumn('status', 'Status', 'center',120);
        $column_resultado = new TDataGridColumn('resultado', 'Resultado', 'center',120);

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_turma_id);
        $this->datagrid->addColumn($column_cpf);
        $this->datagrid->addColumn($column_status);
        $this->datagrid->addColumn($column_resultado);


        // creates the datagrid column actions
        $order_status = new TAction(array($this, 'onReload'));
        $order_status->setParameter('order', 'status');
        $column_status->setAction($order_status);
        
        $order_cpf = new TAction(array($this, 'onReload'));
        $order_cpf->setParameter('order', 'cpf');
        $column_cpf->setAction($order_cpf);
        
        $order_resultado = new TAction(array($this, 'onReload'));
        $order_resultado->setParameter('order', 'resultado');
        $column_resultado->setAction($order_resultado);
        
        $order_turma_id = new TAction(array($this, 'onReload'));
        $order_turma_id->setParameter('order', 'turma_id');
        $column_turma_id->setAction($order_turma_id);

        // create EDIT action
        $action_edit = new TDataGridAction(array('alunoForm', 'onEdit'));
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
        $this->datagrid->addAction($action_del);
        
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
            $object = new aluno($key); // instantiates the Active Record
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
        TSession::setValue('alunoList_filter_status',   NULL);
        TSession::setValue('alunoList_filter_cpf',   NULL);
        TSession::setValue('alunoList_filter_aluno',   NULL);
        TSession::setValue('alunoList_filter_resultado',   NULL);
        TSession::setValue('alunoList_filter_restricao',   NULL);
        TSession::setValue('alunoList_filter_turma_id',   NULL);

        if (isset($data->status) AND ($data->status)) {
            $filter = new TFilter('status', '=', "$data->status"); // create the filter
            TSession::setValue('alunoList_filter_status',   $filter); // stores the filter in the session
        }

        if (isset($data->aluno) AND ($data->aluno)) {
            $sql = "(SELECT cpf FROM efetivo.servidor WHERE nome LIKE '%" . strtoupper($data->aluno) . "%')";
            $filter = new TFilter('cpf', 'IN', $sql); // create the filter
            TSession::setValue('alunoList_filter_aluno',   $filter); // stores the filter in the session
        }


        if (isset($data->cpf) AND ($data->cpf)) {
            $filter = new TFilter('cpf', 'like', "%{$data->cpf}%"); // create the filter
            TSession::setValue('alunoList_filter_cpf',   $filter); // stores the filter in the session
        }


        if (isset($data->resultado) AND ($data->resultado)) {
            $filter = new TFilter('resultado', '=', "$data->resultado"); // create the filter
            TSession::setValue('alunoList_filter_resultado',   $filter); // stores the filter in the session
        }
        
        if (isset($data->restricao) AND ($data->restricao)) {
            $filter = new TFilter('restricao', '=', "$data->restricao"); // create the filter
            TSession::setValue('alunoList_filter_restricao',   $filter); // stores the filter in the session
        }


        if (isset($data->turma_id) AND ($data->turma_id)) {
            $filter = new TFilter('turma_id', '=', "$data->turma_id"); // create the filter
            TSession::setValue('alunoList_filter_turma_id',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('aluno_filter_data', $data);
        
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
            
            // creates a repository for aluno
            $repository = new TRepository('aluno');
            $limit = 25;
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
                $query = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . 
                            $this->listas['valores'].") OR id IN (SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores'].")))";
                $criteria->add (new TFilter ('turma_id','IN',$query));
            }

            if (TSession::getValue('alunoList_filter_status')) {
                $criteria->add(TSession::getValue('alunoList_filter_status')); // add the session filter
            }


            if (TSession::getValue('alunoList_filter_cpf')) {
                $criteria->add(TSession::getValue('alunoList_filter_cpf')); // add the session filter
            }

            if (TSession::getValue('alunoList_filter_aluno')) {
                $criteria->add(TSession::getValue('alunoList_filter_aluno')); // add the session filter
            }

            if (TSession::getValue('alunoList_filter_resultado')) {
                $criteria->add(TSession::getValue('alunoList_filter_resultado')); // add the session filter
            }

            if (TSession::getValue('alunoList_filter_restricao')) {
                $criteria->add(TSession::getValue('alunoList_filter_restricao')); // add the session filter
            }

            if (TSession::getValue('alunoList_filter_turma_id')) {
                $criteria->add(TSession::getValue('alunoList_filter_turma_id')); // add the session filter
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
                $sis = new TSisacad();
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // Conversões
                    $object->turma_id  = $object->get_turma()->sigla;
                    $object->status    = $fer->lista_status_aluno($object->status);
                    $object->resultado = $fer->lista_resultado_aluno($object->resultado);

                    $dados = $sis->getDadosAluno($object->cpf);//Busca dados do Aluno para preencher campo
                    if ($dados != false)//Se retornar os dados do aluno, preenche
                    {
                        if (!empty($dados->rgmilitar))
                        {
                            $rg = ' RG ' . $dados->rgmilitar; 
                        }
                        else if (!empty($dados->rgcivil))
                        {
                            $rg = ' CI ' . $dados->rgcivil;
                        }
                        else
                        {
                            $rg = '';
                        }
                        $rg .= ' ';
                        $posto = $dados->postograd;
                        $posto = (!empty($posto)) ? $sis->getPostograd($posto) : '';
                        $ident = $posto . $rg . $dados->nome . ', CPF '.$dados->cpf;
                    }
                    else
                    {
                        $ident = '-- Dados do aluno não localizado -- ';
                    }
                    
                    $object->cpf = $ident;
                    // add the object inside the datagrid
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
            $object = new aluno($key, FALSE); // instantiates the Active Record
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
                    $object = new aluno;
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
    }
}
