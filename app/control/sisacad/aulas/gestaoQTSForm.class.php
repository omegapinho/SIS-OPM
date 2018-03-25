<?php
/**
 * gestaoQTSForm Form
 * @author  <your name here>
 */
class gestaoQTSForm extends TPage
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
        $this->form = new TQuickForm('form_controleaula');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Controle de QTS');
        
        $fer = new TFerramentas();
        $disciplina = TSession::getValue('gestao_aula');
        //var_dump($disciplina);

        // create the form fields
        $id                 = new THidden('id');
        $dt_inicio          = new TCombo('dt_inicio');
        $materia_id         = new TCombo('materia_id');
        $hora_inicio        = new TEntry('hora_inicio');
        $horas_aula         = new TEntry('horas_aula');
        $status             = new THidden('status');
        $justificativa      = new THidden('justificativa');
        $professores_lst    = new TSelect('professores_lst');
        $professores_sel    = new TSelect('professores_sel');
        
        //Valores
        $materia_id->addItems($this->onDisciplinas($disciplina->turma));
        
        //Ações
        $change_action_disciplina = new TAction(array($this, 'onSelect'));//Lista os professores
        $materia_id->setChangeAction($change_action_disciplina);
        
        //Tamanhos
        $dt_inicio->setSize(120);
        $materia_id->setSize(200);
        $hora_inicio->setSize(80);
        $horas_aula->setSize(80);
        $professores_lst->setSize(200,80);
        $professores_sel->setSize(200,80);
        
        $hbox1 = new THBox;
        $hbox1->addRowSet($id,new TLabel('Data'),$dt_inicio,new TLabel('Disciplina:'),$materia_id,
                            new TLabel('Hora de Início:'),$hora_inicio,new TLabel('Qnt. Aulas'), $horas_aula);
        $hbox2 = new THBox;
        $hbox2->addRowSet(new TLabel('Disponíveis =>'),$professores_lst,new TLabel('---- Selecionados =>'),$professores_sel);
        
        $frame1 = new TFrame;
        $frame1->setLegend('Dados da Aula');
        $frame1->add($hbox1);
        
        $frame2 = new TFrame;
        $frame2->setLegend('Professores da Disciplina');
        $frame2->add($hbox2);

        $hbox3 = new THBox;
        
        $frame3 = new TFrame;
        $frame3->setLegend('Quadro de Trabalho Semanal');
        $frame3->add($hbox3);
        
        //$frame1->add($frame2);


        // add the fields
        /*$this->form->addQuickField('Id', $id,  80 );
        $this->form->addQuickField('Dia', $dt_inicio,  120 , new TRequiredValidator);
        $this->form->addQuickField('Disciplina', $materia_id,  400 , new TRequiredValidator);
        $this->form->addQuickField('Hora Inicio', $hora_inicio,  120 , new TRequiredValidator);
        $this->form->addQuickField('Quantidade de Aulas', $horas_aula,  100 , new TRequiredValidator);
        $this->form->addQuickField('Status', $status,  200 );
        $this->form->addQuickField('Justificativa', $justificativa,  200 );*/




        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        /** samples
         $this->form->addQuickFields('Date', array($date1, new TLabel('to'), $date2)); // side by side fields
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( 100, 40 ); // set size
         **/
       
        // create the form actions
        //$this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        //$this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
        
        
        $this->form->setFields(array($id,$dt_inicio,$materia_id,$hora_inicio,$horas_aula,$status,
                                        $justificativa,$professores_lst,$professores_sel));
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'gestaoAulasForm'));

        $this->form->add($frame1);
        $this->form->add($frame2);
        $this->form->add($frame3);
        
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
            TTransaction::open('sisacad'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            
            $object = new controle_aula;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
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
                TTransaction::open('sisacad'); // open a transaction
                $object = new controle_aula($key); // instantiates the Active Record
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
    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega Professores para Seleção
 *---------------------------------------------------------------------------------------*/
    static function onSelect($param)
    {
        if (array_key_exists('key',$param))
        {
            $key = $param['key'];
            if ($key=='')
            {
                return;
            }
        }
        else
        {
            return;
        }
        $lista = array('0'=>'- Nenhum Professor Vinculado -');
        try
        {

            if ($key != "XX")
            {
                TTransaction::open('sisacad'); // open a transaction
                
                $disciplina = new disciplina($key);
                $professores = $disciplina->getprofessors();
                
                TTransaction::close(); // close the transaction
                if (!empty($professores))
                {
                    $lista = array();
                    foreach ($professores as $professor)
                    {
                        $lista[$professor->id] = $professor->nome;
                    }
                }
            }
            TDBSelect::reload('form_controleaula', 'professores_lst', $lista);
            //var_dump($lista);
            TSession::setValue(__CLASS__.'_lista_professores[]', $lista);
            TSession::setValue(__CLASS__.'_materia_id', $key);

        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega disciplinas da turma
 *---------------------------------------------------------------------------------------*/
    public function onDisciplinas($param)
    {
        if (!empty($param))
        {
            $key = $param;//id da turma
        }
        else
        {
            return array('0'=>'- Nenhuma Disciplina Cadastrada -');
        }
        try
        {
            if ($key != "XX")
            {
                TTransaction::open('sisacad'); // open a transaction
                $turma = new turma($key);
                $disciplinas = $turma->getmaterias();
                //var_dump($militares);
                $lista = array();
                foreach ($disciplinas as $disciplina)
                {
                    $dados = new disciplina($disciplina->disciplina_id);
                    if ($dados->oculto != 'S')
                    {
                        $lista[$disciplina->disciplina_id] = $dados->nome;
                    }
                }
                TTransaction::close(); // close the transaction
            }
            else
            {
                $lista = array('0'=>'- Nenhuma Disciplina Cadastrada -');
            }
            //TSession::setValue(__CLASS__.'_lista_professores', $lista);
            //TSession::setValue(__CLASS__.'_materia_id', $key);
            return $lista;
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
            return array('0'=>'- Nenhuma Disciplina Cadastrada -');            
        }

    }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Carrega datas
 *---------------------------------------------------------------------------------------*/
    public function onDatas($param)
    {
        if (!empty($param))
        {
            $key = $param;//Data inicial
        }
        else
        {
            TSession::setValue('gestao_aula',null);
            TApplication::loadPage('gestaoAulasForm');
        }
        

    }//Fim Módulo

}
