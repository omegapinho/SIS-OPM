<?php
/**
 * prontuario_opmList Listing
 * @author  <your name here>
 */
class prontuario_opmList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    var $popAtivo = true;
    var $sistema  = 'Gestão de OPM';  //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Dados OPM';      //Nome da página de serviço.
    
    protected $nivel_sistema = false;  //Registra o nível de acesso do usuário
    protected $config        = array();//Array com configuração
    protected $config_load   = false;  //Informa que a configuração está carregada
    
    private $up_date_pm    = false;  //Ativa desativa atualização pessoal
    static $up_date_opm   = true;  //Ativa desativa atualização de OPM    
    
    //Nomes registrados em banco de configuração e armazenados na array config
    //private $cfg_ord     = 'criar_ordinaria';
    
    private $opm_operador = false;     // Unidade do Usuário
    private $listas = false;           // Lista de valores e array de OPM
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_search_prontuario_opm');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Lista de OPMs');
        
        // Inicía ferramentas auxiliares
        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if ($this->opm_operador==false)                     //Carrega OPM do usuário
        {
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
            $this->opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        }
        if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
        {
            $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
            $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                               //Informa que configuração foi carregada
        }

        //Monta ComboBox com OPMs que o Operador pode ver
        //echo $this->nivel_sistema.'---'.$this->opm_operador;
        /*if ($this->nivel_sistema>=80)           //Adm e Gestor
        {
            $criteria_opm = null;
        }
        else if ($this->nivel_sistema>=50 )     //Nível Operador (carrega OPM e subOPMs)
        {
            $criteria_opm = new TCriteria;
            //Se não há lista de OPM, carrega só a OPM do usuário
            $lista = ($this->listas['valores']!='') ? $this->listas['valores'] : $profile['unidade']['id'];
            $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$lista."))";
            $criteria_opm->add (new TFilter ('id','IN',$query));
        }
        else if ($this->nivel_sistema<50)       //nível de visitante (só a própria OPM)
        {
            $criteria_opm = new TCriteria;
            $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$this->opm_operador."))";
            $criteria_opm->add (new TFilter ('id','IN',$query));
        }*/

        // create the form fields
        $nome = new TEntry('nome');
        $status = new TCombo('status');
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('uf','=','GO'));
        $cidade = new TDBCombo('cidade','sicad','cidades','nome','nome','nome',$criteria);


        // add the fields
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Status', $status,  200 );
        $this->form->addQuickField('Cidade', $cidade,  400 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('prontuario_opm_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('prontuario_opmForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'center');
        $column_status = new TDataGridColumn('status', 'Status', 'center');
        $column_cidade = new TDataGridColumn('cidade', 'Cidade', 'center');
        $column_telefone = new TDataGridColumn('telefone', 'Fone', 'center');
        $column_email = new TDataGridColumn('email', 'Email', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_status);
        $this->datagrid->addColumn($column_cidade);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_email);


        // creates the datagrid column actions
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_status = new TAction(array($this, 'onReload'));
        $order_status->setParameter('order', 'status');
        $column_status->setAction($order_status);
        
        $order_cidade = new TAction(array($this, 'onReload'));
        $order_cidade->setParameter('order', 'cidade');
        $column_cidade->setAction($order_cidade);
        
        $order_telefone = new TAction(array($this, 'onReload'));
        $order_telefone->setParameter('order', 'telefone');
        $column_telefone->setAction($order_telefone);
        
        $order_email = new TAction(array($this, 'onReload'));
        $order_email->setParameter('order', 'email');
        $column_email->setAction($order_email);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('prontuario_opmForm', 'onEdit'));
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
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
            $object = new prontuario_opm($key); // instantiates the Active Record
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
        TSession::setValue('prontuario_opmList_filter_nome',   NULL);
        TSession::setValue('prontuario_opmList_filter_status',   NULL);
        TSession::setValue('prontuario_opmList_filter_cidade',   NULL);

        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('prontuario_opmList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->status) AND ($data->status)) {
            $filter = new TFilter('status', '=', "$data->status"); // create the filter
            TSession::setValue('prontuario_opmList_filter_status',   $filter); // stores the filter in the session
        }


        if (isset($data->cidade) AND ($data->cidade)) {
            $filter = new TFilter('cidade', 'like', "%{$data->cidade}%"); // create the filter
            TSession::setValue('prontuario_opmList_filter_cidade',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('prontuario_opm_filter_data', $data);
        
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
            
            // creates a repository for prontuario_opm
            $repository = new TRepository('prontuario_opm');
            $limit = 10;
            // creates a criteria
            $criteria = new TCriteria;
            
            //Monta ComboBox com OPMs que o Operador pode ver
            //echo $this->nivel_sistema.'---'.$this->opm_operador;
            if ($this->nivel_sistema>80 )
            {
                
            } 
            else if ($this->nivel_sistema>=50 )     //Nível Operador (carrega OPM e subOPMs)
            {
                //Se não há lista de OPM, carrega só a OPM do usuário
                $lista = ($this->listas['valores']!='') ? $this->listas['valores'] : $profile['unidade']['id'];
                $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$lista."))";
                $criteria->add (new TFilter ('id','IN',$query));
            }
            else if ($this->nivel_sistema<50)       //nível de visitante (só a própria OPM)
            {
                $query = "(SELECT DISTINCT id FROM g_geral.opm WHERE id IN (".$this->opm_operador."))";
                $criteria->add (new TFilter ('id','IN',$query));
            }
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            

            if (TSession::getValue('prontuario_opmList_filter_nome')) {
                $criteria->add(TSession::getValue('prontuario_opmList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('prontuario_opmList_filter_status')) {
                $criteria->add(TSession::getValue('prontuario_opmList_filter_status')); // add the session filter
            }


            if (TSession::getValue('prontuario_opmList_filter_cidade')) {
                $criteria->add(TSession::getValue('prontuario_opmList_filter_cidade')); // add the session filter
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
            $object = new prontuario_opm($key, FALSE); // instantiates the Active Record
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
                    $object = new prontuario_opm;
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
