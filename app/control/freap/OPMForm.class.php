<?php
/**
 * OPMForm Master/Detail - Relaciona Serviços Freap para OPM
 * @author  <your name here>
 */
class OPMForm extends TPage
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
        $this->form = new TForm('form_OPM');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('OPM'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados da OPM');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Serviços FREAP desta OPM');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');

        // sizes
        $id->setSize('100');
        $nome->setSize('400');
        
        //Formatação
        $id->setProperty('style','text-align:center');
        $nome->setProperty('style','text-align:left');

        $id->setEditable(FALSE);
        $nome->setEditable(false);
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('ID'), $id );
        $table_general->addRowSet( new TLabel('Nome'), $nome );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        //$this->table_details->addSection('thead');
        //$row = $this->table_details->addRow();
        
        // detail header
        //$row->addCell( new TLabel('Serviços já Cadastrado') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');

        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/

        // Cria botão para Retorno
        $back_button=new TButton(_t('Back to the listing'));
        $back_button->setAction(new TAction(array($this, 'onBack')), _t('Back to the listing'));
        $back_button->setImage('fa:table blue');
        
        // define form fields
        $this->form->addField($save_button);
        //$this->form->addField($new_button);
        $this->form->addField($back_button);
        
        $table_master->addRowSet( array($save_button, $back_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'OPM_ServicoList'));
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
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new OPM($key);
                $this->form->setData($object);
                
                $items  = grupo_servico_opm::where('id_opm', '=', $key)->load();
                
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
        $id_servico = new TDBCombo('id_servico[]','freap','servico','id','nome_chave');

        // set id's
        $id_servico->setId('id_servico_'.$uniqid);

        // set sizes
        $id_servico->setSize('400');
        $id_servico->setTip('Selecione o Serviço para incluir para a OPM.');
        
        
        // set row counter
        $id_servico->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->id_servico)) { $id_servico->setValue( $item->id_servico ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        $del->title='Remove este serviço...';

        $row = $this->table_details->addRow($del);
        // add cells
        $row->addCell( $del )->width='20px';
        $row->addCell($id_servico);

        

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($id_servico);
        
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
     * Save the OPM and the grupo_servico's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            $id = (int) $param['id'];
            $master = new OPM;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            // delete details
            grupo_servico_opm::where('id_opm', '=', $master->id)->delete();
            
            if( !empty($param['id_servico']) AND is_array($param['id_servico']) )
            {
                foreach( $param['id_servico'] as $row => $id_servico)
                {
                    if (!empty($id_servico))
                    {
                        $detail = new grupo_servico_opm;
                        $detail->id_opm = $master->id;
                        $detail->id_servico = $param['id_servico'][$row];
                        $detail->store();
                    }
                }
            }
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_OPM', $data);
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
/*
 *            Retorna para Listagem
 */
    public function onBack ()
    {
        $param = null;
        TApplication::loadPage('OPM_ServicoList','OnEdit',$param);
    }    
}
