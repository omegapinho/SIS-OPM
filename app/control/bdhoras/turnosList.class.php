<?php 
/**
 * turnosList Listing
 * @author  <your name here>
 */
class turnosList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    var $excluidos = array(0,13); //Não permite que certos IDs apareçam
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_search_turnos');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem dos Turnos de Serviço já Cadastrados');
        

        // create the form fields
        $tag = new TEntry('tag');
        $inicia_seg = new TCombo('inicia_seg');
        $quarta = new TCombo('quarta');
        $sabado = new TCombo('sabado');
        $domingo = new TCombo('domingo');
        $feriado = new TCombo('feriado');
        $oculto = new TCombo('oculto');

        //Define valores padrão
        $item = array();
        $item['f'] = 'Não';
        $item['t'] = 'Sim';
        $inicia_seg->addItems($item);
        $domingo->addItems($item);
        $sabado->addItems($item);
        $feriado->addItems($item);
        $quarta->addItems($item);
        $oculto->addItems($item);

        // add the fields
        $this->form->addQuickField('Abreviação', $tag,  400 );
        $this->form->addQuickField('Inicia Segunda?', $inicia_seg,  80 );
        $this->form->addQuickField('Trab. Quarta?', $quarta,  80 );
        $this->form->addQuickField('Trab. Sábado?', $sabado,  80 );
        $this->form->addQuickField('Trab. Domingo?', $domingo,  80 );
        $this->form->addQuickField('Trab. Feriado?', $feriado,  80 );
        $this->form->addQuickField('Oculto?', $oculto,  80 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('turnos_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('turnosForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check         = new TDataGridColumn('check', '', 'center');
        $column_tag           = new TDataGridColumn('tag', 'Abreviação', 'left');
        $column_inicia_seg    = new TDataGridColumn('inicia_seg', 'Inicia Segunda?', 'center');
        $column_quarta        = new TDataGridColumn('quarta', 'Trab. Quarta?', 'center');
        $column_sabado        = new TDataGridColumn('sabado', 'Trab. Sábado?', 'center');
        $column_domingo       = new TDataGridColumn('domingo', 'Trab. Domingo?', 'center');
        $column_feriado       = new TDataGridColumn('feriado', 'Trab. Feriado?', 'center');
        $column_qnt_h_turno1  = new TDataGridColumn('qnt_h_turno1', '1ºTurno', 'right');
        $column_qnt_h_intervalo1 = new TDataGridColumn('qnt_h_intervalo1', 'Intervalo', 'right');
        $column_qnt_h_turno2  = new TDataGridColumn('qnt_h_turno2', '2ºTurno', 'right');
        $column_qnt_h_folga   = new TDataGridColumn('qnt_h_folga', 'Folga', 'right');
        $column_oculto        = new TDataGridColumn('oculto', 'Oculto?', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_tag);
        $this->datagrid->addColumn($column_inicia_seg);
        $this->datagrid->addColumn($column_quarta);
        $this->datagrid->addColumn($column_sabado);
        $this->datagrid->addColumn($column_domingo);
        $this->datagrid->addColumn($column_feriado);
        $this->datagrid->addColumn($column_qnt_h_turno1);
        $this->datagrid->addColumn($column_qnt_h_intervalo1);
        $this->datagrid->addColumn($column_qnt_h_turno2);
        $this->datagrid->addColumn($column_qnt_h_folga);
        $this->datagrid->addColumn($column_oculto);


        // creates the datagrid column actions
        $order_tag = new TAction(array($this, 'onReload'));
        $order_tag->setParameter('order', 'tag');
        $column_tag->setAction($order_tag);
        
        $order_inicia_seg = new TAction(array($this, 'onReload'));
        $order_inicia_seg->setParameter('order', 'inicia_seg');
        $column_inicia_seg->setAction($order_inicia_seg);
        
        $order_quarta = new TAction(array($this, 'onReload'));
        $order_quarta->setParameter('order', 'quarta');
        $column_quarta->setAction($order_quarta);
        
        $order_sabado = new TAction(array($this, 'onReload'));
        $order_sabado->setParameter('order', 'sabado');
        $column_sabado->setAction($order_sabado);
        
        $order_domingo = new TAction(array($this, 'onReload'));
        $order_domingo->setParameter('order', 'domingo');
        $column_domingo->setAction($order_domingo);
        
        $order_feriado = new TAction(array($this, 'onReload'));
        $order_feriado->setParameter('order', 'feriado');
        $column_feriado->setAction($order_feriado);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('turnosForm', 'onEdit'));
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
            $object = new turnos($key); // instantiates the Active Record
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
        TSession::setValue('turnosList_filter_tag',   NULL);
        TSession::setValue('turnosList_filter_inicia_seg',   NULL);
        TSession::setValue('turnosList_filter_quarta',   NULL);
        TSession::setValue('turnosList_filter_sabado',   NULL);
        TSession::setValue('turnosList_filter_domingo',   NULL);
        TSession::setValue('turnosList_filter_feriado',   NULL);

        if (isset($data->tag) AND ($data->tag)) {
            $filter = new TFilter('tag', 'like', "%{$data->tag}%"); // create the filter
            TSession::setValue('turnosList_filter_tag',   $filter); // stores the filter in the session
        }


        if (isset($data->inicia_seg) AND ($data->inicia_seg)) {
            $filter = new TFilter('inicia_seg', '=', "$data->inicia_seg"); // create the filter
            TSession::setValue('turnosList_filter_inicia_seg',   $filter); // stores the filter in the session
        }


        if (isset($data->quarta) AND ($data->quarta)) {
            $filter = new TFilter('quarta', '=', "$data->quarta"); // create the filter
            TSession::setValue('turnosList_filter_quarta',   $filter); // stores the filter in the session
        }


        if (isset($data->sabado) AND ($data->sabado)) {
            $filter = new TFilter('sabado', '=', "$data->sabado"); // create the filter
            TSession::setValue('turnosList_filter_sabado',   $filter); // stores the filter in the session
        }


        if (isset($data->domingo) AND ($data->domingo)) {
            $filter = new TFilter('domingo', '=', "$data->domingo"); // create the filter
            TSession::setValue('turnosList_filter_domingo',   $filter); // stores the filter in the session
        }


        if (isset($data->feriado) AND ($data->feriado)) {
            $filter = new TFilter('feriado', '=', "$data->feriado"); // create the filter
            TSession::setValue('turnosList_filter_feriado',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('turnos_filter_data', $data);
        
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
            
            // creates a repository for turnos
            $repository = new TRepository('turnos');
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
            

            if (TSession::getValue('turnosList_filter_tag')) {
                $criteria->add(TSession::getValue('turnosList_filter_tag')); // add the session filter
            }


            if (TSession::getValue('turnosList_filter_inicia_seg')) {
                $criteria->add(TSession::getValue('turnosList_filter_inicia_seg')); // add the session filter
            }


            if (TSession::getValue('turnosList_filter_quarta')) {
                $criteria->add(TSession::getValue('turnosList_filter_quarta')); // add the session filter
            }


            if (TSession::getValue('turnosList_filter_sabado')) {
                $criteria->add(TSession::getValue('turnosList_filter_sabado')); // add the session filter
            }


            if (TSession::getValue('turnosList_filter_domingo')) {
                $criteria->add(TSession::getValue('turnosList_filter_domingo')); // add the session filter
            }


            if (TSession::getValue('turnosList_filter_feriado')) {
                $criteria->add(TSession::getValue('turnosList_filter_feriado')); // add the session filter
            }

            $criteria->add(new TFilter('id','NOT IN',$this->excluidos)); // add the session filter
            //$criteria->add(new TFilter('id','!=',13)); // add the session filter
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
                    $object->domingo    = ($object->domingo=='f') ? 'Não' : 'Sim';
                    $object->sabado     = ($object->sabado=='f') ? 'Não' : 'Sim';
                    $object->feriado    = ($object->feriado=='f') ? 'Não' : 'Sim';
                    $object->inicia_seg = ($object->inicia_seg=='f') ? 'Não' : 'Sim';
                    $object->quarta     = ($object->quarta=='f') ? 'Não' : 'Sim';
                    $object->oculto     = ($object->oculto=='f') ? 'Não' : 'Sim';
                    
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
            $object = new turnos($key, FALSE); // instantiates the Active Record
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
                    $object = new turnos;
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
