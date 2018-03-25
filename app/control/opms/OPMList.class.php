<?php
/**
 * OPMList Listing
 * @author  <your name here>
 */
class OPMList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    private $opm_superior;
    private $opms_load = false;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        if ($this->opms_load == false)
        {
            $this->opm_superior = $this->getOpmsSubordinadas();
            $this->opms_load = true;
        } 
        
        // creates the form
        $this->form = new TQuickForm('form_search_OPM');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem de Unidades Policiais');
        

        // create the form fields
        $nome = new TEntry('nome');
        $idsuperior = new TEntry('idsuperior');
        $sigla = new TEntry('sigla');


        // add the fields
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Subordinação', $idsuperior,  400 );
        $this->form->addQuickField('Sigla', $sigla,  200 );

        $idsuperior->setEditable(false);
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('OPM_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('OPMForm_edt', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'ID', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'center');
        $column_idsuperior = new TDataGridColumn('idsuperior', 'Subordinação', 'center');
        $column_sigla = new TDataGridColumn('sigla', 'Sigla', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_idsuperior);
        $this->datagrid->addColumn($column_sigla);


        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_idsuperior = new TAction(array($this, 'onReload'));
        $order_idsuperior->setParameter('order', 'idsuperior');
        $column_idsuperior->setAction($order_idsuperior);
        
        $order_sigla = new TAction(array($this, 'onReload'));
        $order_sigla->setParameter('order', 'sigla');
        $column_sigla->setAction($order_sigla);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('OPMForm_edt', 'onEdit'));
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
            $object = new OPM($key); // instantiates the Active Record
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
        TSession::setValue('OPMList_filter_nome',   NULL);
        TSession::setValue('OPMList_filter_idsuperior',   NULL);
        TSession::setValue('OPMList_filter_sigla',   NULL);

        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('OPMList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->idsuperior) AND ($data->idsuperior)) {
            $filter = new TFilter('idsuperior', 'like', "%{$data->idsuperior}%"); // create the filter
            TSession::setValue('OPMList_filter_idsuperior',   $filter); // stores the filter in the session
        }


        if (isset($data->sigla) AND ($data->sigla)) {
            $filter = new TFilter('sigla', 'like', "%{$data->sigla}%"); // create the filter
            TSession::setValue('OPMList_filter_sigla',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('OPM_filter_data', $data);
        
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
            
            // creates a repository for OPM
            $repository = new TRepository('OPM');
            $limit = 8;
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
            

            if (TSession::getValue('OPMList_filter_nome')) {
                $criteria->add(TSession::getValue('OPMList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('OPMList_filter_idsuperior')) {
                $criteria->add(TSession::getValue('OPMList_filter_idsuperior')); // add the session filter
            }


            if (TSession::getValue('OPMList_filter_sigla')) {
                $criteria->add(TSession::getValue('OPMList_filter_sigla')); // add the session filter
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
                    //var_dump($object);
                    //$object->idsuperior = $this->opm_superior[$object->idsuperior]['nome'];
                    //var_dump($this->opm_superior);
                    //echo $object->idsuperior;
                    $opm = $this->opm_superior[$object->idsuperior]['sigla'];
                    $object->idsuperior = $opm;
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
            $object = new OPM($key, FALSE); // instantiates the Active Record
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
    }//Fim Módulo
    public function getOpmsSubordinadas ($param = null)
    {
        try
        {
            TTransaction::open('sicad');
            $limite = "SELECT DISTINCT idsuperior FROM g_geral.opm WHERE idsuperior IS NOT NULL";
            $sql = "SELECT DISTINCT * FROM g_geral.opm WHERE id IN (".$limite.");"; 
            $conn = TTransaction::get(); 
            $res = $conn->prepare($sql);
            $res->execute();
            $res->setFetchMode(PDO::FETCH_NAMED);
            $campos = $res->fetchAll();
            TTransaction::close();
            //print_r($campos);
            //$ret = $campos;
            $ret = array();
            $ret[0] = array('id'=>0,'nome'=>'--','sigla'=>'--');
            foreach ($campos as $campo)
            {
                $ret[$campo['id']]= $campo;
            }
            //if (self::is_dev())
            //{
                //var_dump($ret);
            //}
            //print_r ($ret);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $ret = array(0=>'--'); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
        return $ret;
    }//Fim Módulo
    
    
    
}
