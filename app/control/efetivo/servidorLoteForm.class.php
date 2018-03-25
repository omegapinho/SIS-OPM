<?php
/**
 * alunoLoteForm Form
 * @author  Fernando de Pinho Araújo
 */
class servidorLoteForm extends TPage
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
        $this->form = new TForm('form_servidor_lote');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        //$this->form->('Inclusão de Alunos em Lote usando dados do SICAD');
        
        
        // creates a table
        $table_data    = new TTable;

        $notebook = new TNotebook(700, 320);
        
        // add the notebook inside the form
        $this->form->add($notebook);
        $this->form->add(new TLabel('Insira RGs ou CPFs nos quadros específicos separando cada um usando vírgulas...'));
        $notebook->appendPage('Inclusão de Servidores com extração de Dados do SICAD', $table_data);
        
        // create the form fields
        $rg = new TText('rg');
        $cpf = new TText('cpf');
        
        
        //Seta Dicas
        $rg->setTip('Entre com os RGs separando-os com vírgula');
        $cpf->setTip('Entre com os CPFs separando-os com vírgula');
        
        //Tamanho
        $rg->setSize(600,100);
        $cpf->setSize(600,100);

        // Adiciona linhas na aba pessoal
        $table_data->addRowSet(new TLabel('RG:'),  $rg);
        $table_data->addRowSet(new TLabel('CPF:'), $cpf);

        
        // create an action button
        $save_button=new TButton('save');
        $save_button->setAction(new TAction(array($this, 'onSave')), _t('Save'));
        $save_button->setImage('ico_save.png');
        
        // create an action button
        $new_button=new TButton('new');
        $new_button->setAction(new TAction(array($this, 'onClear')), _t('New'));
        $new_button->setImage('ico_new.png');
                
        // create an action button (go to list)
        $return_button=new TButton('list');
        $return_button->setAction(new TAction(array($this, 'onBack')), _t('Back to the listing'));
        $return_button->setImage('ico_back.png');
        
        // define wich are the form fields
        $this->form->setFields(array($rg,$cpf,$save_button,$new_button,$return_button));
         
        $subtable = new TTable;
        $row = $subtable->addRow();
        $row->addCell($save_button);
        $row->addCell($new_button);
        $row->addCell($return_button);
        
        // wrap the page content
        $vbox = new TVBox;
        $vbox->add(new TXMLBreadCrumb('menu.xml', 'servidorList'));
        $vbox->add($this->form);
        $vbox->add($subtable);
        
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
    function onSave()
    {
        try
        {
            TTransaction::open('sicad'); // open a transaction
            TTransaction::setLogger(new TLoggerTXT('tmp/servidorLoteForm.txt'));
            $data = $this->form->getData(); // get form data as array
            
            if (!$data->rg && !$data->cpf)//Os campos devem ter algum registro
            {
                throw new Exception('É necessário a inscrição de um RG ou CPF.');
            }
            $CI = new TSicadDados;
            $relatorio = array();//Retornará o resultado para cada RG/CPF
            $rgs = explode(',',$data->rg);//Converte o campo RG numa array com os rgs
            $cpfs = explode(',',$data->cpf);//Faz o mesmo com CPFs
            $militares = array_merge($rgs,$cpfs);//Cria uma array com todos inscritos
            foreach ($militares as $militar)
            {
                if (!empty($militar))//Dado não pode ser vazio
                {
                    if (strlen($militar)==11)//verificar pelo CPF
                    {
                        $result = servidor::where('cpf','=',$militar)->load();
                    }
                    else //Verifica pelo RG
                    {
                        $result = servidor::where('rgmilitar','=',$militar)->load();
                    }
                    if ($result)//Militar já existe, não cadastrar
                    {
                        foreach ($result as $parte)
                        {
                            $nome = $parte->nome;
                        }
                        $relatorio[]=array('id'=>$militar,'info'=>'já cadastrado','nome'=>$nome);
                    }
                    else//Militar não existe, fazer cadastro
                    {
                        $cadastro = $CI->dados_servidor($militar, true);
                        //var_dump($cadastro);
                        if (!is_array($cadastro))
                        {
                            $relatorio[]=array('id'=>$militar,'info'=>'não tem cadastro ativo no SICAD','nome'=>'');
                        }
                        else
                        {
                            //Transfere os dados
                            $object = new servidor;  // create an empty object
                            $cadastro = array_change_key_case($cadastro,CASE_LOWER);//Converte a Key da array para caixa baixa
                            //print_r($cadastro); echo "<br>";
                            $cadastro['dtnascimento']    = $CI->time_To_Date_SICAD($cadastro['dtnascimento']);
                            $cadastro['dtexpedicaocnh']  = $CI->time_To_Date_SICAD($cadastro['dtexpedicaocnh']);
                            $cadastro['dtvalidadecnh']   = $CI->time_To_Date_SICAD($cadastro['dtvalidadecnh']);
                            $cadastro['dtpromocao']      = $CI->time_To_Date_SICAD($cadastro['dtpromocao']);
                            //Campos distoados
                            $cadastro['orgaoexpedicaorg']= $cadastro['orgaoexpediçãorg'];
                            $cadastro['ufexpedicaorg']   = $cadastro['ufexpediçãorg'];
                            $muntitulo = $cadastro['municipiotituloeleitoral'];
                            if (0<strpos( $muntitulo,'-'))
                            {
                                $t = explode($muntitulo,'-');
                                $cadastro['municipiotituloeleitoral'] = $t[0];
                            }
                            $object->fromArray( (array) $cadastro); // load the object with data
                            //$object->store(); // save the object
                            $dependentes = $cadastro['dependentes'];
                            if ($dependentes)
                            {
                                foreach($dependentes as $dependente)
                                {
                                    if ($dependente)
                                    {
                                        $filho = new dependente();
                                        $dependente = array_change_key_case($dependente,CASE_LOWER);
                                        $filho->fromArray( (array) $dependente);
                                        $filho->servidor_id = $object->id;
                                        $filho->boletiminclusao = self::boletim($dependente['boletiminclusao']);
                                        $filho->boletimexclusao = self::boletim($dependente['boletimexclusao']);
                                        $filho->dtnascimento = $CI->time_To_Date_SICAD($dependente['dtnascimento']);
                                        //$filho->store();
                                        $object->addDependente($filho);
                                    }
                                }
                            }
                            $endereco = array_change_key_case($cadastro['endereco'],CASE_LOWER);//Carregas os dados de endereço
                            $object->logradouro   = $endereco['logradouro'];
                            $object->numero       = $endereco['numero'];
                            $object->quadra       = $endereco['quadra'];
                            $object->lote         = $endereco['lote'];
                            $object->complemento  = $endereco['complemento'];
                            $object->bairro       = $endereco['bairro'];
                            $object->codbairro    = $endereco['codbairro'];
                            $object->municipio    = $endereco['municipio'];
                            $object->codmunicipio = $endereco['codmunicipio'];
                            $object->uf           = $endereco['estado'];
                            $object->cep          = $endereco['cep'];
                            
                                                        
                            $object->store();
                            
                            //print_r($endereco);
                            $relatorio[]=array('id'=>$militar,'info'=>'cadastrado','nome'=>$cadastro['nome']);
                        }
                    }
                }
            }
            //Monta relatório
            $mensagem = '';
            foreach ($relatorio as $rel)
            {
                $mensagem .= '- O PM ';
                $mensagem.= (strlen($rel['id']==11)) ? 'CPF' : 'RG';
                $cor = ($rel['info']=='cadastrado') ? 'green' : 'red';
                $mensagem.=' '.$rel['id'].' '.$rel['nome'].' - <strong><font color="'.$cor.'">'.$rel['info'].'</font></strong><br>';
            }
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            //new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
            new TMessage('info', $mensagem);
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Edição
 *   Nota: Não deve ser usado
 *------------------------------------------------------------------------------*/
    function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('sicad'); // open a transaction
                $object = new servorid($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Retorno para Listagem
 *------------------------------------------------------------------------------*/
    public function onBack ()
    {
        TApplication::loadPage('servidorList');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *   Função: Retorno Boletim
 *------------------------------------------------------------------------------*/
    public function boletim ($param)
    {
        if (is_array($param))
        {
            $ci = new TSicadDados();
            $bol = ($param['numero'])  ? $param['numero']    : '';
            $ano = ($param['ano'])     ? $param['ano']       : '';
            $opm = ($param['unidade']) ? $param['unidade']   : '';
            $tip = ($param['tipo'])    ? $param['tipo']      : '';
            $dat = ($param['data'])    ? $ci->time_To_Date_SICAD($param['data'])  : '';
            return 'BOL('.$tip.') nº'.$bol.'/'.$ano.'-'.$opm.' de '.$dat;
        }
        else
        {
            return '';
        }
       
    }//Fim Módulo    
}//Fim Classe
