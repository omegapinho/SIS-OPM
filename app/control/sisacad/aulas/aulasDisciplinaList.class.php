<?php
/**
 * aulasDisciplinaList Listing
 * @author  <your name here>
 */
class aulasDisciplinaList extends TPage
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
    var $servico  = 'Aula';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    static $cfg_abona_falta  = 'abona_falta_aluno';
    static $turma_key;
   
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
        
        //Procura se veio variável de seção
        $aula_disciplina = TSession::getValue('aula_disciplina');
        //var_dump($aula_disciplina);
        // creates the form
        $this->form = new TQuickForm('form_search_controle_aula');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Controle de Aulas da Disciplina - Listagem da Aulas já ministradas');
        

        // create the form fields
        $dt_inicio = new TDate('dt_inicio');
        $horas_aula = new TEntry('horas_aula');
        
        $criteria = new TCriteria();
        $criteria->add(new TFilter('oculto','!=','S'));
        
        $materia_id = new TDBCombo('materia_id','sisacad','disciplina','id','nome','nome',$criteria);

        //Valores
        if (!empty($aula_disciplina))
        {
            //$materia_id->setValue($aula_disciplina);
            $materia_id->setEditable(false);
        }
        
        //Mascara
        $dt_inicio->setMask('dd/mm/yyyy');
        
        // add the fields
        $this->form->addQuickField('Data da Aula', $dt_inicio,  120 );
        $this->form->addQuickField('C.H.', $horas_aula,  50 );
        $this->form->addQuickField('Disciplina', $materia_id,  300 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('controle_aula_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('aulasDisciplinaForm', 'onEdit')), 'bs:plus-sign green');
        $this->form->addQuickAction('Retorna ao Controle de Aula',  new TAction(array('ControleAulaList', 'onReload')), 'ico_back.png');        
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_materia_id = new TDataGridColumn('materia_id', 'Disciplina', 'center');
        $column_dt_inicio = new TDataGridColumn('dt_inicio', 'Data da Aula', 'right');
        $column_horas_aula = new TDataGridColumn('horas_aula', 'C.H.', 'right');



        // add the columns to the DataGrid
        //$this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_materia_id);
        $this->datagrid->addColumn($column_dt_inicio);
        $this->datagrid->addColumn($column_horas_aula);



        // creates the datagrid column actions
        $order_dt_inicio = new TAction(array($this, 'onReload'));
        $order_dt_inicio->setParameter('order', 'dt_inicio');
        $column_dt_inicio->setAction($order_dt_inicio);
        
        $order_horas_aula = new TAction(array($this, 'onReload'));
        $order_horas_aula->setParameter('order', 'horas_aula');
        $column_horas_aula->setAction($order_horas_aula);
        
        $order_materia_id = new TAction(array($this, 'onReload'));
        $order_materia_id->setParameter('order', 'materia_id');
        $column_materia_id->setAction($order_materia_id);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('aulasDisciplinaForm', 'onEdit'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel('Edita as ausências de aula');
        $action_edit->setImage('fa:user-times red fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(TRUE);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('fa:trash-o red fa-lg');
        $action_del->setField('id');
        //$this->datagrid->addAction($action_del);
        
        
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
        //$this->formgrid->addField($this->deleteButton);
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'ControleAulaList'));
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
            $object = new controle_aula($key); // instantiates the Active Record
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
        TSession::setValue('aulasDisciplinaList_filter_dt_inicio',   NULL);
        TSession::setValue('aulasDisciplinaList_filter_horas_aula',   NULL);
        TSession::setValue('aulasDisciplinaList_filter_materia_id',   NULL);

        if (isset($data->dt_inicio) AND ($data->dt_inicio)) {
            $filter = new TFilter('dt_inicio', '=', "$data->dt_inicio"); // create the filter
            TSession::setValue('aulasDisciplinaList_filter_dt_inicio',   $filter); // stores the filter in the session
        }


        if (isset($data->horas_aula) AND ($data->horas_aula)) {
            $filter = new TFilter('horas_aula', '=', "$data->horas_aula"); // create the filter
            TSession::setValue('aulasDisciplinaList_filter_horas_aula',   $filter); // stores the filter in the session
        }


        if (isset($data->materia_id) AND ($data->materia_id)) {
            $filter = new TFilter('materia_id', '=', "$data->materia_id"); // create the filter
            TSession::setValue('aulasDisciplinaList_filter_materia_id',   $filter); // stores the filter in the session
        }
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('controle_aula_filter_data', $data);
        
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
            
            // creates a repository for controle_aula
            $repository = new TRepository('controle_aula');
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
            

            if (TSession::getValue('aulasDisciplinaList_filter_dt_inicio')) {
                $criteria->add(TSession::getValue('aulasDisciplinaList_filter_dt_inicio')); // add the session filter
            }


            if (TSession::getValue('aulasDisciplinaList_filter_horas_aula')) {
                $criteria->add(TSession::getValue('aulasDisciplinaList_filter_horas_aula')); // add the session filter
            }

            $aula_disciplina = TSession::getValue('aula_disciplina');
            if (!empty($aula_disciplina))
            {
                $filter = new TFilter('materia_id', '=', "$aula_disciplina"); // create the filter
                $criteria->add($filter);
            }
            else if (TSession::getValue('aulasDisciplinaList_filter_materia_id')) {
                $criteria->add(TSession::getValue('aulasDisciplinaList_filter_materia_id')); // add the session filter
            }
            //var_dump($criteria->dump());

            
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
                    $object->dt_inicio = TDate::date2br($object->dt_inicio);
                    $object->materia_id = $object->materia->disciplina->nome;
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
            $object = new controle_aula($key, FALSE); // instantiates the Active Record
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
                    $object = new controle_aula;
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
