<?php
/**
 * ItemList Listing
 * @author  <your name here>
 */
class ItemList extends TPage
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
        $this->form = new TQuickForm('form_search_Item');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem de Itens para COMBO');
        

        // create the form fields
        $nome = new TEntry('nome');
        $dominio = new TEntry('dominio');
        $oculto = new TCombo('oculto');

        //Valores dos campos pre-definidos
        $itemStatus= array();
        $itemStatus['t'] = 'Sim';
        $itemStatus['f'] = 'Não';
        $oculto->addItems($itemStatus);
        $oculto->setValue('f');
        
        $dominio->setCompletion(array('PARENTESCO','POSTO/GRADUAÇÃO','STATUS','SITUAÇÃO','SEXO','HABILITAÇÃO'));

        // add the fields
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Domínio', $dominio,  400 );
        $this->form->addQuickField('Oculto?', $oculto,  80 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('Item_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('ItemForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_nome = new TDataGridColumn('nome', 'Nome', 'center');
        $column_ordem = new TDataGridColumn('ordem', 'Ordem', 'center');
        $column_dominio = new TDataGridColumn('dominio', 'Domínio', 'center');
        $column_subdominio = new TDataGridColumn('subdominio', 'Subdomínio', 'center');
        $column_oculto = new TDataGridColumn('oculto', 'Oculto?', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_ordem);
        $this->datagrid->addColumn($column_dominio);
        $this->datagrid->addColumn($column_subdominio);
        $this->datagrid->addColumn($column_oculto);


        // creates the datagrid column actions
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_ordem = new TAction(array($this, 'onReload'));
        $order_ordem->setParameter('order', 'ordem');
        $column_ordem->setAction($order_ordem);
        
        $order_dominio = new TAction(array($this, 'onReload'));
        $order_dominio->setParameter('order', 'dominio');
        $column_dominio->setAction($order_dominio);
        
        $order_subdominio = new TAction(array($this, 'onReload'));
        $order_subdominio->setParameter('order', 'subdominio');
        $column_subdominio->setAction($order_subdominio);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        


        // inline editing
        $ordem_edit = new TDataGridAction(array($this, 'onInlineEdit'));
        $ordem_edit->setField('id');
        $column_ordem->setEditAction($ordem_edit);
        
        //$oculto_edit = new TDataGridAction(array($this, 'onInlineEdit'));
        //$oculto_edit->setField('id');
        //$column_oculto->setEditAction($oculto_edit);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('ItemForm', 'onEdit'));
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
            $object = new Item($key); // instantiates the Active Record
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
        TSession::setValue('ItemList_filter_nome',   NULL);
        TSession::setValue('ItemList_filter_dominio',   NULL);
        TSession::setValue('ItemList_filter_oculto',   NULL);

        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('ItemList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->dominio) AND ($data->dominio)) {
            $filter = new TFilter('dominio', 'like', "%{$data->dominio}%"); // create the filter
            TSession::setValue('ItemList_filter_dominio',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('ItemList_filter_oculto',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('Item_filter_data', $data);
        
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
            
            // creates a repository for Item
            $repository = new TRepository('Item');
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
            

            if (TSession::getValue('ItemList_filter_nome')) {
                $criteria->add(TSession::getValue('ItemList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('ItemList_filter_dominio')) {
                $criteria->add(TSession::getValue('ItemList_filter_dominio')); // add the session filter
            }


            if (TSession::getValue('ItemList_filter_oculto')) {
                $criteria->add(TSession::getValue('ItemList_filter_oculto')); // add the session filter
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
                    $object->oculto = ($object->oculto='f') ? "Não" : "Sim";
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
            $object = new Item($key, FALSE); // instantiates the Active Record
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
