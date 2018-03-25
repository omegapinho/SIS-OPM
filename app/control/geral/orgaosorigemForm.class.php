<?php
/**
 * orgaosorigemForm Master/Detail
 * @author  <your name here>
 */
class orgaosorigemForm extends TPage
{
    protected $form; // form
    protected $formFields;
    protected $detail_list;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TForm('form_orgaosorigem');
        $this->form->class = 'tform'; // CSS class
        $this->form->style = 'max-width:700px'; // style
        parent::include_css('app/resources/custom-frame.css');
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Cadastro de Posto/Graduações e Funções a um Órgão de Origem'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_detail  = new TTable;
        $table_general-> width = '100%';
        $table_detail-> width  = '100%';
        
        $frame_general = new TFrame;
        $frame_general->setLegend('Identificação do Órgão de Origem');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $table_detail );
        
        $this->form->add($table_master);

        $fer = new TFerramentas();
        
        // master fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $sigla = new TEntry('sigla');
        $oculto = new TCombo('oculto');
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        // detail fields
        $detail_id = new THidden('detail_id');
        $detail_nome = new TEntry('detail_nome');
        $detail_sigla = new TEntry('detail_sigla');
        $detail_ordem = new TEntry('detail_ordem');
        $detail_oculto = new TCombo('detail_oculto');
        
        //Valores
        $oculto->addItems($fer->lista_sim_nao());
        $detail_oculto->addItems($fer->lista_sim_nao());
        
        $oculto->setValue('N');
        $detail_oculto->setValue('N');
        
        //Tamanho
        $id->setSize(80);
        $nome->setSize(400);
        $sigla->setSize(200);
        $oculto->setSize(120);
        $detail_nome->setSize(400);
        $detail_oculto->setSize(100);
        $detail_ordem->setSize(80);
        $detail_sigla->setSize(200);
        
        

        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
        
        // master
        $table_general->addRowSet( array(new TLabel('Id'), $id, new TLabel('Nome'), $nome) );
        $table_general->addRowSet(array( new TLabel('Sigla'), $sigla, new TLabel('Oculto?'), $oculto) );
        
         // detail
        $frame_details = new TFrame();
        $frame_details->setLegend('Posto/Graduação ou Função');
        $row = $table_detail->addRow();
        $row->addCell($frame_details);
        
        $btn_save_detail = new TButton('btn_save_detail');
        $btn_save_detail->setAction(new TAction(array($this, 'onSaveDetail')), 'Register');
        $btn_save_detail->setImage('fa:save');
        
        $table_details = new TTable;
        $frame_details->add($table_details);
        
        $table_details->addRowSet( array('', $detail_id, new TLabel('Nome'), $detail_nome ) );
        $table_details->addRowSet(array( new TLabel('Sigla'), $detail_sigla, new TLabel('Ordem'), $detail_ordem, 
                                         new TLabel('Oculto?'), $detail_oculto) );
        
        $table_details->addRowSet( $btn_save_detail );
        
        $this->detail_list = new TQuickGrid;
        $this->detail_list->setHeight( 175 );
        $this->detail_list->makeScrollable();
        $this->detail_list->disableDefaultClick();
        $this->detail_list->addQuickColumn('', 'edit', 'left', 50);
        $this->detail_list->addQuickColumn('', 'delete', 'left', 50);
        
        // items
        $this->detail_list->addQuickColumn('Nome', 'nome', 'center', 250);
        $this->detail_list->addQuickColumn('Sigla', 'sigla', 'center', 150);
        $this->detail_list->addQuickColumn('Ordem', 'ordem', 'center', 100);
        $this->detail_list->addQuickColumn('Oculto?', 'oculto', 'center', 120);
        $this->detail_list->createModel();
        
        $row = $table_detail->addRow();
        $row->addCell($this->detail_list);
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
        
        // retorno
        $ret_button=new TButton('retorno');
        $ret_button->setAction(new TAction(array($this, 'onReturn')), _t('Back to the listing'));
        $ret_button->setImage('ico_back.png');
        
        // define form fields
        $this->formFields   = array($id,$nome,$sigla,$oculto,$detail_nome,$detail_sigla,$detail_ordem,$detail_oculto);
        $this->formFields[] = $btn_save_detail;
        $this->formFields[] = $save_button;
        $this->formFields[] = $new_button;
        $this->formFields[] = $ret_button;
        $this->formFields[] = $detail_id;
        $this->form->setFields( $this->formFields );
        
        $table_master->addRowSet( array($save_button, $new_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'sisacadConfiguracao'));
        $container->add($this->form);
        parent::add($container);
    }
    
    
    /**
     * Clear form
     * @param $param URL parameters
     */
    public function onClear($param)
    {
        $this->form->clear(TRUE);
        TSession::setValue(__CLASS__.'_items', array());
        $this->onReload( $param );
    }
    
    /**
     * Save an item from form to session list
     * @param $param URL parameters
     */
    public function onSaveDetail( $param )
    {
        try
        {
            TTransaction::open('sisacad');
            $data = $this->form->getData();
            
            /** validation sample
            if (! $data->fieldX)
                throw new Exception('The field fieldX is required');
            **/
            
            $items = TSession::getValue(__CLASS__.'_items');
            $key = empty($data->detail_id) ? 'X'.mt_rand(1000000000, 1999999999) : $data->detail_id;
            
            $items[ $key ] = array();
            $items[ $key ]['id'] = $key;
            $items[ $key ]['nome'] = $data->detail_nome;
            $items[ $key ]['sigla'] = $data->detail_sigla;
            $items[ $key ]['ordem'] = $data->detail_ordem;
            $items[ $key ]['oculto'] = $data->detail_oculto;
            
            TSession::setValue(__CLASS__.'_items', $items);
            
            // clear detail form fields
            $data->detail_id = '';
            $data->detail_nome = '';
            $data->detail_sigla = '';
            $data->detail_ordem = '';
            $data->detail_oculto = 'N';
            
            TTransaction::close();
            $this->form->setData($data);
            
            $this->onReload( $param ); // reload the items
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Load an item from session list to detail form
     * @param $param URL parameters
     */
    public function onEditDetail( $param )
    {
        $data = $this->form->getData();
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // get the session item
        $item = $items[ $param['item_key'] ];
        
        $data->detail_id = $item['id'];
        $data->detail_nome = $item['nome'];
        $data->detail_sigla = $item['sigla'];
        $data->detail_ordem = $item['ordem'];
        $data->detail_oculto = $item['oculto'];
        
        // fill detail fields
        $this->form->setData( $data );
    
        $this->onReload( $param );
    }
    
    /**
     * Delete an item from session list
     * @param $param URL parameters
     */
    public function onDeleteDetail( $param )
    {
        $data = $this->form->getData();
        
        // reset items
            $data->detail_nome = '';
            $data->detail_sigla = '';
            $data->detail_ordem = '';
            $data->detail_oculto = '';
        
        // clear form data
        $this->form->setData( $data );
        
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        // delete the item from session
        unset($items[ $param['item_key'] ] );
        TSession::setValue(__CLASS__.'_items', $items);
        
        // reload items
        $this->onReload( $param );
    }
    
    /**
     * Load the items list from session
     * @param $param URL parameters
     */
    public function onReload($param)
    {
        // read session items
        $items = TSession::getValue(__CLASS__.'_items');
        
        $this->detail_list->clear(); // clear detail list
        $data = $this->form->getData();
        
        if ($items)
        {
            $cont = 1;
            $fer = new TFerramentas();
            foreach ($items as $list_item_key => $list_item)
            {
                $item_name = 'prod_' . $cont++;
                $item = new StdClass;
                
                // create action buttons
                $action_del = new TAction(array($this, 'onDeleteDetail'));
                $action_del->setParameter('item_key', $list_item_key);
                
                $action_edi = new TAction(array($this, 'onEditDetail'));
                $action_edi->setParameter('item_key', $list_item_key);
                
                $button_del = new TButton('delete_detail'.$cont);
                $button_del->class = 'btn btn-default btn-sm';
                $button_del->setAction( $action_del, '' );
                $button_del->setImage('fa:trash-o red fa-lg');
                
                $button_edi = new TButton('edit_detail'.$cont);
                $button_edi->class = 'btn btn-default btn-sm';
                $button_edi->setAction( $action_edi, '' );
                $button_edi->setImage('fa:edit blue fa-lg');
                
                $item->edit   = $button_edi;
                $item->delete = $button_del;
                
                $this->formFields[ $item_name.'_edit' ] = $item->edit;
                $this->formFields[ $item_name.'_delete' ] = $item->delete;
                
                // items
                $item->id = $list_item['id'];
                $item->nome = $list_item['nome'];
                $item->sigla = $list_item['sigla'];
                $item->ordem = $list_item['ordem'];
                $item->oculto = $fer->lista_sim_nao($list_item['oculto']);
                
                $row = $this->detail_list->addItem( $item );
                $row->onmouseover='';
                $row->onmouseout='';
            }

            $this->form->setFields( $this->formFields );
        }
        
        $this->loaded = TRUE;
    }
    
    /**
     * Load Master/Detail data from database to form/session
     */
    public function onEdit($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new orgaosorigem($key);
                $items  = postograd::where('orgaosorigem_id', '=', $key)->load();
                
                $session_items = array();
                foreach( $items as $item )
                {
                    $item_key = $item->id;
                    $session_items[$item_key] = $item->toArray();
                    $session_items[$item_key]['id'] = $item->id;
                    $session_items[$item_key]['nome'] = $item->nome;
                    $session_items[$item_key]['sigla'] = $item->sigla;
                    $session_items[$item_key]['ordem'] = $item->ordem;
                    $session_items[$item_key]['oculto'] = $item->oculto;
                }
                TSession::setValue(__CLASS__.'_items', $session_items);
                
                $this->form->setData($object); // fill the form with the active record data
                $this->onReload( $param ); // reload items list
                TTransaction::close(); // close transaction
            }
            else
            {
                $this->form->clear(TRUE);
                TSession::setValue(__CLASS__.'_items', null);
                $this->onReload( $param );
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Save the Master/Detail data from form/session to database
     */
    public function onSave()
    {
        try
        {
            // open a transaction with database
            TTransaction::open('sisacad');
            
            $data = $this->form->getData();
            $master = new orgaosorigem;
            $master->fromArray( (array) $data);
            $this->form->validate(); // form validation
            
            $master->store(); // save master object
            // delete details
            $old_items = postograd::where('orgaosorigem_id', '=', $master->id)->load();
            
            $keep_items = array();
            
            // get session items
            $items = TSession::getValue(__CLASS__.'_items');
            
            if( $items )
            {
                foreach( $items as $item )
                {
                    if (substr($item['id'],0,1) == 'X' ) // new record
                    {
                        $detail = new postograd;
                    }
                    else
                    {
                        $detail = postograd::find($item['id']);
                    }
                    $detail->nome  = $item['nome'];
                    $detail->sigla  = $item['sigla'];
                    $detail->ordem  = $item['ordem'];
                    $detail->oculto  = $item['oculto'];
                    $detail->orgaosorigem_id = $master->id;
                    $detail->store();
                    
                    $keep_items[] = $detail->id;
                }
            }
            
            if ($old_items)
            {
                foreach ($old_items as $old_item)
                {
                    if (!in_array( $old_item->id, $keep_items))
                    {
                        $old_item->delete();
                    }
                }
            }
            TTransaction::close(); // close the transaction
            
            // reload form and session items
            $this->onEdit(array('key'=>$master->id));
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback();
        }
    }
    
    /**
     * Show the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') )
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna a edição da turma
 *------------------------------------------------------------------------------*/
    public function onReturn ($param)
    {
         //$data = $this->form->getData();
         TApplication::loadPage('orgaosorigemList');
         //$this->form->setData($data);
    }//Fim Módulo
}
