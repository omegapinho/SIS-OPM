<?php
/**
 * recalculo_aulaForm Master/Detail
 * @author  <your name here>
 */
class recalculo_aulaForm extends TPage
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
        $this->form = new TForm('form_professor');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Sistema de Recalculo de Horas Aula'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados do Professor');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Aulas Ministradas que sofrerão correção');
        
        $scroll = new TScroll();
        $scroll->setSize('100%',200);
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $row->addCell( $frame_details );
        
        $this->form->add($table_master);
        
        // master fields
        $id         = new TEntry('id');
        $nome       = new TEntry('nome');
        $postograd  = new TDBCombo('postograd_id','sisacad','postograd','id','nome','nome');
        $cpf        = new TEntry('cpf');
        $chamado_id = new THidden('chamado_id');

        // sizes
        $id->setSize('50');
        $nome->setSize('250');
        $postograd->setSize('180');
        $cpf->setSize('100');

        $crit = new TCriteria;
        
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        $postograd->setEditable(FALSE);
        $nome->setEditable(FALSE);
        $cpf->setEditable(FALSE);
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($nome);
        $this->form->addField($postograd);
        $this->form->addField($cpf);
        $this->form->addField($chamado_id);
        
        // add form fields to the screen
        $table_general->addRowSet( array(new TLabel('Id'), $id,
                                   new TLabel('Cargo'), $postograd ,
                                   new TLabel('Nome'), $nome ,
                                   new TLabel('CPF'), $cpf,$chamado_id));
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $scroll->add($this->table_details);
        $frame_details->add($scroll);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( new TLabel('QNT Aulas') );
        $row->addCell( new TLabel('Data da Aula') );
        $row->addCell( new TLabel('Nivel Curso') );
        $row->addCell( new TLabel('Tit. Anterior') );
        $row->addCell( new TLabel('Tit. Novo') );
        $row->addCell( new TLabel('R$ Anterior') );
        $row->addCell( new TLabel('R$ Novo') );
        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), 'Confirma');
        $save_button->setImage('fa:thumbs-o-up green fa-lg');

        // create an new button (edit with no parameters)
        $new_button=new TButton('cancela');
        $new_button->setAction(new TAction(array($this, 'onCancela')), 'Cancela');
        $new_button->setImage('fa:thumbs-o-down red fa-lg');
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($new_button);
        
        $table_master->addRowSet( array($save_button, $new_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'professorList'));
        $container->add($this->form);
        parent::add($container);
    }
    
    /**
     * Executed whenever the user clicks at the edit button da datagrid
     */
    public function onEdit($param)
    {
        try
        {
            TTransaction::open('sisacad');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new professor($key);
                $object->chamado_id = (isset($param['chamado'])) ? $param['chamado'] : false; //Inclui o id do chamado
                $this->form->setData($object);
                $items  = professorcontrole_aula::where('professor_id', '=', $key)->
                                                  where('aulas_saldo','>','0')->
                                                  where('data_quitacao','IS',NULL)->
                                                  orderby('data_aula')->
                                                  load();
                $this->table_details->addSection('tbody');
                $acad = new TSisacad;
                if ($items)
                {
                    foreach($items  as $item )
                    {
                        $item->nivel_pagamento_nome = $item->nivel_pagamento->nome;
                        $item->titularidade_nome    = $item->titularidade->nome;

                        $titulo_max = $acad->getMaiorTitulo(                                //Busca novo título
                                        array('data_aula'=>TDate::date2br($item->data_aula),
                                        'professor_id'=>$key));
                        $item->valor_correcao = $acad->getValorAula(                        //Corrige valor
                                                        $item->nivel_pagamento_id,
                                                        $titulo_max,
                                                        $item->data_aula);
                        if ( ($item->valor_aula != $item->valor_correcao) || ($item->titularidade_id != $titulo_max) )
                        {
                            $n_tit = new titularidade($titulo_max);
                            $item->new_titulo_nome = $n_tit->nome;
                            $this->addDetailRow($item);
                        }
                        
                    }
                    // create add button
                    /*$add = new TButton('clone');
                    $add->setLabel('Add');
                    $add->setImage('fa:plus-circle green');
                    $add->addFunction('ttable_clone_previous_row(this)');
                    
                    // add buttons in table
                    $this->table_details->addRowSet([$add]);*/
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
        $id                   = new THidden('detail_id[]');
        $aulas_saldo          = new TEntry('aulas_saldo[]');
        //$aulas_pagas          = new TEntry('aulas_pagas[]');
        $data_aula            = new TDate('data_aula[]');
        $nivel_pagamento_id   = new THidden('nivel_pagamento_id[]');
        $titularidade_id      = new THidden('titularidade_id[]');
        $valor_aula           = new TEntry('valor_aula[]');
        $titularidade_nome    = new TEntry('titularidade_nome[]');
        $new_titulo_nome      = new TEntry('new_titulo_nome[]');
        $nivel_pagamento_nome = new TEntry('nivel_pagamento_nome[]');
        $valor_correcao       = new TEntry('valor_correcao[]');

        // set id's
        $id->setId('detail_id_'.$uniqid);
        $aulas_saldo->setId('aulas_saldo_'.$uniqid);
        //$aulas_pagas->setId('aulas_pagas_'.$uniqid);
        $data_aula->setId('data_aula_'.$uniqid);
        $nivel_pagamento_nome->setId('nivel_pagamento_nome_'.$uniqid);
        $titularidade_nome->setId('titularidade_nome_'.$uniqid);
        $new_titulo_nome->setId('new_titulo_nome_'.$uniqid);
        $valor_aula->setId('valor_aula_'.$uniqid);
        $nivel_pagamento_id->setId('nivel_pagamento_id_'.$uniqid);
        $titularidade_id->setId('titularidade_id_'.$uniqid);
        $valor_correcao->setId('valor_correcao_'.$uniqid);

        // set sizes
        $aulas_saldo->setSize('80');
        //$aulas_pagas->setSize('80');
        $data_aula->setSize('120');
        $nivel_pagamento_nome->setSize('100');
        $titularidade_nome->setSize('100');
        $new_titulo_nome->setSize('100');
        $valor_aula->setSize('100');
        $valor_correcao->setSize('100');
        
        // set row counter
        $id->{'data-row'} = $this->detail_row;
        $aulas_saldo->{'data-row'} = $this->detail_row;
        //$aulas_pagas->{'data-row'} = $this->detail_row;
        $data_aula->{'data-row'} = $this->detail_row;
        $nivel_pagamento_nome->{'data-row'} = $this->detail_row;
        $titularidade_nome->{'data-row'} = $this->detail_row;
        $new_titulo_nome->{'data-row'} = $this->detail_row;
        $valor_aula->{'data-row'} = $this->detail_row;
        $valor_correcao->{'data-row'} = $this->detail_row;
        $nivel_pagamento_id->{'data-row'} = $this->detail_row;
        $titularidade_id->{'data-row'} = $this->detail_row;
        
        $aulas_saldo->setEditable(false);
        //$aulas_pagas->setEditable(false);
        $data_aula->setEditable(false);
        $nivel_pagamento_nome->setEditable(false);
        $titularidade_nome->setEditable(false);
        $new_titulo_nome->setEditable(false);
        $valor_aula->setEditable(false);
        $valor_correcao->setEditable(false);

        // set value
        if (!empty($item->id)) { $id->setValue( $item->id ); }
        if (!empty($item->aulas_saldo)) { $aulas_saldo->setValue( $item->aulas_saldo ); }
        //if (!empty($item->aulas_pagas)) { $aulas_pagas->setValue( $item->aulas_pagas ); }
        if (!empty($item->data_aula)) { $data_aula->setValue( TDate::date2br($item->data_aula )); }
        if (!empty($item->nivel_pagamento_nome)) { $nivel_pagamento_nome->setValue( $item->nivel_pagamento_nome ); }
        if (!empty($item->titularidade_nome)) { $titularidade_nome->setValue( $item->titularidade_nome ); }
        if (!empty($item->new_titulo_nome)) { $new_titulo_nome->setValue( $item->new_titulo_nome ); }
        if (!empty($item->valor_aula)) { $valor_aula->setValue( number_format($item->valor_aula,2,'.','') ); }
        if (!empty($item->nivel_pagamento_id)) { $nivel_pagamento_id->setValue( $item->nivel_pagamento_id ); }
        if (!empty($item->titularidade_id)) { $titularidade_id->setValue( $item->titularidade_id ); }
        if (!empty($item->valor_correcao)) { $valor_correcao->setValue( number_format($item->valor_correcao,2,'.','') ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        $row = $this->table_details->addRow();

        // add cells
        $row->addCell( $del );
        $row->addCell($aulas_saldo);
        //$row->addCell($aulas_pagas);
        $row->addCell($data_aula);
        $row->addCell($nivel_pagamento_nome);
        $row->addCell($titularidade_nome);
        $row->addCell($new_titulo_nome);
        $row->addCell($valor_aula);
        $row->addCell($valor_correcao);
        $row->addCell($nivel_pagamento_id);
        $row->addCell($titularidade_id);
        $row->addCell($id);

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($id);
        $this->form->addField($aulas_saldo);
        //$this->form->addField($aulas_pagas);
        $this->form->addField($data_aula);
        $this->form->addField($nivel_pagamento_nome);
        $this->form->addField($titularidade_nome);
        $this->form->addField($new_titulo_nome);
        $this->form->addField($valor_aula);
        $this->form->addField($valor_correcao);
        $this->form->addField($nivel_pagamento_id);
        $this->form->addField($titularidade_id);
        
        $this->detail_row ++;
    }
    
    /**
     * Clear form
     */
    public function onClear($param)
    {
        /*$this->table_details->addSection('tbody');
        $this->addDetailRow( new stdClass );
        
        // create add button
        $add = new TButton('clone');
        $add->setLabel('Add');
        $add->setImage('fa:plus-circle green');
        $add->addFunction('ttable_clone_previous_row(this)');
        
        // add buttons in table
        $this->table_details->addRowSet([$add]);*/
    }
    
    /**
     * Save the professor and the professorcontrole_aula's
     */
    public static function onSave($param)
    {
        try
        {
            TTransaction::open('sisacad');
            //print_r($param);
            $acad = new TSisacad;
            $id = (int) $param['id'];
            $master = new professor;
            $master->fromArray( $param);
            $master->store(); // save master object
            
            if( !empty($param['detail_id']) AND is_array($param['detail_id']) )
            {
                foreach( $param['detail_id'] as $row => $detail_id)
                {
                    if (!empty($detail_id))
                    {
                        $detail = new professorcontrole_aula($detail_id);
                        $titulo_novo    = $acad->getMaiorTitulo(
                                        array('data_aula'=>TDate::date2br($detail->data_aula),
                                        'professor_id'=>$master->id));
                        $valor_correcao = $acad->getValorAula(
                                                        $detail->nivel_pagamento_id,
                                                        $titulo_novo,
                                                        $detail->data_aula);
                        $detail->titularidade_id = $titulo_novo;        //Título Corrigido
                        $detail->valor_aula      = $valor_correcao;     //Valor Corrigido
                        $detail->store();
                    }
                }
            }
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_professor', $data);
            TTransaction::close(); // close the transaction
            $chamado = new TMantis();
            $chamado->fechaChamado(array('key'=>$param['chamado_id']));
            $action = new TAction(array('professorList','onReload'));
            new TMessage('info', 'Resolução salva.<br>'.
                                 'Estou encerrando o chamado para esta ação.',$action);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *
 *------------------------------------------------------------------------------*/
    public function onCancela ($param = null)
    {
        $chamado = new TMantis();
        $chamado->fechaChamado(array('key'=>$param['chamado_id']),90);//Não será corrigido
        $action = new TAction(array('professorList','onReload'));
        new TMessage('info', 'Resolução salva, não será atualizado!<br>'.
                             'Estou encerrando o chamado para esta ação.',$action);
           
    }
    
}//Fim Classe
