<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/WebServices/ECS/classes/class.ilECSConnector.php';
include_once './Services/WebServices/ECS/classes/class.ilECSConnectorException.php';

/*
 * Handler for ecs subparticipant ressources
 * 
 */
class ilECSSolutionConnector extends ilECSConnector
{
	const RESOURCE_PATH = '/numlab/solutions';
	
	/**
	 * Constructor
	 * @param ilECSSetting $settings 
	 */
	public function __construct(ilECSSetting $settings = null)
	{
		parent::__construct($settings);
	}


    /**
     * @param $sol
     * @param $a_receiver_com
     * @return int
     * @throws ilECSConnectorException
     */
	public function addSolution($sol, $a_receiver_com): int
    {
		
		ilLoggerFactory::getLogger('viplab')->debug('Add new solution ressource for subparticipant: ' . print_r($a_receiver_com,true));
		ilLoggerFactory::getLogger('viplab')->debug(print_r($sol,true));

	 	$this->path_postfix = self::RESOURCE_PATH;
	 	
	 	try 
	 	{
	 		$this->prepareConnection();

			$this->addHeader('Content-Type', 'application/json');
			$this->addHeader('Accept', 'application/json');
			$this->addHeader(ilECSConnector::HEADER_MEMBERSHIPS, $a_receiver_com);

			$this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
			$this->curl->setOpt(CURLOPT_HEADER,TRUE);
	 		$this->curl->setOpt(CURLOPT_POST,TRUE);
			
			if(strlen($sol))
			{
				$this->curl->setOpt(CURLOPT_POSTFIELDS, $sol);
			}
			else
			{
				$this->curl->setOpt(CURLOPT_POSTFIELDS, json_encode(NULL));
			}
			$ret = $this->call();
			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
	
			ilLoggerFactory::getLogger('viplab')->debug('Checking HTTP status');
			if($info != self::HTTP_CODE_CREATED)
			{
				ilLoggerFactory::getLogger('viplab')->error('Cannot create solution, did not receive HTTP 201.');
				ilLoggerFactory::getLogger('viplab')->info(print_r($ret, true));
				
				throw new ilECSConnectorException('Received HTTP status code: '.$info);
			}
			ilLoggerFactory::getLogger('viplab')->info('Received HTTP 201: created');

			$eid =  ilViPLabUtil::fetchEContentIdFromHeader($this->curl->getResponseHeaderArray());
			
			// store new ressource
			$ressource = new ilECSViPLabRessource();
			$ressource->setRessourceId($eid);
			$ressource->setRessourceType(ilECSViPLabRessource::RES_SOLUTION);
			$ressource->create();
			
			return $eid;
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	} finally {
            $this->curl->close();
        }
		
	}

    /**
     * @param $a_sol_id
     * @return ilECSResult
     * @throws ilECSConnectorException
     * @throws ilECSResourceNotFoundException
     */
	public function deleteSolution($a_sol_id): ilECSResult
    {
		ilLoggerFactory::getLogger('viplab')->debug('Delete solution with id '. $a_sol_id);
	 	$this->path_postfix = self::RESOURCE_PATH;
	 	
	 	if($a_sol_id)
	 	{
	 		$this->path_postfix .= ('/'.(int) $a_sol_id);
	 	}
	 	else
	 	{
	 		throw new ilECSConnectorException('Error calling exercise: No solution id given.');
	 	}
	
	 	try 
	 	{
	 		$this->prepareConnection();
	 		$this->curl->setOpt(CURLOPT_CUSTOMREQUEST,'DELETE');
			$res = $this->call();
            ilViPLabUtil::checkECSResourceForNonPresence($res, $this->curl);
			return new ilECSResult($res);
	 	} catch(ilCurlConnectionException $exc) {
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	} finally {
            $this->curl->close();
        }
	}

    /**
     * @param $a_name
     * @param $a_value
     * @return void
     */
	public function addHeader($a_name,$a_value): void
	{
		if(is_array($a_value))
		{
			$header_value = implode(',', $a_value);
		}
		else
		{
			$header_value = $a_value;
		}
		parent::addHeader($a_name, $header_value);
	}
	
}