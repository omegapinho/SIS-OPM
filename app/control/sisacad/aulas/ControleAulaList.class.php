<?php
/**
 * ControleAulaList Listing
 * @author  <your name here>
 */
class ControleAulaList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $expressoButton;
    //private $deleteButton;

/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Controle de Aulas';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    public static  $cfg_CH_limite = true;
   
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
        TSession::setValue('aula_disciplina',null);//Limpa variável de seção
        
        // creates the form
        $this->form = new TQuickForm('form_search_materia');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem de Disciplina/Turmas para Controle de Aula ');
        
        // create the form fields
        //Cursos
        $criteria = new TCriteria();
        if ($this->nivel_sistema<=80)//Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT curso_id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            $query3 = "(SELECT curso_id FROM sisacad.turma WHERE id IN " . $query2 .")";
            $sql    = "(SELECT id FROM sisacad.curso WHERE id IN " . $query1 . "OR id IN " . $query3 . ")";
            $criteria->add (new TFilter ('id','IN',$sql));
        }
        $criteria->add (new TFilter ('oculto','!=','S'));
        $curso_id = new TDBCombo('curso_id','sisacad','curso','id','nome','nome',$criteria);
        
        //Turmas
        $criteria = new TCriteria();        
        if ($this->nivel_sistema<=80)//Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . $this->listas['valores'] . "))";
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $this->listas['valores'] . "))";
            $sql    = "(SELECT id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . ")";
            $criteria->add (new TFilter ('id','IN',$sql));
        }
        $criteria->add (new TFilter ('oculto','!=','S'));
        //$turma_id = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        $turma_id = new TEntry('turma_id');//,'sisacad','turma','id','nome','nome',$criteria);
        
        //Disciplinas
        $criteria = new TCriteria();
        if ($this->nivel_sistema<=80)//Lista as disciplinas somente da unidade dos Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            
            $query3 = "(SELECT DISTINCT curso_id FROM sisacad.turma WHERE id IN " . $query1 . 
                        " OR id IN " . $query2 . " )";
            $query4 = "(SELECT DISTINCT disciplina_id FROM sisacad.materias_previstas WHERE curso_id IN " . $query3 . ")";
            $criteria->add (new TFilter ('id','IN',$query4));
        }
        $criteria->add (new TFilter ('oculto','!=','S'));
        $disciplina_id = new TDBCombo('disciplina_id','sisacad','disciplina','id','nome','nome',$criteria);

        $carga_horaria = new TEntry('carga_horaria');
        $carga_minima  = new TEntry('carga_minima');
        $interessados  = new TCombo('interessados'); 
        
        //Mascaras
        $carga_horaria->setMask('999');
        $carga_minima->setMask('999');
        
        //Valores
        if (!empty($turma))
        {
            $turma_id->setValue((int) $turma->id);
            $turma_id->setEditable(false);
            $this->form->addQuickAction('Retorna a Turma',  new TAction(array($this, 'onReturn')), 'ico_back.png');
        }
        $interessados->addItems($fer->lista_sim_nao());
        
        //Ações
        $change_action = new TAction(array($this, 'onChangeAction_curso'));    //troca as turmas
        $curso_id->setChangeAction($change_action);
        
        //$change_action = new TAction(array($this, 'onChangeAction_turma'));//troca as disciplinas
        //$turma_id->setChangeAction($change_action);

        // add the fields
        $this->form->addQuickField('Curso', $curso_id,  400 );
        $this->form->addQuickField('Turma', $turma_id,  400 );
        $this->form->addQuickField('Disciplina', $disciplina_id,  400 );
        $this->form->addQuickField('C.H. Máxima (maior ou igual)', $carga_horaria,  120 );
        $this->form->addQuickField('C.H. Usada (maior ou igual)', $carga_minima,  120 );
        $this->form->addQuickField('Lista somente onde há docentes?', $interessados,  120 );

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('materia_filter_data') );

        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction('Registro de Aula', new TAction(array($this, 'onControle')), 'bs:check green');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%;';
        $this->datagrid->scrollable = true;
        $this->datagrid->setHeight = 175;

        // creates the datagrid columns
        $column_check         = new TDataGridColumn('check', '', 'center');
        $column_turma_id      = new TDataGridColumn('turma_id', 'Turma', 'center');
        $column_disciplina_id = new TDataGridColumn('disciplina_id', 'Disciplina', 'center');
        $column_carga_horaria = new TDataGridColumn('carga_horaria', 'C.H. Max', 'right');
        $column_carga_total   = new TDataGridColumn('carga_total', 'C.H. Usada', 'right');
        $column_carga_percent = new TDataGridColumn('carga_percent', '% de Uso', 'right');
        $column_docentes      = new TDataGridColumn('docentes', 'Docentes Designados', 'right',80);

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
        //$this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_turma_id);
        $this->datagrid->addColumn($column_disciplina_id);
        $this->datagrid->addColumn($column_carga_horaria);
        $this->datagrid->addColumn($column_carga_total);
        $this->datagrid->addColumn($column_carga_percent);
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
        $action_edit = new TDataGridAction(array($this, 'onControleAulaDisciplinaList'));
        $action_edit->setUseButton(false);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel('Edita aulas Lançadas');
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
        //$this->datagrid->addAction($action_del);
        
        // create Lança Aula
        $action_aul = new TDataGridAction(array($this, 'onAula'));
        $action_aul->setUseButton(false);
        $action_aul->setButtonClass('btn btn-default');
        $action_aul->setLabel('Lança Aula');
        $action_aul->setImage('fa:tags blue fa-lg');
        $action_aul->setField('id');
        $this->datagrid->addAction($action_aul);
        
        // create Lançamento expresso de Aulas
        $action_exp = new TDataGridAction(array($this, 'onAulasExpresso'));
        $action_exp->setUseButton(false);
        $action_exp->setButtonClass('btn btn-default');
        $action_exp->setLabel('Lançamento Expresso de aulas');
        $action_exp->setImage('fa:bolt red fa-lg');
        $action_exp->setField('id');
        if ($this->nivel_sistema>10)
        {
            $this->datagrid->addAction($action_exp);
        }
        // create Falta Aula
        $action_fal = new TDataGridAction(array($this, 'onFalta'));
        $action_fal->setUseButton(false);
        $action_fal->setButtonClass('btn btn-default');
        $action_fal->setLabel('Registra ausência de Aula');
        $action_fal->setImage('fa:user-times red fa-lg');
        $action_fal->setField('id');
        $this->datagrid->addAction($action_fal);
        
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
        //$this->deleteButton = new TButton('delete_collection');
        //$this->deleteButton->setAction(new TAction(array($this, 'onDeleteCollection')), AdiantiCoreTranslator::translate('Delete selected'));
        //$this->deleteButton->setImage('fa:remove red');
        //$this->formgrid->addField($this->deleteButton);
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        //$gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        //$this->transformCallback = array($this, 'onBeforeLoad');


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
    public function onSearch($param)
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('professorMateriaList_filter_turma_id',   NULL);
        TSession::setValue('professorMateriaList_filter_disciplina_id',   NULL);
        TSession::setValue('professorMateriaList_filter_carga_horaria',   NULL);
        TSession::setValue('professorMateriaList_filter_docentes',   NULL);
        TSession::setValue('professorMateriaList_filter_interessados',   NULL);
        TSession::setValue('professorMateriaList_filter_curso_id',   NULL);

        if (isset($data->turma_id) AND ($data->turma_id)) {
            $sql = "(SELECT id FROM sisacad.turma WHERE nome LIKE '%{$data->turma_id}%')";
            
            $filter = new TFilter('turma_id', 'IN', $sql); // create the filter
            //$filter = new TFilter('turma_id', '=', "$data->turma_id"); // create the filter            
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

        if (isset($data->curso_id) AND ($data->curso_id)) 
        {
            $sql = "(SELECT id FROM sisacad.turma WHERE curso_id = " . $data->curso_id .")";
            $filter = new TFilter('turma_id', 'IN', $sql); // create the filter
            TSession::setValue('professorMateriaList_filter_curso_id',   $filter); // stores the filter in the session
        }        

        if (isset($data->interessados) AND ($data->interessados)) 
        {
            $sql = "(SELECT materia_id FROM sisacad.professormateria)";
            if ($data->interessados == 'N')
            {
                $filter = new TFilter('id', 'NOT IN', $sql); // create the filter   
            }
            else if ($data->interessados == 'S')
            {
                $filter = new TFilter('id', 'IN', $sql); // create the filter
            }
            TSession::setValue('professorMateriaList_filter_interessados',   $filter); // stores the filter in the session
            
        }
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('materia_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
        
        $this->onChangeAction_curso((array) $data);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        $data = $this->form->getData();
        
        if (isset($param['chamado']))
        {
            if (!empty($param['chamado']))
            {
                $chamado = new TMantis();
                $chamado->fechaChamado(array('key'=>$param['chamado']));
            }
        }
        //Retorna para a mesma págian de onde estava...
        if (is_array($param) && (isset($param['order']) && $param['class'] != 'ControleAulaList') || 
                (!isset($param['order'])))
        {
            $dado = TSession::getValue('aula_disciplina_paginacao');

            if (is_array($dado) && $dado['class'] == 'ControleAulaList')
            {
                $param = $dado;
                TSession::setValue('aula_disciplina_paginacao',null);
            }
        }
        
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');

            if (isset($param['chamado']))
            {
                if (!empty($param['chamado']))
                {
                    $chamado = new TMantis();
                    $chamado->fechaChamado(array('key'=>$param['chamado']));
                }
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
                $param['direction'] = 'desc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);

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
                $query = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . 
                            $this->listas['valores'].") OR id IN (SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores'].")))";
                $criteria->add (new TFilter ('turma_id','IN',$query));
            }

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

            if (TSession::getValue('professorMateriaList_filter_interessados')) {
                $criteria->add(TSession::getValue('professorMateriaList_filter_interessados')); // add the session filter
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
                $acad = new TSisacad();
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $ch = $acad->getCargaHoraria($object->id);
                    if ((isset($data->carga_minima) && !empty($data->carga_minima) && $ch >= $data->carga_minima) ||
                       (isset($data->carga_minima) && empty($data->carga_minima)))
                    {
                        $docentes = professormateria::where('materia_id','=',$object->id)->load();
                        $object->carga_total = $ch;
                        $percent = number_format(($ch * 100) / $object->carga_horaria,2,",",".");
                        $object->carga_percent = (string) $percent .'%' ;
                        $object->turma_id = $object->get_turma()->sigla;
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
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            TSession::setValue('aula_disciplina_dados',$param);

            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage() . '<br>'. $criteria->dump());
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
    public function on_BeforeLoad($objects, $param)
    {
        // update the action parameters to pass the current page to action
        // without this, the action will only work for the first page
        //$deleteAction = $this->deleteButton->getAction();
        //$deleteAction->setParameters($param); // important!
        
        //$gridfields = array( $this->deleteButton );
        
        //foreach ($objects as $object)
        //{
            //$object->check = new TCheckButton('check' . $object->id);
            //$object->check->setIndexValue('on');
            //$gridfields[] = $object->check; // important
        //}
        
        //$this->formgrid->setFields($gridfields);
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
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function getAulasDadas ($param)
    {
        $lista = array('-- Nenhuma aula lançada --');
        try
        {
            TTransaction::open('sisacad');
            $aulas = controle_aula::where('materia_id','=',$param['id'])->orderBy('dt_inicio','desc')->load();
            if (!empty($aulas))
            {
                $lista = array();
                foreach($aulas as $aula)
                {
                    $data = (!empty($aula->dt_inicio)) ? TDate::date2br($aula->dt_inicio) : '';
                    $ch   = (!empty($aula->horas_aula)) ? $aula->horas_aula . ' hs'  : '';
                    $lista[] = $data . ' - ' . $ch;
                }
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Lança Aula
 *---------------------------------------------------------------------------------------*/
    public function onAula ($param = null)
    {
        $fer = new TFerramentas();
        
        $id     = new THidden('id');
        $nome   = new TEntry('nome');
        $data   = new TDate('data');
        $aula   = new TEntry('aula');
        $dadas  = new TSelect('dadas');
        $falta  = new TRadioGroup('falta');
        
        //                    $docentes = professormateria::where('materia_id','=',$object->id)->load();
        $professores = new TCheckGroup ('professores');
        $conteudo    = new TText('conteudo');
        //var_dump($param);
        $id->setValue($param['key']);
        $nome->setValue($this->getIdentificacao($param['key']));
        $items_prof = $this->getProfessores ($param['key']);
        if ($items_prof == false)
        {
            new TMessage ('error','Disciplina sem Docentes. Verifique!');
            return;
        }
        $professores->addItems($items_prof);
        $dadas->addItems($this->getAulasDadas(array('id'=>$param['key'])));
        $falta->addItems($fer->lista_sim_nao());
        $falta->setValue('N');
        
        //Tamanho
        $id->setSize(50);
        $nome->setSize(450);
        $data->setSize(120);
        $aula->setSize(80);
        $conteudo->setSize(250, 40);
        $dadas->setSize(200,80);
        
        //Mascaras
        $data->setMask('dd-mm-yyyy');
        $aula->setMask('99');
        
        //Trava
        $id->setEditable(false);
        $nome->setEditable(false);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $falta->setTip('Caso tenha alguma ausência, marque sim.<br>Após gravar irá abrir a tela onde poderá lançar os dados dos ausêntes.');
        
        $table = new TTable;
        $table->addRowSet( '', $id );
        $table->addRowSet( new TLabel('Identificação: '), $nome );
        $table->addRowSet( new TLabel('Aulas dadas: '), $dadas );
        $table->addRowSet( $lbl = new TLabel('Dia: '), $data );
        $lbl->setFontColor('red');
        $table->addRowSet( $lbl = new TLabel('Aulas: '), $aula );  
        $lbl->setFontColor('red');      
        $table->addRowSet( $lbl = new TLabel('Docentes: '), $professores );  
        $lbl->setFontColor('red');
        $table->addRowSet( new TLabel('Conteúdo: '), $conteudo );
        $table->addRowSet( $lbl = new TLabel('Teve Faltas?: '), $falta );
        $lbl->setFontColor('red');
        
        
        $form->setFields(array($nome, $aula, $id, $data,$dadas,$falta));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'onConfirm'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Lançamento de Aulas', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Rotina: Cadastra lançamento
 *------------------------------------------------------------------------------*/
    public static function onConfirm( $param )
    {
        //var_dump($param);
        $cfg = TSession::getValue('SISACAD_CONFIG');
        if ((isset($param['data']) && $param['data']) &&
            (isset($param['aula']) && $param['aula']) &&
            (isset($param['professores']) && $param['professores']) &&
            (isset($param['id']) && $param['id'])) // validate required field
        {
            /*$acao = new TAction(array('ControleAulaList', 'Confirm'));
            $acao->setParameters($param);
            new TMessage('info', "Confirma o Lançamento de: " . $param['aula'] . 'aula(s)?' , $acao);*/
            $acad = new TSisacad();
            $ch_usada = $acad->getCargaHoraria($param['id']);
            
            try
            {
                TTransaction::open('sisacad');
                $controle                = new controle_aula;
                $controle->materia_id    = $param['id'];
                $controle->dt_inicio     = TDate::date2us($param['data']);
                $controle->hora_inicio   = '00:00';
                
                $disciplina = new materia ($param['id']);
                $ch_max = $disciplina->carga_horaria;
                //Calcula se pode passar a carga horária
                if (($ch_usada + (int) $param['aula'])>$ch_max && $cfg['carga_horaria_maxima'] == 'S')
                {
                    $valor = (int) $param['aula'] - (($ch_usada + (int) $param['aula']) - $ch_max);
                    $valor = ($valor <0) ? 0 : $valor;
                }
                else
                {
                    $valor = (int) $param['aula'];
                }
                $controle->horas_aula    = (int) $param['aula'];
                $controle->status        = 'L'; //Refere-se ao status da aula (L = lançado, N = Não lançado etc)
                $controle->justificativa = 'NC';
                $controle->cadastrador   = TSession::getValue('login');
                $controle->conteudo      = $param['conteudo'];
                $controle->dt_cadastro   = date('Y-m-d');
                $controle->store();
                
                $professores = $param['professores'];
                $voluntario = 0;
                foreach ($professores as $professor)
                {
                    $controle_prof = new professorcontrole_aula;
                    $controle_prof->professor_id       = $professor;
                    $controle_prof->controle_aula_id   = $controle->id;
                    $controle_prof->data_aula          = TDate::date2us($param['data']);
                    if ($acad->getVoluntario (array ('professor_id'=>$professor,'materia_id'=>$param['id']))==false)
                    {
                        $renumera = $valor;
                    } 
                    else
                    {
                        $renumera = 0;
                        $voluntario ++;
                    }
                    $controle_prof->aulas_saldo        = $renumera;
                    $controle_prof->aulas_pagas        = 0;
                    $controle_prof->nivel_pagamento_id = $acad->getNivelPagamento($param['id']);
                    $controle_prof->titularidade_id    = $acad->getMaiorTitulo(
                                                            array('data_aula'=>$param['data'],
                                                            'professor_id'=>$professor));
                    $controle_prof->validado           = 'N';
                    $controle_prof->valor_aula         = $acad->getValorAula(
                                                                $controle_prof->nivel_pagamento_id,
                                                                $controle_prof->titularidade_id,
                                                                $controle_prof->data_aula);
                    $controle_prof->store();
                    //var_dump($controle_prof);
                }
                TTransaction::close();
                if (isset($param['falta']) && $param['falta'] == 'S')
                {
                    $acao = new TAction(array('aulasDisciplinaForm', 'onEdit'));
                    $acao->setParameters(array('key'=>$controle->id));
                    $comp_msg = '<br>Agora clique em OK para poder lançar as faltas.';
                }
                else
                {
                    $acao = new TAction(array('ControleAulaList', 'onReload'));
                    $comp_msg = '';
                }
                //$acao->setParameters($param);
                if(($valor > 0) && ($valor == (int) $param['aula']))
                {
                    if ($voluntario == 0)
                    {
                        new TMessage('info', $valor . " aula(s) lançada(s) com Sucesso.". $comp_msg , $acao);
                    }
                    else
                    {
                        new TMessage('info', $valor . " aula(s) lançada(s) com Sucesso, porém havia ".$voluntario .
                                    " professor(es) voluntário(s) que, portanto, não registra saldo a pagar." . 
                                    $comp_msg , $acao);
                    }
                }
                else
                {
                    if ($voluntario == 0)
                    {
                        new TMessage('info', " Aula lançada com Sucesso, porém a carga horária excedeu o máximo previsto.". 
                        "<br>" . $valor . " aula(s) computadas ao invés de " . $param['aula'] . 
                        $comp_msg , $acao);
                    }
                    else
                    {
                        new TMessage('info', " Aula lançada com Sucesso, porém a carga horária excedeu o máximo previsto.". 
                        "<br>" . $valor . " aula(s) computadas ao invés de " . $param['aula'] . "<br>" .
                        "Havia(m) ".$voluntario." professor(es) voluntário(s) que, portanto, não " . 
                        "registra saldo a pagar." . $comp_msg , $acao);
                    } 
                }
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
        else
        {
            new TMessage('error', 'Um campo requirido não foi preenchido. Verifique.');
        }
        /*
         * Montar rotinha de remessa para lançar ausências
         */

        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a Professores da Matéria
 *------------------------------------------------------------------------------*/
    public function getProfessores ($key = null)
    {

        $lista = false;
        try
        {
            TTransaction::open('sisacad');
            $docentes = professormateria::where('materia_id','=',$key)->load();
            if (count($docentes) > 0)
            {
                $lista = array();
                foreach ($docentes as $docente)
                {
                    $mestre = new professor($docente->professor_id);
                    $posto = (!empty($mestre->postograd)) ? $mestre->postograd->sigla.' ' : '';
                    $orgao = (!empty($mestre->orgaosorigem_id))  ? ' - '.$mestre->orgaosorigem->sigla : '';
                    $lista [$mestre->id] = $posto . $mestre->nome . $orgao;
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;

    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a Identificação do curso Matéria
 *------------------------------------------------------------------------------*/
    public function getIdentificacao ($key = null)
    {
        $lista = false;
        try
        {
            TTransaction::open('sisacad');
            $materia = new materia($key);
            if (!empty($materia))
            {
                $lista = $materia->turma->sigla . ' - Disciplina: ' . $materia->disciplina->nome;
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Direciona para Editar as aulas da Disciplina
 *------------------------------------------------------------------------------*/
     public function onControleAulaDisciplinaList ($param = null)
     {
         
         if ($param)
         {
             $key = $param['key'];
         }
         if (empty($key))
         {
             new TMessage('info','Houve um erro. Tente Novamente');
         }
         else
         {
              TSession::setValue('aula_disciplina',$key);
              $dados = TSession::getValue('aula_disciplina_dados');
              if (is_array($dados))
              {
                  TSession::setValue('aula_disciplina_paginacao',$dados);
              }
              else
              {
                  TSession::setValue('aula_disciplina_paginacao',null);
              }
              

              TApplication::gotoPage('ControleAulaDisciplinaList');
         }
     }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Lançamento expresso de Aulas
 *---------------------------------------------------------------------------------------*/
    public function onAulasExpresso ($param = null)
    {
        $fer = new TFerramentas();
        $id     = new THidden('id');
        $nome   = new TEntry('nome');
        $data   = new TText('data');
        //$aula   = new TEntry('aula');
        //                    $docentes = professormateria::where('materia_id','=',$object->id)->load();
        $professores = new TCheckGroup ('professores');
        $dadas       = new TSelect('dadas');
        
        //Valores
        $id->setValue($param['key']);
        $nome->setValue($this->getIdentificacao($param['key']));
        $items_prof = $this->getProfessores ($param['key']);
        if ($items_prof == false)
        {
            new TMessage ('error','Disciplina sem Docentes. Verifique!');
            return;
        }
        $professores->addItems($items_prof);
        $dadas->addItems($this->getAulasDadas(array('id'=>$param['key'])));
        
        //Tamanho
        $id->setSize(50);
        $nome->setSize(450);
        $data->setSize(400,40);
        $dadas->setSize(200,80);
       
        //Trava
        $id->setEditable(false);
        $nome->setEditable(false);
        
        //Tips
        $data->setTip('Campo para lançamento de multi-datas com sua respectiva carga horária.<br>'.
                        'Para efetivar o lançamento observe as seguintes regras:<br>'.
                        '- Escreva o dia da aula no formato dia/mes/ano (dd/mm/YYYY);<br>'.
                        '- Separe a C.H. com um traço;<br>'.
                        '- Se houver mais de um dia, separe-os usando vírgula.<br>'.
                        'Ex.: 03/07/2017-3,04/07/2017-4,05/07/2017-2');
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        
        
        $table = new TTable;
        $table->addRowSet( '', $id );
        $table->addRowSet( new TLabel('Identificação: '), $nome );
        $table->addRowSet( new TLabel('Aulas dadas: '), $dadas );
        $table->addRowSet( $lbl = new TLabel('Dia(s)-CH: '), $data );
        $lbl->setFontColor('red');
        $table->addRowSet( $lbl = new TLabel('Docentes: '), $professores );  
        $lbl->setFontColor('red');
       
        $form->setFields(array($nome, $id, $data,$dadas));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'onConfirmExpresso'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Lançamento Expresso de Aulas', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Rotina: Cadastra lançamento
 *------------------------------------------------------------------------------*/
    public static function ExecutaExpresso( $param )
    {
        //var_dump($param);
        $cfg = TSession::getValue('SISACAD_CONFIG');
        if ((isset($param['data']) && $param['data']) &&
            (isset($param['aula']) && $param['aula']) &&
            (isset($param['professores']) && $param['professores']) &&
            (isset($param['id']) && $param['id'])) // validate required field
        {
            $acad = new TSisacad();
            $ch_usada = $acad->getCargaHoraria($param['id']);
            
            try
            {
                TTransaction::open('sisacad');
                $controle                = new controle_aula;
                $controle->materia_id    = $param['id'];
                $controle->dt_inicio     = TDate::date2us($param['data']);
                $controle->hora_inicio   = '00:00';
                
                $disciplina = new materia ($param['id']);
                $ch_max = $disciplina->carga_horaria;
                //Calcula se pode passar a carga horária
                if (($ch_usada + (int) $param['aula'])>$ch_max && $cfg['carga_horaria_maxima'] == 'S')
                {
                    $valor = (int) $param['aula'] - (($ch_usada + (int) $param['aula']) - $ch_max);
                    $valor = ($valor <0) ? 0 : $valor;
                }
                else
                {
                    $valor = (int) $param['aula'];
                }
                $controle->horas_aula    = (int) $param['aula'];
                $controle->status        = 'L'; //Refere-se ao status da aula (L = lançado, N = Não lançado etc)
                $controle->justificativa = 'NC';
                $controle->cadastrador   = TSession::getValue('login');
                $controle->conteudo      = $param['conteudo'];
                $controle->dt_cadastro   = date('Y-m-d');
                $controle->store();
                
                $professores = $param['professores'];
                $voluntario = 0;
                foreach ($professores as $professor)
                {
                    $controle_prof = new professorcontrole_aula;
                    $controle_prof->professor_id       = $professor;
                    $controle_prof->controle_aula_id   = $controle->id;
                    $controle_prof->data_aula          = TDate::date2us($param['data']);
                    if ($acad->getVoluntario (array ('professor_id'=>$professor,'materia_id'=>$param['id']))==false)
                    {
                        $renumera = $valor;
                    } 
                    else
                    {
                        $renumera = 0;
                        $voluntario ++;
                    }
                    $controle_prof->aulas_saldo        = $renumera;
                    $controle_prof->aulas_pagas        = 0;
                    $controle_prof->nivel_pagamento_id = $acad->getNivelPagamento($param['id']);
                    $controle_prof->titularidade_id    = $acad->getMaiorTitulo(
                                                            array('data_aula'=>$param['data'],
                                                            'professor_id'=>$professor));
                    $controle_prof->validado           = 'N';
                    $controle_prof->valor_aula         = $acad->getValorAula(
                                                                $controle_prof->nivel_pagamento_id,
                                                                $controle_prof->titularidade_id,
                                                                $controle_prof->data_aula);
                    $controle_prof->store();
                }
                TTransaction::close();
                /*$acao = new TAction(array('ControleAulaList', 'onReload'));
                //$acao->setParameters($param);*/
                if(($valor > 0) && ($valor == (int) $param['aula']))
                {
                    if ($voluntario == 0)
                    {
                        $retorno = ';';//$valor . " aula(s) lançada(s) com Sucesso.";
                    }
                    else
                    {
                        $retorno =  ", porém havia ".$voluntario .
                                    " professor(es) voluntário(s) que, portanto, não registra saldo a pagar;";
                    }
                }
                else
                {
                    if ($voluntario == 0)
                    {
                        $retorno = ", porém a carga horária excedeu o máximo previsto. ". 
                        $valor . " aula(s) computadas ao invés de " . $param['aula'] .";";
                    }
                    else
                    {
                        $retorno = ", porém a carga horária excedeu o máximo previsto.". 
                        $valor . " aula(s) computadas ao invés de " . $param['aula'] . 
                        "Havia(m) ".$voluntario." professor(es) voluntário(s) que, portanto, não registra saldo a pagar;";
                    } 
                }
                return array('status'=>true,'mensagem'=>$retorno);
            }
            catch (Exception $e)
            {
                //new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
        return array('status'=>false,'mensagem'=>'');//Faltou algum dado ou houve erro ao gravar
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Rotina: Cadastra lançamento
 *------------------------------------------------------------------------------*/
    public static function onConfirmExpresso( $param )
    {
        if ((isset($param['data']) && $param['data']) &&
        (isset($param['professores']) && $param['professores']) &&
        (isset($param['id']) && $param['id'])) // validate required field
        {
            $datas = explode(',',$param['data']);
            //print_r($datas);
            $fer = new TFerramentas();
            $erro = '';
            $sucesso = '';
            foreach($datas as $data)
            {
                $aula = explode('-',$data);
                $aula['1'] = (!isset($aula['1'])) ? null : $aula['1'];
                if ($fer->isValidData($aula['0']) == true && $aula['1']>0)
                {
                    $dados = array('professores'=>$param['professores'],
                                   'id'=>$param['id'],
                                   'data'=>$aula['0'],
                                   'aula'=>$aula['1'],
                                   'conteudo'=>'Lançamento expresso');
                    //var_dump($dados);
                    $result = self::ExecutaExpresso($dados);
                    if ($result['status'] == true)
                    {
                        $sucesso .= (!empty($sucesso)) ? '<br>' : '';
                        $sucesso .= '* Aula no dia <b><font size="3" color="green">' . $aula['0'] .' </font></b>com a carga horária de ' . $aula['1'] . 
                                    ' hora(s) lançada(s) com SUCESSO';
                        $sucesso .= $result['mensagem'];
                    }
                    else
                    {
                        $erro .= (!empty($erro)) ? '<br>' : '';
                        $erro .= '* Erro ao tentar lançar o dia <b><font size="3" color="red">' . $aula['0'] .' </font></b>com a carga horária ' . $aula['1'];
                    }
                    
                }
                else
                {
                    $erro .= (!empty($erro)) ? '<br>' : '';
                    $erro .= '* Erro ao tentar lançar o dia <b><font size="3" color="red">' . $aula['0'] .' </font></b>com a carga horária de ' . $aula['1'] .' hora(s)';
                }
            }
            $acao = new TAction(array('ControleAulaList', 'onReload'));
            //$acao->setParameters($param);
            $men_sucesso = (!empty($sucesso)) ? '<center><font size="3" color="green">---  SUCESSOS  ---</font></center><br>' . $sucesso : '';
            $men_erro    = (!empty($erro))    ? '<br><center><font size="3" color="red">--- FALHAS ---</font></center><br>' . $erro : '';
            new TMessage('info', $men_sucesso . $men_erro,$acao);
        }
        else
        {
            new TMessage('error', 'Entre com todos os dados nos campos destacados em vermelho.');
        }
        

        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Cadastra Controle de Aula
 *---------------------------------------------------------------------------------------*/
    public function onControle ($param = null)
    {
        $criteria = new TCriteria();

        if ($this->nivel_sistema<=80)//Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query1), TExpression::OR_OPERATOR);
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query2), TExpression::OR_OPERATOR);
        }
        $criteria->add (new TFilter ('oculto','!=','S'));

        $turma_id = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        
        //var_dump($param);
        
        //Tamanho
        $turma_id->setSize(300);
        
        //Mascaras

        //Trava
        //$id->setEditable(false);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( $lbl = new TLabel('Turma: '), $turma_id );
        $lbl->setFontColor('red');

        
        $form->setFields(array($turma_id));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'Controle'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Cadastro de Controle/Registro de Aula', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Formulário para a Carga de Comprovantes de Aula
 *------------------------------------------------------------------------------*/
    public function Controle ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         //exit;
         //$data = $this->form->getData();
         //$dados = TSession::getValue('turma_militar');
         
         if (empty($param['turma_id']))
         {
             new TMessage('info','Por favor, entre em uma turma para prosseguir!!!');
         }
         else
         {
              $dados = new stdClass;
              $dados->documento = 'COMPROVANTE';
              $dados->id  = $param['turma_id'];
              $dados->retorno = 'ControleAulaList';
              //var_dump($dados);
              TSession::setValue('turma_militar',$dados);
              //exit;
              TApplication::loadPage('documentos_turmaForm');
              //var_dump($data);
         }
         $this->onReload();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Direciona para Editar as faltas a aulas da Disciplina
 *------------------------------------------------------------------------------*/
     public function onFalta ($param = null)
     {
         
         if ($param)
         {
             $key = $param['key'];
         }
         if (empty($key))
         {
             new TMessage('info','Houve um erro. Tente Novamente');
         }
         else
         {
              TSession::setValue('aula_disciplina',$key);
              TApplication::loadPage('aulasDisciplinaList','onReload');
              //var_dump($data);
         }
         //$this->form->setData($data);
         
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
                $nivel_sistema = $fer->getnivel ('ControleAulaList');//Verifica qual nível de acesso do usuário
                $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
                if ($key == 'ALL')
                {
                    if ($nivel_sistema<=80)//Gestores e/Operadores
                    {
                        $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . $listas['valores'] . "))";
                        $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $listas['valores'] . "))";
                        $sql    = "(SELECT id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . ")";
                        $criteria->add (new TFilter ('id','IN',$sql));
                    }
                    $criteria->add (new TFilter ('oculto','!=','S'));
                    
                    $turmas = $repository->load($criteria, FALSE);
                }
                else
                {
                    $criteria->add (new TFilter ('curso_id','=',$key));
                    if ($nivel_sistema<=80)//Gestores e/Operadores
                    {
                        $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . $listas['valores'] . "))";
                        $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (" . $listas['valores'] . "))";
                        $sql    = "(SELECT id FROM sisacad.turma WHERE id IN " . $query1 . " OR id IN ". $query2 . ")";
                        $criteria->add (new TFilter ('id','IN',$sql));
                    }
                    $turmas = $repository->load($criteria, FALSE);
                }
                //Monta a lista
                if (!empty($turmas))
                {
                    $lista = array();
                    foreach($turmas as $turma)
                    {
                        $lista [$turma->id] = $turma->nome;
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
        //TCombo::reload('form_search_materia','turma_id', $lista,true);
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
 *   Trocas as disciplinas
 *------------------------------------------------------------------------------*/
    public static function onChangeAction_turma($param = 'ALL')
    {
        $lista = array(0=>'-- SEM DISCIPLINAS VINCULADAS --');
        if ($param)
        {
            $key = (is_array($param)) ? $param['turma_id'] : $param;
            if (empty($key) && !empty($param['curso_id']))
            {
                $key   = 'CURSO';
                $c_key = $param['curso_id'];
            }
            else if (empty($key) && empty($param['curso_id']))
            {
                $key = 'ALL';
            }
            
            try
            {
                TTransaction::open('sisacad');
                $repository = new TRepository('materia');
                $criteria   = new TCriteria();
                $fer   = new TFerramentas();                        // Ferramentas diversas
                $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
                //Verifica o perfil do usuário e define suas OPMs
                $profile = TSession::getValue('profile');           //Profile da Conta do usuário
                $opm_operador = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
                $nivel_sistema = $fer->getnivel ('ControleAulaList');//Verifica qual nível de acesso do usuário
                $listas        = $sicad->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
                if ($key == 'ALL')
                {
                    if ($nivel_sistema<=80)//Lista as disciplinas somente da unidade dos Gestores e/Operadores
                    {
                        $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$listas['valores']."))";
                        $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$listas['valores']."))";
                        
                        $query3 = "(SELECT DISTINCT id FROM sisacad.turma WHERE id IN " . $query1 . 
                                    " OR id IN " . $query2 . " )";
                        $query4 = "(SELECT DISTINCT id FROM sisacad.materia WHERE turma_id IN " . $query3 . ")";
                        $criteria->add (new TFilter ('id','IN',$query4));
                    }
                    else
                    {
                        $criteria->add (new TFilter ('oculto','!=','S'));
                    }
                    //var_dump($criteria->dump());
                    $materias = $repository->load($criteria, FALSE);
                }
                else if ($key == 'CURSO')
                {
                    $sql = "(SELECT id FROM sisacad.turma WHERE curso_id = " . $c_key . " AND oculto != 'S')";
                    $criteria->add (new TFilter('turma_id','IN',$sql));
                    if ($nivel_sistema<=80)//Lista as disciplinas somente da unidade dos Gestores e/Operadores
                    {
                        $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$listas['valores']."))";
                        $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$listas['valores']."))";
                        
                        $query3 = "(SELECT DISTINCT id FROM sisacad.turma WHERE id IN " . $query1 . 
                                    " OR id IN " . $query2 . " )";
                        $query4 = "(SELECT DISTINCT id FROM sisacad.materia WHERE turma_id IN " . $query3 . ")";
                        $criteria->add (new TFilter ('id','IN',$query4));
                    }
                    //var_dump($criteria->dump());
                    $materias = $repository->load($criteria, FALSE);
                    //var_dump($materias);
                }
                else
                {
                    $sql = "(SELECT id FROM sisacad.turma WHERE nome LIKE '%{$key}%')";

                    //$criteria->add (new TFilter('turma_id','=',$key));
                    $criteria->add (new TFilter('turma_id', 'IN', $sql));
                    if ($nivel_sistema<=80)//Lista as disciplinas somente da unidade dos Gestores e/Operadores
                    {
                        $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$listas['valores']."))";
                        $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$listas['valores']."))";
                        
                        $query3 = "(SELECT DISTINCT id FROM sisacad.turma WHERE id IN " . $query1 . 
                                    " OR id IN " . $query2 . " )";
                        $query4 = "(SELECT DISTINCT id FROM sisacad.materia WHERE turma_id IN " . $query3 . ")";
                        $criteria->add (new TFilter ('id','IN',$query4));
                    }
                    $materias = $repository->load($criteria, FALSE);
                }
                if (!empty($materias))
                {
                    $lista = array();
                    $lista[] = '';
                    foreach($materias as $materia)
                    {
                        $lista [$materia->disciplina->id] = $materia->disciplina->nome;
                    }
                }
                //var_dump($lista); 
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }            
        }
        //$lista[1000] = '-- Entrada tipo --'. $key;
        TCombo::reload('form_search_materia','disciplina_id', $lista,false);
    }//Fim Módulo
}//Fim Classe

