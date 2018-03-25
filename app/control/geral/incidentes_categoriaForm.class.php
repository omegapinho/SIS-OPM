<?php
/**
 * incidentes_categoriaForm Master/Detail
 * @author  <your name here>
 */
class incidentes_categoriaForm extends TPage
{
    protected $form; // form
    protected $table;
    protected $detail_row;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct($param);
        
        // creates the form
        $this->form = new TForm('form_incidentes_categoria');
        $this->table = new TTable;
        $this->table->width = '100%';
        $this->form->add($this->table);
        
        $this->table->addSection('thead');
        $row = $this->table->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('Id') );
        $row->addCell( new TLabel('Sistema de Uso') );
        //$row->addCell( new TLabel('Grupo') );
        //$row->addCell( new TLabel('User Id') );
        $row->addCell( new TLabel('Descrição') );
        //$row->addCell( new TLabel('Status') );
        $row->addCell( new TLabel('Sem Uso?') );
        
        $this->addDetailRow();
        
        // create add button
        $add = new TButton('clone');
        $add->setLabel('Add');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // create an action button (save)
        $save = new TButton('save');
        $save->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save->setImage('ico_save.png');

        // define form fields
        $this->form->addField($save);
        $this->table->addRowSet( array($add, $save) );
        
        $this->detail_row = 0;
        
        $panel = new TPanelGroup('incidentes_categoria');
        $panel->add($this->form);
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        //$container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($panel);
        parent::add($container);
    }
    
    /**
     * Add detail row
     */
    public function addDetailRow()
    {
        $this->table->addSection('tbody');
        
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $id = new TEntry('id[]');
        
        $criteria = new TCriteria;
        $criteria->add(new TFilter('dominio','=','configura'));
        $criteria->add(new TFilter('oculto','=','f'));
        $sistema_id = new TDBCombo('sistema_id[]','sisacad','item','id','nome','ordem',$criteria);
        
        $grupo_id = new THidden('grupo_id[]');
        $user_id = new THidden('user_id[]');
        $nome = new TEntry('nome[]');
        $status = new THidden('status[]');
        $oculto = new TCombo('oculto[]');

        // set id's
        $id->setId('id_'.$uniqid);
        $sistema_id->setId('sistema_id_'.$uniqid);
        $grupo_id->setId('grupo_id_'.$uniqid);
        $user_id->setId('user_id_'.$uniqid);
        $nome->setId('nome_'.$uniqid);
        $status->setId('status_'.$uniqid);
        $oculto->setId('oculto_'.$uniqid);

        // set sizes
        $id->setSize('50');
        $sistema_id->setSize('150');
        //$grupo_id->setSize('200');
        //$user_id->setSize('100');
        $nome->setSize('200');
        //$status->setSize('100');
        $oculto->setSize('80');
        
        // set row counter
        $id->{'data-row'} = $this->detail_row;
        $sistema_id->{'data-row'} = $this->detail_row;
        $nome->{'data-row'} = $this->detail_row;
        $oculto->{'data-row'} = $this->detail_row;
        $user_id->{'data-row'} = $this->detail_row;
        $status->{'data-row'} = $this->detail_row;
        $grupo_id->{'data-row'} = $this->detail_row;


        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($id);
        $row->addCell($sistema_id);
        $row->addCell($nome);
        $row->addCell($oculto);
        $row->addCell($status);
        $row->addCell($grupo_id);
        $row->addCell($user_id);
        
        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($id);
        $this->form->addField($sistema_id);
        $this->form->addField($nome);
        $this->form->addField($oculto);
        $this->form->addField($status);
        $this->form->addField($grupo_id);
        $this->form->addField($user_id);

        
        $this->detail_row ++;
    }
    
    /**
     * Clear form
     */
    public function onClear($param)
    {
    }
    
    /**
     * Save the incidentes_categoria
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            if( !empty($param['id']) AND is_array($param['id']) )
            {
                foreach( $param['id'] as $row => $id)
                {
                    if (!empty($id))
                    {
                        $detail = new incidentes_categoria;
                        $detail->id = $param['id'][$row];
                        $detail->sistema_id = $param['sistema_id'][$row];
                        $detail->grupo_id = $param['grupo_id'][$row];
                        $detail->user_id = $param['user_id'][$row];
                        $detail->nome = $param['nome'][$row];
                        $detail->status = $param['status'][$row];
                        $detail->oculto = $param['oculto'][$row];
                        $detail->store();
                    }
                }
            }
            
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
