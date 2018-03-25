<?php
/**
 * avaliacao_cursoList Listing
 * @author  <your name here>
 */
class avaliacao_cursoList extends TPage
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
        //$error = new LogErrorSystem('production');    
        parent::__construct();

        $fer   = new TFerramentas();                        // Ferramentas diversas
        $sicad = new TSicadDados();                         // Ferramentas de acesso ao SICAD
        //Realiza definições iniciais de acesso
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
        if ($this->opm_operador == false)                     //Carrega OPM do usuário
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
        
        $curso_militar     = TSession::getValue('curso_militar');
         if (empty($curso_militar))
         {
              TSession::setValue('curso_militar',null);
              TApplication::loadPage('cursoList','onReload');
              //var_dump($data);
         }
        
        // creates the form
        $this->form = new TQuickForm('form_search_avaliacao_curso');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Gestor de Avaliações - Listagem');
        

        // create the form fields
        $criteria             = new TCriteria();
        $criteria->add( new TFilter('id','=',$curso_militar->id));
        
        $curso                 = new TDBCombo('curso_id','sisacad','curso','id','nome','nome',$criteria);
        $materias_previstas_id = new TCombo('materias_previstas_id');
        $tipo_avaliacao        = new TCombo('tipo_avaliacao');
        $dt_inicio             = new TDate('dt_inicio');
        $oculto                = new TCombo('oculto');
        $ch_minima             = new TEntry('ch_minima');

        //Valores
        $curso->setValue($curso_militar->id);
        $curso->setEditable(false);
        $materias_previstas_id->addItems($this->getDisciplinas($curso_militar->id));
        $oculto->addItems($fer->lista_sim_nao());
        $tipo_avaliacao->addItems($fer->lista_verificacoes());
        
        //Mascaras
        $ch_minima->setMask('999');
        $dt_inicio->setMask('dd/mm/yyyy');

        // add the fields
        $this->form->addQuickField('Curso', $curso,  400 );
        $this->form->addQuickField('Matéria', $materias_previstas_id,  400 );
        $this->form->addQuickField('Tipo de  Avaliação', $tipo_avaliacao,  300 );
        $this->form->addQuickField('Data de Início', $dt_inicio,  120 );
        $this->form->addQuickField('CH Mínima', $ch_minima,  80 );
        $this->form->addQuickField('Avaliação Encerrada?', $oculto,  100 );


        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('avaliacao_curso_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('avaliacao_cursoForm', 'onEdit')), 'bs:plus-sign green');
        $this->form->addQuickAction('Retorna ao Curso',  new TAction(array($this, 'onReturn')), 'ico_back.png');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'false';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_materias_previstas_id = new TDataGridColumn('materias_previstas_id', 'Matéria', 'center');
        $column_tipo_avaliacao = new TDataGridColumn('tipo_avaliacao', 'Tipo de  Avaliação', 'center');
        $column_dt_inicio = new TDataGridColumn('dt_inicio', 'Data de Início', 'right');
        $column_ch_minima = new TDataGridColumn('ch_minima', 'CH Mínima', 'right');
        $column_oculto = new TDataGridColumn('oculto', 'Avaliação Encerrada?', 'center');



        // add the columns to the DataGrid
        //$this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_materias_previstas_id);
        $this->datagrid->addColumn($column_tipo_avaliacao);
        $this->datagrid->addColumn($column_dt_inicio);
        $this->datagrid->addColumn($column_ch_minima);
        $this->datagrid->addColumn($column_oculto);

        // creates the datagrid column actions
        $order_materias_previstas_id = new TAction(array($this, 'onReload'));
        $order_materias_previstas_id->setParameter('order', 'materias_previstas_id');
        $column_materias_previstas_id->setAction($order_materias_previstas_id);
        
        $order_tipo_avaliacao = new TAction(array($this, 'onReload'));
        $order_tipo_avaliacao->setParameter('order', 'tipo_avaliacao');
        $column_tipo_avaliacao->setAction($order_tipo_avaliacao);
        
        $order_dt_inicio = new TAction(array($this, 'onReload'));
        $order_dt_inicio->setParameter('order', 'dt_inicio');
        $column_dt_inicio->setAction($order_dt_inicio);
        
        $order_oculto = new TAction(array($this, 'onReload'));
        $order_oculto->setParameter('order', 'oculto');
        $column_oculto->setAction($order_oculto);
        
        $order_ch_minima = new TAction(array($this, 'onReload'));
        $order_ch_minima->setParameter('order', 'ch_minima');
        $column_ch_minima->setAction($order_ch_minima);
        

        
        // create EDIT action
        $action_edit = new TDataGridAction(array('avaliacao_cursoForm', 'onEdit'));
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
        
        // create Acompanhar action
        $action_aco = new TDataGridAction(array('avaliacao_cursoProgressoForm', 'onEdit'));
        $action_aco->setUseButton(false);
        $action_aco->setButtonClass('btn btn-default');
        $action_aco->setLabel('Acompanhar');
        $action_aco->setImage('fa:binoculars yellow fa-lg');
        $action_aco->setField('id');
        $action_aco->setDisplayCondition(array($this,'displayAcompanhar'));
        $this->datagrid->addAction($action_aco);
        
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
        /*$this->deleteButton = new TButton('delete_collection');
        $this->deleteButton->setAction(new TAction(array($this, 'onDeleteCollection')), AdiantiCoreTranslator::translate('Delete selected'));
        $this->deleteButton->setImage('fa:remove red');
        $this->formgrid->addField($this->deleteButton);*/
        
        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);
        $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        
        $this->transformCallback = array($this, 'onBeforeLoad');


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'cursoList'));
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
            $object = new avaliacao_curso($key); // instantiates the Active Record
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
        TSession::setValue('avaliacao_cursoList_filter_materias_previstas_id',   NULL);
        TSession::setValue('avaliacao_cursoList_filter_tipo_avaliacao',   NULL);
        TSession::setValue('avaliacao_cursoList_filter_dt_inicio',   NULL);
        TSession::setValue('avaliacao_cursoList_filter_oculto',   NULL);
        TSession::setValue('avaliacao_cursoList_filter_ch_minima',   NULL);
        TSession::setValue('avaliacao_cursoList_filter_curso_id',   NULL);

        if (isset($data->materias_previstas_id) AND ($data->materias_previstas_id)) {
            $filter = new TFilter('materias_previstas_id', '=', "$data->materias_previstas_id"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_materias_previstas_id',   $filter); // stores the filter in the session
        }


        if (isset($data->tipo_avaliacao) AND ($data->tipo_avaliacao)) {
            $filter = new TFilter('tipo_avaliacao', '=', "$data->tipo_avaliacao"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_tipo_avaliacao',   $filter); // stores the filter in the session
        }


        if (isset($data->dt_inicio) AND ($data->dt_inicio)) {
            $filter = new TFilter('dt_inicio', '=', "$data->dt_inicio"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_dt_inicio',   $filter); // stores the filter in the session
        }


        if (isset($data->oculto) AND ($data->oculto)) {
            $filter = new TFilter('oculto', '=', "$data->oculto"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_oculto',   $filter); // stores the filter in the session
        }
        else if (isset($data->oculto) AND empty($data->oculto))
        {
            $filter = new TFilter('oculto', '!=', "S"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_oculto',   $filter); // stores the filter in the session
        }
        


        if (isset($data->ch_minima) AND ($data->ch_minima)) {
            $filter = new TFilter('ch_minima', '>=', "$data->ch_minima"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_ch_minima',   $filter); // stores the filter in the session
        }

        /*if (isset($data->curso_id) AND ($data->curso_id)) {
            $filter = new TFilter('curso_id', '=', "$data->curso_id"); // create the filter
            TSession::setValue('avaliacao_cursoList_filter_curso_id',   $filter); // stores the filter in the session
        }*/
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('avaliacao_curso_filter_data', $data);
        
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
        $fer = new TFerramentas();
        try
        {
            // open a transaction with database 'sisacad'
            TTransaction::open('sisacad');
            
            // creates a repository for avaliacao_curso
            $repository = new TRepository('avaliacao_curso');
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
            

            if (TSession::getValue('avaliacao_cursoList_filter_materias_previstas_id')) {
                $criteria->add(TSession::getValue('avaliacao_cursoList_filter_materias_previstas_id')); // add the session filter
            }


            if (TSession::getValue('avaliacao_cursoList_filter_tipo_avaliacao')) {
                $criteria->add(TSession::getValue('avaliacao_cursoList_filter_tipo_avaliacao')); // add the session filter
            }


            if (TSession::getValue('avaliacao_cursoList_filter_dt_inicio')) {
                $criteria->add(TSession::getValue('avaliacao_cursoList_filter_dt_inicio')); // add the session filter
            }


            if (TSession::getValue('avaliacao_cursoList_filter_oculto')) {
                $criteria->add(TSession::getValue('avaliacao_cursoList_filter_oculto')); // add the session filter
            }


            if (TSession::getValue('avaliacao_cursoList_filter_ch_minima')) {
                $criteria->add(TSession::getValue('avaliacao_cursoList_filter_ch_minima')); // add the session filter
            }

            if (TSession::getValue('avaliacao_cursoList_filter_curso_id')) {
                $criteria->add(TSession::getValue('avaliacao_cursoList_filter_curso_id')); // add the session filter
            }
            $curso_militar     = TSession::getValue('curso_militar');
            $filter = new TFilter('curso_id', '=', $curso_militar->id); // create the filter
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
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $object->dt_inicio = TDate::date2br($object->dt_inicio);
                    $object->oculto    = $fer->lista_sim_nao($object->oculto);
                    $object->tipo_avaliacao = $fer->lista_verificacoes($object->tipo_avaliacao);
                    $object->materias_previstas_id = $object->materias_previstas->disciplina->nome;
                    
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
/*------------------------------------------------------------------------------
 *    Testa para ver se há provas em andamento
 *------------------------------------------------------------------------------*/
    public function getProvas ($param)
    {
        $deletar = true;
        try
        {
            $key=$param['key']; // get the parameter $key
            TTransaction::open('sisacad');
            $provas = new avaliacao_curso($key);
            $turmas = $provas->getavaliacao_turmas();
            foreach($turmas as $turma)
            {
                $aplicadas  = $turma->getavaliacao_provas(); 
                if (!empty($aplicadas))
                {
                    $deletar = false;
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
        return $deletar;
        
    }    
    /**
     * Ask before deletion
     */
    public function onDelete($param)
    {

        $deletar = $this->getProvas($param);
        TSession::setValue(__CLASS__.'deletar', $deletar);
        if ($deletar === true)
        {
            // define the delete action
            $action = new TAction(array($this, 'Delete'));
            $action->setParameters($param); // pass the key parameter ahead
            
            // shows a dialog to the user
            new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
        }
        else
        {
            new TMessage('info','Não posso apagar pois já há provas em andamento');
            $this->onReload( $param ); // reload the listing
        }
    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
        if (TSession::getValue(__CLASS__.'deletar') === true)
        {
            TSession::setValue(__CLASS__.'deletar', null);
            try
            {
                $key=$param['key']; // get the parameter $key
                TTransaction::open('sisacad'); // open a transaction with database
                //Apaga as provas destinada as turmas
                $deletar = avaliacao_turma::where('avaliacao_curso_id','=',$key)->delete();
                //Apaga a avaliação do curso
                $object = new avaliacao_curso($key, FALSE); // instantiates the Active Record
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
        else
        {
            new TMessage('info','Não posso apagar pois já há provas em andamento');
            $this->onReload( $param ); // reload the listing
        }
    }
    
    /**
     * Ask before delete record collection
     */
    public function onDeleteCollection( $param )
    {
        return;
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
                    $object = new avaliacao_curso;
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
        /*$deleteAction = $this->deleteButton->getAction();
        $deleteAction->setParameters($param); // important!
        
        $gridfields = array( $this->deleteButton );
        
        foreach ($objects as $object)
        {
            $object->check = new TCheckButton('check' . $object->id);
            $object->check->setIndexValue('on');
            $gridfields[] = $object->check; // important
        }
        
        $this->formgrid->setFields($gridfields);*/
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
        $lista = array(0=>' --- Sem Disciplinas na Ementa ---');
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
/*------------------------------------------------------------------------------
 *  Retorna a edição do curso
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         $data = $this->form->getData();
         TApplication::loadPage('cursoForm','onEdit', array('key'=>$data->curso_id));
         //$this->form->setData($data);
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 * Função que habilita visualizar acompanhamento de aplicação de provas
 *---------------------------------------------------------------------------------------*/
    public function displayAcompanhar($object)
    {
        if (avaliacao_cursoForm::verificaProvas($object->id) == 'S')
        {
            return true;
        }
        return false;
    }

}//Fim Classe
