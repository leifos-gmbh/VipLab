<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Represents an ECS evaluations job
 * 
 */
class ilECSEvaluationJob
{
	public $EvaluationJob = NULL;
	
	
	private $json;
	private $part_id;
	
	public function __construct()
	{
		$this->EvaluationJob = new stdClass();
		$this->EvaluationJob->resources = new stdClass();
		$this->EvaluationJob->method = 'MergeAndCompute';
	}
	
	public function setName($a_name)
	{
		$this->EvaluationJob->name = $a_name;
	}
	
	public function setIdentifier($a_identifier)
	{
		$this->EvaluationJob->identifier = $a_identifier;
	}
	
	public function setPostTime(ilDateTime $dt)
	{
		$this->EvaluationJob->postTime = $dt->get(IL_CAL_DATETIME, '', ilTimeZone::UTC).' Z';
	}
	
	public function setExercise($a_excercise)
	{
		$ecs = ilViPLabSettings::getInstance()->getECSServer();

		
		$uri = $ecs->getServerURI();
		$uri .= ilECSExerciseConnector::RESOURCE_PATH;
		$uri .= ('/'.$a_excercise);
		
		
		$this->EvaluationJob->resources->exercise = $uri;
	}
	
	public function setEvaluation($a_evaluation)
	{
		$ecs = ilViPLabSettings::getInstance()->getECSServer();

		
		$uri = $ecs->getServerURI();
		$uri .= ilECSEvaluationConnector::RESOURCE_PATH;
		$uri .= ('/'.$a_evaluation);

		$this->EvaluationJob->resources->evaluation = $uri;
	}
	
	public function setSolution($a_solution)
	{
		$ecs = ilViPLabSettings::getInstance()->getECSServer();

		$uri = $ecs->getServerURI();
		$uri .= ilECSSolutionConnector::RESOURCE_PATH;
		$uri .= ('/'.$a_solution);

		$this->EvaluationJob->resources->solution = $uri;
	}
	
	public function setMid($a_mid)
	{
		$this->EvaluationJob->target = new stdClass();
		$this->EvaluationJob->target->mid =  $a_mid;
	}
	
	public function getJson()
	{
		return json_encode($this);
	}

}
?>