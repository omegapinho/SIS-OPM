<?php
/**
 * LoginForm Registration
 * @author  <your name here>
 */
class LoginForm extends TPage
{
    protected $form; // form
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct();
        if (!isset($_GET['access_token']))
        {
/*------------------------------------------------------------------------------------------------------------
 * Define o ambiente de trabalho: localweb, sefaz-h(homologação), sefaz-p(produção)
 *------------------------------------------------------------------------------------------------------------*/
            //var_dump($_GET['access_token']);
            $arq = "sisopm_cfg.ini";
            
            if (file_exists($arq)) 
            {
                $config = parse_ini_file($arq, true );
                $handle = $config['config_geral']['ambiente'];
                $login_externo = $config['config_geral']['site_sso'];
                $base_url = $config['config_geral']['base_url'];
                if ($handle) 
                {
                    if ($handle!='local') 
                    {
                        //var_dump($config);
                        $ci = new TSicadDados;
                        $site_externo = $ci->get_Prd_Hom($login_externo, $handle). "&client_id=sisopm"//sisopm
                            . "&redirect_uri=".$base_url."/".'index.php';
                        echo '
                         <script language="javascript" type="text/javascript">
                             <!--
                             window.setTimeout(\'window.location="'.$site_externo.'"; \',0);
                             // -->
                         </script>
                        ';
                        exit;
                    }
                    else
                    {
                        $table = new TTable;
                        $table->width = '100%';
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
                
                        $row=$table->addRow();
                        $row->addCell( new TLabel('Log in') )->colspan = 2;
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
                    }//Fim if handle = local
                }//Fim handle
            }//Fim File Exist
       }//Fim $_GET
       else
       {
           $this->onLogin_web($param);
       }
    
    }//Fim Módulo

    /**
     * Authenticate the User
     */
    public function onLogin($param=array())
    {
        try
        {
            TTransaction::open('permission');
            $data = $this->form->getData('StdClass');
            $this->form->validate();
            $user = SystemUser::authenticate( $data->login, $data->password );
            if ($user)
            {
                TSession::regenerate();
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;
                $fer = new TFerramentas();
                TSession::setValue('logged', TRUE);
                TSession::setValue('login', $data->login);
                TSession::setValue('userid', $user->id);
                TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
                TSession::setValue('username', $user->name);
                TSession::setValue('frontpage', '');
                TSession::setValue('programs',$programs);
                TSession::setValue('token', false);
                TSession::setValue('profile', $fer->get_Profile($data->login));
                TSession::setValue('ambiente', 'local');
                TSession::setValue('sessionTime', microtime(true));
                TSession::setValue('area','SISTEMA');
                
                if (!empty($user->unit))
                {
                    TSession::setValue('userunitid',$user->unit->id);
                }
                
                $frontpage = $user->frontpage;
                SystemAccessLog::registerLogin();

                $this->verifica_bd();
                
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
            }
            TTransaction::close();
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
        $area = TSession::getValue('area');//)) ? TSession::getValue('area') : false;
        if ($area != 'SISTEMA')
        {
            LoginExterno::reloadPermissions();
        }
        else
        {
            try
            {
                TTransaction::open('permission');
                $user = SystemUser::newFromLogin( TSession::getValue('login') );
                if ($user)
                {
                    $programs = $user->getPrograms();
                    $programs['LoginForm'] = TRUE;
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
    }
    
    /**
     * Logout
     */
    public static function onLogout()
    {
        $area = TSession::getValue('area');
        if (isset($area) && ($area == 'PROFESSOR' || $area == 'ALUNO' || $area == 'SERVIDOR'))
        {
            SystemAccessLog::registerLogout();
            TSession::freeSession();
            AdiantiCoreApplication::gotoPage('LoginExterno', '');
        }
        else
        {
            SystemAccessLog::registerLogout();
            TSession::freeSession();
            $arq = "sisopm_cfg.ini";
            if (file_exists($arq)) 
            {
                $config = parse_ini_file($arq, true );
                $handle = $config['config_geral']['ambiente'];
                $logoff_externo = $config['config_geral']['logoff_sso'];
                $base_url = $config['config_geral']['base_url'];
                if ($handle!='local') 
                {
                    $ci = new TSicadDados;
                    $site_externo = $ci->get_Prd_Hom($logoff_externo,$handle). "&client_id=sisopm"//sisopm
                        . "&redirect_uri=".$base_url."/".'index.php';
                    echo '
                     <script language="javascript" type="text/javascript">
                         <!--
                         window.setTimeout(\'window.location="'.$site_externo.'"; \',10);
                         // -->
                     </script>
                    ';
                    exit; 
                }
                else
                {
                    AdiantiCoreApplication::gotoPage('LoginForm', '');
                }
             }
             else
             {
                AdiantiCoreApplication::gotoPage('LoginForm', '');
             }
         }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Transforma objeto em array
 *------------------------------------------------------------------------------- */    
    public function object_to_array($data) {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *                        Novo Login através do SSO da SSPAP
 *------------------------------------------------------------------------------- */     
    public function onLogin_web ($param=array()) 
    {
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
        if (isset($_GET['access_token'])) 
        {
            $token = $_GET['access_token'];
            $fer = new TSicadDados();
            $items = $fer->validateLogin($token,$handle);
            $profile = $items;//$this->object_to_array($items);
            if ($this->verifica_user($profile['cpf'])==false)
            {
                $ci = new TFerramentas();
                $ret = $ci->perfil_Sigu($profile);
            }            
        }
        try
        {
            TTransaction::open('permission');
            $user = SystemUser::authenticate_web( $profile['cpf'], '' );//Novo autentificador
            if ($user)
            {
                TSession::regenerate();
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;
                TSession::setValue('logged', TRUE);
                TSession::setValue('login', $profile['cpf']);
                TSession::setValue('userid', $user->id);
                TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
                TSession::setValue('username', $user->name);
                TSession::setValue('token', $token);
                TSession::setValue('profile', $profile);
                TSession::setValue('frontpage', '');
                TSession::setValue('programs',$programs);
                TSession::setValue('ambiente',$handle);
                TSession::setValue('sessionTime', microtime(true));
                TSession::setValue('area','SISTEMA');
                
                //var_dump($profile);exit;
                if (!empty($user->unit))
                {
                    TSession::setValue('userunitid',$user->unit->id);
                }
                $frontpage = $user->frontpage;
                SystemAccessLog::registerLogin();
                
                $this->verifica_bd();
                
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
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage());
            TSession::setValue('logged', FALSE);
            TTransaction::rollback();
            $this->onLogout();            
            
        }
    }//Fim Módulo
/*-------------------------------------------------------------------------------
 *            Verifica Base de Dados
 *-------------------------------------------------------------------------------*/
     static function verifica_bd ()
     {
        new TMessage('info','<center>Bem-vindo.... <br>Verificando e Atualizando a Base de Dados...<br><br>Aguarde alguns instantes...</center>');
        $CI = new TSicadDados;
        if ($CI->get_periodo_vence('opms')) //Verifica o período
        {
            $CI->atualiza_opms();
        }
        if ($CI->get_periodo_vence('estados')) //Verifica o período
        {
            $CI->atualiza_estados();
        }
        if ($CI->get_periodo_vence('municipios')) //Verifica o período
        {
            $CI->atualiza_cidades();
        }
        
     }//Fim Módulo
/*-------------------------------------------------------------------------------
 *            Verifica se existe conta do usuário
 *-------------------------------------------------------------------------------*/
     static function verifica_user ($param)
     {
        try
        {
            TTransaction::open('permission');
            $verifica = SystemUser::where('login','=',$param)->load();
            TTransaction::close();
            if ($verifica)
            {
                return true;
            }
            return false;
        }
         catch (Exception $e)
        {
            return false;
        } 
     }//Fim Módulo
    
       
}
