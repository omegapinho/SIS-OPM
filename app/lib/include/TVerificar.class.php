<?php
                if (TSession::getValue('logged')) // logged
                {
                    $tempoInicio = TSession::getValue('sessionTime');
                    $tempoFinal  = microtime(true);
                    $tempoGasto  = $tempoFinal - $tempoInicio;
                    $arq = "sisopm_cfg.ini";
                    if ($tempoGasto >= 60 || empty($tempoInicio))
                    {
                        SystemAccessLog::registerLogout();
                        TSession::freeSession();
                        if (file_exists($arq)) 
                        {
                            $config = parse_ini_file($arq, true );
                            $handle = $config['config_geral']['ambiente'];
                            if ($handle!='local') 
                            {
                                new TMessage('info', "Tempo da Seção Expirou.<br>Entre novamente...", new TAction(array('LoginForm','onLogout_web')) );
                                //echo $tempoGasto;exit;
                            }
                            else
                            {
                                new TMessage('info', "Tempo da Seção Expirou.<br>Entre novamente...", new TAction(array('LoginForm','onLogout')) );
                                //echo $tempoGasto;exit;
                            }
                            exit;
                            //parent::run($debug);
                        }

                    }
                    else
                    {
                        TSession::setValue('sessionTime',$tempoFinal);
                        if (file_exists($arq)) 
                        {
                            $config = parse_ini_file($arq, true );
                            $handle = $config['config_geral']['ambiente'];
                            if ($handle!='local')
                            {
                                $fer = new TFerramentas();
                                $items = $fer->validateLogin(TSession::getValue('token'),$handle);
                                if (empty($items))
                                {
                                    SystemAccessLog::registerLogout();
                                    TSession::freeSession();
                                    new TMessage('info', "Tempo da Seção Expirou.<br>Entre novamente...", new TAction(array('LoginForm','onLogout_web')) );
                                    exit;
                                }
                            }
                        }
                    }
                }






//http://www.porndavid.com/pdv/VY3BCoMwDIafxt4qxs6qhx6E4k1hbC9QtHOF2nZtnWNPvzrYYRCSL4H8341B3TQnoBQ9WIECg5Yil0ize4wuI11W9qk2Z71RIp_smranmqUNCaBuy6pI4ET0alICRymNkljLRWgsFvk9pOkDXqyRWBihj0jSO29XmxEORUkbdGdQVWj7t-77nh_iWSTjz-1SG95nGK8XEnj3GvnAu-MBSQYf