<?php
/**
 * professorMateriaList Listing
 * @author  <your name here>
 */
class professorMateriaList extends TPage
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
    var $servico  = 'Professor';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    protected $chamado = false;          //Controle de correção de chamado
   
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
        // creates the form
        $this->form = new TQuickForm('form_search_materia');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Designa Professores para Disciplina da Turma - Listagem');
        
        
        $turma = TSession::getValue('turma_militar');

        // create the form fields
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('oculto','!=','S'));
        
        $turma_id = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        $curso_id = new THidden('curso_id');
        if ($this->nivel_sistema <= 80)
        {
            $sql = '(SELECT disciplina_id FROM sisacad.materias_previstas WHERE curso_id = ' . $turma->curso_id . ')';
            $filter = new TFilter ('id','IN',$sql);
            $criteria->add($filter);
        }
        $disciplina_id = new TDBCombo('disciplina_id','sisacad','disciplina','id','nome','nome',$criteria);
        $carga_horaria = new TEntry('carga_horaria');


        // add the fields
        $this->form->addQuickField('Turma', $turma_id,  400 );
        $this->form->addQuickField('Disciplina', $disciplina_id,  400 );
        $this->form->addQuickField('Carga Horária', $carga_horaria,  120 );
        $this->form->addQuickField('', $curso_id,  120 );

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('materia_filter_data') );

        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        if (!$this->nivel_sistema >80) //Controle de acesso
        {
            $this->form->addQuickAction(_t('New'),  new TAction(array('professorMateriaForm', 'onEdit')), 'bs:plus-sign green');
        }

        //Valores
        if (!empty($turma))
        {
            $turma_id->setValue((int) $turma->id);
            $turma_id->setEditable(false);
            $this->form->addQuickAction('Retorna a Turma',  new TAction(array($this, 'onReturn')), 'ico_back.png');
        }
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_turma_id = new TDataGridColumn('turma_id', 'Turma', 'center');
        $column_disciplina_id = new TDataGridColumn('disciplina_id', 'Disciplina', 'center');
        $column_carga_horaria = new TDataGridColumn('carga_horaria', 'Carga Horária', 'right');
        $column_docentes = new TDataGridColumn('docentes', 'Docentes', 'right');

        $column_docentes->setTransformer( 
            function ($value,$object,$row)
            {
                $div = new TElement('span');
                $div->class = 'label label-' . ($value == '- OK -' ? 'success' : 'danger');
                $div->add($value);
                return $div;
            } 
        );

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_turma_id);
        $this->datagrid->addColumn($column_disciplina_id);
        $this->datagrid->addColumn($column_carga_horaria);
        $this->datagrid->addColumn($column_docentes);


        // creates the datagrid column actions
        $order_turma_id = new TAction(array($this, 'onReload'));
        $order_turma_id->setParameter('order', 'turma_id');
        $column_turma_id->setAction($order_turma_id);
        
        $order_disciplina_id = new TAction(array($this, 'onReload'));
        $order_disciplina_id->setParameter('order', 'disciplina_id');
        $column_disciplina_id->setAction($order_disciplina_id);
        
        $order_carga_horaria = new TAction(array($this, 'onReload'));
        $order_carga_horaria->setParameter('order', 'carga_horaria');
        $column_carga_horaria->setAction($order_carga_horaria);
        
        $order_docentes = new TAction(array($this, 'onReload'));
        $order_docentes->setParameter('order', 'carga_horaria');
        $column_docentes->setAction($order_docentes);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('professorMateriaForm', 'onEdit'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(false);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('fa:trash-o red fa-lg');
        $action_del->setField('id');
        if (!$this->nivel_sistema >80) //Controle de acesso
        {
            $this->datagrid->addAction($action_del);
        }
        
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
        if (!$this->nivel_sistema >80) //Controle de acesso
        {
            $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        }
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
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
            $object = new materia($key); // instantiates the Active Record
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
        $turma = TSession::getValue('turma_militar');
        
        // clear session filters
        TSession::setValue('professorMateriaList_filter_turma_id',   NULL);
        TSession::setValue('professorMateriaList_filter_disciplina_id',   NULL);
        TSession::setValue('professorMateriaList_filter_carga_horaria',   NULL);
        TSession::setValue('professorMateriaList_filter_docentes',   NULL);
        TSession::setValue('professorMateriaList_filter_curso_id',   NULL);

        if (isset($data->turma_id) AND ($data->turma_id)) {
            $filter = new TFilter('turma_id', '=', "$data->turma_id"); // create the filter
            TSession::setValue('professorMateriaList_filter_turma_id',   $filter); // stores the filter in the session
        }


        if (isset($data->disciplina_id) AND ($data->disciplina_id)) {
            $filter = new TFilter('disciplina_id', '=', "$data->disciplina_id"); // create the filter
            TSession::setValue('professorMateriaList_filter_disciplina_id',   $filter); // stores the filter in the session
        }


        if (isset($data->carga_horaria) AND ($data->carga_horaria)) {
            $filter = new TFilter('carga_horaria', '>=', "$data->carga_horaria"); // create the filter
            TSession::setValue('professorMateriaList_filter_carga_horaria',   $filter); // stores the filter in the session
        }


        if (isset($data->docentes) AND ($data->docentes)) {
            $filter = new TFilter('docentes', '>=', "$data->docentes"); // create the filter
            TSession::setValue('professorMateriaList_filter_docentes',   $filter); // stores the filter in the session
        }
        
     
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('materia_filter_data', $data);
        
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

            $turma = TSession::getValue('turma_militar');
            
            if (!empty($turma)) 
            {
                $filter = new TFilter('turma_id', '=', $turma->id); // create the filter
                TSession::setValue('professorMateriaList_filter_turma_id',   $filter); // stores the filter in the session
                if ($this->nivel_sistema <= 80)
                {
                    $sql = '(SELECT disciplina_id FROM sisacad.materias_previstas WHERE curso_id = ' . $turma->curso_id . ')';
                    $filter = new TFilter ('disciplina_id','IN',$sql);
                }
                else
                {
                    $filter = null;
                }
                TSession::setValue('professorMateriaList_filter_curso_id',   $filter); // stores the filter in the session
            }
               
            // creates a repository for materia
            $repository = new TRepository('materia');
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
            

            if (TSession::getValue('professorMateriaList_filter_turma_id')) {
                $criteria->add(TSession::getValue('professorMateriaList_filter_turma_id')); // add the session filter
            }


            if (TSession::getValue('professorMateriaList_filter_disciplina_id')) {
                $criteria->add(TSession::getValue('professorMateriaList_filter_disciplina_id')); // add the session filter
            }


            if (TSession::getValue('professorMateriaList_filter_carga_horaria')) {
                $criteria->add(TSession::getValue('professorMateriaList_filter_carga_horaria')); // add the session filter
            }
            
            if (TSession::getValue('professorMateriaList_filter_docentes')) {
                $criteria->add(TSession::getValue('professorMateriaList_filter_docentes')); // add the session filter
            }
            
            if (TSession::getValue('professorMateriaList_filter_curso_id')) {
                $criteria->add(TSession::getValue('professorMateriaList_filter_curso_id')); // add the session filter
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
                    $docentes = professormateria::where('materia_id','=',$object->id)->load();
                    //$docentes = $disciplina->getprofessors();
                    //var_dump($docentes);echo '<br>';
                    
                    $object->turma_id = $object->get_turma()->nome;
                    $object->disciplina_id = $object->get_disciplina()->nome;

                    $object->docentes = (count($docentes) == 0) ? '- NC -' : '- OK -';
                    $row = $this->datagrid->addItem($object);
                    $tip='';
                    if (count($docentes)>0)
                    {
                        
                        $row->popover = 'true';
                        $row->popside = 'top';
                        $row->poptitle = 'Lista de Professores';
                        foreach ($docentes as $docente)
                        {
                            $mestre = new professor($docente->professor_id);
                            $posto = $mestre->get_postograd();
                            $grad = ($posto) ? $posto->nome : '';
                            //var_dump($posto);
                            
                            $tip .= '<tr><td>'.$grad . ' ' . $mestre->nome.'</td></tr>';
                        }
                        $row->popcontent = "<table class='popover-table'>" . $tip ."</table>";
                    }
                    else
                    {
                        $row->popover = 'true';
                        $row->popside = 'top';
                        $row->poptitle = 'Lista de Professores';
                        $tip .= '<tr><td>Nenhum Professor Vinculado ainda.</td></tr>';
                        $row->popcontent = "<table class='popover-table'>" . $tip ."</table>";
                    }
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
            $object = new materia($key, FALSE); // instantiates the Active Record
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
                    $object = new materia;
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
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         $data = $this->form->getData();
         TApplication::loadPage('turmaForm','onEdit', array('key'=>$data->turma_id));
         //$this->form->setData($data);
    }//Fim Módulo
}
