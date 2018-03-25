<?php
/**
 * Atualiza dados dos Servidores por OPM ou de toda PM
 * @author  Fernando de Pinho Araújo
 */
class servidorU_DForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    function __construct()
    {
        parent::__construct();
        // creates the form
        $this->form = new TQuickForm('form_atualiza_lote');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Atualização de Dados de Servidor');
        
        // create the form fields
        $opm = new TDBCombo('opm','sicad','OPM','id','nome','nome');
        $status = new TCombo('status');
        
        //Seta Dicas
        $opm->setTip('Escolha uma OPM para Atualizar os dados de todos seus servidores...'.
                        '    <br>Deixe em branco se deseja atualizar todos Militares');
        
        //Tamanho
        $opm->setSize(400);
        $status->setSize(200);
        
        //Dados dos Combos
        $item = array('A'=>'Somentes Ativos','I'=>'Somente Inativos','D'=>'Ativos e Inativos');
        $status->addItems($item);
        $status->setValue('A');

        
        //Frames e caixas
        $frame = new TFrame();
        $frame->setLegend('Filtros');
        $box = new TTable;
        $box->style = 'width: 100%;';// text-align: center;';
        $box->addRowSet(new TLabel ('OPM:'),$opm);
        $box->addRowSet(new TLabel ('Quantos ao Status, atualizo :'),$status);
        $frame->add($box);
        
        // create an action button
        $update_button=new TButton('save');
        $update_button->setAction(new TAction(array($this, 'onUpDate')), "Atualiza");
        $update_button->setImage('ico_save.png');
        
        // create an action button
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
                
        // create an action button (go to list)
        $return_button=new TButton('list');
        $return_button->setAction(new TAction(array($this, 'onBack')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        $subtable = new TFrame;
        $row = new THBox;
        $row->addRowSet($update_button);
        //$row->addRowSet($new_button);
        $row->addRowSet($return_button);
        $subtable->add($row);
        
        $this->form->add($frame);
        $this->form->add($subtable);

        // define wich are the form fields
        $this->form->setFields(array($opm,$status,$update_button,$return_button));//,$new_button
        
        // wrap the page content
        $vbox = new TVBox;
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'servidorList'));
        $vbox->add($this->form);
        //$vbox->add($subtable);
        
        // add the form inside the page
        parent::add($vbox);
    }
/*------------------------------------------------------------------------------
 *   Função: Novo ou Limpa Formulário
 *------------------------------------------------------------------------------*/
    public function onClear( $param )
    {
        $this->form->clear();
    }//Fim módulo
/*------------------------------------------------------------------------------
 *   Função: Salvar
 *------------------------------------------------------------------------------*/
    function onUpDate($param)
    {
        $data = $this->form->getData();
        $upDate = new TServidoresUpdate();
        $upDate->opm  = ($data->opm!='' && $data->opm!=null) ? $data->opm : null;
        $status = ($data->status!='') ? $data->status : 'A';
        $upDate->status = $status;
        
        $ret = $upDate->getMilitares();
        
        if ($ret)
        {
            //echo "Fiz a lista de PMs.";
            $ret = $upDate->upDatePms();
        }
        else
        {
            new TMessage ('error',"Ocorreu um erro ao atualizar o efetivo.");
        }
        $this->form->setData($data);
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Retorno para Listagem
 *------------------------------------------------------------------------------*/
    public function onBack ()
    {
        TApplication::loadPage('servidorList');
    }//Fim Módulo
}//Fim Classe
