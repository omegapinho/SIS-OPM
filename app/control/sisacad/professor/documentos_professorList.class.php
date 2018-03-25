<?php
/**
 * documentos_turmaList Listing
 * @author  <your name here>
 */
class documentos_professorList extends TPage
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
        $turma = TSession::getValue('professor');
        if (empty($turma))
        {        
            TSession::setValue('turma_militar',null);
            TSession::setValue('curso_militar',null);
            TApplication::loadPage('professorList');
        }
        
        // creates the form
        $this->form = new TQuickForm('form_search_documentos_turma');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Documentos do Professor - Listagem');

        // create the form fields
        $professor_id  = new TDBCombo('professor_id','sisacad','professor','id','nome','nome');
        
        $criteria = new TCriteria();
        $criteria->add(new TFilter('oculto','!=','S'));
        $criteria->add(new TFilter('servico','=','PROFESSOR'));
        $tipo_doc = new TDBCombo('tipo_doc','sisacad','tipo_doc','id','nome','nome',$criteria);
        
        $descricao = new TEntry('descricao');
        $oculto = new TCombo('oculto');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        
        if(!empty($turma))
        {
            $professor_id->setValue($turma->id);
            $professor_id->setEditable(FALSE);
        }


        // add the fields
        $this->form->addQuickField('Professor', $professor_id,  400 );
        $this->form->addQuickField('Tipo', $tipo_doc,  400 );
        $this->form->addQuickField('Descrição', $descricao,  400 );
        $this->form->addQuickField('Oculto?', $oculto,  120 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('documentos_turma_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction('Documento Diversos',  new TAction(array($this, 'onDocumentoDiverso')), 'bs:plus-sign green');
        $this->form->addQuickAction('Ficha de Assinaturas',  new TAction(array($this, 'onComprovante')), 'bs:check green');
        $this->form->addQuickAction('Retorna aos Professores',  new TAction(array($this, 'onReturn')), 'ico_back.png');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        //$this->datagrid->datatable = 'false';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_tipo_doc = new TDataGridColumn('tipo_doc', 'Tipo', 'center');
        $column_descricao = new TDataGridColumn('descricao', 'Descrição', 'center');
        $column_oculto = new TDataGridColumn('oculto', 'Oculto?', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_tipo_doc);
        $this->datagrid->addColumn($column_descricao);
        $this->datagrid->addColumn($column_oculto);


        // creates the datagrid column actions
        $order_tipo_doc = new TAction(array($this, 'onReload'));
        $order_tipo_doc->setParameter('order', 'tipo_doc');
        $column_tipo_doc->setAction($order_tipo_doc);
        
        $order_descricao = new TAction(array($this, 'onReload'));
        $order_descricao->setParameter('order', 'descricao');
        $column_descricao->setAction($order_descricao);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('documentos_professorForm', 'onEdit'));
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
        
        // create View Doc action
        $action_view = new TDataGridAction(array($this, 'onViewDocumento'));
        $action_view->setUseButton(false);
        $action_view->setButtonClass('btn btn-default');
        $action_view->setLabel('Ver o Documento');
        $action_view->setImage('fa:eye black fa-lg');
        $action_view->setField('id');
        $this->datagrid->addAction($action_view);
        
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
        $container->add(new TXMLBreadCrumb('menu.xml', 'professorList'));
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
            $object = new documentos_turma($key); // instantiates the Active Record
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
        TSession::setValue('documentos_turmaList_filter_data_aula',   NULL);
        TSession::setValue('documentos_turmaList_filter_tipo_doc',   NULL);
        TSession::setValue('documentos_turmaList_filter_descricao',   NULL);
        TSession::setValue('documentos_turmaList_filter_oculto',   NULL);

        if (isset($data->data_aula) AND ($data->data_aula)) {
            $filter = new TFilter('data_aula', '>=', "$data->data_aula"); // create the filter
            TSession::setValue('documentos_turmaList_filter_data_aula',   $filter); // stores the filter in the session
        }


        if (isset($data->tipo_doc) AND ($data->tipo_doc)) {
            $filter = new TFilter('tipo_doc', '=', "$data->tipo_doc"); // create the filter
            TSession::setValue('documentos_turmaList_filter_tipo_doc',   $filter); // stores the filter in the session
        }


        if (isset($data->descricao) AND ($data->descricao)) {
            $filter = new TFilter('descricao', 'like', "%{$data->descricao}%"); // create the filter
            TSession::setValue('documentos_turmaList_filter_descricao',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('documentos_turmaList_filter_oculto',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('documentos_turma_filter_data', $data);
        
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
            
            // creates a repository for documentos_turma
            $repository = new TRepository('documentos_professor');
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

            if (TSession::getValue('documentos_turmaList_filter_tipo_doc')) {
                $criteria->add(TSession::getValue('documentos_turmaList_filter_tipo_doc')); // add the session filter
            }


            if (TSession::getValue('documentos_turmaList_filter_descricao')) {
                $criteria->add(TSession::getValue('documentos_turmaList_filter_descricao')); // add the session filter
            }


            if (TSession::getValue('documentos_turmaList_filter_oculto')) {
                $criteria->add(TSession::getValue('documentos_turmaList_filter_oculto')); // add the session filter
            }
            $turma = TSession::getValue('professor');
            if (!empty($turma))
            {
                $criteria->add(new TFilter('professor_id','=',$turma->id));
            }
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            $fer = new TFerramentas();
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->oculto = $fer->lista_sim_nao($object->oculto);
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
            $object = new documentos_turma($key, FALSE); // instantiates the Active Record
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
                    $object = new documentos_professor;
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
 *  Formulário para a Carga de Documentos diversos
 *------------------------------------------------------------------------------*/
    public function onDocumentoDiverso ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         $dados = TSession::getValue('professor');
         
         if (empty($dados->id))
         {
             new TMessage('info','Por favor, entre em uma turma para prosseguir!!!');
         }
         else
         {
              $dados->documento = 'DIVERSO';
              TSession::setValue('professor',$dados);
              TApplication::loadPage('documentos_professorForm');
              //var_dump($data);
         }
         $this->form->setData($data);
    }
/*------------------------------------------------------------------------------
 *  Formulário para a Carga de assinaturas do professor
 *------------------------------------------------------------------------------*/
    public function onComprovante ($param)
    {
         if ($param)
         {
             //var_dump($param);
         }
         $data = $this->form->getData();
         $dados = TSession::getValue('professor');
         
         if (empty($dados->id))
         {
             new TMessage('info','Por favor, selecione um professor para prosseguir!!!');
         }
         else
         {
              try
              {
                  TTransaction::open('sisacad');
                  $docs = documentos_professor::where('professor_id','=',$dados->id)->
                                               where('assinatura','=','S')->load();
                  TTransaction::close();
                  if (!empty($docs))
                  {
                      foreach($docs as $doc)
                      {
                          $key = $doc->id;
                          $dados = TSession::getValue('professor');
                          $dados->documento = 'ASSINATURA';
                          TSession::setValue('professor',$dados);
                          TApplication::loadPage('documentos_professorForm','onEdit', array('key'=>$key));
                      }
                  }
              }
              catch (Exception $e) // in case of exception
              {
                  //new TMessage('error', $e->getMessage()); // shows the exception error message
                  TTransaction::rollback(); // undo all pending operations
              }
              
              $dados->documento = 'ASSINATURA';
              TSession::setValue('professor',$dados);
              TApplication::loadPage('documentos_professorForm');
              //var_dump($data);
         }
         //$this->form->setData($data);
    }
/*------------------------------------------------------------------------------
 *  Edita um documento
 *------------------------------------------------------------------------------*/
    public function onEdit ($param)
    {
         $dados = TSession::getValue('professor');
         //$dados->documento = null;
         TSession::setValue('professor',$dados);
         TApplication::loadPage('documentos_professorForm','onEdit', array('key'=>$param['id']));
         //$this->form->setData($data);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         $data = $this->form->getData();
         TSession::setValue('professor',null);
         TApplication::loadPage('professorList','onReload');
         //$this->form->setData($data);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Ver um documento
 *------------------------------------------------------------------------------*/
    public function onViewDocumento ($param)
    {
        $data = $this->form->getData();
        $id = isset($param['id']) ? $param['id'] : false;
        try
        {
            if ($id)
            {
                TTransaction::open('sisacad'); // open a transaction
                $doc = new documentos_professor($id);
                //var_dump($doc);
                $doc_id = $doc->arquivos_professor_id;
                $sql = "SELECT DISTINCT encode(contend, 'base64')as contend FROM sisacad.arquivos_professor WHERE id=".$doc_id;
                $conn = TTransaction::get();
                $res = $conn->prepare($sql);
                $res->execute();
                $dados = $res->fetchAll();
                
                
                $object = new arquivos_professor($doc_id); // instantiates the Active Record
                $arquivo = $dados['0']['contend'];//pg_unescape_bytea($dados['0']['contend']);
                //var_dump($arquivo);
                $file = 'tmp/'. TSession::getValue('login') . '.pdf';//$object->filename;
                if (strtolower(substr($object->filename, -3)) == 'pdf')
                {
                    
                    /*header("Pragma: public");
                    header("Expires: 0"); // set expiration time
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-type: application/pdf");
                    //header("Content-Length: {$filesize}");
                    header("Content-disposition: inline; filename=\"{$object->filename}\"");
                    header("Content-Transfer-Encoding: binary");*/
                    
                    // a readfile da problemas no internet explorer
                    // melhor jogar direto o conteudo do arquivo na tela

                    echo file_put_contents($file,base64_decode($arquivo));
                    TPage::openFile($file);
                }
                else
                {
                    //TPage::openFile("files/documents/{$id}/".$object->filename);
                }
            TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo

}
