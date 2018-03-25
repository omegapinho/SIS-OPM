<?php
/**
 * cursoList Listing
 * @author  <your name here>
 */
class cursoList extends TPage
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
        $this->form = new TQuickForm('form_search_curso');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem dos Cursos');

        $fer = new TFerramentas();        

        // create the form fields
        $criteria = new TCriteria();
        $criteria->add(new TFilter ('oculto','!=','S'));
        $orgaoorigem_id = new TDBCombo('orgaoorigem_id','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $nome = new TEntry('nome');
        $data_inicio = new TDate('data_inicio');
        $carga_horaria = new TEntry('carga_horaria');
        $natureza = new TCombo('natureza');
        $tipo_curso = new TCombo('tipo_curso');
        $nivel_pagamento_id = new TDBCombo('nivel_pagamento_id','sisacad','nivel_pagamento','id','nome','nome');
        $oculto = new TCombo('oculto');

        //Valores
        $natureza->addItems($fer->lista_natureza_curso());
        $tipo_curso->addItems($fer->lista_tipos_curso());
        $oculto->addItems($fer->lista_sim_nao());
        //Máscara
        $data_inicio->setMask('dd-mm-yyyy');
        $carga_horaria->setMask('99999');

        // add the fields
        $this->form->addQuickField('Instituição Interessada', $orgaoorigem_id,  400 );
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Início de Curso (maior ou igual)', $data_inicio,  120 );
        $this->form->addQuickField('Carga Horária (maior ou igual)', $carga_horaria,  120 );
        $this->form->addQuickField('Natureza do Curso', $natureza,  400 );
        $this->form->addQuickField('Tipo de Curso', $tipo_curso,  400 );
        $this->form->addQuickField('Nível do Curso', $nivel_pagamento_id,  400 );
        $this->form->addQuickField('Curso Encerrado?', $oculto,  120 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('curso_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        if ($this->nivel_sistema>80)
        {
            $this->form->addQuickAction(_t('New'),  new TAction(array('cursoForm', 'onEdit')), 'bs:plus-sign green');
        }
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_data_inicio = new TDataGridColumn('data_inicio', 'Data de Início', 'left');
        $column_carga_horaria = new TDataGridColumn('carga_horaria', 'C.H.', 'right');
        $column_natureza = new TDataGridColumn('natureza', 'Natureza do Curso', 'left');
        $column_tipo_curso = new TDataGridColumn('tipo_curso', 'Tipo de Curso', 'left');
        $column_nivel_pagamento_id = new TDataGridColumn('nivel_pagamento_id', 'Nível do Curso', 'right');
        $column_oculto = new TDataGridColumn('oculto', 'Encerrado?', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_data_inicio);
        $this->datagrid->addColumn($column_carga_horaria);
        $this->datagrid->addColumn($column_natureza);
        $this->datagrid->addColumn($column_tipo_curso);
        $this->datagrid->addColumn($column_nivel_pagamento_id);
        $this->datagrid->addColumn($column_oculto);


        // creates the datagrid column actions
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_data_inicio = new TAction(array($this, 'onReload'));
        $order_data_inicio->setParameter('order', 'data_inicio');
        $column_data_inicio->setAction($order_data_inicio);
        
        $order_carga_horaria = new TAction(array($this, 'onReload'));
        $order_carga_horaria->setParameter('order', 'carga_horaria');
        $column_carga_horaria->setAction($order_carga_horaria);
        
        $order_natureza = new TAction(array($this, 'onReload'));
        $order_natureza->setParameter('order', 'natureza');
        $column_natureza->setAction($order_natureza);
        
        $order_tipo_curso = new TAction(array($this, 'onReload'));
        $order_tipo_curso->setParameter('order', 'tipo_curso');
        $column_tipo_curso->setAction($order_tipo_curso);
        
        $order_nivel_pagamento_id = new TAction(array($this, 'onReload'));
        $order_nivel_pagamento_id->setParameter('order', 'nivel_pagamento_id');
        $column_nivel_pagamento_id->setAction($order_nivel_pagamento_id);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        

        // define the transformer method over image
        $column_data_inicio->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });


        
        // create EDIT action
        $action_edit = new TDataGridAction(array('cursoForm', 'onEdit'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        if ($this->nivel_sistema>80)
        {
            $this->datagrid->addAction($action_edit);
        }
        
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
            $object = new curso($key); // instantiates the Active Record
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
        TSession::setValue('cursoList_filter_nome',   NULL);
        TSession::setValue('cursoList_filter_data_inicio',   NULL);
        TSession::setValue('cursoList_filter_carga_horaria',   NULL);
        TSession::setValue('cursoList_filter_natureza',   NULL);
        TSession::setValue('cursoList_filter_tipo_curso',   NULL);
        TSession::setValue('cursoList_filter_nivel_pagamento_id',   NULL);
        TSession::setValue('cursoList_filter_oculto',   NULL);
        TSession::setValue('cursoList_filter_orgaoorigem_id',   NULL);

        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('cursoList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->data_inicio) AND ($data->data_inicio)) {
            $filter = new TFilter('data_inicio', '>=', "$data->data_inicio"); // create the filter
            TSession::setValue('cursoList_filter_data_inicio',   $filter); // stores the filter in the session
        }


        if (isset($data->carga_horaria) AND ($data->carga_horaria)) {
            $filter = new TFilter('carga_horaria', '>=', "$data->carga_horaria"); // create the filter
            TSession::setValue('cursoList_filter_carga_horaria',   $filter); // stores the filter in the session
        }


        if (isset($data->natureza) AND ($data->natureza)) {
            $filter = new TFilter('natureza', '=', "$data->natureza"); // create the filter
            TSession::setValue('cursoList_filter_natureza',   $filter); // stores the filter in the session
        }


        if (isset($data->tipo_curso) AND ($data->tipo_curso)) {
            $filter = new TFilter('tipo_curso', '=', "$data->tipo_curso"); // create the filter
            TSession::setValue('cursoList_filter_tipo_curso',   $filter); // stores the filter in the session
        }


        if (isset($data->nivel_pagamento_id) AND ($data->nivel_pagamento_id)) {
            $filter = new TFilter('nivel_pagamento_id', '=', "$data->nivel_pagamento_id"); // create the filter
            TSession::setValue('cursoList_filter_nivel_pagamento_id',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('cursoList_filter_oculto',   $filter); // stores the filter in the session
        }
        
        if (isset($data->orgaoorigem_id) AND ($data->orgaoorigem_id)) {
            $filter = new TFilter('orgaoorigem_id', '=', "$data->orgaoorigem_id"); // create the filter
            TSession::setValue('cursoList_filter_orgaoorigem_id',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('curso_filter_data', $data);
        
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
            
            // creates a repository for curso
            $repository = new TRepository('curso');
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
            

            if (TSession::getValue('cursoList_filter_nome')) {
                $criteria->add(TSession::getValue('cursoList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('cursoList_filter_data_inicio')) {
                $criteria->add(TSession::getValue('cursoList_filter_data_inicio')); // add the session filter
            }


            if (TSession::getValue('cursoList_filter_carga_horaria')) {
                $criteria->add(TSession::getValue('cursoList_filter_carga_horaria')); // add the session filter
            }


            if (TSession::getValue('cursoList_filter_natureza')) {
                $criteria->add(TSession::getValue('cursoList_filter_natureza')); // add the session filter
            }


            if (TSession::getValue('cursoList_filter_tipo_curso')) {
                $criteria->add(TSession::getValue('cursoList_filter_tipo_curso')); // add the session filter
            }


            if (TSession::getValue('cursoList_filter_nivel_pagamento_id')) {
                $criteria->add(TSession::getValue('cursoList_filter_nivel_pagamento_id')); // add the session filter
            }

            if (TSession::getValue('cursoList_filter_oculto')) {
                $criteria->add(TSession::getValue('cursoList_filter_oculto')); // add the session filter
            }

            if (TSession::getValue('cursoList_filter_orgaoorigem_id')) {
                $criteria->add(TSession::getValue('cursoList_filter_orgaoorigem_id')); // add the session filter
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
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->nivel_pagamento_id = $object->get_nivel_pagamento()->nome;
                    $object->oculto = $fer->lista_sim_nao($object->oculto);
                    $object->tipo_curso = $fer->lista_tipos_curso($object->tipo_curso);
                    $object->natureza = $fer->lista_natureza_curso($object->natureza);
                    
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
            $object = new curso($key, FALSE); // instantiates the Active Record
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
                    $object = new curso;
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
