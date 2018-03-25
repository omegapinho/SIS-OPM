<?php
/*------------------------------------------------------------------------------
 *    Rotinas utilitárias do Sis-Acadêmico
 *
 *------------------------------------------------------------------------------*/

class TSisacad
{
    
/*------------------------------------------------------------------------------
 *  Busca dados do Aluno
 *  @ $param = rgmiltar ou cpf do aluno
 *------------------------------------------------------------------------------*/
    public function getDadosAluno ($param = null)
    {
        if ($param != null)
        {
            try
            {
                TTransaction::open('sisacad'); // open a transaction
                if (strlen($param) == 11)//Verifica de $param é cpf ou rg
                {
                    $objects = servidor::where('cpf','=',$param)->load();
                }
                else
                {
                    $objects = servidor::where('rgmilitar','=',$param)->load();
                }
                TTransaction::close(); // close the transaction
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $ret = $object;
                    }
                    return $object;
                }
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return false;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica qual nível de pagamento de uma dada matéria
 * @param = id na tabela materia
 *------------------------------------------------------------------------------*/
    public function getNivelPagamento ($param = null)
    {
        $nivel = 0;//Menor nível de pagemento
        if ($param != null)
        {
            try
            {
                TTransaction::open('sisacad'); // open a transaction
                $materia = new materia($param);
                if (!empty($materia))
                {
                    $curso_id = $materia->get_turma()->curso_id;
                    if (!empty($curso_id))
                    {
                        $curso = new curso ($curso_id);
                        $nivel = $curso->nivel_pagamento_id;
                    }
                }
                TTransaction::close(); // close the transaction
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $nivel;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica qual maior título do professor em uma dada data
 * @param = professor_id e data_aula (data no formato br)
 *------------------------------------------------------------------------------*/
    public function getMaiorTitulo ($param = array())
    {
        $titulo  = 0;
        $retorno = 1;//Menor indice pago
        if ($param)
        {
            try
            {
                TTransaction::open('sisacad'); // open a transaction
                // creates a repository for materia
                $repository = new TRepository('escolaridade');
                // creates a criteria
                $criteria = new TCriteria;
                $criteria->add(new TFilter('professor_id','=',$param['professor_id']));
                $criteria->add(new TFilter('comprovado','=','S'));

                $criteria->setProperties(array ('order'=>'id','direction'=>'asc'));
                $objects = $repository->load($criteria, FALSE);
                if (!empty($objects))
                {
                    foreach($objects as $object)
                    {
                        if ($object->data_apresentacao <= TDate::date2us($param['data_aula'] ))
                        {
                            $n = new titularidade($object->titularidade_id);
                            if ($n->nivel > $titulo)
                            {
                                $titulo  =  $n->nivel;
                                $retorno = $n->id;
                            }
                        }
                    }
                }
                TTransaction::close(); // close the transaction
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage());
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $retorno;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica carga horária de uma matéria
 *  $param = id da matéria a pesquisar
 *------------------------------------------------------------------------------*/
    public function getCargaHoraria ($param = null)
    {
        $carga = 0;
        if ($param != null)
        {
            try
            {
                TTransaction::open('sicad'); // open a transaction
                $objects = controle_aula::where ('materia_id','=',$param)->load();
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $carga = $carga + $object->horas_aula;
                    }
                }
                TTransaction::close(); // close the transaction
                return $carga;
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $carga;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica se o professor é voluntário
 * @param = array(professor_id e materia_id), retorna true se o professor for voluntário
 *------------------------------------------------------------------------------*/
    public function getVoluntario ($param = array())
    {
        $voluntario = false;//Default é não ser voluntário
        if ($param != null)
        {
            try
            {
                TTransaction::open('sicad'); // open a transaction
                $criteria = new TCriteria;
                $criteria->add(new TFilter('professor_id','=',$param['professor_id']));
                $criteria->add(new TFilter('materia_id','=',$param['materia_id']));
                $repository = new TRepository('professormateria'); 
                $objects = $repository->load($criteria); 
                if (!empty($objects))
                {
                    foreach ($objects as $object)
                    {
                        $v = $object->vinculo;
                    }
                    if ($v == 'V')
                    {
                        $voluntario = true;
                    }
                }
                TTransaction::close(); // close the transaction
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $voluntario;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Verifica o valor da aula do professor
 * @param = professor_id e materia_id, retorna true se o professor for voluntário
 *------------------------------------------------------------------------------*/
    public function getValorAula ($nivel, $titulo, $data)
    {
        $valor = 0;
        if (!empty($nivel) && !empty($titulo) && !empty($data))
        {
            try
            {
                TTransaction::open('sicad'); // open a transaction
                $criteria = new TCriteria;
                $criteria->add(new TFilter('titularidade_id','=',$titulo));
                $criteria->add(new TFilter('nivel_pagamento_id','=',$nivel));
                $repository = new TRepository('valores_pagamento'); 
                $objects = $repository->load($criteria); 
                if (!empty($objects))
                {
                    foreach ($objects as $object)
                    {
                        if ($object->data_inicio <= $data && (empty($object->data_fim) || 
                           (!empty($object->data_fim) && $object->data_fim >= $data)))
                        {
                            $valor = (float) $object->valor;   
                        }
                    }
                }
                TTransaction::close(); // close the transaction
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $valor;
    }//Fim Módulo
/*------------------------------------------------------------------------------
 *  Busca um dos dados do posto graduação
 * @$posto a pesquisar, @$de = pesquisa por, @$para = retorno (S = Sigla, N = Nome)
 *------------------------------------------------------------------------------*/
    public function getPostograd ($posto, $de = 'N', $para = 'S')
    {
        $valor = $posto;
        if (!empty($posto))
        {
            try
            {
                TTransaction::open('sicad'); // open a transaction
                $criteria = new TCriteria;
                switch ($de)
                {
                    case 'S'://Pela Sigla
                        $criteria->add(new TFilter('sigla','=',$posto));
                        break;
                    default:
                        $criteria->add(new TFilter('nome','=',$posto));
                        break;
                }
                $criteria->add(new TFilter('oculto','!=','S'));
                $repository = new TRepository('postograd'); 
                $objects = $repository->load($criteria); 
                if (!empty($objects))
                {
                    $object = $objects[0];
                    switch ($para)
                    {
                        case 'N':
                            $valor = $object->nome;
                            break;
                        default:
                            $valor = $object->sigla;
                            break;
                    }
                }
                TTransaction::close(); // close the transaction
            }
            catch (Exception $e) // in case of exception
            {
                TTransaction::rollback(); // undo all pending operations
            }
        }
        return $valor;
    }//Fim Módulo
    
}//Fim Classe
