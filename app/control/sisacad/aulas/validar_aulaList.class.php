<?php
/**
 * validar_aulaList Listing
 * @author  <your name here>
 */
class validar_aulaList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $ValidaButton;
    
    static $lista_cursos;
    static $lista_turmas;
    static $lista_disciplinas;
    static $lista_professores;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Turmas';            //Nome da página de serviço.
    
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
        $this->form = new TQuickForm('form_search_professorcontrole_aula');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Serviço de Validação de Aula Ministrada');
  
        // create the form fields
        $criteria = new TCriteria;
        
        if ($this->nivel_sistema == 00)//Gestores e/Operadores
        {
            $query1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query1), TExpression::OR_OPERATOR);
            $query2 = "(SELECT DISTINCT turma_id FROM sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores']."))";
            $criteria->add (new TFilter ('id','IN',$query2), TExpression::OR_OPERATOR);
        }

        $criteria->add(new TFilter('oculto','!=','S'));
        //$orgao            = new TDBCombo('orgao','sisacad','orgaosorigem','id','nome','nome',$criteria);
        
        $curso            = new TDBCombo('curso','sisacad','curso','id','nome','nome',$criteria);
        $turma            = new TDBCombo('turma','sisacad','turma','id','nome','nome',$criteria);
        $professor        = new TDBCombo('professor','sisacad','professor','id','nome','nome',$criteria);
        $disciplina       = new TDBCombo('disciplina','sisacad','disciplina','id','nome','nome',$criteria);
        $data_aula        = new TDate('data_aula');
        $validado         = new TCombo('validado');
        
        //Valores
        $validado->addItems($fer->lista_sim_nao());
        
        //Mascara
        $data_aula->setMask('dd-mm-yyyy');
        
        //Ações
        //$change_action = new TAction(array($this, 'onChangeAction_orgao'));//Re-popula os demais combos com base no órgão
        //$orgao->setChangeAction($change_action);
        
        $change_action = new TAction(array($this, 'onChangeAction_curso_turmas'));//Re-popula os demais combos com base no órgão
        $curso->setChangeAction($change_action);
        
        $change_action = new TAction(array($this, 'onChangeAction_turma_professor'));//Re-popula os demais combos com base no órgão
        $turma->setChangeAction($change_action);


        // add the fields
        //$this->form->addQuickField('Órgão Interessado', $orgao,  400 );
        $this->form->addQuickField('Curso', $curso,  400 );
        $this->form->addQuickField('Turma', $turma,  400 );
        $this->form->addQuickField('Disciplina', $disciplina,  400 );
        $this->form->addQuickField('Professor', $professor,  400 );
        $this->form->addQuickField('Data da Aula', $data_aula,  120 );
        $this->form->addQuickField('Validado?', $validado,  100 );
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('professorcontrole_aula_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        //$this->form->addQuickAction(_t('New'),  new TAction(array('validar_aulaForm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';

        // creates the datagrid columns
        $column_check         = new TDataGridColumn('check', '', 'center');
        $column_orgao         = new TDataGridColumn('orgao', 'Órgão Interessado', 'center');
        $column_curso         = new TDataGridColumn('curso', 'Curso', 'center');
        $column_turma         = new TDataGridColumn('turma', 'Turma', 'center');
        $column_professor_id  = new TDataGridColumn('professor_id', 'Professor', 'center');
        $column_disciplina    = new TDataGridColumn('disciplina', 'Disciplina', 'center');
        $column_data_aula     = new TDataGridColumn('data_aula', 'Data da Aula', 'right');
        $column_aulas_saldo   = new TDataGridColumn('aulas_saldo', 'Qnt Aulas', 'right');
        $column_validado      = new TDataGridColumn('validado', 'Validado?', 'center');
        $column_chamados      = new TDataGridColumn('chamados', 'Tudo OK?', 'center');
        //$column_pendentes     = new TDataGridColumn('pendentes', 'Resolvidos?', 'center');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        //$this->datagrid->addColumn($column_orgao);
        $this->datagrid->addColumn($column_curso);
        $this->datagrid->addColumn($column_turma);
        $this->datagrid->addColumn($column_professor_id);
        $this->datagrid->addColumn($column_disciplina);
        $this->datagrid->addColumn($column_data_aula);
        $this->datagrid->addColumn($column_aulas_saldo);
        $this->datagrid->addColumn($column_validado);
        $this->datagrid->addColumn($column_chamados);
        //$this->datagrid->addColumn($column_pendentes);

        $column_validado->setTransformer( 
            function ($value,$object,$row)
            {
                $div = new TElement('span');
                $div->class = 'label label-' . ($value == 'SIM' ? 'success' : 'danger');
                $div->add($value);
                return $div;
            } 
        );
        $column_chamados->setTransformer( 
            function ($value,$object,$row)
            {
                $div = new TElement('span');
                $div->class = 'label label-' . ($value == 'SIM' ? 'success' : 'danger');
                $div->add($value);
                return $div;
            } 
        );
        // create Validador action
        $action_val = new TDataGridAction(array($this, 'onValida'));
        $action_val->setUseButton(false);
        $action_val->setButtonClass('btn btn-default');
        $action_val->setLabel('Valida Aula para Pagamento');
        $action_val->setImage('fa:thumbs-o-up green fa-lg');
        $action_val->setField('id');
        $this->datagrid->addAction($action_val);
        
        // create DesValidador action
        $action_unv = new TDataGridAction(array($this, 'onDesvalida'));
        $action_unv->setUseButton(false);
        $action_unv->setButtonClass('btn btn-default');
        $action_unv->setLabel('Desvalida aula para Pagamento');
        $action_unv->setImage('fa:thumbs-o-down red fa-lg');
        $action_unv->setField('id');
        $this->datagrid->addAction($action_unv);
        
        // create Visualizar action
        $action_ver = new TDataGridAction(array($this, 'onViewControle'));
        $action_ver->setUseButton(false);
        $action_ver->setButtonClass('btn btn-default');
        $action_ver->setLabel('Visualiza Controle de Aula');
        $action_ver->setImage('fa:eye black fa-lg');
        $action_ver->setField('id');
        $action_ver->setDisplayCondition(array($this,'displayCTRL'));
        $this->datagrid->addAction($action_ver);
        
        // create Assinatura action
        $action_ass = new TDataGridAction(array($this, 'onViewAssinatura'));
        $action_ass->setUseButton(false);
        $action_ass->setButtonClass('btn btn-default');
        $action_ass->setLabel('Visualiza a ficha de assinatura');
        $action_ass->setImage('fa:pencil-square-o black fa-lg');
        $action_ass->setField('id');
        $action_ass->setDisplayCondition(array($this,'displayAssina'));
        $this->datagrid->addAction($action_ass);
        
        // create Delete action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(false);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel('Apaga um Registro de Aula');
        $action_del->setImage('fa:trash red fa-lg');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);
        
        // notificação
        //OBS: Deve ser o último item do actiongroup
        $action_not = new TDataGridAction(array($this, 'onNotifica'));
        $action_not->setUseButton(false);
        $action_not->setButtonClass('btn btn-default');
        $action_not->setLabel('Notifica problema no Controle de Aula');
        $action_not->setImage('fa:bell red fa-lg');
        $action_not->setField('id');
        $this->datagrid->addAction($action_not);
        
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
        $this->ValidaButton = new TButton('valida_collection');
        $this->ValidaButton->setAction(new TAction(array($this, 'onValidaCollection')), 'Valida Seleção');
        $this->ValidaButton->setImage('fa:thumbs-o-up green fa-lg');
        $this->formgrid->addField($this->ValidaButton);
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        $gridpack->add($this->ValidaButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'validar_aulaList'));
        $container->add($this->form);
        $container->add($gridpack);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }//Fim Construct
/*---------------------------------------------------------------------------------------
 * Função que habilita visualizar icone no datagrid (Folha de controle de aula)
 *---------------------------------------------------------------------------------------*/
    public function displayCTRL($object)
    {
        if ($object->controle == 'SIM')
        {
            return true;
        }
        return false;
    }
/*---------------------------------------------------------------------------------------
 * Função que habilita visualizar icone no datagrid (Ficha de assinatura)
 *---------------------------------------------------------------------------------------*/
    public function displayAssina($object)
    {
        if ($object->assinatura == 'SIM')
        {
            return true;
        }
        return false;
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
        TSession::setValue('validar_aulaList_filter_professor',   NULL);
        TSession::setValue('validar_aulaList_filter_data_aula',   NULL);
        TSession::setValue('validar_aulaList_filter_validado',   NULL);
        //TSession::setValue('validar_aulaList_filter_orgao',   NULL);
        TSession::setValue('validar_aulaList_filter_curso',   NULL);
        TSession::setValue('validar_aulaList_filter_turma',   NULL);
        TSession::setValue('validar_aulaList_filter_disciplina',   NULL);

        if (isset($data->professor) AND ($data->professor) && ($data->professor !='NC')) {
            $filter = new TFilter('professor_id', '=', "$data->professor"); // create the filter
            TSession::setValue('validar_aulaList_filter_professor',   $filter); // stores the filter in the session
        }


        if (isset($data->data_aula) AND ($data->data_aula)) {
            $filter = new TFilter('data_aula', '=', TDate::date2us($data->data_aula)); // create the filter
            TSession::setValue('validar_aulaList_filter_data_aula',   $filter); // stores the filter in the session
        }

        if (isset($data->validado) AND ($data->validado)) {
            $filter = new TFilter('validado', '=', "$data->validado"); // create the filter
            TSession::setValue('validar_aulaList_filter_validado',   $filter); // stores the filter in the session
        }

        if (isset($data->turma) AND ($data->turma) && ($data->turma !='NC')) {
            
            $sql  = "(SELECT id FROM sisacad.materia WHERE turma_id = " . $data->turma . ")";
            $sql2 = "(SELECT id FROM sisacad.controle_aula WHERE materia_id IN " . $sql . ")";
            $filter = new TFilter('controle_aula_id', 'IN', $sql2); // create the filter
            //var_dump($filter);   
            TSession::setValue('validar_aulaList_filter_turma',   $filter); // stores the filter in the session
        }
        
        if (isset($data->disciplina) AND ($data->disciplina) && ($data->disciplina !='NC')) {
            
            $sql  = "(SELECT id FROM sisacad.materia WHERE disciplina_id = " . $data->disciplina . ")";
            $sql2 = "(SELECT id FROM sisacad.controle_aula WHERE materia_id IN " . $sql . ")";
            $filter = new TFilter('controle_aula_id', 'IN', $sql2); // create the filter
            //var_dump($filter);   
            TSession::setValue('validar_aulaList_filter_disciplina',   $filter); // stores the filter in the session
        }
        
        if (isset($data->curso) AND ($data->curso) && ($data->curso !='NC')) {
            
            $sql  = "(SELECT id FROM sisacad.curso WHERE id = " . $data->curso . ")";
            $sql2 = "(SELECT id FROM sisacad.turma WHERE curso_id IN " . $sql . ")";
            $sql3 = "(SELECT id FROM sisacad.materia WHERE turma_id IN " . $sql2 . ")";
            $sql4 = "(SELECT id FROM sisacad.controle_aula WHERE materia_id IN " . $sql3 . ")";
            $filter = new TFilter('controle_aula_id', 'IN', $sql4); // create the filter
            TSession::setValue('validar_aulaList_filter_curso',   $filter); // stores the filter in the session
        }
        // keep the search data in the session
        TSession::setValue('professorcontrole_aula_filter_data', $data);

        // fill the form with data again
        $this->form->setData($data);
        
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
                $param['order'] = 'data_aula';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);


            if (TSession::getValue('validar_aulaList_filter_professor')) {
                $criteria->add(TSession::getValue('validar_aulaList_filter_professor')); // add the session filter
            }
 
            if (TSession::getValue('validar_aulaList_filter_data_aula')) {
                $criteria->add(TSession::getValue('validar_aulaList_filter_data_aula')); // add the session filter
            }

            if (TSession::getValue('validar_aulaList_filter_validado')) {
                $criteria->add(TSession::getValue('validar_aulaList_filter_validado')); // add the session filter
            }

            if (TSession::getValue('validar_aulaList_filter_disciplina')) {
                $criteria->add(TSession::getValue('validar_aulaList_filter_disciplina')); // add the session filter
            }

            if (TSession::getValue('validar_aulaList_filter_turma')) {
                $criteria->add(TSession::getValue('validar_aulaList_filter_turma')); // add the session filter
            }
            if (TSession::getValue('validar_aulaList_filter_curso')) {
                $criteria->add(TSession::getValue('validar_aulaList_filter_curso')); // add the session filter
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
                $fer = new TFerramentas();
                foreach ($objects as $object)
                {
                    //Cria definições
                    $professor            = new professor($object->professor_id);
                    $posto                = $professor->postograd->sigla;
                    $posto                = (!empty($posto)) ? $posto : '';
                    $controle_aula        = $object->controleaula;
                    $materia              = new materia ($controle_aula->materia_id);
                    $disciplina           = $materia->disciplina->nome;
                    $turma                = $materia->get_turma();
                    //Carrega dados da ficha de assinatura e da ficha de controle.
                    $doc = documentos_professor::where('professor_id','=',$professor->id)->
                                             where('oculto','!=','S')->
                                             where('assinatura','=','S')->load();
                    $assintatura          = (!empty($doc)) ? 'SIM' : 'NÃO';
                    
                    $aula    = new professorcontrole_aula ($object->id);
                    $turma_a = $aula->controleaula->materia->turma->id;
                    $data    = $aula->controleaula->dt_inicio;
                    $doc     = documentos_turma::where('turma_id','=',$turma_a)->
                                             where('data_aula','=',$data)->
                                             where('comprovante','=','S')->load();
                    
                    $ficha_controle       = (!empty($doc)) ? 'SIM' : 'NÃO';
                    
                    $chamados = incidentes::where('json','like','%"notificacao_validacao_aula":"' . 
                                                  $object->id . '"%')->load();
                    
                    $n_chamados = (count($chamados)>0) ? 'NÃO' : 'SIM';
                    $pendentes = 'SIM'; //Usa a lógica invertida...se não há pendencia = SIM
                    $tip = '';
                    //var_dump($chamados);
                    foreach ($chamados as $chamado)
                    {
                        if ($chamado->status != 10)
                        {
                            $tip .= 'RESOLVIDO:' . str_replace('.','<br>',$chamado->resumo)  . '<br>';
                        }
                        else
                        {
                            $pendentes = 'NÃO';
                            $tip .= 'AGUARDANDO:' . str_replace('.','<br>',$chamado->resumo) . '<br>';
                        }

                    }
                    $n_chamados = ($pendentes == 'NÃO') ? "NÃO" : "SIM";
                    
                    //Carrega os dados no object
                    $object->professor_id = $posto . ' ' .$professor->nome;
                    $object->validado     = (!empty($object->validado)) ? $fer->lista_sim_nao($object->validado) : 'NÃO';
                    $object->disciplina   = $disciplina;
                    $object->curso        = $turma->curso->sigla;
                    $object->turma        = $turma->sigla;
                    $object->data_aula    = TDate::date2br($object->data_aula);
                    $object->assinatura   = $assintatura;
                    $object->controle     = $ficha_controle;
                    $object->chamados     = $n_chamados;
                    //$object->pendentes    = $pendentes;
                     
                    // add the object inside the datagrid
                    $row = $this->datagrid->addItem($object);
                    $row->popover = 'true';
                    $row->popside = 'top';
                    $row->poptitle = 'CHAMADOS ABERTOS PARA ESSA AULA';
                    $tip = (empty($tip)) ? '-- Sem Pendências --' : $tip;
                    $row->popcontent = "<center>" . $tip ."</center>";;
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
     * Ask before delete record collection
     */
    public function onValidaCollection( $param )
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
                $action = new TAction(array($this, 'ValidaCollection'));
                $action->setParameters($param); // pass the key parameter ahead
                
                // shows a dialog to the user
                new TQuestion('Valida todas essas aulas Selecionadas?', $action);
            }
        }
    }
    
    /**
     * method deleteCollection()
     * Delete many records
     */
    public function ValidaCollection($param)
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
                    $object = new professorcontrole_aula($id);
                    $object->validado    = 'S';
                    $object->validador   = TSession::getValue('login');
                    $object->dt_validado = date('Y-m-d');
                    $object->store();
                }
                $posAction = new TAction(array($this, 'onReload'));
                $posAction->setParameters( $param );
                new TMessage('info', 'Aulas Validadas!', $posAction);
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
        $deleteAction = $this->ValidaButton->getAction();
        $deleteAction->setParameters($param); // important!
        
        $gridfields = array( $this->ValidaButton );
        
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
/*---------------------------------------------------------------------------------------
 * Altera pesquisa para ajustar ao órgão interessado
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_orgao($param,$reload = false)
    {
        $lista = array ('NC'=>'-- Nenhum Curso aberto a interesse deste órgão --');
        $indice = '0';
        $key = null;
        if (array_key_exists('orgao',$param))
        {
            $key = $param['orgao'];
        }
        if (!empty($key) && $key!='NC')
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    $options  = curso::where('orgaoorigem_id', '=', $key)->load();//Lista de Cidades Filtradas
                    TTransaction::close(); // close the transaction
                    if (!empty($options))
                    {
                        $lista = array();
                        $indice = '';
                        foreach ($options as $option)
                        {
                            if (!empty($indice))
                            {
                                $indice.= ',';
                            }
                            $lista[$option->id] = $option->nome;
                            $indice.=$option->id;
                        }
                    }
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        $carga = ($indice != '0') ? (($reload==false) ? true : false) : false;
        TCombo::reload('form_search_professorcontrole_aula', 'curso', $lista,$carga);
        self::onChangeAction_curso_turmas(array('indice'=>$indice), $reload);
        self::onChangeAction_curso_disciplinas(array('indice'=>$indice), $reload);

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Altera pesquisa para ajustar as Turmas ao curso 
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_curso_turmas($param,$reload = false)
    {
        $lista = array ('NC'=>'-- Nenhuma Turma aberta vinculada ao curso escolhido --');
        $indice = '0';
        $key = null;
        if (array_key_exists('curso',$param))
        {
            $key = $param['curso'];
            $tipo = 1;
        }
        else if (array_key_exists('indice',$param))
        {
            $key = $param['indice'];
            $tipo = 2;
        }
        else
        {
            $tipo = false;
        }
        if ($tipo !=false && !empty($key) && $key!='NC')
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    $query = "(SELECT DISTINCT id FROM sisacad.curso WHERE id IN (".$key."))";
                    $options  = turma::where('curso_id', 'IN', $query)->load();//Lista de Cidades Filtradas
                    TTransaction::close(); // close the transaction
                    if (!empty($options))
                    {
                        $lista = array();
                        $indice = '';
                        foreach ($options as $option)
                        {
                            if (!empty($indice))
                            {
                                $indice.= ',';
                            }
                            $lista[$option->id] = $option->nome;
                            $indice.=$option->id;
                        }
                    }
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        $carga = ($indice != '0') ? (($reload==false) ? true : false) : false;
        TCombo::reload('form_search_professorcontrole_aula', 'turma', $lista,$carga);
        self::onChangeAction_turma_professor(array('indice'=>$indice), $reload);

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Altera pesquisa para ajustar as Disciplinas ao curso
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_curso_disciplinas($param, $reload = false)
    {
        $lista = array ('NC'=>'-- Nenhuma Disciplina vinculada ao curso escolhido --');
        $indice = '0';
        $key = null;
        if (array_key_exists('curso',$param))
        {
            $key = $param['curso'];
            $tipo = 1;
        }
        else if (array_key_exists('indice',$param))
        {
            $key = $param['indice'];
            $tipo = 2;
        }
        else
        {
            $tipo = false;
        }
        if ($tipo !=false && !empty($key) && $key!='NC')
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    $query = "(SELECT DISTINCT disciplina_id FROM sisacad.materias_previstas WHERE curso_id IN (".$key."))";
                    $options  = disciplina::where('id', 'IN', $query)->load();//Lista de Cidades Filtradas
                    TTransaction::close(); // close the transaction
                    if (!empty($options))
                    {
                        $lista = array();
                        $indice = '';
                        foreach ($options as $option)
                        {
                            if (!empty($indice))
                            {
                                $indice.= ',';
                            }
                            $lista[$option->id] = $option->nome;
                            $indice.=$option->id;
                        }
                    }
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        $carga = ($indice != '0') ? (($reload==false) ? true : false) : false;
        TCombo::reload('form_search_professorcontrole_aula', 'disciplina', $lista,$carga);

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Altera pesquisa para ajustar os professores em relação a turma
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_turma_professor($param, $reload = false)
    {
        $lista = array ('NC'=>'-- Nenhum professor vinculado à turma escolhido --');
        $indice = '0';
        $key = null;
        if (array_key_exists('turma',$param))
        {
            $key = $param['turma'];
            $tipo = 1;
        }
        else if (array_key_exists('indice',$param))
        {
            $key = $param['indice'];
            $tipo = 2;
        }
        else
        {
            $tipo = false;
        }
        if ($tipo !=false && !empty($key) && $key!='NC')
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    $query  = "(SELECT DISTINCT disciplina_id FROM sisacad.materia WHERE turma_id IN (".$key."))";
                    $query2 = "(SELECT DISTINCT professor_id FROM sisacad.professormateria WHERE materia_id IN ".$query.")";
                    
                    $options  = professor::where('id', 'IN', $query)->load();//Lista de Cidades Filtradas
                    if (!empty($options))
                    {
                        $lista = array();
                        $indice = '';
                        foreach ($options as $option)
                        {
                            if (!empty($indice))
                            {
                                $indice.= ',';
                            }
                            $posto = (!empty($option->postograd)) ? $option->postograd : '';
                            $orgao = (!empty($option->orgao_origem)) ? $option->orgao_origem : ''; 
                            $lista[$option->id] = $option->nome;
                            $indice.=$option->id;
                        }
                    }
                    TTransaction::close(); // close the transaction
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        self::$lista_professores = $lista;
        $carga = ($indice != '0') ? (($reload==false) ? true : false) : false;
        TCombo::reload('form_search_professorcontrole_aula', 'professor', $lista,$carga);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Valida Aula
 *---------------------------------------------------------------------------------------*/
    public function onValida($param, $reload = true)
    {
        $key = null;
        //var_dump($param);
        if (array_key_exists('id',$param))
        {
            $key = $param['id'];
        }
        if (!empty($key))
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    
                    $aula = new professorcontrole_aula ($key);
                    $aula->validado    = 'S';
                    $aula->validador   = TSession::getValue('login');
                    $aula->dt_validado = date('Y-m-d');
                    $aula->store();
                    TTransaction::close(); // close the transaction
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        if ($reload)
        {
            $this->onReload($param);
        }
        
        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Desvalida Aula
 *---------------------------------------------------------------------------------------*/
    public function onDesvalida($param, $reload = true)
    {
        $key = null;
        //var_dump($param);
        if (array_key_exists('id',$param))
        {
            $key = $param['id'];
        }
        if (!empty($key))
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    
                    $aula = new professorcontrole_aula ($key);
                    $aula->validado    = 'N';
                    $aula->validador   = TSession::getValue('login');
                    $aula->dt_validado = null;
                    $aula->store();
                    TTransaction::close(); // close the transaction
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        if ($reload)
        {
            $this->onReload($param);
        }
        
        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Visualiza o Controle de Aula se houver
 *---------------------------------------------------------------------------------------*/
    public function onViewControle($param)
    {
        $key = null;
        if (array_key_exists('id',$param))
        {
            $key = $param['id'];
        }
        if (!empty($key))
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    
                    $aula = new professorcontrole_aula ($key);
                    $turma = $aula->controleaula->materia->turma->id;
                    $data  = $aula->controleaula->dt_inicio;
                    $doc = documentos_turma::where('turma_id','=',$turma)->
                                             where('data_aula','=',$data)->
                                             where('comprovante','=','S')->load();
                    if (!empty($doc))
                    {
                        $arquivo = $doc['0']->arquivos_externos_id;
                        $sql = "SELECT DISTINCT encode(contend, 'base64')as contend FROM sisacad.arquivos_externos WHERE id=".$arquivo;
                        $conn = TTransaction::get();
                        $res = $conn->prepare($sql);
                        $res->execute();
                        $dados = $res->fetchAll();
                        
                        
                        $object = new arquivos_externos($arquivo); // instantiates the Active Record
                        $arquivo = $dados['0']['contend'];//pg_unescape_bytea($dados['0']['contend']);
                        //var_dump($arquivo);
                        $file = 'tmp/'. TSession::getValue('login') . '.pdf';//$object->filename;
                        if (strtolower(substr($object->filename, -3)) == 'pdf')
                        {
                            echo file_put_contents($file,base64_decode($arquivo));
                            TPage::openFile($file);
                        }
                    }
                    else
                    {
                        new TMessage('info','Controle de Aula não carregado para o Banco');
                    }
                    TTransaction::close(); // close the transaction
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        $this->onReload($param);
        
        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Visualiza a assinatura do professor
 *---------------------------------------------------------------------------------------*/
    public function onViewAssinatura($param)
    {
        $key = null;
        if (array_key_exists('id',$param))
        {
            $key = $param['id'];
        }
        if (!empty($key))
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    
                    $aula = new professorcontrole_aula ($key);
                    $professor = $aula->professor_id;
                    $doc = documentos_professor::where('professor_id','=',$professor)->
                                             where('oculto','!=','S')->
                                             where('assinatura','=','S')->load();
                    //var_dump($doc);
                    if (!empty($doc))
                    {
                        $arquivo = $doc['0']->arquivos_professor_id;
                        $sql = "SELECT DISTINCT encode(contend, 'base64')as contend FROM sisacad.arquivos_professor ".
                                "WHERE id=".$arquivo ;
                        $conn = TTransaction::get();
                        $res = $conn->prepare($sql);
                        $res->execute();
                        $dados = $res->fetchAll();
                        
                        
                        $object = new arquivos_professor($arquivo); // instantiates the Active Record
                        $arquivo = $dados['0']['contend'];//pg_unescape_bytea($dados['0']['contend']);
                        //var_dump($arquivo);
                        $file = 'tmp/'. TSession::getValue('login') . '.pdf';//$object->filename;
                        if (strtolower(substr($object->filename, -3)) == 'pdf')
                        {
                            echo file_put_contents($file,base64_decode($arquivo));
                            TPage::openFile($file);
                        }
                    }
                    else
                    {
                        new TMessage('info','Folha de Assinaturas do professor não localizada.');
                    }
                    TTransaction::close(); // close the transaction
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        $this->onReload($param);
        
        
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Cria notificação
 *---------------------------------------------------------------------------------------*/
    public function onNotifica ($param = null)
    {
        $fer       = new TFerramentas();
        $id        = new THidden('id');
        $tipo      = new TCombo('tipo');
        $criteria  = new TCriteria();// Para filtrar só usuários que usem um dado sistema da tabela:opmv.item 
                                     //dominio:configura e que estejam na OPM do lançador
        $criteria->add(new TFilter('id','IN',$fer->getIdsDestino(array('sistema'=>'SISACAD'))));
        //$criteria->dump();
        $servidor  = new TDBCombo ('servidor','permission','SystemUser','id','name','name',$criteria);
        $texto     = new TText('texto');
        //var_dump($param);
        //Tamanho
        $texto->setSize(300, 120);
        $tipo->setSize(300);
        $servidor->setSize(300);
        
        //Valores
        $id->setValue($param['id']);
        $tipos = array('SEM_CONTROLE'=>'FALTA DA FOLHA DE CONTROLE DE AULA',
                       'SEM_ASSINATURA'=>'FOLHA ASSINATURA NÃO PREENCHIDO',
                       'OUTRO_PROFESSOR'=>'OUTRO PROFESSOR ASSINOU NO LUGAR',
                       'DUPLICADO'=>'AULA DUPLICADA',
                       'OUTROS'=>'OUTROS');
        $tipo->addItems($tipos);
        $servidor->setValue($this->get_Lancador($param));//Buscar o nome do lançador do item($param['id']);
        
        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( new TLabel('Tipo de Problema: '), $tipo );
        $table->addRowSet( new TLabel('Destinatário: '), $servidor );
        $table->addRowSet( new TLabel('Descrição do Problema: '), $texto );
        $table->addRowSet( '', $id );
        
        $form->setFields(array($tipo,$texto,$id,$servidor));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'Notifica'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Notificação de Problema', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public function Notifica ($param)
    {

        //var_dump($param);exit;
        if (!empty($param['tipo']))
        {
            try
            {
                TTransaction::open('sisacad');
                
                //var_dump($data);
                $aula        = new professorcontrole_aula($param['id']);
                if (!empty($param['servidor']))
                {
                    $destino     = new SystemUser($param['servidor']);
                }
                else
                {
                    $param['servidor'] = $this->get_Lancador($param['id']);
                    $destino     = new SystemUser($param['servidor']);
                }
                $ma          = new TMantis;
                $profile     = TSession::getValue('profile');
                $servidor    = $ma->FindServidor($profile['login']);
                $sistema     = $ma->FindSistema('SISACAD');
                $parameters  = json_encode(
                               array ('key'=>$param['id'],
                                     'class_to'=>'validar_aulaList',
                                     'method_to'=>'onReload',
                                     'notificacao_validacao_aula'=>$param['id']));
                
                $posto     = $aula->professor->postograd->sigla;
                $posto     = (!empty($posto)) ? $posto . ' ' : '';
                $orgao     = $aula->professor->orgaosorigem->sigla;
                $orgao     = (!empty($orgao)) ? '(' . $orgao . ')' : '';
                $professor = $posto . $aula->professor->nome . $orgao;
                
                $texto  = 'Problema no Controle de Aula do professor(a) ' . $professor . '.';
                $texto .= 'Aula do dia ' . TDate::date2br($aula->controleaula->dt_inicio) . ', ';
                $texto .= 'Ministrada para a turma ' . $aula->controleaula->materia->turma->sigla . '.';
                //Necessita do id do lançador do controle de aula...
                switch ($param['tipo'])
                {
                    case 'SEM_CONTROLE'://FALTA DA FICHA DE CONTROLE DE AULA
                        $texto .= 'INCIDENTE: Faltando ficha de Controle de Aula.';
                        break;
                    case 'SEM_ASSINATURA':
                        $texto .= 'INCIDENTE: Faltando Folha de assinatura do professor.';
                        break;
                    case 'OUTRO_PROFESSOR'://FALTA DA FICHA DE CONTROLE DE AULA
                        $texto .= 'INCIDENTE: Outro professor assinou no lugar.';
                        break;
                    case 'DUPLICADO'://FALTA DA FICHA DE CONTROLE DE AULA
                        $texto .= 'INCIDENTE: Aula duplicada.';
                        break;
                    case 'OUTROS'://FALTA DA FICHA DE CONTROLE DE AULA
                        $texto .= 'INCIDENTE: Outros.';
                        break;
                    default:
                        $texto .= 'INCIDENTE: Outros.';
                        break;
                }
                $info = array (
                    'relator_id'    => $servidor->id,
                    'operador_id'   => $servidor->id,
                    'duplicata_id'  => 0,
                    'prioridade'    => 50,
                    'gravidade'     => 30,
                    'status'        => 10,
                    'resolucao'     => 0,
                    'destino_id'    => 1,
                    'resumo'        => $texto,
                    'categoria_id'  => 5,
                    'sistema_id'    => $sistema->id,
                    'grupo_id'      => 90,
                    'servidor_id'   => 0,
                    'data_inicio'   => date('Y-m-d'),
                    'data_fim'      => '',
                    'data_atual'    => date('Y-m-d'),
                    'oculto'        => 'N',
                    'json'          => $parameters,
                    'acesso'        => 10);//Acesso publico
                //$ma->chamado       = $info;
                //$ret               = $ma->criaChamado();
                $chamado = new incidentes;
                $chamado->fromArray($info);
                $chamado->store();
                $ret = $chamado->id;
                

                                                    
                //Necessita do id do lançador do controle de aula...
                switch ($param['tipo'])
                {
                    case 'SEM_CONTROLE'://FALTA DA FICHA DE CONTROLE DE AULA
                        $nota  = 'O(A) servidor(a) '.$servidor->nome .' relata que não localizou o Controle da aula ' . 
                                 'do professor(a) ' . $professor . ' para a aula do dia ' . TDate::date2br($aula->controleaula->dt_inicio) . '.<br>';
                        $nota  .= 'Ministrada para a turma ' . $aula->controleaula->materia->turma->sigla . '.';
                        $nota  .= (!empty($param['texto'])) ? ' Conforme relatado: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Controle de Aula';
                        //Mudar abaixo para tela de documentos da turma
                        $acao   = 'class=ControleAulaList&method=onReload&key=' . $param['id'] . '&chamado=' . $ret;
                    
                        break;
                    case 'SEM_ASSINATURA'://FICHA DE ASSINATURAS DO PROFESSOR NÃO CADASTRADA
                        $nota  = 'O(A) servidor(a) '.$servidor->nome .' relata que não achou a ficha de assinaturas  '.
                                 'professor(a) ' . $professor ;
                        $nota  .= (!empty($param['texto'])) ? ' conforme o próprio relata: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o cadastro de documentos do professor.';
                        //Mudar abaixo para tela de documentos do professor
                        $acao   = 'class=professorList&method=onCorrecao&key=' . $aula->professor_id . '&chamado=' . $ret;
                        break;
                    case 'OUTRO_PROFESSOR':
                        $nota  = 'O(A) servidor(a) '.$servidor->nome .' relata que outro professor assinou no lugar do '.
                                 'professor(a) ' . $professor;
                        $nota  .= (!empty($param['texto'])) ? ' conforme o próprio relata: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Formulário de Designação de Docente em turma';
                        //Mudar para tela de controle de aula.
                        $acao   = 'class=turmaForm&method=onEdit&key=' . $aula->controleaula->materia->turma_id . '&chamado=' . $ret;
                        break;
                    case 'DUPLICADO'://FALTA DA FICHA DE CONTROLE DE AULA
                        $nota   = 'O(A) servidor(a) '.$servidor->nome .' que a aula  do professor(a) ' . $professor . 
                                  ' para a aula do dia ' . TDate::date2br($aula->controleaula->dt_inicio) . '.<br>';
                        $nota  .= 'Ministrada para a turma ' . $aula->controleaula->materia->turma->sigla . ' está duplicada.';
                        $nota  .= (!empty($param['texto'])) ? ' Conforme relatado: ' . $param['texto'] : '.';
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Controle de Aula';
                        //Mudar abaixo para tela de documentos da turma
                        $acao   = 'class=ControleAulaList&method=onReload&key=' . $param['id'] . '&chamado=' . $ret;
                    
                        break;
                    default:
                        $nota = (!empty($param['texto'])) ? str_replace('.','<br>',$texto)  . ' Conforme descreve abaixo:<br>' . $param['texto'] 
                                                          : str_replace('.','<br>',$texto);
                        $fazer  = 'Aperte o botão abaixo para ser redirecionado para o Controle de Aula';
                        //Mudar abaixo para tela de documentos da turma
                        $acao   = 'class=ControleAulaList&method=onReload&key=' . $param['id'] . '&chamado=' . $ret;
                        break;
                }

                
                //Mudar o redirecionamento conforme o tipo de problema
                //Para cada tipo de problema reescrever a tela de forma que faça o registro no Mantis
                //Substituir o numero 3 pelo id do lançador do controle de aula
                //var_dump($destino);
                SystemNotification::register( $param['servidor'], $nota, $fazer, $acao,'Correção', 'fa fa-pencil-square-o blu');
                $action = new TAction(array($this, 'onReload'));
                $action->setParameter('key', $param['id']);
                new TMessage('info','Operador Notificado',$action);
                TTransaction::close();

    
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage().'Erro na rotina de Notificação.');
                TTransaction::rollback();
            }
        }
        else
        {
            $action = new TAction(array($this, 'onReload'));
            $action->setParameter('key', $param['id']);
            new TMessage('error','Deve-se escolher um tipo de problema para notificar.',$action);
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Busca lançador da aula
 *---------------------------------------------------------------------------------------*/
    public function get_Lancador($param)
    {
        $key = null;
        $ret = '';
        if (array_key_exists('id',$param))
        {
            $key = $param['id'];
        }
        if (!empty($key))
        {
            try
            {
                    TTransaction::open('sisacad'); // open a transaction
                    
                    $aula = new professorcontrole_aula ($key);
                    $cadastrador = $aula->controleaula->cadastrador;
                    
                    if (!empty($cadastrador))
                    {
                        $users = SystemUser::where('login','=',$cadastrador)->load();
                        foreach($users as $user)
                        {
                            $ret = $user->id;
                        }
                    }
                    TTransaction::close(); // close the transaction
    
            }
            catch (Exception $e) // in case of exception 
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations            
            }
        }
        return $ret;
        
    }//Fim Módulo
}//Fim Classe
