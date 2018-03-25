<?php
//ini_set('display_errors',1);
//error_reporting(E_ALL); 
use Adianti\Control\TAction;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Registry\TSession;
use Adianti\Widget\Dialog\TMessage;

require_once 'init.php';
require_once 'rollbar.class.php';
Rollbar::init(array("access_token"=>"1878e954e7bc4998b5448e6d23c95b99"));

class TApplication extends AdiantiCoreApplication
{
    static public function run($debug = TRUE)
    {
        
        
        
        try 
        {
            new TSession;
            if ($_REQUEST)
            {
                $ini    = AdiantiApplicationConfig::get();
                $class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
                $public = in_array($class, $ini['permission']['public_classes']);
                
                if (TSession::getValue('logged')) // logged
                {
                    $programs = (array) TSession::getValue('programs'); // programs with permission
                    $programs = array_merge($programs, array('Adianti\Base\TStandardSeek' => TRUE,
                                                             'LoginForm' => TRUE,
                                                             'AdiantiMultiSearchService' => TRUE,
                                                             'AdiantiUploaderService' => TRUE,
                                                             'AdiantiAutocompleteService' => TRUE,
                                                             'EmptyPage' => TRUE,
                                                             'MessageList' => TRUE,
                                                             'SystemDocumentUploaderService' => TRUE,
                                                             'NotificationList' => TRUE,
                                                             'SearchBox' => TRUE));
                    
                    if( isset($programs[$class]) || $public )
                    {
                        parent::run($debug);
                    }
                    else
                    {
                        new TMessage('error', _t('Permission denied') );
                    }
                }
                else if ($class == 'LoginForm' OR $public )
                {
                    parent::run($debug);
                }
                else
                {
                    new TMessage('error', _t('Permission denied'), new TAction(array('LoginForm','onLogout')) );
                }
            }
        }//Fim Try
        catch (Exception $e)
        {
            //new TMessage('info', $e->getMessage()); // shows the exception error message
            Rollbar::report_exception($e);
        }
    }//Fim Módulo
}//Fim Classe

TApplication::run(TRUE);
