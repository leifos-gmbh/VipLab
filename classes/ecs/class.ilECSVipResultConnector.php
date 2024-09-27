<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/WebServices/ECS/classes/class.ilECSConnector.php';
include_once './Services/WebServices/ECS/classes/class.ilECSConnectorException.php';

/*
 * Handler for ecs subparticipant ressources
 * 
 */
class ilECSVipResultConnector extends ilECSConnector
{
	const RESOURCE_PATH = '/numlab/results';
	
	/**
	 * Constructor
	 * @param ilECSSetting $settings 
	 */
	public function __construct(ilECSSetting $settings = null)
	{
		parent::__construct($settings);
	}


    /**
     * @param $result
     * @param $a_receiver_com
     * @return int
     * @throws ilECSConnectorException
     */
	public function addResult($result, $a_receiver_com): int
    {
		ilLoggerFactory::getLogger('viplab')->debug('Add new result resource for subparticipant: ' . json_encode($a_receiver_com));

	 	$this->path_postfix = self::RESOURCE_PATH;
	 	
	 	try 
	 	{
	 		$this->prepareConnection();

			$this->addHeader('Content-Type', 'text/plain');
			$this->addHeader('Accept', 'application/json');
			$this->addHeader(ilECSConnector::HEADER_MEMBERSHIPS, $a_receiver_com);
			
			$this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
			$this->curl->setOpt(CURLOPT_HEADER,TRUE);
	 		$this->curl->setOpt(CURLOPT_POST,TRUE);
			
			if(strlen($result))
			{
				$this->curl->setOpt(CURLOPT_POSTFIELDS, $result);
			}
			else
			{
				$this->curl->setOpt(CURLOPT_POSTFIELDS, json_encode(NULL));
			}
			$ret = $this->call();
			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
	
			ilLoggerFactory::getLogger('viplab')->debug('Checking HTTP status...');
			if($info != self::HTTP_CODE_CREATED)
			{
				ilLoggerFactory::getLogger('viplab')->error('Cannot create result, did not receive HTTP 201. ');
				ilLoggerFactory::getLogger('viplab')->error(print_r($ret,true));
				
				throw new ilECSConnectorException('Received HTTP status code: '.$info);
			}
			ilLoggerFactory::getLogger('viplab')->debug('...got HTTP 201 (created)');

			$eid =  ilViPLabUtil::fetchEContentIdFromHeader($this->curl->getResponseHeaderArray());
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
     * @param $a_exc_id
     * @return ilECSResult
     * @throws ilECSConnectorException
     */
	public function deleteResult($a_exc_id): ilECSResult
    {
		ilLoggerFactory::getLogger('viplab')->debug('Delete result with id: ' . $a_exc_id);
	 	$this->path_postfix = self::RESOURCE_PATH;
	 	
	 	if($a_exc_id)
	 	{
	 		$this->path_postfix .= ('/'.(int) $a_exc_id);
	 	}
	 	else
	 	{
	 		throw new ilECSConnectorException('Error calling result: No result id given.');
	 	}
	
	 	try 
	 	{
	 		$this->prepareConnection();
	 		$this->curl->setOpt(CURLOPT_CUSTOMREQUEST,'DELETE');
			$res = $this->call();
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	} finally {
            $this->curl->close();
        }
	}
	
	/**
	 * Add Header
	 * @param string $a_name
	 * @param string $a_value
	 * @deprecated
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