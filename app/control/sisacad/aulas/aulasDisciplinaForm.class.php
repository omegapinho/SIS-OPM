<?php
/**
 * aulasDisciplinaForm Master/Detail
 * @author  <your name here>
 */
class aulasDisciplinaForm extends TPage
{
    protected $form; // form
    protected $table_details;
    protected $detail_row;
    
/*------------------------------------------------------------------------------
 * Variáveis de configuração
 *------------------------------------------------------------------------------*/
    protected $nivel_sistema   = false;  //Registra o nível de acesso do usuário
    public    $config          = array();//Array com configuração
    protected $config_load     = false;  //Informa que a configuração está carregada

    var $sistema  = 'SISACAD';           //Nome do sistema que irá configurar(filtro)
    var $servico  = 'Aula';            //Nome da página de serviço.
    
    private $opm_operador    = false;    // Unidade do Usuário
    private $listas          = false;    // Lista de valores e array de OPM
    static $cfg_abona_falta  = 'abona_falta_aluno';
    static $turma_key;
   
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();

/*------------------------------------------------------------------------------
 * Carrega configurações
 *------------------------------------------------------------------------------*/
        $fer   = new TFerramentas();                                            //Ferramentas diversas
        $sicad = new TSicadDados();                                             //Ferramentas SICAD
        $profile = TSession::getValue('profile');                               //Profile da Conta do usuário
        if (!$this->nivel_sistema || $this->config_load == false)               //Carrega OPMs que tem acesso
        {
            $this->opm_operador  = $sicad->get_OPM();                           //Carrega OPM do Usuário
            $this->nivel_sistema = $fer->getnivel (get_class($this));           //Verifica qual nível de acesso do usuário
            $this->listas        = $sicad->get_OPMsUsuario();                   //Carrega Listas de OPMs
            $this->config        = $fer->getConfig($this->sistema);             //Carrega config
            TSession::setValue('SISACAD_CONFIG', $this->config);                //Busca o Nível de acesso que o usuário tem para a Classe
            $this->config_load = true;                                          //Informa que configuração foi carregada
        }
        // creates the form
        $this->form = new TForm('form_controle_aula');
        $this->form->class = 'tform'; // CSS class
        
        $table_master = new TTable;
        $table_master->width = '100%';
        
        $table_master->addRowSet( new TLabel('Controle de Aulas da Disciplina - Edição das ausências'), '', '')->class = 'tformtitle';
        
        // add a table inside form
        $table_general = new TTable;
        $table_general->width = '100%';
        
        $frame_general = new TFrame;
        $frame_general->class = 'tframe tframe-custom';
        $frame_general->setLegend('Dados Básicos da Aula');
        $frame_general->style = 'background:whiteSmoke';
        $frame_general->add($table_general);
        
        $frame_details = new TFrame;
        $frame_details->class = 'tframe tframe-custom';
        $frame_details->setLegend('Dados do Aluno faltante');
        
        $table_master->addRow()->addCell( $frame_general )->colspan=2;
        $row = $table_master->addRow();
        $scroll = new TScroll();
        $scroll->setSize('100%',240);
        $scroll->add($frame_details);
        $row->addCell( $scroll );
        
        $this->form->add($table_master);
        
        // master fields
        $id = new TEntry('id');
        $dt_inicio = new TDate('dt_inicio');
        $horas_aula = new TEntry('horas_aula');
        $materia_id = new TEntry('materia_id');
        $turma_id = new TEntry('turma_id');

        // sizes
        $id->setSize('80');
        $dt_inicio->setSize('110');
        $horas_aula->setSize('80');
        $materia_id->setSize('400');
        $turma_id->setSize('400');
        
        //Mascaras
        $dt_inicio->setMask('dd/mm/yyyy');
        $horas_aula->setMask('999');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
            $dt_inicio->setEditable(FALSE);
            $horas_aula->setEditable(FALSE);
            $materia_id->setEditable(FALSE);
            $turma_id->setEditable(FALSE);
        }
        
        // add form fields to be handled by form
        $this->form->addField($id);
        $this->form->addField($dt_inicio);
        $this->form->addField($horas_aula);
        $this->form->addField($turma_id);
        $this->form->addField($materia_id);
        
        // add form fields to the screen
        $table_general->addRowSet( new TLabel('ID da Aula'), $id );
        $table_general->addRowSet( new TLabel('Data da aula'), $dt_inicio );
        $table_general->addRowSet( new TLabel('C.H.'), $horas_aula );
        $table_general->addRowSet( new TLabel('Turma'), $turma_id );
        $table_general->addRowSet( new TLabel('Disciplina'), $materia_id );
        
        // detail
        $this->table_details = new TTable;
        $this->table_details-> width = '100%';
        $frame_details->add($this->table_details);
        
        $this->table_details->addSection('thead');
        $row = $this->table_details->addRow();
        
        // detail header
        $row->addCell( new TLabel('') );
        $row->addCell( $lbl = new TLabel('Aluno') );
        $lbl->setFontColor('red');
        $row->addCell( $lbl = new TLabel('Aulas ausente') );
        $lbl->setFontColor('red');
        $row->addCell( $lbl = new TLabel('Ausência Abonada?') );
        if ($this->nivel_sistema >= $this->config[self::$cfg_abona_falta])
        {
            $lbl->setFontColor('red');
        }
        $row->addCell( $lbl = new TLabel('Justificativa') );
        if ($this->nivel_sistema < $this->config[self::$cfg_abona_falta])
        {
            $lbl->setFontColor('red');
        }

        
        // create an action button (save)
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');
        
        // create an action button (save)
        $ret_button=new TButton('return');
        $ret_button->setAction(new TAction(array('ControleAulaList', 'onReload')), 'Retorna ao Ctr. Aulas');
        $ret_button->setImage('ico_back.png');

        // create an new button (edit with no parameters)
        /*$new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');*/
        
        // define form fields
        $this->form->addField($save_button);
        $this->form->addField($ret_button);
        //$this->form->addField($new_button);
        
        //$table_master->addRowSet( array($save_button, $new_button), '', '')->class = 'tformaction'; // CSS class
        $table_master->addRowSet( array($save_button,$ret_button), '', '')->class = 'tformaction'; // CSS class
        
        $this->detail_row = 0;
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', 'ControleAulaList'));
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
                
                $object             = new controle_aula($key);
                $this->turma_key    = $object->materia->turma->id;//Armazena o valor da turma
                //var_dump($this->turma_key);
                //Adequações de valores para apresentação
                $object->materia_id = $object->materia->disciplina->nome;
                $object->turma_id   = $object->materia->turma->nome;
                $object->dt_inicio  = TDate::date2br($object->dt_inicio);
                
                $this->form->setData($object);
                
                $items  = aluno_presenca::where('controle_aula_id', '=', $key)->load();
                
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
        $fer = new TFerramentas();
        
        $uniqid = mt_rand(1000000, 9999999);
        
        // create fields
        $faltas = new TEntry('faltas[]');
        $abonadas = new TCombo('abonadas[]');
        $justificativa = new TText('justificativa[]');
        
        $aluno_id = new TCombo('aluno_id[]');

        // set id's
        $faltas->setId('faltas_'.$uniqid);
        $abonadas->setId('abonadas_'.$uniqid);
        $justificativa->setId('justificativa_'.$uniqid);
        $aluno_id->setId('aluno_id_'.$uniqid);

        //valores
        $abonadas->addItems($fer->lista_sim_nao());
        $aluno_id->addItems($this->getAlunos());
        
        
        // set sizes
        $faltas->setSize('110');
        $abonadas->setSize('140');
        $justificativa->setSize('250','48');
        $aluno_id->setSize('350');
        
        if ($this->nivel_sistema < $this->config[self::$cfg_abona_falta])
        {
            $abonadas->setEditable(false);
        } 
        
        // set row counter
        $faltas->{'data-row'} = $this->detail_row;
        $abonadas->{'data-row'} = $this->detail_row;
        $justificativa->{'data-row'} = $this->detail_row;
        $aluno_id->{'data-row'} = $this->detail_row;

        // set value
        if (!empty($item->faltas))        { $faltas->setValue( $item->faltas ); }
        if (!empty($item->abonadas))      { $abonadas->setValue( $item->abonadas ); }
        if (!empty($item->justificativa)) { $justificativa->setValue( $item->justificativa ); }
        if (!empty($item->aluno_id))      { $aluno_id->setValue( $item->aluno_id ); }
        
        // create delete button
        $del = new TImage('fa:trash-o red');
        $del->onclick = 'ttable_remove_row(this)';
        
        $row = $this->table_details->addRow();
        // add cells
        $row->addCell( $del );
        $row->addCell($aluno_id);
        $row->addCell($faltas);
        $row->addCell($abonadas);
        $row->addCell($justificativa);

        

        $row->{'data-row'} = $this->detail_row;
        
        // add form field
        $this->form->addField($faltas);
        $this->form->addField($abonadas);
        $this->form->addField($justificativa);
        $this->form->addField($aluno_id);
        
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
     * Save the controle_aula and the aluno_presenca's
     */
    public static function onSave($param)
    {
        $fer     = new TFerramentas();
        $ci      = new TSicadDados();
        $profile = TSession::getValue('profile');           //Profile da Conta do usuário
            //Confere se já foi carregado a OPM, senão carrega...ou se o ambiente for de desenvolvimento usa a OPM = 140
        $opm_operador  = ($fer->is_dev()==true) ? 140 : $profile['unidade']['id'];
        $nivel_sistema = $fer->getnivel ('aulasDisciplinaForm');//Verifica qual nível de acesso do usuário
        $listas        = $ci->get_OpmsRegiao($opm_operador);//Carregas as OPMs que o usuário administra
        $config        = $fer->getConfig('SISACAD');         //Busca o Nível de acesso que o usuário tem para a Classe
        $config_load   = true;                               //Informa que configuração foi carregada
        try
        {
            TTransaction::open('sisacad');
            
            //Carrega os dados do controle_aula para o $master
            $id = (int) $param['id'];
            $master = new controle_aula;
            $master->fromArray( $param);
            //$master->store(); // Não necessita salvar.
            
            // delete details
            aluno_presenca::where('controle_aula_id', '=', $master->id)->delete();
            

            if( !empty($param['faltas']) AND is_array($param['faltas']) )
            {
                $erros      = 0;
                $reg_faltas = 0;
                setlocale(LC_CTYPE, 'pt_BR.iso-8859-1');
                foreach( $param['faltas'] as $row => $faltas)
                {
                    if (!empty($faltas))
                    {
                        $detail = new aluno_presenca;
                        $detail->controle_aula_id = $master->id;
                        $detail->faltas           = $param['faltas'][$row];
                        $detail->abonadas         = $param['abonadas'][$row];
                        $detail->justificativa    = mb_strtoupper($param['justificativa'][$row],'UTF-8');
                        $detail->aluno_id         = $param['aluno_id'][$row];
                        
                        //As faltas não podem ser menor que zero (erro) nem maiores que CH da aula (correção automática)
                        $no_justificativa = true;
                        if ($nivel_sistema < $config['abona_falta_aluno'])
                        {
                            $detail->abonadas = (empty($detail->abonadas)) ? 'N' :$detail->abonadas;
                            if (empty($detail->justificativa))
                            {
                                $no_justificativa = false;
                            } 
                        } 
                        $detail->faltas = ($detail->faltas > $master->horas_aula) ? $master->horas_aula : $detail->faltas;
                        $detail->faltas = ($detail->faltas <= 0) ? 0 : $detail->faltas;
                        //Verificar se os dados vieram corretos.
                        if (self::verifica_falta ($detail) == true && $no_justificativa == true)
                        {
                            $detail->store();
                            $reg_faltas ++;
                        }
                        else
                        {
                            $erros ++;
                        }
                    }//Fim if
                }//Fim foreach
            }//Fim if(empty($param[faltas])
            
            $data = new stdClass;
            $data->id = $master->id;
            TForm::sendData('form_controle_aula', $data);
            TTransaction::close(); // close the transaction
            $action = new TAction(array('aulasDisciplinaForm', 'onEdit'));
            $action->setParameter('key', $data->id);
            if ($erros == 0 && $reg_faltas > 0)
            {
                new TMessage('info', 'Registros de Aula atualizados com ' . $reg_faltas . ' falta(s) lançadas',$action);
            }
            else if ($erros == 0 && $reg_faltas == 0)
            {
                new TMessage('erro', 'Reveja o lançamento pois nenhum dado informado foi consistente o ' .
                                     'suficiente para ser cadastrado. ',$action);
            }
            else
            {
                new TMessage('erro', 'Reveja o(s) lançamento(s) pois houve(m) ' . $erros . ' lançamento(s) cancelado(s) ' .
                                     'em virtude da inconsistência dos dados.',$action);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica se a falta foi digitada corretamente
 *------------------------------------------------------------------------------*/
    public static function verifica_falta ($detail)
    {
        $retorno = true;
        if ($detail->faltas == false || $detail->faltas == 0) $retorno = false;
        if (empty($detail->abonadas)) $retorno = false;
        if (empty($detail->aluno_id)) $retorno = false;
        return $retorno;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Retorna lista de alunos da turma
 *------------------------------------------------------------------------------*/
    public function getAlunos ($key = null)
    {
        $lista = false;
        //var_dump($this->turma_key);
        try
        {
            TTransaction::open('sisacad');
            
            if ($this->nivel_sistema<=80 && $this->turma_key == null)//Gestores e/Operadores
            {
                $sql1 = "(SELECT DISTINCT id FROM sisacad.turma WHERE opm_id IN (" . 
                            $this->listas['valores'].") OR id IN (SELECT DISTINCT turma_id FROM " . 
                            "sisacad.turmaopm WHERE opm_id IN (".$this->listas['valores'].")))";
                $sql2 = "(SELECT DISTINCT id FROM sisacad.turma WHERE id IN " . $sql1 . " AND oculto != 'S')";
                //$criteria->add (new TFilter ('turma_id','IN',$query));
                $alunos = aluno::where('turma_id','IN',$query)->load();
            }
            else if ($this->turma_key!=null)
            {
                $alunos = aluno::where('turma_id','=',$this->turma_key)->load();
                
            }
            else
            {
                $alunos = aluno::where('turma_id','=',$this->turma_key)->load();
                
            }
            

            if (count($alunos) > 0)
            {
                $lista = array();
                foreach ($alunos as $aluno)
                {
                    //$mestre = new professor($docente->professor_id);
                    $posto              = (!empty($aluno->aluno->postograd))  ? $aluno->aluno->postograd.' ' : '';
                    $rgmilitar          = (!empty($aluno->aluno->rgmilitar))  ? 'RG ' . $aluno->aluno->rgmilitar.' ' : '';
                    $lista [$aluno->id] = $posto . $rgmilitar . $aluno->aluno->nome;
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
        return $lista;

    }//Fim Módulo


    
}//Fim Classe
