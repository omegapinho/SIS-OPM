<?php
/**
 * defineDisciplinaProfessorList Listing
 * @author  <your name here>
 */
class defineDisciplinaProfessorList extends TPage
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
    var $servico  = 'Disciplinas';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
   
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
        $this->form = new TQuickForm('form_search_disciplina');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Define disciplinas de Interesse para o corpo de Professores - Listagem');
        
        $fer = new TFerramentas();

        // create the form fields
        $nome          = new TEntry('nome');
        $sigla         = new TEntry('sigla');
        $oculto        = new TCombo('oculto');
        $interessados  = new TCombo('interessados');

        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $interessados->addItems($fer->lista_sim_nao());
        //
        $oculto->setValue('N');

        // add the fields
        $this->form->addQuickField('Disciplina', $nome,  400 );
        $this->form->addQuickField('Sigla', $sigla,  200 );
        $this->form->addQuickField('Fora de Uso?', $oculto,  120 );
        $this->form->addQuickField('Lista só onde há interessados?', $interessados,  120 );
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('disciplina_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        //Se não é Gestor acima desativa
        echo $this->nivel_sistema;
        if ($this->nivel_sistema>80)
        {
            $this->form->addQuickAction(_t('New'),  new TAction(array('defineDisciplinaProfessorForm', 'onEdit')), 'bs:plus-sign green');
        }
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_nome     = new TDataGridColumn('nome', 'Disciplina', 'left');
        $column_sigla    = new TDataGridColumn('sigla', 'Sigla', 'center');
        $column_oculto   = new TDataGridColumn('oculto', 'Fora de Uso?', 'center');
        $column_docentes = new TDataGridColumn('docentes', 'Designados', 'center',80);

        $column_docentes->setTransformer( 
            function ($value,$object,$row)
            {
                $div = new TElement('span');
                $div->class = 'label label-' . (($value == '- OK -') ? 'success' : 'danger');
                $div->add($value);
                return $div;
            } 
        );


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_sigla);
        $this->datagrid->addColumn($column_oculto);
        $this->datagrid->addColumn($column_docentes);


        // creates the datagrid column actions
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_sigla = new TAction(array($this, 'onReload'));
        $order_sigla->setParameter('order', 'sigla');
        $column_sigla->setAction($order_sigla);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);

        // create EDIT action
        $action_edit = new TDataGridAction(array('defineDisciplinaProfessorForm', 'onEdit'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
        //Se não é Gestor acima desativa
        if ($this->nivel_sistema>80)
        {
            // create DELETE action
            $action_del = new TDataGridAction(array($this, 'onDelete'));
            $action_del->setUseButton(false);
            $action_del->setButtonClass('btn btn-default');
            $action_del->setLabel(_t('Delete'));
            $action_del->setImage('fa:trash-o red fa-lg');
            $action_del->setField('id');
            $this->datagrid->addAction($action_del);
        }
        
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
            
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new disciplina($key); // instantiates the Active Record
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
        $data->nome  = mb_strtoupper($data->nome,'UTF-8');
        $data->sigla = mb_strtoupper($data->sigla,'UTF-8');
        
        // clear session filters
        TSession::setValue('defineDisciplinaProfessorList_filter_nome',   NULL);
        TSession::setValue('defineDisciplinaProfessorList_filter_sigla',   NULL);
        TSession::setValue('defineDisciplinaProfessorList_filter_oculto',   NULL);
        TSession::setValue('defineDisciplinaProfessorList_filter_interessados',   NULL);

        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('defineDisciplinaProfessorList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->sigla) AND ($data->sigla)) {
            $filter = new TFilter('sigla', 'like', "%{$data->sigla}%"); // create the filter
            TSession::setValue('defineDisciplinaProfessorList_filter_sigla',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('defineDisciplinaProfessorList_filter_oculto',   $filter); // stores the filter in the session
        }

        if (isset($data->interessados) AND ($data->interessados)) 
        {
            $sql = "(SELECT disciplina_id FROM sisacad.professordisciplina)";
            if ($data->interessados == 'N')
            {
                $filter = new TFilter('id', 'NOT IN', $sql); // create the filter   
            }
            else if ($data->interessados == 'S')
            {
                $filter = new TFilter('id', 'IN', $sql); // create the filter
            }
            TSession::setValue('defineDisciplinaProfessorList_filter_interessados',   $filter); // stores the filter in the session
            
        }
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('disciplina_filter_data', $data);
        
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
            
            // creates a repository for disciplina
            $repository = new TRepository('disciplina');
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
            

            if (TSession::getValue('defineDisciplinaProfessorList_filter_nome')) {
                $criteria->add(TSession::getValue('defineDisciplinaProfessorList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('defineDisciplinaProfessorList_filter_sigla')) {
                $criteria->add(TSession::getValue('defineDisciplinaProfessorList_filter_sigla')); // add the session filter
            }


            if (TSession::getValue('defineDisciplinaProfessorList_filter_oculto')) {
                $criteria->add(TSession::getValue('defineDisciplinaProfessorList_filter_oculto')); // add the session filter
            }
            
            if (TSession::getValue('defineDisciplinaProfessorList_filter_interessados')) {
                $criteria->add(TSession::getValue('defineDisciplinaProfessorList_filter_interessados')); // add the session filter
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
                $fer = new TFerramentas();
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $docentes = professordisciplina::where('disciplina_id','=',$object->id)->load();
                    $object->oculto = $fer->lista_sim_nao($object->oculto);
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
                        $tip .= '<tr><td>Área de interesse sem professor</td></tr>';
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
            $object = new disciplina($key, FALSE); // instantiates the Active Record
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
