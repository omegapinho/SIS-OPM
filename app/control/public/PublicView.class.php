<?php
class PublicView extends TPage
{
    public function __construct()
    {
        parent::__construct();

      
        $imagem = new TImage('app/images/pmgo_ico.gif');
        TSession::setValue('keyuser',null);
        
        $table = new TTable;
        $table->width = '100%';
        
        $tablep = new TTable;
        $tablep->width = '100%';
        
        $row = $tablep->addRow();
        $row->addCell($imagem );
        
        $row->addCell(new TLabel('Bem-Vindo a nossa área pública') )->colspan = 2;
        $row->class='tformtitle';
        $row->style = 'text-align: center;';
        
        $row->addCell($imagem );
        
        // creates the form
        $this->form = new TForm('form_login');
        $this->form->class = 'tform';
        $this->form->style = 'max-width: 450px; margin:auto; margin-top:120px;';

        // add the notebook inside the form
        $this->form->add($table);

        // create the form fields
        $login = new TEntry('login');
        $password = new TPassword('password');
        
        // define the sizes
        $login->setSize('70%', 40);
        $password->setSize('70%', 40);

        $login->style = 'height:35px; font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $password->style = 'height:35px;margin-bottom: 15px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        
        $login->addValidation('Usuário', new TRequiredValidator);
        $password->addValidation('Senha', new TRequiredValidator);
        

        $row=$table->addRow();
        $row->addCell( new TLabel('Já possuo Cadastro') )->colspan = 2;
        $row->class='tformtitle';

        $login->placeholder = _t('User');
        $password->placeholder = _t('Password');

        $user = '<span style="float:left;width:35px;margin-left:45px;height:35px;" class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>';
        $locker = '<span style="float:left;width:35px;margin-left:45px;height:35px;" class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>';

        $container1 = new TElement('div');
        $container1->add($user);
        $container1->add($login);

        $container2 = new TElement('div');
        $container2->add($locker);
        $container2->add($password);

        $row=$table->addRow();
        $row->addCell($container1)->colspan = 2;

        // add a row for the field password
        $row=$table->addRow();        
        $row->addCell($container2)->colspan = 2;
        
        // create an action button (save)
        $save_button=new TButton('save');
        // define the button action
        $save_button->setAction(new TAction(array($this, 'onEntra')), _t('Log in'));
        $save_button->class = 'btn btn-success';
        $save_button->style = 'font-size:18px;width:90%;padding:10px';

        $row=$table->addRow();
        $row->class = 'tformaction';
        $cell = $row->addCell( $save_button );
        $cell->colspan = 2;
        $cell->style = 'text-align:center';
        
        /*$row=$table->addRow();
        $row->addCell( new TLabel('Novo Cadastro') )->colspan = 2;
        $row->class='tformtitle';*/
        
        // create an action button (save)
        $new_button=new TButton('new');
        // define the button action
        $new_button->setAction(new TAction(array('servidor_novoForm', 'onClear')), 'Novo Cadastro');
        $new_button->class = 'btn btn-warning';
        $new_button->style = 'font-size:18px;width:90%;padding:10px';
        $new_button->setTip('Se não já não é militar, clique aqui');
        
        $row=$table->addRow();
        $row->class = 'tformaction';
        $cell = $row->addCell( $new_button );
        $cell->colspan = 2;
        $cell->style = 'text-align:center';

        

        $this->form->setFields(array($login, $password, $save_button, $new_button));

        // add the form to the page
        parent::add($tablep);
        parent::add($this->form);
    }
    
    public function onEntra ($param)
    {
        $data = $this->form->getData();
        if (!empty($data->login) && !empty($data->password))
        {
            try
            {
                TTransaction::open('sisacad');
                $this->form->validate();
                $usuario = servidor_novo::where ('cpf','=',$data->login)->load();
                if (!empty($usuario))
                {
                    $usuario = $usuario['0'];
                    if (md5($data->password) != $usuario->senha)
                    {
                        new TMessage('error','Usuário/Senha não conferem');
                    }
                    else
                    {
                        $key = $usuario->id;
                        TSession::setValue('keyuser',$key);
                        AdiantiCoreApplication::gotoPage('servidor_novoForm','onEdit',array('key'=>$key));
                        //TApplication::gotoPage('servidor_novoForm','onEdit',array('key'=>$key));
                    }   
                }
                else
                {
                        new TMessage('error','Usuário/Senha não conferem');
                }
                
                
                TTransaction::close();
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback();
            }
        }
        $this->form->setData($data);
    }//Fim Módulo
    public function onNovoCadastro ($param)
    {
        
    }
}
