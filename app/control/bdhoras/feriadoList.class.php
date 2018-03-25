<?php
/**
 * feriadoList Listing
 * @author  <your name here>
 */
class feriadoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_search_feriado');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem de Feriados');
        

        // create the form fields
        $id = new TEntry('id');
        $dataferiado = new TEntry('dataferiado');
        $nome = new TEntry('nome');
        $tipo = new TCombo('tipo');
        $movel = new TCombo('movel');


        // add the fields
        $this->form->addQuickField('ID', $id,  50 );
        $this->form->addQuickField('Data', $dataferiado,  120 );
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Tipo', $tipo,  200 );
        $this->form->addQuickField('Data Móvel?', $movel,  80 );
        
        //Mascaras
        $dataferiado->setMask('99/99');
        
        //Valores dos Campos
        $item = array();
        $item['f'] = 'Não';
        $item['t'] = 'Sim';
        $movel->addItems($item);
        $item = array();
        $item['NACIONAL']       = 'Feriado Nacional/Estadual';
        $item['MUNICIPAL']      = 'Feriado Municipal';
        $item['INSTITUCIONAL']  = 'Feriado ou comemoração da Instituição';
        $tipo->addItems($item);
        

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('feriado_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('feriadoForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'ID', 'right');
        $column_dataferiado = new TDataGridColumn('dataferiado', 'Data', 'center');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'center');
        $column_tipo = new TDataGridColumn('tipo', 'Tipo', 'center');
        $column_movel = new TDataGridColumn('movel', 'Data Móvel?', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_dataferiado);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_tipo);
        $this->datagrid->addColumn($column_movel);


        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_dataferiado = new TAction(array($this, 'onReload'));
        $order_dataferiado->setParameter('order', 'dataferiado');
        $column_dataferiado->setAction($order_dataferiado);
        
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_tipo = new TAction(array($this, 'onReload'));
        $order_tipo->setParameter('order', 'tipo');
        $column_tipo->setAction($order_tipo);
        
        $order_movel = new TAction(array($this, 'onReload'));
        $order_movel->setParameter('order', 'movel');
        $column_movel->setAction($order_movel);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('feriadoForm', 'onEdit'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(TRUE);
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
        


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($this->datagrid);
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
            
            TTransaction::open('sicad'); // open a transaction with database
            $object = new feriado($key); // instantiates the Active Record
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
        TSession::setValue('feriadoList_filter_id',   NULL);
        TSession::setValue('feriadoList_filter_dataferiado',   NULL);
        TSession::setValue('feriadoList_filter_nome',   NULL);
        TSession::setValue('feriadoList_filter_tipo',   NULL);
        TSession::setValue('feriadoList_filter_movel',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('feriadoList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->dataferiado) AND ($data->dataferiado)) {
            $filter = new TFilter('dataferiado', '=', "$data->dataferiado"); // create the filter
            TSession::setValue('feriadoList_filter_dataferiado',   $filter); // stores the filter in the session
        }


        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('feriadoList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->tipo) AND ($data->tipo)) {
            $filter = new TFilter('tipo', '=', "$data->tipo"); // create the filter
            TSession::setValue('feriadoList_filter_tipo',   $filter); // stores the filter in the session
        }


        if (isset($data->movel) AND ($data->movel)) {
            $filter = new TFilter('movel', '=', "$data->movel"); // create the filter
            TSession::setValue('feriadoList_filter_movel',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('feriado_filter_data', $data);
        
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
            // open a transaction with database 'sicad'
            TTransaction::open('sicad');
            
            // creates a repository for feriado
            $repository = new TRepository('feriado');
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
            

            if (TSession::getValue('feriadoList_filter_id')) {
                $criteria->add(TSession::getValue('feriadoList_filter_id')); // add the session filter
            }


            if (TSession::getValue('feriadoList_filter_dataferiado')) {
                $criteria->add(TSession::getValue('feriadoList_filter_dataferiado')); // add the session filter
            }


            if (TSession::getValue('feriadoList_filter_nome')) {
                $criteria->add(TSession::getValue('feriadoList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('feriadoList_filter_tipo')) {
                $criteria->add(TSession::getValue('feriadoList_filter_tipo')); // add the session filter
            }


            if (TSession::getValue('feriadoList_filter_movel')) {
                $criteria->add(TSession::getValue('feriadoList_filter_movel')); // add the session filter
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
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->movel = ($object->movel='t') ? "SIM" : "NÃO";
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
            TTransaction::open('sicad'); // open a transaction with database
            $object = new feriado($key, FALSE); // instantiates the Active Record
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
