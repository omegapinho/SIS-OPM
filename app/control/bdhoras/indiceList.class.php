<?php
/**
 * indiceList Listing
 * @author  <your name here>
 */
class indiceList extends TPage
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
        $this->form = new TQuickForm('form_search_indice');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('indice');
        

        // create the form fields
        $id = new TEntry('id');
        $diario = new TEntry('diario');
        $segundosdiarios = new TEntry('segundosdiarios');
        $horassemanal = new TEntry('horassemanal');
        $valorhora = new TEntry('valorhora');
        $datavigencia = new TDate('datavigencia');

        //Máscara
        $datavigencia->setMask('dd/mm/yyyy');

        // add the fields
        $this->form->addQuickField('ID', $id,  50 );
        $this->form->addQuickField('Quantidade de Horas Diário', $diario,  80 );
        $this->form->addQuickField('Segundos Diários', $segundosdiarios,  80 );
        $this->form->addQuickField('Horas Semanal', $horassemanal,  80 );
        $this->form->addQuickField('Valor por Hora', $valorhora,  80 );
        $this->form->addQuickField('Data Inicial de Vigência', $datavigencia,  120 );

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('indice_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('indiceForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'ID', 'center');
        $column_diario = new TDataGridColumn('diario', 'Quantidade de Horas Diário', 'center');
        $column_segundosdiarios = new TDataGridColumn('segundosdiarios', 'Segundos Diários', 'center');
        $column_horassemanal = new TDataGridColumn('horassemanal', 'Horas Semanal', 'center');
        $column_valorhora = new TDataGridColumn('valorhora', 'Valor por Hora', 'center');
        $column_datavigencia = new TDataGridColumn('datavigencia', 'Data Final de Vigência', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_diario);
        $this->datagrid->addColumn($column_segundosdiarios);
        $this->datagrid->addColumn($column_horassemanal);
        $this->datagrid->addColumn($column_valorhora);
        $this->datagrid->addColumn($column_datavigencia);


        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_diario = new TAction(array($this, 'onReload'));
        $order_diario->setParameter('order', 'diario');
        $column_diario->setAction($order_diario);
        
        $order_segundosdiarios = new TAction(array($this, 'onReload'));
        $order_segundosdiarios->setParameter('order', 'segundosdiarios');
        $column_segundosdiarios->setAction($order_segundosdiarios);
        
        $order_horassemanal = new TAction(array($this, 'onReload'));
        $order_horassemanal->setParameter('order', 'horassemanal');
        $column_horassemanal->setAction($order_horassemanal);
        
        $order_valorhora = new TAction(array($this, 'onReload'));
        $order_valorhora->setParameter('order', 'valorhora');
        $column_valorhora->setAction($order_valorhora);
        
        $order_datavigencia = new TAction(array($this, 'onReload'));
        $order_datavigencia->setParameter('order', 'datavigencia');
        $column_datavigencia->setAction($order_datavigencia);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('indiceForm', 'onEdit'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        

        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
            $object = new indice($key); // instantiates the Active Record
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
        TSession::setValue('indiceList_filter_id',   NULL);
        TSession::setValue('indiceList_filter_diario',   NULL);
        TSession::setValue('indiceList_filter_segundosdiarios',   NULL);
        TSession::setValue('indiceList_filter_horassemanal',   NULL);
        TSession::setValue('indiceList_filter_valorhora',   NULL);
        TSession::setValue('indiceList_filter_datavigencia',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('indiceList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->diario) AND ($data->diario)) {
            $filter = new TFilter('diario', '=', "$data->diario"); // create the filter
            TSession::setValue('indiceList_filter_diario',   $filter); // stores the filter in the session
        }


        if (isset($data->segundosdiarios) AND ($data->segundosdiarios)) {
            $filter = new TFilter('segundosdiarios', '=', "$data->segundosdiarios"); // create the filter
            TSession::setValue('indiceList_filter_segundosdiarios',   $filter); // stores the filter in the session
        }


        if (isset($data->horassemanal) AND ($data->horassemanal)) {
            $filter = new TFilter('horassemanal', '=', "$data->horassemanal"); // create the filter
            TSession::setValue('indiceList_filter_horassemanal',   $filter); // stores the filter in the session
        }


        if (isset($data->valorhora) AND ($data->valorhora)) {
            $filter = new TFilter('valorhora', '=', "$data->valorhora"); // create the filter
            TSession::setValue('indiceList_filter_valorhora',   $filter); // stores the filter in the session
        }


        if (isset($data->datavigencia) AND ($data->datavigencia)) {
            $filter = new TFilter('datavigencia', '>=', "$data->datavigencia"); // create the filter
            TSession::setValue('indiceList_filter_datavigencia',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('indice_filter_data', $data);
        
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
            
            // creates a repository for indice
            $repository = new TRepository('indice');
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
            

            if (TSession::getValue('indiceList_filter_id')) {
                $criteria->add(TSession::getValue('indiceList_filter_id')); // add the session filter
            }


            if (TSession::getValue('indiceList_filter_diario')) {
                $criteria->add(TSession::getValue('indiceList_filter_diario')); // add the session filter
            }


            if (TSession::getValue('indiceList_filter_segundosdiarios')) {
                $criteria->add(TSession::getValue('indiceList_filter_segundosdiarios')); // add the session filter
            }


            if (TSession::getValue('indiceList_filter_horassemanal')) {
                $criteria->add(TSession::getValue('indiceList_filter_horassemanal')); // add the session filter
            }


            if (TSession::getValue('indiceList_filter_valorhora')) {
                $criteria->add(TSession::getValue('indiceList_filter_valorhora')); // add the session filter
            }


            if (TSession::getValue('indiceList_filter_datavigencia')) {
                $criteria->add(TSession::getValue('indiceList_filter_datavigencia')); // add the session filter
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
                    $object->datavigencia = TDate::date2br($object->datavigencia);
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
            $object = new indice($key, FALSE); // instantiates the Active Record
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
