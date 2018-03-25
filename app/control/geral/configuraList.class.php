<?php
/**
 * configuraList Listing
 * @author  <your name here>
 */
class configuraList extends TPage
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
        $this->form = new TQuickForm('form_search_configura');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('configura');
        

        // create the form fields
        $id = new TEntry('id');
        
        $criteria = new TCriteria; 
        $criteria->add(new TFilter('dominio', '=', 'configura'));
        
        $dominio = new TDBCombo('dominio','sicad','Item','nome','nome','ordem',$criteria);
        $pagina = new TEntry('pagina');
        $name = new TEntry('name');
        $ativo = new TCombo('ativo');
        $visivel = new TCombo('visivel');

        //Valores
        $item = array ('S'=>'SIM','N'=>'NÃO');
        $ativo->addItems($item);
        $visivel->addItems($item);


        // add the fields
        $this->form->addQuickField('ID', $id,  50 );
        $this->form->addQuickField('Sistema Utilizado', $dominio,  250 );
        $this->form->addQuickField('Serviço/Página', $pagina,  400 );
        $this->form->addQuickField('Nome', $name,  200 );
        $this->form->addQuickField('Ativo?', $ativo,  80 );
        $this->form->addQuickField('Visível?', $visivel,  80 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('configura_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('configuraForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_id = new TDataGridColumn('id', 'ID', 'right');
        $column_dominio = new TDataGridColumn('dominio', 'Sistema Utilizado', 'left');
        $column_pagina = new TDataGridColumn('pagina', 'Serviço/Página', 'left');
        $column_name = new TDataGridColumn('name', 'Nome', 'left');
        $column_ativo = new TDataGridColumn('ativo', 'Ativo?', 'left');
        $column_visivel = new TDataGridColumn('visivel', 'Visível?', 'left');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_dominio);
        $this->datagrid->addColumn($column_pagina);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_ativo);
        $this->datagrid->addColumn($column_visivel);


        // creates the datagrid column actions
        $order_dominio = new TAction(array($this, 'onReload'));
        $order_dominio->setParameter('order', 'dominio');
        $column_dominio->setAction($order_dominio);
        
        $order_pagina = new TAction(array($this, 'onReload'));
        $order_pagina->setParameter('order', 'pagina');
        $column_pagina->setAction($order_pagina);
        
        $order_name = new TAction(array($this, 'onReload'));
        $order_name->setParameter('order', 'name');
        $column_name->setAction($order_name);
        
        $order_ativo = new TAction(array($this, 'onReload'));
        $order_ativo->setParameter('order', 'ativo');
        $column_ativo->setAction($order_ativo);
        
        $order_visivel = new TAction(array($this, 'onReload'));
        $order_visivel->setParameter('order', 'visivel');
        $column_visivel->setAction($order_visivel);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('configuraForm', 'onEdit'));
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
            
            TTransaction::open('sicad'); // open a transaction with database
            $object = new configura($key); // instantiates the Active Record
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
        TSession::setValue('configuraList_filter_id',   NULL);
        TSession::setValue('configuraList_filter_dominio',   NULL);
        TSession::setValue('configuraList_filter_pagina',   NULL);
        TSession::setValue('configuraList_filter_name',   NULL);
        TSession::setValue('configuraList_filter_ativo',   NULL);
        TSession::setValue('configuraList_filter_visivel',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('configuraList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->dominio) AND ($data->dominio)) {
            $filter = new TFilter('dominio', '=', "$data->dominio"); // create the filter
            TSession::setValue('configuraList_filter_dominio',   $filter); // stores the filter in the session
        }


        if (isset($data->pagina) AND ($data->pagina)) {
            $filter = new TFilter('pagina', 'like', "%{$data->pagina}%"); // create the filter
            TSession::setValue('configuraList_filter_pagina',   $filter); // stores the filter in the session
        }


        if (isset($data->name) AND ($data->name)) {
            $filter = new TFilter('name', '=', "$data->name"); // create the filter
            TSession::setValue('configuraList_filter_name',   $filter); // stores the filter in the session
        }


        if (isset($data->ativo) AND ($data->ativo)) {
            $filter = new TFilter('ativo', '=', "$data->ativo"); // create the filter
            TSession::setValue('configuraList_filter_ativo',   $filter); // stores the filter in the session
        }


        if (isset($data->visivel) AND ($data->visivel)) {
            $filter = new TFilter('visivel', '=', "$data->visivel"); // create the filter
            TSession::setValue('configuraList_filter_visivel',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('configura_filter_data', $data);
        
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
            
            // creates a repository for configura
            $repository = new TRepository('configura');
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
            

            if (TSession::getValue('configuraList_filter_id')) {
                $criteria->add(TSession::getValue('configuraList_filter_id')); // add the session filter
            }


            if (TSession::getValue('configuraList_filter_dominio')) {
                $criteria->add(TSession::getValue('configuraList_filter_dominio')); // add the session filter
            }


            if (TSession::getValue('configuraList_filter_pagina')) {
                $criteria->add(TSession::getValue('configuraList_filter_pagina')); // add the session filter
            }


            if (TSession::getValue('configuraList_filter_name')) {
                $criteria->add(TSession::getValue('configuraList_filter_name')); // add the session filter
            }


            if (TSession::getValue('configuraList_filter_ativo')) {
                $criteria->add(TSession::getValue('configuraList_filter_ativo')); // add the session filter
            }


            if (TSession::getValue('configuraList_filter_visivel')) {
                $criteria->add(TSession::getValue('configuraList_filter_visivel')); // add the session filter
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
            $object = new configura($key, FALSE); // instantiates the Active Record
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
            TTransaction::open('sicad');
            if ($selected)
            {
                // delete each record from collection
                foreach ($selected as $id)
                {
                    $object = new configura;
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
