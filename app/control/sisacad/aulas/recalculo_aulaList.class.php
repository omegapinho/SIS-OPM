<?php
/**
 * recalculo_aulaList Listing
 * @author  <your name here>
 */
class recalculo_aulaList extends TPage
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
        $this->form = new TQuickForm('form_search_professorcontrole_aula');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('professorcontrole_aula');
        

        // create the form fields
        $id = new TEntry('id');
        $professor_id = new TEntry('professor_id');
        $controle_aula_id = new TEntry('controle_aula_id');
        $aulas_saldo = new TEntry('aulas_saldo');
        $aulas_pagas = new TEntry('aulas_pagas');
        $data_aula = new TEntry('data_aula');
        $nivel_pagamento_id = new TEntry('nivel_pagamento_id');
        $titularidade_id = new TEntry('titularidade_id');
        $data_pagamento = new TEntry('data_pagamento');
        $data_quitacao = new TEntry('data_quitacao');
        $validado = new TEntry('validado');
        $validador = new TEntry('validador');
        $valor_aula = new TEntry('valor_aula');


        // add the fields
        $this->form->addQuickField('Id', $id,  200 );
        $this->form->addQuickField('Professor Id', $professor_id,  200 );
        $this->form->addQuickField('Controle Aula Id', $controle_aula_id,  200 );
        $this->form->addQuickField('Aulas Saldo', $aulas_saldo,  200 );
        $this->form->addQuickField('Aulas Pagas', $aulas_pagas,  200 );
        $this->form->addQuickField('Data Aula', $data_aula,  200 );
        $this->form->addQuickField('Nivel Pagamento Id', $nivel_pagamento_id,  200 );
        $this->form->addQuickField('Titularidade Id', $titularidade_id,  200 );
        $this->form->addQuickField('Data Pagamento', $data_pagamento,  200 );
        $this->form->addQuickField('Data Quitacao', $data_quitacao,  200 );
        $this->form->addQuickField('Validado', $validado,  200 );
        $this->form->addQuickField('Validador', $validador,  200 );
        $this->form->addQuickField('Valor Aula', $valor_aula,  200 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('professorcontrole_aula_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_professor_id = new TDataGridColumn('professor_id', 'Professor Id', 'right');
        $column_controle_aula_id = new TDataGridColumn('controle_aula_id', 'Controle Aula Id', 'right');
        $column_aulas_saldo = new TDataGridColumn('aulas_saldo', 'Aulas Saldo', 'right');
        $column_aulas_pagas = new TDataGridColumn('aulas_pagas', 'Aulas Pagas', 'right');
        $column_data_aula = new TDataGridColumn('data_aula', 'Data Aula', 'left');
        $column_nivel_pagamento_id = new TDataGridColumn('nivel_pagamento_id', 'Nivel Pagamento Id', 'right');
        $column_titularidade_id = new TDataGridColumn('titularidade_id', 'Titularidade Id', 'right');
        $column_data_pagamento = new TDataGridColumn('data_pagamento', 'Data Pagamento', 'left');
        $column_data_quitacao = new TDataGridColumn('data_quitacao', 'Data Quitacao', 'left');
        $column_validado = new TDataGridColumn('validado', 'Validado', 'left');
        $column_validador = new TDataGridColumn('validador', 'Validador', 'left');
        $column_valor_aula = new TDataGridColumn('valor_aula', 'Valor Aula', 'right');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_professor_id);
        $this->datagrid->addColumn($column_controle_aula_id);
        $this->datagrid->addColumn($column_aulas_saldo);
        $this->datagrid->addColumn($column_aulas_pagas);
        $this->datagrid->addColumn($column_data_aula);
        $this->datagrid->addColumn($column_nivel_pagamento_id);
        $this->datagrid->addColumn($column_titularidade_id);
        $this->datagrid->addColumn($column_data_pagamento);
        $this->datagrid->addColumn($column_data_quitacao);
        $this->datagrid->addColumn($column_validado);
        $this->datagrid->addColumn($column_validador);
        $this->datagrid->addColumn($column_valor_aula);

        
        // create EDIT action
        $action_edit = new TDataGridAction(array($this, 'onReload'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('professor_id');
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
            
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new professorcontrole_aula($key); // instantiates the Active Record
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
        TSession::setValue('recalculo_aulaList_filter_id',   NULL);
        TSession::setValue('recalculo_aulaList_filter_professor_id',   NULL);
        TSession::setValue('recalculo_aulaList_filter_controle_aula_id',   NULL);
        TSession::setValue('recalculo_aulaList_filter_aulas_saldo',   NULL);
        TSession::setValue('recalculo_aulaList_filter_aulas_pagas',   NULL);
        TSession::setValue('recalculo_aulaList_filter_data_aula',   NULL);
        TSession::setValue('recalculo_aulaList_filter_nivel_pagamento_id',   NULL);
        TSession::setValue('recalculo_aulaList_filter_titularidade_id',   NULL);
        TSession::setValue('recalculo_aulaList_filter_data_pagamento',   NULL);
        TSession::setValue('recalculo_aulaList_filter_data_quitacao',   NULL);
        TSession::setValue('recalculo_aulaList_filter_validado',   NULL);
        TSession::setValue('recalculo_aulaList_filter_validador',   NULL);
        TSession::setValue('recalculo_aulaList_filter_valor_aula',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', 'like', "%{$data->id}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->professor_id) AND ($data->professor_id)) {
            $filter = new TFilter('professor_id', 'like', "%{$data->professor_id}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_professor_id',   $filter); // stores the filter in the session
        }


        if (isset($data->controle_aula_id) AND ($data->controle_aula_id)) {
            $filter = new TFilter('controle_aula_id', 'like', "%{$data->controle_aula_id}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_controle_aula_id',   $filter); // stores the filter in the session
        }


        if (isset($data->aulas_saldo) AND ($data->aulas_saldo)) {
            $filter = new TFilter('aulas_saldo', 'like', "%{$data->aulas_saldo}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_aulas_saldo',   $filter); // stores the filter in the session
        }


        if (isset($data->aulas_pagas) AND ($data->aulas_pagas)) {
            $filter = new TFilter('aulas_pagas', 'like', "%{$data->aulas_pagas}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_aulas_pagas',   $filter); // stores the filter in the session
        }


        if (isset($data->data_aula) AND ($data->data_aula)) {
            $filter = new TFilter('data_aula', 'like', "%{$data->data_aula}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_data_aula',   $filter); // stores the filter in the session
        }


        if (isset($data->nivel_pagamento_id) AND ($data->nivel_pagamento_id)) {
            $filter = new TFilter('nivel_pagamento_id', 'like', "%{$data->nivel_pagamento_id}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_nivel_pagamento_id',   $filter); // stores the filter in the session
        }


        if (isset($data->titularidade_id) AND ($data->titularidade_id)) {
            $filter = new TFilter('titularidade_id', 'like', "%{$data->titularidade_id}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_titularidade_id',   $filter); // stores the filter in the session
        }


        if (isset($data->data_pagamento) AND ($data->data_pagamento)) {
            $filter = new TFilter('data_pagamento', 'like', "%{$data->data_pagamento}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_data_pagamento',   $filter); // stores the filter in the session
        }


        if (isset($data->data_quitacao) AND ($data->data_quitacao)) {
            $filter = new TFilter('data_quitacao', 'like', "%{$data->data_quitacao}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_data_quitacao',   $filter); // stores the filter in the session
        }


        if (isset($data->validado) AND ($data->validado)) {
            $filter = new TFilter('validado', 'like', "%{$data->validado}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_validado',   $filter); // stores the filter in the session
        }


        if (isset($data->validador) AND ($data->validador)) {
            $filter = new TFilter('validador', 'like', "%{$data->validador}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_validador',   $filter); // stores the filter in the session
        }


        if (isset($data->valor_aula) AND ($data->valor_aula)) {
            $filter = new TFilter('valor_aula', 'like', "%{$data->valor_aula}%"); // create the filter
            TSession::setValue('recalculo_aulaList_filter_valor_aula',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('professorcontrole_aula_filter_data', $data);
        
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
            
            // creates a repository for professorcontrole_aula
            $repository = new TRepository('professorcontrole_aula');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'professor_id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            

            if (TSession::getValue('recalculo_aulaList_filter_id')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_id')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_professor_id')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_professor_id')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_controle_aula_id')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_controle_aula_id')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_aulas_saldo')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_aulas_saldo')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_aulas_pagas')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_aulas_pagas')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_data_aula')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_data_aula')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_nivel_pagamento_id')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_nivel_pagamento_id')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_titularidade_id')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_titularidade_id')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_data_pagamento')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_data_pagamento')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_data_quitacao')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_data_quitacao')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_validado')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_validado')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_validador')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_validador')); // add the session filter
            }


            if (TSession::getValue('recalculo_aulaList_filter_valor_aula')) {
                $criteria->add(TSession::getValue('recalculo_aulaList_filter_valor_aula')); // add the session filter
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
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new professorcontrole_aula($key, FALSE); // instantiates the Active Record
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
