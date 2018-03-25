<?php
/**
 * turnosForm Form
 * @author  <your name here>
 */
class turnosForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_turnos');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Cadastro de Turno de Serviço');
        


        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $tag = new TEntry('tag');
        $descricao = new TEntry('descricao');
        $inicia_seg = new TCombo('inicia_seg');
        $quarta = new TCombo('quarta');
        $sabado = new TCombo('sabado');
        $domingo = new TCombo('domingo');
        $feriado = new TCombo('feriado');
        $qnt_h_turno1 = new TEntry('qnt_h_turno1');
        $qnt_h_intervalo1 = new TEntry('qnt_h_intervalo1');
        $qnt_h_turno2 = new TEntry('qnt_h_turno2');
        $qnt_h_folga = new TEntry('qnt_h_folga');
        $oculto = new TCombo('oculto');



        // add the fields
        $this->form->addQuickField('Id', $id,  50 );
        $this->form->addQuickField('Nome', $nome,  400 );
        $this->form->addQuickField('Nome simplificado', $tag,  200 );
        $this->form->addQuickField('Descrição', $descricao,  400 );
        $this->form->addQuickField('Inicia sempre as Segundas?', $inicia_seg,  80 );
        $this->form->addQuickField('Quarta meio Expediente?', $quarta,  80 );
        $this->form->addQuickField('Trabalha aos Sábados?', $sabado,  80 );
        $this->form->addQuickField('Trabalha aos Domingos?', $domingo,  80 );
        $this->form->addQuickField('Trabalha nos Feriados?', $feriado,  80 );
        $this->form->addQuickField('Horas trabalhadas no 1ºTurno', $qnt_h_turno1,  80 );
        $this->form->addQuickField('Horas Folgadas', $qnt_h_folga,  80 );
        $this->form->addQuickField('Horas trabalhadas no 2ºTurno', $qnt_h_turno2,  80 );
        $this->form->addQuickField('Horas do intervalo', $qnt_h_intervalo1,  80 );
        $this->form->addQuickField('Oculto?', $oculto,  80 );
        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        //Define valores padrão
        $item = array();
        $item['f'] = 'Não';
        $item['t'] = 'Sim';
        $inicia_seg->addItems($item);
        $domingo->addItems($item);
        $sabado->addItems($item);
        $feriado->addItems($item);
        $quarta->addItems($item);
        $oculto->addItems($item);
        
        //Tips
        $nome->setTip('Define um nome para uso e identificação do turno. Limitado até 40 caracteres.');
        $tag->setTip('Simplifique o nome em no máximo 10 caracteres.');
        $descricao->setTip('Entre com a descrição deste turno.');
        $qnt_h_turno1->setTip('Defina a quantidade de horas que se trabalha no primeiro turno.');
        $qnt_h_folga->setTip('Periodo de descanso.');
        $qnt_h_intervalo1->setTip('Se a escala tem turnos variados, defina o intervalo entre um dia de trabalho para outro. Se a escala é regular como na 12X36, não precisa preencher.');
        $qnt_h_turno2->setTip('Quantidade de horas de trabalho do segundo turno. Se a escala é regular como na 12X36, não precisa preencher.');
        $oculto->setTip('Marque SIM para que essa escala não apareça para os usuários.');
        $inicia_seg->setTip('É a resposta da Pergunta. Se SIM, ao gerar escala o sistema não irá pular esse dia.');
        $domingo->setTip('É a resposta da Pergunta. Se SIM, ao gerar escala o sistema não irá pular esse dia.');
        $sabado->setTip('É a resposta da Pergunta. Se SIM, ao gerar escala o sistema não irá pular esse dia.');
        $feriado->setTip('É a resposta da Pergunta. Se SIM, ao gerar escala o sistema não irá pular esse dia.');
        $quarta->setTip('É a resposta da Pergunta. Se NÃO, ao gerar escala o sistema contará o tempo integral previsto.');
        
        //Mascaras e definição de tamanho
        $nome->setMaxLength(40);
        $tag->setMaxLength(20);
        $qnt_h_turno1->setMask('999');
        $qnt_h_turno2->setMask('999');
        $qnt_h_folga->setMask('999');
        $qnt_h_intervalo1->setMask('999');
        
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('turnosList', 'onReload')), 'ico_back.png');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'turnosList'));
        $container->add($this->form);
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('sicad'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            
            $object = new turnos;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            
            /*$object->domingo    = ($object->domingo=='f') ? false : true;
            $object->sabado     = ($object->sabado=='f') ? false : true;
            $object->feriado    = ($object->feriado=='f') ? false : true;
            $object->inicia_seg = ($object->inicia_seg=='f') ? false : true;
            $object->quarta     = ($object->quarta=='f') ? false : true;
            $object->oculto     = ($object->oculto=='f') ? false : true;*/
            
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sicad'); // open a transaction
                $object = new turnos($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
