<?php
/**
 * OPMForm Form
 * @Fernando de Pinho Araújo
 */
class OPMForm_edt extends TPage
{
    protected $form; // form
    protected $program_list;
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_OPM');
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle( "Unidades Policiais" );
        


        // create the form fields
        $id = new THidden('id');
        $nome = new TEntry('nome');
        $idsuperior = new TDBCombo('idsuperior','sicad','OPM','id','nome','nome');
        $sigla = new TEntry('sigla');
        $corporacao = new THidden('corporacao');
        $superior = new THidden('superior');
        $corporacaoid = new THidden('corporacaoid');
        $level = new THidden('level');
        $telefone = new TEntry('telefone');
        
        $id->setSize(50);
        $nome->setSize(400);
        $idsuperior->setSize(400);
        $sigla->setSize(200);
        $superior->setSize(200);
        $corporacaoid->setSize(50);
        $corporacao->setSize(50);
        $level->setSize(50);
        $telefone->setSize(200);

        $categoria_id = new TDBSeekButton('categoria_id', 'gdocs', 'form_OPM', 'CategoriasDoc', 'descricao', 'categoria_id', 'categoria');
        $categoria    = new TEntry('categoria');
        $categoria_id->setSize('50');
        $categoria->setSize('calc(100% - 200px)');
        $categoria->setEditable(FALSE);

        $frame_programs = new TFrame;
        $frame_programs->setLegend("Categorias de Documentos");
        $frame_programs->style .= ';margin:0px;width:95%';

        // add the fields
        $this->form->addFields([new TLabel('Nome')], [$nome],[$id]);
        $this->form->addFields([new TLabel('Subordinado a')], [$idsuperior]);
        $this->form->addFields([new TLabel('Sigla')], [$sigla],[new TLabel('Telefone')], [$telefone]);
        $this->form->addFields([$corporacao], [$superior],[$corporacaoid], [$level]);
        $this->form->addContent( [$frame_programs] );

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $nome->setEditable(FALSE);
        $idsuperior->setEditable(FALSE);
        $sigla->setEditable(FALSE);
        $corporacao->setEditable(FALSE);
        $superior->setEditable(FALSE);
        $corporacaoid->setEditable(FALSE);
        $level->setEditable(FALSE);
        $telefone->setEditable(FALSE);
        
        $this->program_list = new TQuickGrid();
        $this->program_list->setHeight(80);
        $this->program_list->makeScrollable();
        $this->program_list->style='width: 100%';
        $this->program_list->id = 'program_list';
        $this->program_list->disableDefaultClick();
        $this->program_list->addQuickColumn('', 'delete', 'center', '5%');
        $this->program_list->addQuickColumn('Id', 'id', 'left', '10%');
        $this->program_list->addQuickColumn('Categorias', 'descricao', 'left', '85%');
        $this->program_list->createModel();
        echo md5('123456');
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        //$this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        $this->form->addAction(_t('Back to the listing'),  new TAction(array($this, 'onBack')), 'fa:table blue');
        $add_button  = TButton::create('add',  array($this,'onAddProgram'), _t('Add'), 'fa:plus green');

        $this->form->addField($categoria_id);
        $this->form->addField($categoria);
        $this->form->addField($add_button);

        $hbox = new THBox;
        $hbox->add($categoria_id);
        $hbox->add($categoria, 'display:initial');
        $hbox->add($add_button);
        $hbox->style = 'margin: 4px';
        $vbox = new TVBox;
        $vbox->style='width:100%';
        $vbox->add( $hbox );
        $vbox->add($this->program_list);
        $frame_programs->add($vbox);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'OPMList'));
        $container->add($this->form);
        
        parent::add($container);
    }
/*------------------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------------------*/
    public function onSave( $param )
    {
        //new TMessage('info','Opção de salvar não disponível');
        try
        {
            TTransaction::open('sicad'); // open a transaction
            $this->form->validate(); // validate form data
            
            $object = new OPM;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            $object->clearParts();
           
            $programs = TSession::getValue('program_list');
            if (!empty($programs))
            {
                foreach ($programs as $program)
                {
                    $object->addCategorias( $program['id'] );
                }
            }
            
            $data = array();
            foreach ($object->getCategorias() as $program)
            {
                $data[$program->id] = $program->toArray();
                
                $item = new stdClass;
                $item->id = $program->id;
                $item->descricao = $program->descricao;
                
                $i = new TElement('i');
                $i->{'class'} = 'fa fa-trash red';
                $btn = new TElement('a');
                $btn->{'onclick'} = "__adianti_ajax_exec('class=OPMForm_edt&method=deleteProgram&id={$program->id}');$(this).closest('tr').remove();";
                $btn->{'class'} = 'btn btn-default btn-sm';
                $btn->add( $i );
                
                $item->delete = $btn;
                $tr = $this->program_list->addItem($item);
                $tr->{'style'} = 'width: 100%;display: inline-table;';
            }
            $data = new stdClass;
            $data->id = $object->id;
            TForm::sendData('form_OPM', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------------------*/
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }//Fim Módulo
/*------------------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------------------*/
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sicad'); // open a transaction
                $object = new OPM($key); // instantiates the Active Record
                
                $data = array();
                foreach ($object->getCategorias() as $program)
                {
                    $data[$program->id] = $program->toArray();
                    
                    $item = new stdClass;
                    $item->id = $program->id;
                    $item->descricao = $program->descricao;
                    
                    $i = new TElement('i');
                    $i->{'class'} = 'fa fa-trash red';
                    $btn = new TElement('a');
                    $btn->{'onclick'} = "__adianti_ajax_exec('class=OPMForm_edt&method=deleteProgram&id={$program->id}');$(this).closest('tr').remove();";
                    $btn->{'class'} = 'btn btn-default btn-sm';
                    $btn->add( $i );
                    
                    $item->delete = $btn;
                    $tr = $this->program_list->addItem($item);
                    $tr->{'style'} = 'width: 100%;display: inline-table;';
                }
                
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
                TSession::setValue('program_list', $data);
            }
            else
            {
                $this->form->clear();
                TSession::setValue('program_list', null);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------------------*/
    public function onBack ()
    {
        $param = null;
        TApplication::loadPage('OPMList','OnEdit',$param);
    }//Fim Módulo
/*------------------------------------------------------------------------------------------
 *    Adiciona Categorias
 *------------------------------------------------------------------------------------------*/
    public static function onAddProgram($param)
    {
        try
        {
            //var_dump($param);
            $id = $param['categoria_id'];
            $program_list = TSession::getValue('program_list');
            //var_dump( $program_list[$id]);
            if (!empty($id) AND empty($program_list[$id]))
            {
                TTransaction::open('gdocs');
                $program = new CategoriasDoc ($id);
                $program_list[$id] = $program->toArray();
                //var_dump($program_list);
                TSession::setValue('program_list', $program_list);
                TTransaction::close();
                
                $i = new TElement('i');
                $i->{'class'} = 'fa fa-trash red';
                $btn = new TElement('a');
                $btn->{'onclick'} = "__adianti_ajax_exec(\'class=OPMForm_edt&method=deleteProgram&id=$id\');$(this).closest(\'tr\').remove();";
                $btn->{'class'} = 'btn btn-default btn-sm';
                $btn->add($i);
                
                $tr = new TTableRow;
                $tr->{'class'} = 'tdatagrid_row_odd';
                $tr->{'style'} = 'width: 100%;display: inline-table;';
                $cell = $tr->addCell( $btn );
                $cell->{'style'}='text-align:center';
                $cell->{'class'}='tdatagrid_cell';
                $cell->{'width'} = '5%';
                $cell = $tr->addCell( $program->id );
                $cell->{'class'}='tdatagrid_cell';
                $cell->{'width'} = '10%';
                $cell = $tr->addCell( $program->descricao );
                $cell->{'class'}='tdatagrid_cell';
                $cell->{'width'} = '85%';
                
                TScript::create("tdatagrid_add_serialized_row('program_list', '$tr');");
                
                $data = new stdClass;
                $data->program_id = '';
                $data->program_name = '';
                TForm::sendData('form_OPM', $data);
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }//Fim Módulo
    /**
     * Remove program from session
     */
    public static function deleteProgram($param)
    {
        $programs = TSession::getValue('program_list');
        unset($programs[ $param['id'] ]);
        TSession::setValue('program_list', $programs);
    }
    
    
}//Fim Classe
