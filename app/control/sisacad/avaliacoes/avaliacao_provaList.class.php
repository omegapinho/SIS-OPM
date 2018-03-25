<?php
/**
 * avaliacao_provaList Listing
 * @author  <your name here>
 */
class avaliacao_provaList extends TPage
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
        $this->form = new TQuickForm('form_search_avaliacao_prova');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Gestor de Provas - Listagem');
        

        // create the form fields
        $curso_id           = new TCombo('curso_id');
        $turma_id           = new TCombo('turma_id');
        $materia_id         = new TCombo('materia_id');
        //$avaliacao_turma_id = new TCombo('avaliacao_turma_id');
        $dt_aplicacao       = new TDate('dt_aplicacao');
        $tipo_prova         = new TCombo('tipo_prova');

        //Mascaras
        $dt_aplicacao->setMask('dd/mm/yyyy');
        
        //Valores
        $tipo_prova->addItems($fer->lista_tipo_prova());
        $curso_id->addItems($this->getCursos());
        $turma_id->addItems($this->getTurmas());
        $materia_id->addItems($this->getMaterias());

        //Ações
        $change_action = new TAction(array($this, 'onChangeAction_curso'));    //troca as turmas
        $curso_id->setChangeAction($change_action);
        
        $change_action = new TAction(array($this, 'onChangeAction_turma'));//troca as disciplinas
        $turma_id->setChangeAction($change_action);
        

        // add the fields
        $this->form->addQuickField('Curso', $curso_id,  400 );
        $this->form->addQuickField('Turma', $turma_id,  400 );
        $this->form->addQuickField('Matéria', $materia_id,  400 );
        $this->form->addQuickField('Data de Aplicação', $dt_aplicacao,  120 );
        $this->form->addQuickField('Tipo de Prova', $tipo_prova,  300 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('avaliacao_prova_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        //$this->form->addQuickAction(_t('New'),  new TAction(array('avaliacao_provaForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'false';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_avaliacao_turma_id = new TDataGridColumn('avaliacao_turma_id', 'Avaliação/Turma', 'center');
        $column_dt_aplicacao       = new TDataGridColumn('dt_aplicacao', 'Data de Aplicação', 'right');
        $column_tipo_prova         = new TDataGridColumn('tipo_prova', 'Tipo de Prova', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_avaliacao_turma_id);
        $this->datagrid->addColumn($column_dt_aplicacao);
        $this->datagrid->addColumn($column_tipo_prova);


        // creates the datagrid column actions
        $order_avaliacao_turma_id = new TAction(array($this, 'onReload'));
        $order_avaliacao_turma_id->setParameter('order', 'avaliacao_turma_id');
        $column_avaliacao_turma_id->setAction($order_avaliacao_turma_id);
        
        $order_dt_aplicacao = new TAction(array($this, 'onReload'));
        $order_dt_aplicacao->setParameter('order', 'dt_aplicacao');
        $column_dt_aplicacao->setAction($order_dt_aplicacao);
        
        $order_tipo_prova = new TAction(array($this, 'onReload'));
        $order_tipo_prova->setParameter('order', 'tipo_prova');
        $column_tipo_prova->setAction($order_tipo_prova);
        

        // define the transformer method over image
        $column_dt_aplicacao->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });

        // create EDIT action
        $action_edit = new TDataGridAction(array($this, 'enviarEmail'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel('Email');
        $action_edit->setImage('fa: blue fa-lg');
        $action_edit->setField('id');
        //$this->datagrid->addAction($action_edit);
        
        // create DELETE action
        /*$action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(TRUE);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('fa:trash-o red fa-lg');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);*/
        
        // create DELETE action
        $action_prv = new TDataGridAction(array($this, 'onProva'));
        $action_prv->setUseButton(TRUE);
        $action_prv->setButtonClass('btn btn-default');
        $action_prv->setLabel('Lança Notas');
        $action_prv->setImage('fa:pencil gray fa-lg');
        $action_prv->setField('id');
        $this->datagrid->addAction($action_prv);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'avaliacao_provaList'));
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
            $object = new avaliacao_prova($key); // instantiates the Active Record
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
        TSession::setValue('avaliacao_provaList_filter_curso_id',   NULL);
        TSession::setValue('avaliacao_provaList_filter_turma_id',   NULL);
        TSession::setValue('avaliacao_provaList_filter_materia_id',   NULL);
        TSession::setValue('avaliacao_provaList_filter_dt_aplicacao',   NULL);
        TSession::setValue('avaliacao_provaList_filter_tipo_prova',   NULL);

        if (isset($data->curso_id) AND ($data->curso_id)) {
            $query1 = "(SELECT DISTINCT id FROM sisacad.avaliacao_curso WHERE curso_id = " . $data->curso_id . ")";
            $query2 = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE avaliacao_curso_id IN " . $query1 . ")";
            $query3 = "(SELECT DISTINCT id FROM sisacad.avaliacao_prova WHERE avaliacao_turma_id IN " . $query2 . ")";
            $filter = new TFilter('id', 'IN', $query3); // create the filter
            TSession::setValue('avaliacao_provaList_filter_curso_id',   $filter); // stores the filter in the session
        }

        if (isset($data->turma_id) AND ($data->turma_id)) {
            $query1 = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE turma_id = " . $data->turma_id . ")";
            $filter = new TFilter('avaliacao_turma_id', 'IN', $query1); // create the filter
            TSession::setValue('avaliacao_provaList_filter_turma_id',   $filter); // stores the filter in the session
        }

        if (isset($data->materia_id) AND ($data->materia_id)) {
            $query1 = "(SELECT DISTINCT id FROM sisacad.materia WHERE disciplina_id = " . $data->materia_id . ")";
            $query2 = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE materia_id IN " . $query1 . ")";
            $filter = new TFilter('avaliacao_turma_id', 'IN', $query2); // create the filter
            TSession::setValue('avaliacao_provaList_filter_materia_id',   $filter); // stores the filter in the session
        }

        if (isset($data->dt_aplicacao) AND ($data->dt_aplicacao)) {
            $filter = new TFilter('dt_aplicacao', '=', "$data->dt_aplicacao"); // create the filter
            TSession::setValue('avaliacao_provaList_filter_dt_aplicacao',   $filter); // stores the filter in the session
        }


        if (isset($data->tipo_prova) AND ($data->tipo_prova)) {
            $filter = new TFilter('tipo_prova', '=', "$data->tipo_prova"); // create the filter
            TSession::setValue('avaliacao_provaList_filter_tipo_prova',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('avaliacao_prova_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
        self::onChangeAction_curso((array) $data);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        $fer = new TFerramentas();
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            TTransaction::setLogger(new TLoggerTXT('tmp/listaprovaprofessor.txt')); 
            TTransaction::log("Provas do professor");
            // creates a repository for avaliacao_prova
            $repository = new TRepository('avaliacao_prova');
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
            

            if (TSession::getValue('avaliacao_provaList_filter_curso_id')) {
                $criteria->add(TSession::getValue('avaliacao_provaList_filter_curso_id')); // add the session filter
            }

            if (TSession::getValue('avaliacao_provaList_filter_turma_id')) {
                $criteria->add(TSession::getValue('avaliacao_provaList_filter_turma_id')); // add the session filter
            }
            
            if (TSession::getValue('avaliacao_provaList_filter_materia_id')) {
                $criteria->add(TSession::getValue('avaliacao_provaList_filter_materia_id')); // add the session filter
            }

            if (TSession::getValue('avaliacao_provaList_filter_dt_aplicacao')) {
                $criteria->add(TSession::getValue('avaliacao_provaList_filter_dt_aplicacao')); // add the session filter
            }


            if (TSession::getValue('avaliacao_provaList_filter_tipo_prova')) {
                $criteria->add(TSession::getValue('avaliacao_provaList_filter_tipo_prova')); // add the session filter
            }

            if (TSession::getValue('area') == 'PROFESSOR')
            {
                    $query1 = "(SELECT DISTINCT id FROM sisacad.professor WHERE cpf = '" . TSession::getValue('login') . 
                              "')";
                    $query2 = "(SELECT DISTINCT materia_id FROM sisacad.professormateria WHERE professor_id IN " . 
                              $query1 . ")";
                    $query3 = "(SELECT DISTINCT id FROM sisacad.avaliacao_turma WHERE materia_id IN " . $query2 . " )";
                    $criteria->add (new TFilter ('avaliacao_turma_id','IN',$query3));
                    $criteria->add (new TFilter ('oculto','!=','S'));
                    $criteria->add(new TFilter('status','IN',array('AP','PE')));
            }
            
            // load the objects according to criteria
            //var_dump($criteria->dump());
            $objects = $repository->load($criteria, FALSE);
            //var_dump($objects);
            
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
                    $object->tipo_prova         = $fer->lista_tipo_prova($object->tipo_prova);
                    $object->avaliacao_turma_id = $object->avaliacao_turma->materia->disciplina->nome . 
                                                  '(' . $object->avaliacao_turma->turma->nome . ')'; 
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
            $object = new avaliacao_prova($key, FALSE); // instantiates the Active Record
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
 *   Busca a lista de cursos
 *------------------------------------------------------------------------------*/
    public static function getCursos($param = 'ALL')
    {
        $lista = array(0=>'-- SEM CURSOS VINCULADOS --');
        $professor = (TSession::getValue('area') == 'PROFESSOR') ? true : false;
        if ($param)
        {
            $key = $param;
            if (empty($key))
            {
                $key = 'ALL';
            }
            try
            {
                TTransaction::open('sisacad');
                $repository = new TRepository('curso');
                $criteria   = new TCriteria();
                $fer   = new TFerramentas();                        // Ferramentas diversas
                $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
                //Verifica o perfil do usuário e define suas OPMs
                $profile = TSession::getValue('profile');           //Profile da Conta do usuário
                $opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
                $nivel_sistema = $fer->getnivel ('avaliacao_provaList');//Verifica qual nível de acesso do usuário
                $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
                if ($nivel_sistema<=80 && $professor == false)//Gestores e/Operadores
                {
                    $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . $listas['valores'] . "))";
                    $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $listas['valores'] . "))";
                    $sql    = "(SELECT curso_id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . ")";
                    $criteria->add (new TFilter ('id','IN',$sql));
                }
                else if ($professor == true)
                {
                    $query1 = "(SELECT DISTINCT id FROM sisacad.professor WHERE cpf = '" . TSession::getValue('login') . "')";
                    $query2 = "(SELECT DISTINCT materia_id FROM sisacad.professormateria WHERE professor_id IN " . $query1 . ")";
                    $query3 = "(SELECT DISTINCT turma_id FROM sisacad.materia WHERE id IN " . $query2 . ")";
                    $sql    = "(SELECT curso_id FROM sisacad.turma WHERE id IN " . $query3 . ")";
                    $criteria->add (new TFilter ('id','IN',$sql));
                }
                $criteria->add (new TFilter ('oculto','!=','S'));
                //var_dump($criteria->dump());
                
                $cursos = $repository->load($criteria, FALSE);

                //Monta a lista
                if (!empty($cursos))
                {
                    $lista = array();
                    foreach($cursos as $curso)
                    {
                        $lista [$curso->id] = $curso->nome;
                    }
                }
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }            
        }
        return $lista;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Busca a lista de turmas
 *------------------------------------------------------------------------------*/
    public static function getTurmas($param = 'ALL')
    {
        $lista = array(0=>'-- SEM TURMAS VINCULADAS --');
        $professor = (TSession::getValue('area') == 'PROFESSOR') ? true : false;
        if ($param)
        {
            $key = $param;
            if (empty($key))
            {
                $key = 'ALL';
            }
            try
            {
                TTransaction::open('sisacad');
                $repository = new TRepository('turma');
                $criteria   = new TCriteria();
                $fer   = new TFerramentas();                        // Ferramentas diversas
                $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
                //Verifica o perfil do usuário e define suas OPMs
                $profile = TSession::getValue('profile');           //Profile da Conta do usuário
                $opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
                $nivel_sistema = $fer->getnivel ('avaliacao_provaList');//Verifica qual nível de acesso do usuário
                $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
                if ($nivel_sistema<=80 && $professor == false)//Gestores e/Operadores
                {
                    $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . $listas['valores'] . "))";
                    $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $listas['valores'] . "))";
                    $sql    = "(SELECT DISTINCT id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . ")";
                    $criteria->add (new TFilter ('id','IN',$sql));
                }
                else if ($professor == true)
                {
                    $query1 = "(SELECT DISTINCT id FROM sisacad.professor WHERE cpf = '" . TSession::getValue('login') . "')";
                    $query2 = "(SELECT DISTINCT materia_id FROM sisacad.professormateria WHERE professor_id IN " . $query1 . ")";
                    $query3 = "(SELECT DISTINCT turma_id FROM sisacad.materia WHERE id IN " . $query2 . ")";
                    //$sql    = "(SELECT curso_id FROM sisacad.turma WHERE id IN " . $query3 . ")";
                    $criteria->add (new TFilter ('id','IN',$query3));
                }
                if ($key != 'ALL')
                {
                    $criteria->add (new TFilter ('curso_id','=',$key));
                }
                $criteria->add (new TFilter ('oculto','!=','S'));
                //var_dump($criteria->dump());
                
                $turmas = $repository->load($criteria, FALSE);

                //Monta a lista
                if (!empty($turmas))
                {
                    $lista = array();
                    foreach($turmas as $turma)
                    {
                        $lista [$turma->id] = $turma->nome . '(' . $turma->curso->sigla . ')';
                    }
                }
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }            
        }
        return $lista;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Busca a lista de turmas
 *------------------------------------------------------------------------------*/
    public static function getMaterias($param = 'ALL')
    {
        $lista = array(0=>'-- SEM MATERIAS VINCULADAS --');
        $professor = (TSession::getValue('area') == 'PROFESSOR') ? true : false;
        //var_dump($param);
        if ($param)
        {
            $key   = (is_array($param) && array_key_exists('turma_id',$param)) ? $param['turma_id'] : $param;
            $c_key = (is_array($param) && array_key_exists('curso_id',$param)) ? $param['curso_id'] : null;
            if (empty($key))
            {
                $key = (empty($c_key)) ? 'ALL' : 'CURSO';
            }
            try
            {
                TTransaction::open('sisacad');
                $repository    = new TRepository('disciplina');
                $criteria      = new TCriteria();
                $fer           = new TFerramentas();                        // Ferramentas diversas
                $sicad         = new TSicadDados();                         // Ferramentas de acesso ao SICAD
                //Verifica o perfil do usuário e define suas OPMs
                $profile       = TSession::getValue('profile');           //Profile da Conta do usuário
                $opm_operador  = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
                $nivel_sistema = $fer->getnivel ('avaliacao_provaList');//Verifica qual nível de acesso do usuário
                $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
                if ($nivel_sistema<=80 && $professor == false)//Gestores e/Operadores
                {
                    //echo "GESTOR";
                    $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . $listas['valores'] . "))";
                    $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $listas['valores'] . "))";
                    if ($key != 'ALL' && $key!='CURSO')
                    {
                        $query3 = "(SELECT DISTINCT id FROM sisacad.turma WHERE (id IN " . $query1 . " OR id IN ". $query2 . 
                                  ") AND id = " . $key . ")";
                    }
                    else if ($key == 'ALL')
                    {
                        $query3 = "(SELECT DISTINCT id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . ")";
                    }
                    else
                    {
                        $a_query = "(SELECT DISTINCT curso_id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . "AND oculto!='S')";
                        $b_query = (!empty($c_key)) ? " AND curso_id = " . $c_key : '';
                        $query3  = "(SELECT DISTINCT id FROM sisacad.turma WHERE curso_id IN " . $a_query . $b_query . ")";
                    }
                    $query4 = "(SELECT DISTINCT disciplina_id FROM sisacad.materia WHERE turma_id IN " . $query3 . ")";
                    $criteria->add (new TFilter ('id','IN',$query4));
                }
                else if ($professor == true)//Professores
                {
                    //echo "PROF";
                    $query1 = "(SELECT DISTINCT id FROM sisacad.professor WHERE cpf = '" . TSession::getValue('login') . "')";
                    $query2 = "(SELECT DISTINCT materia_id FROM sisacad.professormateria WHERE professor_id IN " . $query1 . ")";
                    if ($key != 'ALL' && $key!='CURSO')
                    {
                        $query3 = "(SELECT DISTINCT disciplina_id FROM sisacad.materia WHERE id IN " . $query2 . 
                                  " AND turma_id = " . $key . ")";
                    }
                    else if ($key == 'ALL')
                    {
                        $query3 = "(SELECT DISTINCT disciplina_id FROM sisacad.materia WHERE id IN " . $query2 . ")";
                    }
                    else
                    {
                        $query3 = "(SELECT DISTINCT disciplina_id FROM sisacad.materia WHERE id IN " . $query2 . ")";
                    }
                    //$sql    = "(SELECT curso_id FROM sisacad.turma WHERE id IN " . $query3 . ")";
                    $criteria->add (new TFilter ('id','IN',$query3));
                }
                else//Administrador
                {
                    //echo "ADM";
                    $query1 = "(SELECT DISTINCT id FROM sisacad.curso WHERE oculto != 'S')";
                    
                    if ($key != 'ALL' && $key!='CURSO')
                    {
                        $query2 = "(SELECT DISTINCT id FROM sisacad.turma WHERE curso_id IN " . $query1 . " AND turma_id = " . $key . ")";
                    }
                    else if ($key == 'ALL')
                    {
                        $query2 = "(SELECT DISTINCT id FROM sisacad.turma WHERE curso_id IN " . $query1 . ")";
                    }
                    else
                    {
                        $query1 = "(SELECT DISTINCT id FROM sisacad.curso WHERE oculto != 'S' AND id = " . $c_key .")";
                        $query2 = "(SELECT DISTINCT id FROM sisacad.turma WHERE curso_id IN " . $query1 . ")";
                    }
                    $sql = "(SELECT DISTINCT disciplina_id FROM sisacad.materia WHERE turma_id IN " . $query2 .")";
                    $criteria->add (new TFilter ('id','IN',$sql));
                }
                //var_dump($criteria->dump());
                
                $materias = $repository->load($criteria, FALSE);

                //Monta a lista
                if (!empty($materias))
                {
                    $lista = array();
                    foreach($materias as $materia)
                    {
                        $lista [$materia->id] = $materia->nome ;
                    }
                }
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }            
        }
        return $lista;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Trocas as turmas
 *------------------------------------------------------------------------------*/
    public static function onChangeAction_curso($param = 'ALL')
    {
        $lista = array(0=>'-- SEM TURMAS VINCULADAS --');
        if ($param)
        {
            $key = (is_array($param)) ? $param['curso_id'] : $param;
            if (empty($key))
            {
                $key = 'ALL';
            }
        }
        $lista = self::getTurmas($key);
        TCombo::reload('form_search_avaliacao_prova','turma_id', $lista,true);
        if ($key == 'ALL')
        {
            $param['turma_id'] = null;
            $param['curso_id'] = null;
        }
        else
        {
            $param['curso_id'] = (empty($param['curso_id'])) ? null : $param['curso_id'];
            $param['turma_id'] = null;
        }
        self::onChangeAction_turma($param);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Trocas as Materias
 *------------------------------------------------------------------------------*/
    public static function onChangeAction_turma($param = 'ALL')
    {
        $lista = array(0=>'-- SEM MATÉRIAS VINCULADAS --');
        //var_dump($param);
        if ($param)
        {
            $key   = (is_array($param) && array_key_exists('turma_id',$param)) ? $param['turma_id'] : $param;
            $c_key = (is_array($param) && array_key_exists('curso_id',$param)) ? $param['curso_id'] : $param;
            if (empty($key))
            {
                $key = (empty($c_key)) ? 'ALL' : 'CURSO';
            }
        }
        $lista = self::getMaterias(array('turma_id'=>$key,'curso_id'=>$c_key));
        //var_dump($lista);
        TCombo::reload('form_search_avaliacao_prova','materia_id', $lista,true);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Abre prova
 *------------------------------------------------------------------------------*/
    public function onProva($param = null)
    {
        $key   = (is_array($param) && array_key_exists('key',$param)) ? $param['key'] : $param;
        $data = $this->form->getData();
        try
        {
            TTransaction::open('sisacad');
            $prova  = new avaliacao_prova($key);
            $alunos = $prova->getavaliacao_alunos();

            if (empty($alunos)) //Carrega a turma com os matriculados até a data
            {
                $turma_id = $prova->avaliacao_turma->turma->id;
                $alunos = aluno::where('turma_id','=',$turma_id)->load();
                foreach ($alunos as $aluno)
                {
                    $avaliado                     = new avaliacao_aluno();
                    $avaliado->avaliacao_prova_id = $key;
                    $avaliado->aluno_id           = $aluno->id;
                    $avaliado->status             = 'P';
                    $avaliado->nota               = 0;
                    $avaliado->usuario_lancador   = TSession::getValue('login');
                    $avaliado->data_lancamento    = date('Y-m-d');
                    $avaliado->store();
                }
            }
            TTransaction::close();
            //TSession::setValue('avaliacao_prova',$data);
            TApplication::loadPage('avaliacao_provaForm','onEdit',array('key'=>$key));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }
        $this->form->setData($data);
        $this->onReload($param); 
    }//Fim Módulo
    
/*------------------------------------------------------------------------------
 *   Testa Enviar Email
 *------------------------------------------------------------------------------*/
    public function enviarEmail($param)
    {
        $email = 'o.megapinho@gmail.com';
        $protocolo = "param['protocolo']";//Número do protocolo da solicitação
        
        try {
            $mail_template = 'Teste de Email PROTOCOLO';//file_get_contents('app/resources/confirmacao_email.html');
            $mail = new TMail;
            
            $mail->setFrom('sicadpm@gmail.com', 'SDAS-DTIC');
            $mail->setSubject('ASSUNTO DO E-MAIL');
            $mail_template = str_replace('{PROTOCOLO}', $protocolo, $mail_template);
            $mail->setHtmlBody($mail_template);
            $mail->addAddress($email, 'NOME DO CONTATO');
            $mail->SetUseSmtp();
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPAuth = true;
            $mail->SetSmtpHost('smtp.gmail.com', '465');
            $mail->SetSmtpUser('sicadpm@gmail.com', 'SICADPMdtic');
            $mail->send();
        } catch(Exception $e) {
            new TMessage('error', 'Não foi possível enviar seu e-mail.');
        }
    }//Fim Módulo 
}//Fim Classe
