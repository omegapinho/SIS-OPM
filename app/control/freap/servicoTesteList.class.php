<?php
/**
 * servicoTesteList Listing
 * @author  <your name here>
 */
class servicoTesteList extends TPage
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
        $this->form = new TQuickForm('form_search_servico');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('servico');
        

        // create the form fields
        $id = new TEntry('id');

        //Preenche combo
        $ci = new TSicadDados;
        $retorno = $ci->get_ListaServicoOPM('140');
        if ($retorno!==false)
        {
            $nome = new TCombo('nome');
            $nome->addItems($retorno);
        }
        else
        {
            $nome = new TLabel('Não há Serviços definidos para Esta OPM');
        }


        // add the fields
        $this->form->addQuickField('ID', $id,  50 );
        $this->form->addQuickField('Nome', $nome,  400 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('servico_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('servicoForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_codigo = new TDataGridColumn('codigo', 'Codigo', 'left');
        $column_valor_base = new TDataGridColumn('valor_base', 'Valor Base', 'right');
        $column_valor_km = new TDataGridColumn('valor_km', 'Valor Km', 'right');
        $column_valor_pm = new TDataGridColumn('valor_pm', 'Valor Pm', 'right');
        $column_valor_juros = new TDataGridColumn('valor_juros', 'Valor Juros', 'right');
        $column_valor_multa = new TDataGridColumn('valor_multa', 'Valor Multa', 'right');
        $column_oculto = new TDataGridColumn('oculto', 'Oculto', 'left');
        $column_nome_chave = new TDataGridColumn('nome_chave', 'Nome Chave', 'left');
        $column_tipo_servico = new TDataGridColumn('tipo_servico', 'Tipo Servico', 'right');
        $column_valor_diaria = new TDataGridColumn('valor_diaria', 'Valor Diaria', 'right');
        $column_pm_max = new TDataGridColumn('pm_max', 'Pm Max', 'right');
        $column_diaria_min = new TDataGridColumn('diaria_min', 'Diaria Min', 'right');
        $column_diaria_max = new TDataGridColumn('diaria_max', 'Diaria Max', 'right');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_codigo);
        $this->datagrid->addColumn($column_valor_base);
        $this->datagrid->addColumn($column_valor_km);
        $this->datagrid->addColumn($column_valor_pm);
        $this->datagrid->addColumn($column_valor_juros);
        $this->datagrid->addColumn($column_valor_multa);
        $this->datagrid->addColumn($column_oculto);
        $this->datagrid->addColumn($column_nome_chave);
        $this->datagrid->addColumn($column_tipo_servico);
        $this->datagrid->addColumn($column_valor_diaria);
        $this->datagrid->addColumn($column_pm_max);
        $this->datagrid->addColumn($column_diaria_min);
        $this->datagrid->addColumn($column_diaria_max);

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('servicoForm', 'onEdit'));
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
            
            TTransaction::open('freap'); // open a transaction with database
            $object = new servico($key); // instantiates the Active Record
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
        TSession::setValue('servicoTesteList_filter_id',   NULL);
        TSession::setValue('servicoTesteList_filter_nome',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('servicoTesteList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', '=', "$data->nome"); // create the filter
            TSession::setValue('servicoTesteList_filter_nome',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('servico_filter_data', $data);
        
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
            // open a transaction with database 'freap'
            TTransaction::open('freap');
            
            // creates a repository for servico
            $repository = new TRepository('servico');
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
            

            if (TSession::getValue('servicoTesteList_filter_id')) {
                $criteria->add(TSession::getValue('servicoTesteList_filter_id')); // add the session filter
            }


            if (TSession::getValue('servicoTesteList_filter_nome')) {
                $criteria->add(TSession::getValue('servicoTesteList_filter_nome')); // add the session filter
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
            TTransaction::open('freap'); // open a transaction with database
            $object = new servico($key, FALSE); // instantiates the Active Record
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
