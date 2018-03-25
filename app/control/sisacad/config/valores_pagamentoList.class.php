<?php
/**
 * valores_pagamentoList Listing
 * @author  <your name here>
 */
class valores_pagamentoList extends TPage
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
        $this->form = new TQuickForm('form_search_valores_pagamento');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem dos valores para pagamento');

        // create the form fields
        $natureza = new TCombo('natureza');
        $titularidade_id = new TDBCombo('titularidade_id','sisacad','titularidade','id','nome','nivel');
        $nivel_pagamento_id = new TDBCombo('nivel_pagamento_id','sisacad','nivel_pagamento','id','nome','nome');
        $data_inicio = new TDate('data_inicio');
        $data_fim = new TDate('data_fim');
        $valor = new TEntry('valor');

        //Mascaras
        $data_inicio->setMask('dd/mm/yyyy');
        $data_fim->setMask('dd/mm/yyyy');
        
        //Valores
        $natureza->addItems($fer->lista_natureza_curso());

        // add the fields
        $this->form->addQuickField('Natureza do Curso', $natureza,  400 );
        $this->form->addQuickField('Nivel do Ensino', $nivel_pagamento_id,  400 );
        $this->form->addQuickField('Título do Docente', $titularidade_id,  400 );
        $this->form->addQuickField('Início da Vigência', $data_inicio,  120 );
        $this->form->addQuickField('Fim de Vigência', $data_fim,  120 );
        $this->form->addQuickField('Valor R$', $valor,  120 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('valores_pagamento_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('valores_pagamentoForm', 'onEdit')), 'bs:plus-sign green');
        $this->form->addQuickAction('Retorna à configuração',  new TAction(array('sisacadConfiguracao', 'onReload')), 'ico_back.png');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_natureza = new TDataGridColumn('natureza', 'Natureza do Curso', 'center');
        $column_nivel_pagamento_id = new TDataGridColumn('nivel_pagamento_id', 'Nivel do Curso', 'center');
        $column_titularidade_id = new TDataGridColumn('titularidade_id', 'Título do Docente', 'center');
        $column_data_inicio = new TDataGridColumn('data_inicio', 'Início da Vigência', 'center');
        $column_data_fim = new TDataGridColumn('data_fim', 'Fim de Vigência', 'center');
        $column_valor = new TDataGridColumn('valor', 'Valor R$', 'right');
        
        


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_natureza);
        $this->datagrid->addColumn($column_nivel_pagamento_id);
        $this->datagrid->addColumn($column_titularidade_id);
        $this->datagrid->addColumn($column_data_inicio);
        $this->datagrid->addColumn($column_data_fim);
        $this->datagrid->addColumn($column_valor);


        // creates the datagrid column actions
        $order_valor = new TAction(array($this, 'onReload'));
        $order_valor->setParameter('order', 'valor');
        $column_valor->setAction($order_valor);
        
        $order_natureza = new TAction(array($this, 'onReload'));
        $order_natureza->setParameter('order', 'natureza');
        $column_natureza->setAction($order_natureza);
        
        $order_data_inicio = new TAction(array($this, 'onReload'));
        $order_data_inicio->setParameter('order', 'data_inicio');
        $column_data_inicio->setAction($order_data_inicio);
        
        $order_data_fim = new TAction(array($this, 'onReload'));
        $order_data_fim->setParameter('order', 'data_fim');
        $column_data_fim->setAction($order_data_fim);
        
        $order_titularidade_id = new TAction(array($this, 'onReload'));
        $order_titularidade_id->setParameter('order', 'titularidade_id');
        $column_titularidade_id->setAction($order_titularidade_id);
        
        $order_nivel_pagamento_id = new TAction(array($this, 'onReload'));
        $order_nivel_pagamento_id->setParameter('order', 'nivel_pagamento_id');
        $column_nivel_pagamento_id->setAction($order_nivel_pagamento_id);
        

        // define the transformer method over image
        $column_valor->setTransformer( function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });

        // define the transformer method over image
        $column_data_inicio->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });

        // define the transformer method over image
        $column_data_fim->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });


        
        // create EDIT action
        $action_edit = new TDataGridAction(array('valores_pagamentoForm', 'onEdit'));
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
        $container->add(new TXMLBreadCrumb('menu.xml', 'sisacadConfiguracao'));
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
            $object = new valores_pagamento($key); // instantiates the Active Record
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
        TSession::setValue('valores_pagamentoList_filter_nivel_pagamento_id',   NULL);
        TSession::setValue('valores_pagamentoList_filter_titularidade_id',   NULL);
        TSession::setValue('valores_pagamentoList_filter_data_inicio',   NULL);
        TSession::setValue('valores_pagamentoList_filter_data_fim',   NULL);
        TSession::setValue('valores_pagamentoList_filter_valor',   NULL);

        if (isset($data->nivel_pagamento_id) AND ($data->nivel_pagamento_id)) {
            $filter = new TFilter('nivel_pagamento_id', '=', "$data->nivel_pagamento_id"); // create the filter
            TSession::setValue('valores_pagamentoList_filter_nivel_pagamento_id',   $filter); // stores the filter in the session
        }


        if (isset($data->titularidade_id) AND ($data->titularidade_id)) {
            $filter = new TFilter('titularidade_id', '=', "$data->titularidade_id"); // create the filter
            TSession::setValue('valores_pagamentoList_filter_titularidade_id',   $filter); // stores the filter in the session
        }


        if (isset($data->data_inicio) AND ($data->data_inicio)) {
            $filter = new TFilter('data_inicio', '>=', "$data->data_inicio"); // create the filter
            TSession::setValue('valores_pagamentoList_filter_data_inicio',   $filter); // stores the filter in the session
        }


        if (isset($data->data_fim) AND ($data->data_fim)) {
            $filter = new TFilter('data_fim', '<=', "$data->data_fim"); // create the filter
            TSession::setValue('valores_pagamentoList_filter_data_fim',   $filter); // stores the filter in the session
        }


        if (isset($data->valor) AND ($data->valor)) {
            $filter = new TFilter('valor', '>=', "$data->valor"); // create the filter
            TSession::setValue('valores_pagamentoList_filter_valor',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('valores_pagamento_filter_data', $data);
        
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
            
            // creates a repository for valores_pagamento
            $repository = new TRepository('valores_pagamento');
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
            

            if (TSession::getValue('valores_pagamentoList_filter_nivel_pagamento_id')) {
                $criteria->add(TSession::getValue('valores_pagamentoList_filter_nivel_pagamento_id')); // add the session filter
            }


            if (TSession::getValue('valores_pagamentoList_filter_titularidade_id')) {
                $criteria->add(TSession::getValue('valores_pagamentoList_filter_titularidade_id')); // add the session filter
            }


            if (TSession::getValue('valores_pagamentoList_filter_data_inicio')) {
                $criteria->add(TSession::getValue('valores_pagamentoList_filter_data_inicio')); // add the session filter
            }


            if (TSession::getValue('valores_pagamentoList_filter_data_fim')) {
                $criteria->add(TSession::getValue('valores_pagamentoList_filter_data_fim')); // add the session filter
            }


            if (TSession::getValue('valores_pagamentoList_filter_valor')) {
                $criteria->add(TSession::getValue('valores_pagamentoList_filter_valor')); // add the session filter
            }
            
            if (TSession::getValue('valores_pagamentoList_filter_natureza')) {
                $criteria->add(TSession::getValue('valores_pagamentoList_filter_natureza')); // add the session filter
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
                    $object->titularidade_id = $object->get_titularidade()->nome;
                    $object->nivel_pagamento_id = $object->get_nivel_pagamento()->nome;
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
            $object = new valores_pagamento($key, FALSE); // instantiates the Active Record
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
                    $object = new valores_pagamento;
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
