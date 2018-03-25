<?php
/**
 * controle_aulaForm Form
 * @author  <your name here>
 */
class gestaoAulasForm extends TPage
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
        $this->form = new TQuickForm('form_controle_aula');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Gerador de Quadro de Trabalho Semanal');

        // create the form fields
        $criteria = new TCriteria;
        $criteria->add (new TFilter ('oculto','=','N'));
        $curso = new TDBCombo('curso','sisacad','curso','id','nome','nome',$criteria);
        
        $turma = new TCombo('turma');
        $data = new TDate('data');
        
        //Ações
        $change_action_curso = new TAction(array($this, 'onChangeAction_curso'));//Popula as cidades com a troca da UF
        $curso->setChangeAction($change_action_curso);
        
        //Mascaras
        $data->setMask('dd-mm-yyyy');

        // add the fields
        $this->form->addQuickField('Curso', $curso,  400 );
        $this->form->addQuickField('Turma', $turma,  400);
        $this->form->addQuickField('Dia inicial', $data,  120 );

        // create the form actions
        $this->form->addQuickAction('Continua', new TAction(array($this, 'onNext')), 'fa:arrow-right red');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }
/*------------------------------------------------------------------------------
 *    Direciona para Gerir disciplinas do professor
 *------------------------------------------------------------------------------*/
     public function onNext ($param = null)
     {
        if ($param)
        {
             //var_dump($param);
        }
        $data = $this->form->getData();
        $fer = new TFerramentas();
        if ($fer->diasemana($data->data)!='1')
        {
            new TMessage('info','A data escolhida não é uma segunda-feira!!!');
            $this->form->setData($data);
            return;
        }
        if (empty($data->turma) || empty($data->curso))
        {
            new TMessage('info','Os campos Curso e Turma são necessários');
            $this->form->setData($data);
            return;
        }
        TSession::setValue('gestao_aula',$data);
        TApplication::loadPage('gestaoQTSForm','onEdit',array('curso'=>$data->curso,'turma'=>$data->turma,'data'=>$data->data));
         
     }//Fim Módulo
/*---------------------------------------------------------------------------------------
 *                   Troca cidades conforme UF - Residência
 *---------------------------------------------------------------------------------------*/
    public static function onChangeAction_curso($param)
    {
        if (array_key_exists('curso',$param))
        {
            if(empty($param['curso']))
            {
                return;
            }
            $key = $param['curso'];
        }
        else
        {
            return;
        }
        try
        {
                TTransaction::open('sisacad'); // open a transaction
                $criteria = new TCriteria; 
                $criteria->add(new TFilter('oculto', '!=', 'S'));
                $criteria->add(new TFilter('curso_id', '=', $key));  
                
                $repository = new TRepository('turma'); 
                $turmas = $repository->load($criteria); 

                TTransaction::close(); // close the transaction
                $lista = array();
                foreach ($turmas as $turma)
                {
                    $lista[$turma->id] = $turma->nome;
                    
                }
                TDBCombo::reload('form_controle_aula', 'turma', $lista);
        }
        catch (Exception $e) // in case of exception 
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations            
        }

    }//Fim Módulo
}
