<?php
/**
 * servidor_novoList Listing
 * @author  <your name here>
 */
class servidor_novoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    protected $nivel_sistema = false;  //Registra o nível de acesso do usuário
    protected $config        = array();//Array com configuração
    protected $config_load   = false;  //Informa que a configuração está carregada

    var $sistema  = 'SIS-PESSOAL';          //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Servidores Novos';      //Nome da página de serviço.
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

        $fer = new TFerramentas();
        
        if (!$this->nivel_sistema || $this->config_load == false)    //Carrega OPMs que tem acesso
        {
            $this->nivel_sistema = $fer->getnivel (get_class($this));//Verifica qual nível de acesso do usuário
            //$this->listas        = $sicad->get_OpmsRegiao($this->opm_operador);//Carregas as OPMs que o usuário administra
            $this->config = $fer->getConfig($this->sistema);         //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                               //Informa que configuração foi carregada
        }

        // creates the form
        $this->form = new TQuickForm('form_search_servidor_novo');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Listagem dos Militares recém incluidos');
        

        // create the form fields
        $postograd = new TCombo('postograd');
        $nome = new TEntry('nome');
        $cpf = new TEntry('cpf');
        $nomemae = new TEntry('nomemae');
        $dtnascimento = new TDate('dtnascimento');


        // add the fields
        $this->form->addQuickField('Posto/Graduação', $postograd,  140 );
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('CPF', $cpf,  100 );
        $this->form->addQuickField('Mãe', $nomemae,  400 );
        $this->form->addQuickField('Aniversário', $dtnascimento,  100 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('servidor_novo_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        if ($this->nivel_sistema!='S')
        {
            $this->form->addQuickAction(_t('New'),  new TAction(array('servidor_novoForm', 'onEdit')), 'bs:plus-sign green');
            $this->form->addQuickAction('Importar', new TAction(array($this,'onImporte')), 'fa:download red');
        }
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_postograd = new TDataGridColumn('postograd', 'Posto/Graduação', 'center');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_cpf = new TDataGridColumn('cpf', 'CPF', 'center');
        $column_nomemae = new TDataGridColumn('nomemae', 'Mãe', 'left');
        $column_dtnascimento = new TDataGridColumn('dtnascimento', 'Aniversário', 'right');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_postograd);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_cpf);
        $this->datagrid->addColumn($column_nomemae);
        $this->datagrid->addColumn($column_dtnascimento);


        // creates the datagrid column actions
        $order_postograd = new TAction(array($this, 'onReload'));
        $order_postograd->setParameter('order', 'postograd');
        $column_postograd->setAction($order_postograd);
        
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_nomemae = new TAction(array($this, 'onReload'));
        $order_nomemae->setParameter('order', 'nomemae');
        $column_nomemae->setAction($order_nomemae);
        
        $order_dtnascimento = new TAction(array($this, 'onReload'));
        $order_dtnascimento->setParameter('order', 'dtnascimento');
        $column_dtnascimento->setAction($order_dtnascimento);
        

        // define the transformer method over image
        $column_nome->setTransformer( function($value, $object, $row) {
            return strtoupper($value);
        });
        // define the transformer method over image
        $column_nomemae->setTransformer( function($value, $object, $row) {
            return strtoupper($value);
        });
        // define the transformer method over image
        $column_dtnascimento->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });


        
        // create EDIT action
        $action_edit = new TDataGridAction(array($this, 'onEdit'));
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


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
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
            $object = new servidor_novo($key); // instantiates the Active Record
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
        TSession::setValue('servidor_novoList_filter_postograd',   NULL);
        TSession::setValue('servidor_novoList_filter_nome',   NULL);
        TSession::setValue('servidor_novoList_filter_cpf',   NULL);
        TSession::setValue('servidor_novoList_filter_nomemae',   NULL);
        TSession::setValue('servidor_novoList_filter_dtnascimento',   NULL);

        if (isset($data->postograd) AND ($data->postograd)) {
            $filter = new TFilter('postograd', '=', "$data->postograd"); // create the filter
            TSession::setValue('servidor_novoList_filter_postograd',   $filter); // stores the filter in the session
        }


        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('servidor_novoList_filter_nome',   $filter); // stores the filter in the session
        }


        if (isset($data->cpf) AND ($data->cpf)) {
            $filter = new TFilter('cpf', '=', "$data->cpf"); // create the filter
            TSession::setValue('servidor_novoList_filter_cpf',   $filter); // stores the filter in the session
        }


        if (isset($data->nomemae) AND ($data->nomemae)) {
            $filter = new TFilter('nomemae', 'like', "%{$data->nomemae}%"); // create the filter
            TSession::setValue('servidor_novoList_filter_nomemae',   $filter); // stores the filter in the session
        }


        if (isset($data->dtnascimento) AND ($data->dtnascimento)) {
            $filter = new TFilter('dtnascimento', '>=', "$data->dtnascimento"); // create the filter
            TSession::setValue('servidor_novoList_filter_dtnascimento',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('servidor_novo_filter_data', $data);
        
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
            
            // creates a repository for servidor_novo
            $repository = new TRepository('servidor_novo');
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
            

            if (TSession::getValue('servidor_novoList_filter_postograd')) {
                $criteria->add(TSession::getValue('servidor_novoList_filter_postograd')); // add the session filter
            }


            if (TSession::getValue('servidor_novoList_filter_nome')) {
                $criteria->add(TSession::getValue('servidor_novoList_filter_nome')); // add the session filter
            }


            if (TSession::getValue('servidor_novoList_filter_cpf')) {
                $criteria->add(TSession::getValue('servidor_novoList_filter_cpf')); // add the session filter
            }


            if (TSession::getValue('servidor_novoList_filter_nomemae')) {
                $criteria->add(TSession::getValue('servidor_novoList_filter_nomemae')); // add the session filter
            }


            if (TSession::getValue('servidor_novoList_filter_dtnascimento')) {
                $criteria->add(TSession::getValue('servidor_novoList_filter_dtnascimento')); // add the session filter
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
                $postograd = array(''=>'','307'=>'Aluno Soldado','267'=>'Cadete 1º Ano');
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->postograd = $postograd[$object->postograd];
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
        if ($this->nivel_sistema<80)
        {
            return;
        }
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
            $object = new servidor_novo($key, FALSE); // instantiates the Active Record
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
        if ($this->nivel_sistema<80)
        {
            return;
        }
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
                    $object = new servidor_novo;
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
    public function onEdit ($param = null)
    {
        $data = $this->form->getData();
        if (!empty($data))
        {
            TSession::setValue('keyuser',$param['id']);
            TApplication::loadPage('servidor_novoForm','onEdit');
        }
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *  Rotina: Importa Novatos
 *---------------------------------------------------------------------------------------*/
    public function onImporte ($param = null)
    {
        $postograd = new TCombo('postograd');
        $arquivo   = new TFile('arquivo');
        $criteria = new TCriteria;
        $criteria->add( new TFilter('oculto','!=','S'));
        
        $turma1    = new TDBCombo('turma1','sisacad','turma','id','nome','nome',$criteria);
        $turma2    = new TDBCombo('turma2','sisacad','turma','id','nome','nome',$criteria);
        $turma3    = new TDBCombo('turma3','sisacad','turma','id','nome','nome',$criteria);
        $opm_id    = new TDBCombo('opm_id','sicad','OPM','id','nome','nome');
        
        //Valores
        $postograd->addItems(array('307'=>'Aluno Soldado','267'=>'Cadete 1º Ano'));
        
        //Tamanho
        $arquivo->setSize(300);
        $postograd->setSize(300);
        $opm_id->setSize(300);
        $turma1->setSize(200);
        $turma2->setSize(200);
        $turma3->setSize(200);
       
        $arquivo->setProperty('accept','application/csv');//Aceitar somente PDF

        $form = new TForm('input_form');
        $form->style = 'padding:20px';
        
        $table = new TTable;
        $table->addRowSet( $lbl = new TLabel('Arquivo: '), $arquivo );
        $lbl->setFontColor('red');
        $table->addRowSet( $lbl = new TLabel('Posto/Graduação: '), $postograd );
        $lbl->setFontColor('red');
        $table->addRowSet( $lbl = new TLabel('Turma - 1: '), $turma1 );
        $lbl->setFontColor('red');
        $table->addRowSet( new TLabel('Turma - 2: '), $turma2 );
        $table->addRowSet( new TLabel('Turma - 3: '), $turma3 );
        $table->addRowSet( $lbl = new TLabel('OPM: '), $opm_id );
        $lbl->setFontColor('red');
        
        $form->setFields(array($arquivo));
        $form->add($table);
        
        // show the input dialog
        $action = new TAction(array($this, 'onConfirm'));
        $action->setParameter('stay-open', 1);
        new TInputDialog('Importar dados de Alunos', $form, $action, 'Confirma');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Rotina: Cadastra Alunos
 *------------------------------------------------------------------------------*/
    public static function onConfirm( $param )
    {
        //var_dump($param);echo "<br>";
        //$cfg = TSession::getValue('SISACAD_CONFIG');
        if ((isset($param['arquivo']) && $param['arquivo'])) // validate required field
        {
            $fer = new TFerramentas();
            $sicad = new TSicadDados();
            $file = 'tmp/'.$param['arquivo'];
            $linhas = $fer->csv_in_array($file,";", "\"", true );
            $report = new TRelatorioOP();
            $report->mSucesso = ' Cadastrado com sucesso.';
            $report->mFalha   = ' não foi Cadastrado. Verifique o CFP/RG se está correto ou se não repete.';
            set_time_limit ( 180 );
            try
            {
                // open a transaction with database
                //'codigomaoqueescreve'=>array('107'=>'AMBAS','106'=>'DIREITA','105'=>'ESQUERDA'),
                TTransaction::open('sisacad');
                $lotacao = new OPM($param['opm_id']);
                $lista = array();
                foreach ($linhas as $linha)
                {
                    //Repara o CPF caso ele venha com menos de 11 digitos
                    $cpf    = $fer->soNumeros($linha['cpf']);
                    //var_dump($linha);
                    if (strlen($cpf)<11)
                    {
                        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
                        $linha['cpf'] = $cpf;
                    }
                    $valido = $fer->isValidCPF($cpf);
                    if ($valido == true)
                    {
                        //Verifica se o servidor já existe
                        $loc = servidor::where('cpf','=',$cpf)->load();
                        if (empty($loc) || empty($cpf))
                        {   
                            $master = new servidor;
                            $master->fromArray($linha);
    
                            $sangue = $master->tiposangue;
                            $codcnh = $sicad->dados_servidor('categoriacnh',$master->categoriacnh);
                            $codcnh = ($codcnh !=false) ? $codcnh : null;
    
                            $mao = (isset($linha['codigomaoqueescreve'])) ? self::codigoMao($linha['codigomaoqueescreve']) : null; 
                            
                            $master->cpf                    = $cpf;
                            $master->postograd              = mb_strtoupper($sicad->caracteristicas_SICAD('postograd',$param['postograd']),'UTF-8');
                            $master->unidadeid              = $lotacao->id;
                            $master->unidade                = $lotacao->nome;
                            $master->siglaunidade           = $lotacao->sigla;
                            $master->codigomaoqueescreve    = $mao;
                            $master->dtexpedicaocnh         = $fer->confereData($master->dtexpedicaocnh);
                            $master->dtexpedicaorg          = $fer->confereData($master->dtexpedicaorg);
                            $master->dtnascimento           = $fer->confereData($master->dtnascimento);
                            $master->dtvalidadecnh          = $fer->confereData($master->dtvalidadecnh);
                            $master->dtexpedicaoreservista  = $fer->confereData($master->dtexpedicaoreservista);
                            $master->dtpromocao             = $fer->confereData($master->dtpromocao);
                            $master->codcategoriacnh        = $codcnh;
                            $master->cnh                    = $fer->soNumeros($master->cnh);
                            $master->pispasep               = $fer->soNumeros($master->pispasep);
                            $master->reservista             = $fer->soNumeros($master->reservista);
                            $master->tituloeleitor          = $fer->soNumeros($master->tituloeleitor);
                            $master->zonatituloeleitor      = $fer->soNumeros($master->zonatituloeleitor);
                            $master->secaotituloeleitor     = $fer->soNumeros($master->secaotituloeleitor);
                            $master->codigotipocabelo       = $fer->codigoCaracteristica($master->codigotipocabelo,'codigotipocabelo');
                            $master->codigocorolho          = $fer->codigoCaracteristica($master->codigocorolho,'codigocorolho');
                            $master->codigocorcabelo        = $fer->codigoCaracteristica($master->codigocorcabelo,'codigocorcabelo');
                            $master->codigocorpele          = $fer->codigoCaracteristica($master->codigocorpele,'codigocorpele');
                            $master->tiposangue             = $fer->codigoSangue($sangue,'tiposangue');
                            $master->fatorrh                = $fer->codigoSangue($sangue,'fatorrh');
                            $master->sexo                   = $fer->codigoCaracteristica($master->sexo,'sexo');
                            $master->altura                 = $fer->alturaPeso($master->altura, 'altura');
                            $master->peso                   = $fer->alturaPeso($master->peso,'peso');
                            $master->status                 = 'ATIVO';
                            
                            $master->store(); // save master object
                            //var_dump($master);echo '<br>';
                            $lista[] = $master->cpf;
                            $men = 'O Aluno, novo servidor, '.$master->nome.' CPF '.$master->cpf . ' cadastrado.';
                            $report->addMensagem($men,true);
                        }
                        else
                        {
                            $men = 'O Aluno, novo servidor, '.$linha['nome'].' CPF '.$linha['cpf'] . ' já foi cadastrado uma vez';
                            $report->addMensagem($men,false);
                            foreach ($loc as $l)
                            {
                                $id = $l->cpf;
                            }
                            $lista[] = $id;
                        }
                    }
                    else
                    {
                        $men = 'O Aluno, novo servidor, '.$linha['nome'].' CPF '.$linha['cpf'] . ' está com CPF INVÁLIDO.';
                        $report->addMensagem($men,false);
                    }
                }//Fim Foreach
                //Matrícula
                $status    = 'EMC';
                $restricao = 'APT';
                $turmas = array();
                if ($param['turma1']!=null)
                {
                    $turmas[] = $param['turma1'];
                }
                if ($param['turma2']!=null)
                {
                    $turmas[] = $param['turma2'];
                }
                if ($param['turma3']!=null)
                {
                    $turmas[] = $param['turma3'];
                }
                foreach ($turmas as $turma)
                {
                    $antiguidade = 1;
                    foreach ($lista as $aluno)
                    {
                        $matricula              = new aluno;
                        $matricula->status      = $status;
                        $matricula->restricao   = $restricao;
                        $matricula->turma_id    = $turma;
                        $matricula->cpf         = $aluno;
                        $matricula->turma_id    = $turma;
                        $matricula->antiguidade = $antiguidade;
                        $matricula->store();
                        //var_dump($matricula);
                        $antiguidade ++;
                    }
                }
                $men = ($antiguidade --) .' alunos matriculados.';
                $report->addMensagem($men,true);
                
                TTransaction::close(); // close the transaction
                // reload form and session items
                $report->publicaRelatorio('info');
            }
            catch (Exception $e) // in case of exception
            {
                $men = '';
                if (!empty($linha))
                {
                    $men = '<br>Parei no ' . $linha['nome'] ; 
                }
                new TMessage('error', $e->getMessage(). $men); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations
            }
            TApplication::loadPage('servidor_novoList','onReload');
        }
    }//Fim Módulo
    
/*------------------------------------------------------------------------------
 *   Busca código de característica de escrita
 *------------------------------------------------------------------------------*/
    public function codigoMao ($param)
    {
        if ($param == 'DESTRO' || $param == 'DESTRA')
        {
            $mao = 106;
        }
        else if ($param == 'CANHOTO' || $param == 'CANHOTA')
        {
            $mao = 105;
        }
        else
        {
            $mao = 107;
        }
        return $mao;
    }//Fim Módulo
}//Fim Classe
