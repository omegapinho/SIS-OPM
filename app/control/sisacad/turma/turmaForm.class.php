<?php
/**
 * turmaForm Form
 * @author  Fernando de Pinho Araújo
 */
class turmaForm extends TPage
{
    protected $form; // form
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
    var $servico  = 'Turmas';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    private $cfg_vincula     = 'nivel_vincula_professor';
   
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
        $this->form = new TQuickForm('form_turma');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Gerenciamento de Atividades de Turma - Lançamentos e Edição');
        
        $fer = new TFerramentas();
        $curso = TSession::getValue('curso_militar');//Busca nas variáveis de seção o curso manuseado

        // create the form fields
        $id = new TEntry('id');
        
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('oculto','!=','S'));

        $curso_id = new TDBCombo('curso_id','sisacad','curso','id','nome','nome',$criteria);
        $nome = new TEntry('nome');
        $sigla = new TEntry('sigla');
        
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('uf','=','GO'));
        
        $cidade = new TDBCombo('cidade','sicad','cidades','nome','nome','nome',$criteria);
        $opm_id = new TDBCombo('opm_id','sicad','OPM','id','nome','nome');
        
        $tipo_turma = new TCombo('tipo_turma');
        $oculto = new TCombo('oculto');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $tipo_turma->addItems($fer->lista_tipos_curso());

        $oculto->setValue('N');
        $cidade->setValue('GOIÂNIA');
        $opm_id->setValue(48586);//Seta o CAPM com padrão

        // add the fields
        $this->form->addQuickField('Id', $id,  100 );
        $this->form->addQuickField('Curso Vinculado', $curso_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Nome', $nome,  400 , new TRequiredValidator);
        $this->form->addQuickField('Sigla', $sigla,  200 , new TRequiredValidator);
        $this->form->addQuickField('OPM responsável', $opm_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Cidade Sede', $cidade,  400 , new TRequiredValidator);
        $this->form->addQuickField('Tipo de Turma', $tipo_turma,  400 , new TRequiredValidator);
        $this->form->addQuickField('Encerrada?', $oculto,  120 );


        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        

        // create the form actions
        if ($this->nivel_sistema > 80)
        {
            $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
            $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
            $this->form->addQuickAction('Copia Disciplinas',  new TAction(array($this, 'onCopy')), 'fa:copy red');
        }
        else
        {
            $curso_id->setEditable(FALSE);
            $nome->setEditable(FALSE);
            $sigla->setEditable(FALSE);
            $cidade->setEditable(FALSE);
            $tipo_turma->setEditable(FALSE);
            $oculto->setEditable(FALSE);
            $opm_id->setEditable(FALSE);
        }
        $this->form->addQuickAction('Inscreve Alunos',  new TAction(array($this, 'onMatricula')), 'fa:user blue');
        //var_dump($this->config);
        if ($this->nivel_sistema >= $this->config[$this->cfg_vincula])
        {
            $this->form->addQuickAction('Designação de Professor',  new TAction(array($this, 'onDesigna')), 'fa:graduation-cap black');
        }
        $this->form->addQuickAction('Inclusão de Documentação', new TAction(array($this, 'onDocumento')), 'fa:download black');
        $this->form->addQuickAction('Edita Disciplinas',        new TAction(array($this, 'onEditaDisciplinas')), 'fa:exchange red');
        $this->form->addQuickAction('Resultados',               new TAction(array($this, 'onVerResultados')), 'fa:eye red');
        $this->form->addQuickAction('Professores',              new TAction(array($this, 'onRelatorioProfessores')), 'fa:users darkgray');
        $this->form->addQuickAction('Ranking',                  new TAction(array($this, 'onCriaRankingTurma')), 'fa:list-ol darkgray');       
        if (empty($curso))
        {
            $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('turmaList', 'onReload')), 'ico_back.png');
        }
        else
        {
            $this->form->addQuickAction('Retorna ao Curso',  new TAction(array($this, 'onReturn')), 'ico_back.png');

            // keep the form filled during navigation with session data
            $this->form->setData( TSession::getValue('turma_filter_data') );
            
            // creates a Datagrid
            $this->datagrid = new TDataGrid;
            $this->datagrid->style = 'width: 100%';
            //$this->datagrid->datatable = 'false';
    
            // creates the datagrid columns
            $column_check = new TDataGridColumn('check', '', 'center');
            $column_nome = new TDataGridColumn('nome', 'Nome da Turma', 'center');
            //$column_sigla = new TDataGridColumn('sigla', 'Sigla', 'center');
            $column_cidade = new TDataGridColumn('cidade', 'Cidade de Execução', 'center');
            $column_tipo_turma = new TDataGridColumn('tipo_turma', 'Tipo de Turma', 'center');
            $column_oculto = new TDataGridColumn('oculto', 'Oculto?', 'center');
    
            // add the columns to the DataGrid
            $this->datagrid->addColumn($column_check);
            $this->datagrid->addColumn($column_nome);
            //$this->datagrid->addColumn($column_sigla);
            $this->datagrid->addColumn($column_cidade);
            $this->datagrid->addColumn($column_tipo_turma);
            $this->datagrid->addColumn($column_oculto);
            
            // create EDIT action
            $action_edit = new TDataGridAction(array('turmaForm', 'onEdit'));
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
            if ($this->nivel_sistema>80)
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
            if ($this->nivel_sistema>80)
            {
                $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
            }
            
            $this->transformCallback = array($this, 'onBeforeLoad');
            
            $nome->setValue($curso->nome.' - Turma ');
            $sigla->setValue($curso->sigla.'-');
            $curso_id->setValue($curso->id);
            $curso_id->setEditable(false);
            $tipo_turma->setValue($curso->tipo_curso);
            $tipo_turma->setEditable(false);
            $oculto->setValue('N');
            $cidade->setValue('GOIÂNIA');
            $opm_id->setValue(48586);
        }
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turmaList'));
        $container->add($this->form);
        if (!empty($curso))
        {
            $table = new TTable();
            $frame = new TFrame();
            $frame->setLegend('Turmas já criadas para: '.$curso->nome);
            $frame->add($gridpack);
            $frame->add($this->pageNavigation);
            $container->add($frame);
       }
      
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('sisacad'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            
            $object = new turma;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            $object->nome = mb_strtoupper($object->nome,'UTF-8');
            $object->sigla = mb_strtoupper($object->sigla,'UTF-8');
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            $param=array();
            $param['offset']    =0;
            $param['first_page']=1;
            $this->onReload($param);
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        if (isset($param['chamado']))
        {
            if (!empty($param['chamado']))
            {
                $chamado = new TMantis();
                $chamado->fechaChamado(array('key'=>$param['chamado']));
            }
        }
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sisacad'); // open a transaction
                $object = new turma($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a edição do curso
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         $data = $this->form->getData();
         TApplication::loadPage('cursoForm','onEdit', array('key'=>$data->curso_id));
         //$this->form->setData($data);
    }//Fim Módulo
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        $curso = TSession::getValue('curso_militar');//Busca nas variáveis de seção o curso manuseado
        if (empty($curso))
        {
            return;
        }
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for turma
            $repository = new TRepository('turma');
            $limit = 15;
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
            
            $curso = TSession::getValue('curso_militar');//Busca nas variáveis de seção o curso manuseado
            if(!empty($curso))
            {
                $filter = new TFilter('curso_id', '=', $curso->id); // create the filter
                $criteria->add($filter); // add the session filter
            }
            /*$filter = new TFilter('oculto', '!=', 'S'); // create the filter
            $criteria->add($filter); // add the session filter*/
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                $fer = new TFerramentas();
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->oculto = $fer->lista_sim_nao($object->oculto);
                    $object->curso_id = $object->get_curso()->nome;
                    $object->tipo_turma = $fer->lista_tipos_curso($object->tipo_turma);
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
            $object = new turma($key, FALSE); // instantiates the Active Record
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
                    $object = new turma;
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
/*------------------------------------------------------------------------------
 *  Copia Matérias da Ementa
 *------------------------------------------------------------------------------*/
    public function onCopy ($param)
    {
     if ($param)
     {
         //var_dump($param);
     }
     $data = $this->form->getData();
     
     if (empty($data->id))
     {
         new TMessage('info','Por favor, salve primeiro antes de copiar as matérias da ementa!!!');
     }
     else
     {
        //var_dump($data);
        $this->Copy (array("deCurso"=> $data->curso_id,"paraTurma"=>$data->id));


     }
     $this->form->setData($data);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Excecuta a Copia Matérias da Ementa
 *------------------------------------------------------------------------------*/
    public function Copy ($param = array())
    {
        if ($param == null)
        {
            return false;
        }
        //var_dump($param);
        $materias = $this->getMateria($param['deCurso']);//Matérias cadastradas na Ementa do Curso
        $cadastro = $this->getTurmaMateria($param['paraTurma']);//Máterias já vinculadas à turma
        //var_dump($cadastro);
        if ($materias != false)//Verifica se há matérias na ementa
        {
            $gravar = array();
            foreach ($materias as $materia)
            {
                if ($cadastro != false)//Verifica se há cadastro no curso
                {
                    $achei = true;
                    foreach ($cadastro as $cad)
                    {
                        if ($materia->disciplina_id == $cad->disciplina_id)
                        {
                            $achei = false;
                        }
                    }
                }
                else
                {
                    $achei = true;
                }
                if ($achei)//Achei uma matéria no curso que não está na turma ainda
                {
                    $gravar[] = $materia;
                }
            }
            if (empty($gravar))
            {
                new TMessage('info','Todas matérias já foram vinculadas a essa turma');
            }
            else
            {
                $ret = $this->salvaMateriasTurma($param['paraTurma'],$gravar);
                if ($ret)
                {
                    new TMessage ('info','Matérias vinculadas à turma.');
                }
                else
                {
                    new TMessage ('info','Houve o problema ao vincular as matérias. Verifique.'); 
                }
            }
        }
        else
        {
            new TMessage ('info','Não há disciplinas na Ementa.<br>'.
                                 'Retorne no Curso e acrescente as disciplinas que o curso terá, após então,'.
                                 ' valide a ementa e retorne aqui.<br>OBS: Não esqueça de colocar a devida carga horária para cada disciplina.');
        }
        //var_dump($gravar);
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Excecuta a Copia Matérias da Ementa
 *------------------------------------------------------------------------------*/
    public function getMateria ($param = null)
    {
        $retorno = false;
        try
        {
            TTransaction::open('sisacad');
            //var_dump($param);
            $object = new curso($param);
            $retorno = $object->getmaterias_previstass();
            TTransaction::close();
            //var_dump($retorno);
            $retorno = (empty($retorno)) ? false : $retorno;
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $retorno;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Excecuta a Copia Matérias da Turma
 *------------------------------------------------------------------------------*/
    public function getTurmaMateria ($param = null)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $object = new turma($param);
            $retorno = $object->getmaterias();
            TTransaction::close();
            $retorno = (empty($retorno)) ? false : $retorno;
        }
        catch (Exception $e)
        {
            //new TMessage('error', $e->getMessage());
            TTransaction::rollback();
            $retorno = false;
        }
        return $retorno;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Excecuta a Copia Matérias da Turma
 *------------------------------------------------------------------------------*/
    public function salvaMateriasTurma($turma_id,$materias)
    {
        $retorno = true;
        foreach ($materias as $materia)
        {
            try
            {
                TTransaction::open('sisacad');
                $object = new materia();
                $object->turma_id = $turma_id;
                $object->disciplina_id = $materia->disciplina_id;
                $object->carga_horaria = $materia->carga_horaria;
                $object->store();
                TTransaction::close();
            }
            catch (Exception $e)
            {
                //new TMessage('error', $e->getMessage());
                TTransaction::rollback();
                $retorno = false;
            }
        }
        return $retorno;
    }//Fim Módulo

/*------------------------------------------------------------------------------
 *  Matricula Alunos
 *------------------------------------------------------------------------------*/
    public function onMatricula ($param)
    {
     if ($param)
     {
         //var_dump($param);
     }
     $data = $this->form->getData();
     
     if (empty($data->id))
     {
         new TMessage('info','Por favor, salve primeiro antes de matricular qualquer aluno na turma!!!');
     }
     else
     {
          TSession::setValue('turma_militar',$data);
          TApplication::loadPage('alunoForm','onEdit');
          //var_dump($data);
     }
     $this->form->setData($data);
        
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Excecuta a Copia Matérias da Ementa
 *------------------------------------------------------------------------------*/
    public function Matricula ($param)
    {
        
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Disponibilização de Professor
 *------------------------------------------------------------------------------*/
    public function onDesigna ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         
         if (empty($data->id))
         {
             new TMessage('info','Por favor, salve primeiro antes de designar professores!!!');
         }
         else
         {
              TSession::setValue('turma_militar',$data);
              TApplication::loadPage('turmaProfessorForm','onEdit',array('key'=>$data->id));
         }
         $this->form->setData($data);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Formulário para a Carga de Documentos
 *------------------------------------------------------------------------------*/
    public function onDocumento ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         
         if (empty($data->id))
         {
             new TMessage('info','Por favor, salve primeiro antes de anexar Documentos!!!');
         }
         else
         {
              TSession::setValue('turma_militar',$data);
              TApplication::loadPage('documentos_turmaList');
              //var_dump($data);
         }
         $this->form->setData($data);
    }
/*------------------------------------------------------------------------------
 *  Formulário para a edição das disciplinas
 *------------------------------------------------------------------------------*/
    public function onEditaDisciplinas ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         
         if (empty($data->id))
         {
             new TMessage('info','Por favor, salve primeiro antes de anexar editar qualquer disciplina!!!');
         }
         else
         {
              TSession::setValue('turma_militar',$data);
              TApplication::loadPage('editaEmentaTurmaForm','onEdit');
              //var_dump($data);
         }
         $this->form->setData($data);
    }
/*------------------------------------------------------------------------------
 *  Ver Resultados de Provas
 *------------------------------------------------------------------------------*/
    public function onVerResultados ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         
         if (empty($data->id))
         {
             new TMessage('info','Por favor, salve primeiro antes e tenha certeza que tenha avaliações para turma!!!');
         }
         else
         {
              TSession::setValue('turma_militar',$data);
              TApplication::loadPage('avaliacao_finalForm','onEdit',array('key'=>$data->id));
              //var_dump($data);
         }
         $this->form->setData($data);
    }//Fim módulo
/*------------------------------------------------------------------------------
 *    Relatório de Professores
 *------------------------------------------------------------------------------*/
     public static function onRelatorioProfessores ($param = null)
     {
         
         if (empty($param['id']))
         {
             new TMessage('info','Por favor, salve primeiro!!!');
         }
         else
         {
    
            $fer = new TFerramentas();
            $sis = new TSisacad();
            try
            {
                TTransaction::open('sisacad');
                $turma = new turma ($param['id']);
                //Busca todos professores que trabalham nas turmas
                //$sql1  = "(SELECT id FROM sisacad.turma WHERE curso_id = " . $param['id'] . ")";
                $sql2  = "(SELECT id FROM sisacad.materia WHERE turma_id =  " . $param['id'] . ")";
                $sql3  = "(SELECT professor_id FROM sisacad.professormateria WHERE materia_id IN " . $sql2 . ")";
                $professores = professor::where ('id','IN',$sql3)->orderBy('nome','ASC')->load();
                
                //Definições das tabelas
                $head_p     = array('Identificação','Relação');//cabeçalho principal
                $head_m     = array('Turma - Disciplina');//Cabeçalho da sub-tablea
                $cabecalho  = '<h4>Relatório de Professores da turma: ' . $turma->nome . '(' . $turma->curso->sigla . ')';//Cabeçalho do Relatório
                //Lista de Professores
                $lista_p    = array();
                if (!empty($professores))
                {
                    foreach ($professores as $professor)
                    {
                        $dado = array();//Lista de dados para item de tabela
                        
                        $dado['identificacao'] = $professor->getidentificacao('!P!N!C!O') ;//Monta nome do professor
                        //ativa filtro para pegar só as matérias que estão no curso
                        $filtro = new TFilter('materia_id','IN',$sql2);
                        $materias = $professor->getmaterias($filtro);
                        //Monta sub-tabela com os disciplinas do professor no curso
                        if (!empty($materias))
                        {
                            $lista_t = array();//Lista de matérias
                            foreach ($materias as $materia)
                            {
                                $turma      = $materia->materia->turma->sigla;
                                $disciplina = $materia->materia->disciplina->nome;
                                $turma      = (!empty($turma))      ? $turma      : 'NC';
                                $disciplina = (!empty($disciplina)) ? $disciplina : 'NC';
                                $lista_t[]  = $turma . '  -||-  ' . $disciplina;//Inclui o nome da turma e disciplina
                            }
                            asort($lista_t);//Põe tudo em ordem alfabética
                            //Remonta a listagem para montar a sub-tabela
                            $lista_m = array();
                            foreach($lista_t as $l_t)
                            {
                                $lista_m[] = array($l_t);
                            }
                            $tabela_m       = $fer->geraTabelaHTML($lista_m,$head_m,array('tab'=>'border="1px" '.
                                                                'bordercolor="black" width="100%" '.
                                                                'style="border-collapse: collapse;"',
                                                                'cab'=>'style="background: lightblue; text-align: center;"',
                                                                'cel'=>'style="background: blue;"'));
                        }
                        else
                        {
                            $tabela_m       = '--  SEM DISCIPLNAS NESTE CURSO --';
                        }
                        $dado['disciplina'] = $tabela_m;//Adiciona ao professor sua tabela de disciplinas no curso
                        $lista_p[] = $dado;
                    }
                    $tabela_p       = $fer->geraTabelaHTML($lista_p,$head_p,array('tab'=>'border="1px" '.
                                                        'bordercolor="black" width="100%" '.
                                                        'style="border-collapse: collapse;"',
                                                        'cab'=>'style="background: lightblue; text-align: center;"',
                                                    'cel'=>'style="background: blue;"'));
                    //Abre janela com a pesquisa
                    if (!empty($tabela_p))
                    {
                        $rel = new TBdhReport();
                        $bot = $rel->scriptPrint();
                        $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                               '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                                $cabecalho . '<br></center>';
                        $botao = '<center>'.$bot['botao'].'</center>';
                        $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela_p . '</div>' . $botao;
                        $window = TWindow::create('Relatório de Professores', 1000, 500);
                        $window->add($tabela);
                        $window->show();
                    }
                }
                else
                {
                    throw new Exception ('Não há professores vinculados à turma ' . $turma->nome);    
                }
                    
                TTransaction::close();
            }
            catch (Exception $e)
            {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
    
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Ver Resultados de Provas
 *------------------------------------------------------------------------------*/
    public static function onCriaRankingTurma ($param = null)
    {
         $fer = new TFerramentas;
         try
         {
             if (empty($param['id']))
             {
                 throw new Exception('Por favor, salve a turma primeiro!!!');
             }
             TTransaction::open('sisacad');
             TTransaction::setLogger(new TLoggerTXT('tmp/turmaFormRanking.txt'));
             $criteria  = new TCriteria;
             $criteria->add(new TFilter('turma_id','=',$param['id']));
             $criteria->add(new TFilter('oculto','=','S'));
             
             $materias  = new TRepository('materia');
             $count     = $materias->count($criteria);
             if ($count <= 0)
             {
                 throw new Exception('Nenhuma Materia da turma foi encerrada ainda!');
             }
             
             //Verificar se já existe um ranking e pegar sua id, se não, cria um ranking novo             
             $ranking = avaliacao_ranking::where('turma_id','=',$param['id'])->load();
             if (count($ranking) === 0)
             {
                 $ranking = new avaliacao_ranking;
                 $ranking->data_fim             = date('Y-m-d');
                 $ranking->data_atualizado      = date('Y-m-d');
                 $ranking->usuario              = TSession::getValue('login');
                 $ranking->usuario_atualizador  = TSession::getValue('login');
                 $ranking->oculto               = 'N';
                 $ranking->turma_id             = $param['id'];
                 $ranking->store();
             }
             else
             {
                 $ranking = $ranking[0];
                 $ranking->usuario_atualizador = TSession::getValue('login');
                 $ranking->data_atualizado     = date('Y-m-d');
                 $ranking->store();
                 //Apagar a relação de alunos do ranking
                 $rankers = avaliacao_rankingaluno::where('avaliacao_ranking_id','=',$ranking->id)->delete();
             }
             
             //Busca a lista de alunos para fazer a média
             $alunos = aluno::where('turma_id','=',$param['id'])->load();
             if (count($alunos) == 0)
             {
                 throw new Exception('Aluno Matriculado na Turma!');
             }
             
             //Calcula a média de cada aluno e salva no avaliacao_rankingaluno
             $lista_ap = array();
             $nota_ap  = array();
             $lista_rp = array();
             $nota_rp  = array();
             foreach ($alunos as $aluno)
             {
                 //Query para pesquisa da nota
                 $sql1 = "SELECT AVG(nota) FROM sisacad.avaliacao_finalaluno WHERE aluno_id = " . $aluno->id ;
                 $sql2 = "SELECT COUNT(aprovado) FROM sisacad.avaliacao_finalaluno WHERE aluno_id = " . $aluno->id .
                         " AND aprovado != 'S'";
                         
                 $sql3 = "SELECT DISTINCT recuperado FROM sisacad.avaliacao_finalaluno WHERE aluno_id = " . $aluno->id .
                         " AND recuperado = 'S'";
                 $sqlf = "SELECT (" . $sql1 . ") AS nota, (" . $sql2 . ") AS reprovado, (" . $sql3 . ") AS recuperado";
                 /*
                  * nota       = média das notas do aluno
                  * recuperado = se passou por recuperação (muda de lista)
                  * reprovado  = quantidade de disciplinas que reprovou
                  */
                 $conn   = TTransaction::get(); // obtém a conexão
                 $sth    = $conn->prepare($sqlf);
                 $sth->execute();
                 $result = $sth->fetchAll();
                 $nota   = 0;
                 if (is_array($result))
                 {
                     $result                   = $result[0];
                 }
                 //Trabalha dados
                 $nota                         = $result['nota'];
                 $recuperado                   = $result['recuperado'];
                 $recuperado                   = (is_null($recuperado) || $recuperado == '') ? 'N' : $recuperado; 
                 $reprovado                    = $result['reprovado'];
                 //Prepara array com lista de itens de cada aluno
                 $dado                         = array();
                 $dado['aluno_id']             = $aluno->id;
                 $dado['avaliacao_ranking_id'] = $ranking->id;
                 $dado['cpf']                  = $aluno->cpf;
                 $dado['nota']                 = $nota;
                 $dado['recuperado']           = $recuperado;
                 $dado['reprovado']            = $reprovado;
                 $lista[$nota]                 = $dado;
                 //Se foi reprovado ou passou por recuperação, troca a lista
                 if ($reprovado > 0 || $recuperado == 'S')
                 {
                     $lista_rp[] = $dado;
                     $nota_rp[]  = $nota;
                 }
                 else
                 {
                     $lista_ap[] = $dado;
                     $nota_ap[]  = $nota;
                 }
             }
             //Salva o avaliacao_rankingaluno memorizando a média, o aluno_id e o cpf do aluno
             //Faz a classificação
             array_multisort($nota_ap,$lista_ap);//Classifica os aprovados
             array_multisort($nota_rp,$lista_rp);//Classifica os reprovados
             $lista = array_merge_recursive($lista_ap,$lista_rp);//Funde as duas listas
             $posicao = 1;
             foreach ($lista as $l)
             {
                 $l['posicao'] = $posicao;
                 $posicao ++;
                 $ranker       = new avaliacao_rankingaluno();
                 $ranker->fromArray($l);
                 $ranker->store();
             }
             //Monta a visualização do ranking
            //Definições das tabelas
            $head_p     = array('Posição','Identificação','Status','Nota');//cabeçalho principal
            //$head_m     = array('Turma - Disciplina');//Cabeçalho da sub-tablea
            $cabecalho  = '<h4>Ranking dos Alunos da Turma: ' . $ranking->turma->nome . '(' . $ranking->turma->curso->sigla . ')';//Cabeçalho do Relatório
            //Lista de Alunos
            $alunos = $ranking->getavaliacao_rankingalunos(array('ordem'=>'nota','sentido'=>'ASC'));
            $lista_p    = array();
            if (!empty($alunos))
            {
                foreach ($alunos as $aluno)
                {
                    $dado = array();//Lista de dados para item de tabela
                    
                    $dado['posicao']       = $aluno->posicao;
                    $dado['identificacao'] = $aluno->aluno->getidentificacao() ;//Monta nome do aluno
                    
                    $reprovado             = $aluno->reprovado;
                    $reprovado             = ($reprovado == 'N') ? 'APROVADO' : 'REPROVADO';
                    
                    $dado['status']        = $reprovado;
                    $dado['nota']          = $aluno->nota;
                    $lista_p[] = $dado;
                }
                $tabela_p       = $fer->geraTabelaHTML($lista_p,$head_p,array('tab'=>'border="1px" '.
                                                    'bordercolor="black" width="100%" '.
                                                    'style="border-collapse: collapse;"',
                                                    'cab'=>'style="background: lightblue; text-align: center;"',
                                                'cel'=>'style="background: blue;"'));
                //Abre janela com a pesquisa
                if (!empty($tabela_p))
                {
                    $rel = new TBdhReport();
                    $bot = $rel->scriptPrint();
                    $cab = '<center>ESTADO DE GOIÁS<br>POLÍCIA MILITAR DO ESTADO DE GOIÁS'.
                           '<h5>COMANDO DA ACADEMIA DE POLICIAL MILITAR - CAPM</h5>'.
                            $cabecalho . '<br></center>';
                    $botao = '<center>'.$bot['botao'].'</center>';
                    $tabela = $bot['codigo'] . '<div id="relatorio">' . $cab . $tabela_p . '</div>' . $botao;
                    $window = TWindow::create('Ranking de Alunos', 1000, 500);
                    $window->add($tabela);
                    $window->show();
                }
            }
            else
            {
                throw new Exception ('Não há alunos vinculados à turma ' . $ranking->turma->nome);    
            }
             
             
             TTransaction::close();
         }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }

    }//Fim módulo
}//Fim Classe

