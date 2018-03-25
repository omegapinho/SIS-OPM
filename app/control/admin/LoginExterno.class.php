<?php
/**
 * LoginForm Externo
 * @author  <your name here>
 */

class LoginExterno extends TPage
{
    protected $form; // form
    protected $palavra; // captcha
    protected $o_palavra;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct();
        
        $ini  = AdiantiApplicationConfig::get();
        
        $this->style = 'max-width: 450px; margin:auto; margin-top:120px;';
        // creates the form
        $this->form = new BootstrapFormBuilder('form_login');
        $this->form->setFormTitle( '<center>LOGIN EXTERNO</center>' );
        $this->form->style = 'background-color: lightgray;';
        
        // create the form fields
        $login    = new TEntry('login');
        $password = new TPassword('password');
        $unit_id  = new TCombo('unit_id');
        $codigo   = new TEntry('codigo');
        $captcha  = new TEntry('captcha');
        
        // define the sizes
        $login->setSize('70%', 40);
        $password->setSize('70%', 40);
        $unit_id->setSize('70%');
        $codigo->setSize('70%', 40);
        $captcha->setSize('70%', 40);

        $login->style    = 'height:35px; font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $password->style = 'height:35px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $unit_id->style  = 'height:35px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $codigo->style   = 'height:35px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $captcha->style  = 'height:35px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        
        $login->placeholder    = _t('User');
        $password->placeholder = _t('Password');
        
        $unit_id->addItems(array('P'=>'PROFESSOR','A'=>'ALUNO','S'=>'SERVENTUÁRIO'));
        //$unit_id->setValue('P');
        
        $codigo->setValue('Repita o código no campo abaixo - '. $this->getCodigo() );
        $codigo->setEditable(false);

        $user   = '<span style="float:left;margin-left:44px;height:35px;" class="login-avatar"><span class="glyphicon glyphicon-user"'.
                  'title="Entre com seu usuário."></span></span>';
        $locker = '<span style="float:left;margin-left:44px;height:35px;" class="login-avatar"><span class="glyphicon glyphicon-lock"'.
                  'title="Digite sua senha, conforme seu perfil de entrada"></span></span>';
        $unit   = '<span style="float:left;margin-left:44px;height:35px;" class="login-avatar"><span class="fa fa-university"'.
                  'title="Escolha o Perfil de Entrada"></span></span>';
        $cad    = '<span style="float:left;margin-left:44px;height:35px;" class="login-avatar"><span class="fa fa-exclamation-triangle red"'.
                  'title="Código de Segurança. Repita-o no campo abaixo."></span></span>';
        $check  = '<span style="float:left;margin-left:44px;height:35px;" class="login-avatar"><span class="fa fa-check-square-o green"'.
                  'title="Copie o código dado no campo acima."></span></span>';
        
        $this->form->addFields( array($user,   $login) );
        $this->form->addFields( array($locker, $password) );
        $this->form->addFields( array($unit,   $unit_id) );
        $this->form->addFields( array($cad,    $codigo) );
        $this->form->addFields( array($check,  $captcha) );
        
        $btn = $this->form->addAction(_t('Log in'), new TAction(array($this, 'onLogin')), '');
        $btn->class = 'btn btn-primary';
        $btn->style = 'height: 40px;width: 90%;display: block;margin: auto;font-size:17px;';
        
        $wrapper = new TElement('div');
        $wrapper->style = 'margin:auto; margin-top:100px;max-width:460px; ';
        $wrapper->id    = 'login-wrapper';
        $wrapper->add($this->form);
        
        // add the form to the page
        parent::add($wrapper);
    }
    /**
     * user exit action
     * Populate unit combo
     */
    
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function old__construct($param)
    {
        parent::__construct();
        


        $table = new TTable;
        $table->width = '100%';
        // creates the form
        $this->form = new TForm('form_login');
        $this->form->class = 'tform';
        $this->form->style = 'max-width: 450px; margin:auto; margin-top:120px;';

        // add the notebook inside the form
        $this->form->add($table);

        // create the form fields
        $login    = new TEntry('login');
        $password = new TPassword('password');
        
        // define the sizes
        $login->setSize('70%', 40);
        $password->setSize('70%', 40);

        $login->style = 'height:35px; font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $password->style = 'height:35px;margin-bottom: 15px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';

        $row=$table->addRow();
        $row->addCell( new TLabel('Login para a Área do Professor') )->colspan = 2;
        $row->class='tformtitle';

        $login->placeholder = _t('User');
        $password->placeholder = _t('Password');

        $user   = '<span style="float:left;width:35px;margin-left:45px;height:35px;" class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>';
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
        $save_button->setAction(new TAction(array($this, 'onLogin')), _t('Log in'));
        $save_button->class = 'btn btn-success';
        $save_button->style = 'font-size:18px;width:90%;padding:10px';

        $row=$table->addRow();
        $row->class = 'tformaction';
        $cell = $row->addCell( $save_button );
        $cell->colspan = 2;
        $cell->style = 'text-align:center';

        $this->form->setFields(array($login, $password, $save_button));

        // add the form to the page
        parent::add($this->form);
    }

    /**
     * Authenticate the User
     */
    public function onLogin()
    {
        $fer   = new TFerramentas;
        $sicad = new TSicadDados;
        $arq = "sisopm_cfg.ini";
        if (file_exists($arq)) 
        {
            $config = parse_ini_file($arq, true );
            $handle = $config['config_geral']['ambiente'];
        }
        else
        {
            $handle = 'local';
        }
        try
        {
            TTransaction::open('sisacad');
            $data      = $this->form->getData('StdClass');
            $captcha   = TSession::getValue('captcha');
            $o_captcha = TSession::getValue('old_captcha');
            
            if (empty($data->captcha))
            {
                throw new Exception('Digite o Código de Segurança no campo apropriado.');
            }
            else if ($data->captcha != $captcha && $data->captcha != $o_captcha)
            {
                throw new Exception('O código de segurança foi digitado errado ' );
            }
            
            if (empty($data->unit_id))
            {
                throw new Exception('Escolha um perfil de entrada');
            }
            
            $this->form->validate();
            if ($data->unit_id == 'P')
            {
                $user = professor::authenticate( $data->login, $data->password );
                $area = 'PROFESSOR';
            }
            else if ($data->unit_id == 'A')
            {
                $user = aluno::authenticate( $data->login, $data->password );
                $area = 'ALUNO';
            }
            else if ($data->unit_id = 'S')
            {
                $user = SystemUser::authenticate( $data->login, $data->password );
                $area = 'SERVIDOR';
            }
            if ($user)
            {
                TSession::regenerate();
                $programs = $user->getPrograms();
                $programs['LoginExterno'] = TRUE;
                
                TSession::setValue('logged', TRUE);
                TSession::setValue('login', $data->login);
                TSession::setValue('userid', $user->id);
                TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
                TSession::setValue('username', $user->nome);
                TSession::setValue('frontpage', '');
                TSession::setValue('programs',$programs);
                TSession::setValue('ambiente',$handle);
                TSession::setValue('sessionTime', microtime(true));
                TSession::setValue('area',$area);

                //TSession::setValue('profile', $profile);
                
                $frontpage = $user->frontpage;
                SystemAccessLog::registerLogin();
                if ($frontpage instanceof SystemProgram AND $frontpage->controller)
                {
                    AdiantiCoreApplication::gotoPage($frontpage->controller); // reload
                    TSession::setValue('frontpage', $frontpage->controller);
                }
                else
                {
                    AdiantiCoreApplication::gotoPage('EmptyPage'); // reload
                    TSession::setValue('frontpage', 'EmptyPage');
                }
                $ret = $sicad->get_OPM();
                if (!empty($ret))
                {
                    TSession::setValue('userunitid',$ret);
                    
                }
                if (empty($user->nome))
                {
                    $profile = TSession::getValue('profile');
                    TSession::setValue('username', $profile['nome']);
                }
            }

            $transation = TTransaction::get();
            if (!empty($transation))
            {
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage());
            TSession::setValue('logged', FALSE);
            TTransaction::rollback();
        }
    }
    
    /** 
     * Reload permissions
     */
    public static function reloadPermissions()
    {
        try
        {
            TTransaction::open('permission');
            $area = TSession::getValue('area');
            if ($area == 'PROFESSOR')
            {
                $user = professor::newFromLogin( TSession::getValue('login') );
            }
            else if ($area == 'ALUNO')
            {
                $user = aluno::newFromLogin( TSession::getValue('login') );
            }
            else if ($area == 'SERVIDOR')
            {
                $user = SystemUser::newFromLogin( TSession::getValue('login') );
            }
            
            if ($user)
            {
                $programs = $user->getPrograms();
                $programs['LoginExterno'] = TRUE;
                TSession::setValue('programs', $programs);
                
                $frontpage = $user->frontpage;
                if ($frontpage instanceof SystemProgram AND $frontpage->controller)
                {
                    TApplication::gotoPage($frontpage->controller); // reload
                }
                else
                {
                    TApplication::gotoPage('EmptyPage'); // reload
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Logout
     */
    public static function onLogout()
    {
        SystemAccessLog::registerLogout();
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginExterno', '');
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *    Gera código para captcha
 *------------------------------------------------------------------------------*/
    public function getCodigo ($param = null)
    {
        $letras  = 6;
        $captcha = TSession::getValue('captcha');
        if (!empty($captcha))
        {
            TSession::setValue('old_captcha',$captcha);
        }
        TSession::regenerate();
        // define a palavra conforme a quantidade de letras definidas no parametro $quantidade_letras
        $this->palavra = substr(str_shuffle("AaBbCcDdEeFfGgHhIiJjKkLMmNnPpQqRrSsTtUuVvYyXxWwZz23456789"),0,($letras));
        
        TSession::setValue('captcha',$this->palavra);
        return $this->palavra ;
    }//Fim Módulo
}//Fim Classe
