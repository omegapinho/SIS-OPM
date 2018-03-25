<?php
/**
 * alunoForm Form
 * @author  <your name here>
 */
class alunoForm extends TPage
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
        $this->form = new TQuickForm('form_aluno');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Gestão do Corpo de Alunos - Edição de Matrícula');

        $turma = TSession::getValue('turma_militar');//Busca nas variáveis de seção o curso manuseado

        // create the form fields
        $id = new TEntry('id');
        
        $criteria = new TCriteria();
        $criteria->add (new TFilter ('oculto','!=','S'));
        
        $turma_id = new TDBCombo('turma_id','sisacad','turma','id','nome','nome',$criteria);
        $cpf = new TText('cpf');
        $status = new TCombo('status');
        $resultado = new THidden('resultado');
        $restricao = new TCombo('restricao');

        //Valores
        $status->addItems($fer->lista_status_aluno());
        $restricao->addItems($fer->lista_status_saude());
        $restricao->setValue('APT');
        
        //$resultado->addItems($fer->lista_resultado_aluno());

        // add the fields
        $this->form->addQuickField('Id', $id,  80 );
        $this->form->addQuickField('Turma', $turma_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('CPF/RG Militar', $cpf,  400 , new TRequiredValidator);
        $this->form->addQuickField('Status', $status,  200 , new TRequiredValidator);
        $this->form->addQuickField('Avaliação de Saúde', $restricao,  200 , new TRequiredValidator);
        $this->form->addQuickField('Resultado', $resultado,  400 );
        

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        $cpf->setTip('Inclua vários alunos separando-os por vírgula. Use RG do militar ou seu CPF para inclusão em lote');
        $cpf->setSize(400,60);
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        
        if (empty($turma))
        {
            $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('alunoList', 'onReload')), 'ico_back.png');
        }
        else
        {
            
            $this->form->addQuickAction('Retorna a Turma',  new TAction(array($this, 'onReturn')), 'ico_back.png');
            
            // creates a Datagrid
            $this->datagrid = new TDataGrid;
            
            $this->datagrid->style = 'width: 100%';
            $this->datagrid->datatable = 'false';
            // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
            
    
            // creates the datagrid columns
            $column_check = new TDataGridColumn('check', '', 'center');
            $column_status = new TDataGridColumn('status', 'Status', 'center');
            $column_cpf = new TDataGridColumn('cpf', 'CPF', 'center');
            $column_resultado = new TDataGridColumn('resultado', 'Resultado', 'center');
            $column_turma_id = new TDataGridColumn('turma_id', 'Turma', 'center');
    
    
            // add the columns to the DataGrid
            $this->datagrid->addColumn($column_check);
            $this->datagrid->addColumn($column_turma_id);
            $this->datagrid->addColumn($column_cpf);
            $this->datagrid->addColumn($column_status);
            $this->datagrid->addColumn($column_resultado);

            // create EDIT action
            $action_edit = new TDataGridAction(array('alunoForm', 'onEdit'));
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
            
            $turma_id->setValue($turma->id);
            $turma_id->setEditable(false);
        }
        
        $status->setValue('EMC');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'alunoList'));
        $container->add($this->form);
        if (!empty($turma))
        {
            $table = new TTable();
            $frame = new TFrame();
            $frame->setLegend('Alunos já matriculados para: '.$turma->nome);
            $frame->add($gridpack);
            $frame->add($this->pageNavigation);
            $container->add($frame);
       }
        
        parent::add($container);

    }//Fim Módulo

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        
        $data = $this->form->getData(); // get form data as array
        if (!empty($data->id))//Edição de UM aluno
        {
            try
            {
                TTransaction::open('sisacad'); // open a transaction
                $this->form->validate(); // validate form data
                $object = new aluno;  // create an empty object
                $object->fromArray( (array) $data); // load the object with data
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
        else //Cadastro de NOVOS Alunos
        {
            $cad = $data->cpf;
            if (strpos($cad,',')) //Matrícula em Lote
            {
                $alunos = explode(',',$data->cpf);
                $report = new TRelatorioOP();
                $report->mSucesso = ' Matriculado com sucesso.';
                $report->mFalha   = ' não foi Matriculado. Verifique o CFP/RG se está correto.';
                //$report->addMensagem('- O PM RG '.$rgmilitar,$ret);
                foreach ($alunos as $aluno)
                {
                    $dados = $this->getDadosAluno($aluno);
                    if (false == $dados)
                    {
                        $men = 'O aluno portador do ';
                        $men.= (strlen($aluno)==11) ? 'CPF ' : 'RG';
                        $men.= $aluno.' (não localizado)';
                        $report->addMensagem($men,false);
                    }
                    else
                    {
                        try
                        {
                            TTransaction::open('sisacad'); // open a transaction
                            $this->form->validate(); // validate form data
                            $object = new aluno;  // create an empty object
                            $object->turma_id  = $data->turma_id;
                            $object->cpf       = $dados->cpf;
                            $object->status    = $data->status;
                            $object->restricao = $data->restricao;
                            $object->resultado = $data->resultado;
                            $object->store(); // save the object
                            TTransaction::close(); // close the transaction
                            $men = 'O aluno portador do ';
                            $men.= (strlen($aluno)==11) ? 'CPF ' : 'RG';
                            $men.= $aluno . ' ' . $dados->postograd.' '.$dados->nome;
                            $report->addMensagem($men,true);
                        }
                        catch (Exception $e) // in case of exception
                        {
                            $men = 'O aluno portador do ';
                            $men.= (strlen($aluno)==11) ? 'CPF ' : 'RG';
                            $men.= $aluno . ' ' . $dados->postograd.' '.$dados->nome;
                            $report->addMensagem($men,false);
                            TTransaction::rollback(); // undo all pending operations
                        }
                    }
                }//Fim Foreach
                $param=array();
                $param['offset']    =0;
                $param['first_page']=1;
                $this->onReload($param);
                $report->publicaRelatorio('info');
            }
            else //Matricula só de um aluno
            {
                $aluno = $data->cpf;
                $dados = $this->getDadosAluno($aluno);
                if (false == $dados)
                {
                    $men = 'O aluno portador do ';
                    $men.= (strlen($aluno)==11) ? 'CPF ' : 'RG';
                    $men.= $aluno . ' Não foi localizado. Verifique.';
                    new TMessage('info',$men);
                }
                else
                {
                    try
                    {
                        TTransaction::open('sisacad'); // open a transaction
                        $this->form->validate(); // validate form data
                        $object = new aluno;  // create an empty object
                        $object->turma_id  = $data->turma_id;
                        $object->cpf       = $dados->cpf;
                        $object->status    = $data->status;
                        $object->restricao = $data->restricao;
                        $object->resultado = $data->resultado;
                        $object->store(); // save the object
                        TTransaction::close(); // close the transaction
                        $men = 'O aluno portador do ';
                        $men.= (strlen($aluno)==11) ? 'CPF ' : 'RG';
                        $men.= $aluno . ' ' . $dados->postograd.' '.$dados->nome;
                        $men.= ' foi matriculado com sucesso.';
                        $param=array();
                        $param['offset']    =0;
                        $param['first_page']=1;
                        $this->onReload($param);
                        
                        new TMessage('info',$men);
                    }
                    catch (Exception $e) // in case of exception
                    {
                        $men = 'O aluno portador do ';
                        $men.= (strlen($aluno)==11) ? 'CPF ' : 'RG';
                        $men.= $aluno . ' Não foi matriculado. Verifique.';
                        new TMessage('info',$men);
                        $this->form->setData($data);
                        TTransaction::rollback(); // undo all pending operations
                    }
                }
            }
        }
    }
/*------------------------------------------------------------------------------
 *  Busca dados do Aluno
 *------------------------------------------------------------------------------*/
    public function getDadosAluno ($param = null)
    {
        if ($param != null)
        {
            try
            {
                TTransaction::open('sicad'); // open a transaction
                if (strlen($param) == 11)
                {
                    $objects = servidor::where('cpf','=',$param)->load();
                }
                else
                {
                    $objects = servidor::where('rgmilitar','=',$param)->load();
                }
                TTransaction::close(); // close the transaction
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $ret = $object;
                    }
                    //var_dump($object);
                    return $object;
                }
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return false;
    }//Fim Módulo
    
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
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sisacad'); // open a transaction
                $object = new aluno($key); // instantiates the Active Record
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
    }
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        $turma = TSession::getValue('turma_militar');//Busca nas variáveis de seção o curso manuseado
        if (empty($turma))
        {
            return;
        }
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for aluno
            $repository = new TRepository('aluno');
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
            
            $turma = TSession::getValue('turma_militar');//Busca nas variáveis de seção o curso manuseado
            $filter = new TFilter('turma_id', '=', $turma->id); // create the filter
            $criteria->add($filter); // add the session filter
            
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
                $sis = new TSisacad();
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // Conversões
                    $object->turma_id  = $object->get_turma()->sigla;
                    $object->status    = $fer->lista_status_aluno($object->status);
                    $object->resultado = $fer->lista_resultado_aluno($object->resultado);
                    
                    $dados = $sis->getDadosAluno($object->cpf);//Busca dados do Aluno para preencher campo
                    $ident = ($dados!=false) ? $dados->postograd . ' ' . $dados->nome . ', CPF '.$dados->cpf : 'Dados do Aluno não localizado.';
                    
                    $object->cpf = $ident;
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
            TTransaction::open('sisacad'); // open a transaction with database
            $object = new aluno($key, FALSE); // instantiates the Active Record
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
                    $object = new aluno;
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
/*------------------------------------------------------------------------------
 *  
 *------------------------------------------------------------------------------*/
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('alunoList_filter_status',   NULL);
        TSession::setValue('alunoList_filter_cpf',   NULL);
        TSession::setValue('alunoList_filter_resultado',   NULL);
        TSession::setValue('alunoList_filter_restricao',   NULL);
        TSession::setValue('alunoList_filter_turma_id',   NULL);

        /*if (isset($data->status) AND ($data->status)) {
            $filter = new TFilter('status', '=', "$data->status"); // create the filter
            TSession::setValue('alunoList_filter_status',   $filter); // stores the filter in the session
        }


        if (isset($data->cpf) AND ($data->cpf)) {
            $filter = new TFilter('cpf', 'like', "%{$data->cpf}%"); // create the filter
            TSession::setValue('alunoList_filter_cpf',   $filter); // stores the filter in the session
        }


        if (isset($data->resultado) AND ($data->resultado)) {
            $filter = new TFilter('resultado', '=', "$data->resultado"); // create the filter
            TSession::setValue('alunoList_filter_resultado',   $filter); // stores the filter in the session
        }
        
        if (isset($data->restricao) AND ($data->restricao)) {
            $filter = new TFilter('restricao', '=', "$data->restricao"); // create the filter
            TSession::setValue('alunoList_filter_restricao',   $filter); // stores the filter in the session
        }*/


        if (isset($data->turma_id) AND ($data->turma_id)) {
            $filter = new TFilter('turma_id', '=', "$data->turma_id"); // create the filter
            TSession::setValue('alunoList_filter_turma_id',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('aluno_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }//Fim Módulo
}
