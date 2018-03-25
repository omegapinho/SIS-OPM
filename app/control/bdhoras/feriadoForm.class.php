<?php
/**
 * feriadoForm Master/Detail
 * @author  <your name here>
 */
class feriadoForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct($param);
        
        // creates the form
        $this->form = new TForm('form_feriado');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Formulário de Cadastro de Feriados'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('feriado');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Unidade Policial');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $dataferiado = new TEntry('dataferiado');
        $nome = new TEntry('nome');
        $tipo = new TCombo('tipo');
        $movel = new TCombo('movel');

        // sizes
        $id->setSize('50');
        $dataferiado->setSize('80');
        $nome->setSize('400');
        $tipo->setSize('250');
        $movel->setSize('80');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        //Valores dos Campos
        $item = array();
        $item['f'] = 'Não';
        $item['t'] = 'Sim';
        $movel->addItems($item);
        $item = array();
        $item['NACIONAL']       = 'Feriado Nacional/Estadual';
        $item['MUNICIPAL']      = 'Feriado Municipal';
        $item['INSTITUCIONAL']  = 'Feriado ou comemoração da Instituição';
        $tipo->addItems($item);
        
        //Mascaras e valores
        $dataferiado->setMask('99/99');
        $movel->setValue('f');
        $tipo->setValue('NACIONAL');
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($dataferiado);
        $this->form->addField($nome);
        $this->form->addField($tipo);
        $this->form->addField($movel);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('ID'), $id );
        $table_general->addRowSet( new TLabel('Data do Feriado'), $dataferiado );
        $table_general->addRowSet( new TLabel('Nome do Feriado'), $nome );
        $table_general->addRowSet( new TLabel('Tipo de Feriado'), $tipo );
        $table_general->addRowSet( new TLabel('Feriado de Data Móvel?'), $movel );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        //$row->addCell( new TLabel('OPM') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');

        // create an action button (go to list)
        $return_button=new TButton('back');
        $return_button->setAction(new TAction(array($this, 'onBack')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($new_button);
        $this->form->addField($return_button);
        
        $table_master->addRowSet( array($save_button, $new_button,$return_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'feriadoList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        try
        {
            TTransaction::open('sicad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new feriado($key);
                $this->form->setData($object);
                
                $items  = feriadoopm::where('feriado_id', '=', $key)->load();
                
                $this->table_details->addSection('tbody');
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $this->addDetailRow($item);
                    }
                    
                    // create add button
                    $add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);
                }
                else
                {
                    $this->onClear($param);
                }
                
                TTransaction::close(); // close transaction
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Add detail row
     */
    public function addDetailRow($item)
    {
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $opm_id = new TDBCombo('opm_id[]','sicad','OPM','id','nome','nome');

        // set id's
        $opm_id->setId('opm_id_'.$uniqid);

        // set sizes
        $opm_id->setSize('400');
        
        // set row counter
        $opm_id->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->opm_id)) { $opm_id->setValue( $item->opm_id ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell($opm_id);
        
        $row->addCell( $del );
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($opm_id);
        
        $this->detail_row ++;
    }
    
    /**
     * Clear form
     */
    public function onClear($param)
    {
        $this->table_details->addSection('tbody');
        $this->addDetailRow( new stdClass );
        
        // create add button
        $add = new TButton('clone');
        $add->setLabel('Add');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // add buttons in table
        $this->table_details->addRowSet([$add]);
    }
    
    /**
     * Save the feriado and the feriadoopm's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sicad');
            
            $id = (int) $param['id'];
            $master = new feriado;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            feriadoopm::where('feriado_id', '=', $master->id)->delete();
            
            if( !empty($param['opm_id']) AND is_array($param['opm_id']) )
            {
                foreach( $param['opm_id'] as $row => $opm_id)
                {
                    if (!empty($opm_id))
                    {
                        $detail = new feriadoopm;
                        $detail->feriado_id = $master->id;
                        $detail->opm_id = $param['opm_id'][$row];
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_feriado', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
/*---------------------------------------------------------------------------------------
 *  Rotina: Retorno a Listagem
 *---------------------------------------------------------------------------------------*/
    public function onBack ()
    {
        TApplication::loadPage('feriadoList');
    }//Fim Módulo
}//Fim Classe
