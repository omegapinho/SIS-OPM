<?php
/**
 * avaliacao_turmaList Listing
 * @author  <your name here>
 */
class avaliacao_turmaList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Avaliações';        //Nome da página de serviço.
    
    private $opm_operador    = false;     // Unidade do Usuário
    private $listas          = false;           // Lista de valores e array de OPM
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

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
            TSession::setValue('SISACAD_CONFIG', $fer->getConfig($this->sistema));         //Busca o Nível de acesso que o usuário tem para a Classe

            $this->config_load = true;                               //Informa que configuração foi carregada
        }
        
        // creates the form
        $this->form = new TQuickForm('form_search_avaliacao_turma');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Gestor de Provas - Listagem');
        

        // create the form fields
        if ($this->nivel_sistema<=80)//Gestores e/Operadores
        {
            $criteria = new TCriteria();
            $criteria->add(new TFilter('oculto','!=','S'));
            $query = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query), TExpression::OR_OPERATOR);
            $query = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query), TExpression::OR_OPERATOR);
        }
        else
        {
            $criteria = null;
        }
        
        $turma_id   = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        
        $criteria= new TCriteria();
        $criteria->add (new TFilter('oculto','!=','S'));
        $criteria->add (new TFilter('nome','IS','NOESC:NOT NULL'));
        
        $materia_id = new TDBCombo('materia_id','sisacad','disciplina','id','nome','nome',$criteria);
        $oculto     = new TCombo('oculto');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $oculto->setValue('N');

        // add the fields
        $this->form->addQuickField('Turma', $turma_id,  400 );
        $this->form->addQuickField('Matéria', $materia_id,  300 );
        $this->form->addQuickField('Avaliação Conclusa?', $oculto,  120 );

        //Bloqueio
        if ($this->nivel_sistema <= 80)
        {
            $oculto->setValue('N');
            $oculto->setEditable(false);
        }
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('avaliacao_turma_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        //$this->form->addQuickAction(_t('New'),  new TAction(array('avaliacao_turmaForm', 'onEdit')), 'bs:plus-sign green');
        $this->form->addQuickAction('Retorna à Turmas',  new TAction(array('turmaList', 'onReload')), 'ico_back.png');
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_turma_id   = new TDataGridColumn('turma_id', 'Turma', 'center');
        $column_materia_id = new TDataGridColumn('materia_id', 'Matéria', 'center');
        $column_oculto     = new TDataGridColumn('oculto', 'Avaliação Conclusa?', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_turma_id);
        $this->datagrid->addColumn($column_materia_id);
        $this->datagrid->addColumn($column_oculto);

        // creates the datagrid column actions
        $order_turma_id = new TAction(array($this, 'onReload'));
        $order_turma_id->setParameter('order', 'turma_id');
        $column_turma_id->setAction($order_turma_id);
        
        $order_materia_id = new TAction(array($this, 'onReload'));
        $order_materia_id->setParameter('order', 'materia_id');
        $column_materia_id->setAction($order_materia_id);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('avaliacao_turmaForm', 'onEdit'));
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
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
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
            $object = new avaliacao_turma($key); // instantiates the Active Record
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
        TSession::setValue('avaliacao_turmaList_filter_turma_id',   NULL);
        TSession::setValue('avaliacao_turmaList_filter_materia_id',   NULL);
        TSession::setValue('avaliacao_turmaList_filter_oculto',   NULL);

        if (isset($data->turma_id) AND ($data->turma_id)) {
            $filter = new TFilter('turma_id', '=', "$data->turma_id"); // create the filter
            TSession::setValue('avaliacao_turmaList_filter_turma_id',   $filter); // stores the filter in the session
        }


        if (isset($data->materia_id) AND ($data->materia_id)) {
            
            $sql = "(SELECT id FROM sisacad.materia WHERE disciplina_id = " . $data->materia_id . ")";
            
            $filter = new TFilter('materia_id', 'IN', $sql); // create the filter
            TSession::setValue('avaliacao_turmaList_filter_materia_id',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('avaliacao_turmaList_filter_oculto',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('avaliacao_turma_filter_data', $data);
        
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
            
            // creates a repository for avaliacao_turma
            $repository = new TRepository('avaliacao_turma');
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
            

            if (TSession::getValue('avaliacao_turmaList_filter_turma_id')) {
                $criteria->add(TSession::getValue('avaliacao_turmaList_filter_turma_id')); // add the session filter
            }


            if (TSession::getValue('avaliacao_turmaList_filter_materia_id')) {
                $criteria->add(TSession::getValue('avaliacao_turmaList_filter_materia_id')); // add the session filter
            }


            if (TSession::getValue('avaliacao_turmaList_filter_oculto')) {
                $criteria->add(TSession::getValue('avaliacao_turmaList_filter_oculto')); // add the session filter
            }
            if ($this->nivel_sistema <= 80)
            {
                $criteria->add(new TFilter('oculto','!=','S'));
            }
            $fer = new TFerramentas();
            $ci  = new TSicadDados();
            $profile = TSession::getValue('profile');           //Profile da Conta do usuário

            if ($this->opm_operador==false)                     //Carrega OPM do usuário
            {
                //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
                $this->opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
            }
            if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
            {
                $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
                $this->listas        = $ci->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
                $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
                $this->config_load = true;                               //Informa que configuração foi carregada
            }
            if ($this->nivel_sistema<=80)//Gestores e/Operadores
            {
                $query = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
                $criteria->add (new TFilter ('turma_id','IN',$query), TExpression::OR_OPERATOR);
                $query = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
                $criteria->add (new TFilter ('turma_id','IN',$query), TExpression::OR_OPERATOR);
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
                    $object->turma_id   = $object->turma->nome;
                    
                    $tipo_prova = $fer->lista_verificacoes($object->avaliacao_curso->tipo_avaliacao);
                    
                    $object->materia_id = $object->materia->disciplina->nome . '<b><font color="blue;"> (' . $tipo_prova . ')</font></b>';
                    $object->oculto     = $fer->lista_sim_nao($object->oculto);
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
            $object = new avaliacao_turma($key, FALSE); // instantiates the Active Record
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
/*------------------------------------------------------------------------------
 *    Monta combo box de Disciplinas
 *------------------------------------------------------------------------------*/
    public static function getDisciplinas($key = null)
    {
        $lista = array(0=>' --- Sem Disciplinas ---');
        try
        {
            TTransaction::open('sisacad');
            $materias = materias_previstas::where('curso_id','=',$key)->load();
            //var_dump($materias);
            if ($materias)
            {
                $lista = array();
                foreach ($materias as $materia)
                {
                    //$disciplina = $materia->get_disciplina();
                    $lista[$materia->id] = $materia->disciplina->nome;
                }
            }
            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        //var_dump($lista);
        return $lista;

    }//Fim Módulo
}//Fim Classe
