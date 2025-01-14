<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Modules/TestQuestionPool/classes/class.assQuestionGUI.php';

/**
 * Question GUI for viPLab questions
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 * @ingroup ModulesTestQuestionPool
 * @ilctrl_iscalledby assViPLabGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * 
 */
class assViPLabGUI extends assQuestionGUI
{
	/**
	 * Constructor
	 * @param integer $a_id The database id of a question object
	 */
	public function __construct($a_id = -1)
	{
		parent::__construct($a_id);
		$this->object = new assViPLab();

		if ($a_id >= 0)
		{
			$this->object->loadFromDb($a_id);
		}
	}
	
	/**
	 * Get question tabs
	 */
	public function setQuestionTabs(): void
	{
		global $ilAccess, $ilTabs;
		
		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if (isset($_GET["q_id"]))
		{
			if ($ilAccess->checkAccess('write', '',$_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_content",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "");
			}
	
			// preview page
            $ilTabs->addTarget("preview",
          	$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
                array("preview"),
                "ilAssQuestionPageGUI", "");
         }

		$force_active = false;
		if ($ilAccess->checkAccess('write', '', $_GET["ref_id"]))
		{
			$url = "";
			if ($classname) 
			{
				$url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			}
			$commands = $_POST["cmd"];
			if (is_array($commands))
			{
				foreach ($commands as $key => $value)
				{
					if (preg_match("/^suggestrange_.*/", $key, $matches))
					{
						$force_active = true;
					}
				}
			}
			// edit question properties
			$ilTabs->addTarget("edit_properties",
				$url,
				array("editQuestion", "save", "cancel", "addSuggestedSolution",
					"cancelExplorer", "linkChilds", "removeSuggestedSolution",
					"parseQuestion", "saveEdit", "suggestRange"),
				$classname, "", $force_active);
		}

        // add tab for question feedback within common class assQuestionGUI
        $this->addTab_QuestionFeedback($ilTabs);

        // add tab for question hint within common class assQuestionGUI
        $this->addTab_QuestionHints($ilTabs);

		// Assessment of questions sub menu entry
		if (isset($_GET["q_id"]))
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];
			$ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
		
	}
	
	/**
	 * @return ilassViPLabPlugin
	 */
	protected function getPlugin()
	{
		return ilassViPLabPlugin::getInstance();
	}
	
	/**
	 * Get viplab object
	 * @return assViPLab
	 */
	protected function getViPLabQuestion()
	{
		return $this->object;
	}
	
	/**
	 * Init edit question form.
	 * The code editor can optianal be shown.
	 * @return ilPropertyFormGUI the edit question form
	 */
	protected function initEditQuestionForm($a_show_editor = FALSE)
	{
		global $lng;

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(FALSE);
		$form->setTableWidth("100%");
		$form->setId("viplabquestion");

		$this->addBasicQuestionFormProperties($form);
		
		$lang = new ilSelectInputGUI($this->getPlugin()->txt('editor_lang'),'language');
		$lang->setInfo($this->getPlugin()->txt('prog_lang_info'));
		$lang->setValue($this->object->getVipLang());
		$options[''] = $this->lng->txt('select_one');
		foreach(ilViPLabSettings::getInstance()->getLanguages() as $lang_key => $mid)
		{
			$options[$lang_key] = $this->getPlugin()->txt('plang_'.$lang_key);
		}
		$lang->setOptions($options);
		$lang->setRequired(TRUE);
		$lang->setDisabled($this->getViPLabQuestion()->getVipSubId());
		$form->addItem($lang);

		// points
		$points = new ilNumberInputGUI($lng->txt("points"), "points");
		$points->setValue($this->object->getPoints());
		$points->setRequired(TRUE);
		$points->setSize(3);
		$points->setMinValue(0.0);
		$form->addItem($points);
		
		// results
		$results = new ilCheckboxInputGUI($this->getPlugin()->txt('store_results'),'result_storing');
		$results->setInfo($this->getPlugin()->txt('store_results_info'));
		$results->setValue(1);
		$results->setChecked($this->getViPLabQuestion()->getVipResultStorage());
		$form->addItem($results);
		
		$scoring = new ilCheckboxInputGUI($this->getPlugin()->txt('auto_scoring'),'auto_scoring');
		$scoring->setInfo($this->getPlugin()->txt('auto_scoring_info'));
		$scoring->setValue(1);
		$scoring->setChecked($this->getViPLabQuestion()->getVipAutoScoring());
		$form->addItem($scoring);
		

		if ($this->object->getId())
		{
			$hidden = new ilHiddenInputGUI("", "ID");
			$hidden->setValue($this->object->getId());
			$form->addItem($hidden);
		}
		
		// add hidden exercise
		$hidden_exc = new ilHiddenInputGUI('vipexercise');
		$hidden_exc->setValue($this->getViPLabQuestion()->getVipExercise());
		$form->addItem($hidden_exc);
		
		// add evaluation
		$hidden_eval = new ilHiddenInputGUI('vipevaluation');
		$hidden_eval->setValue($this->getViPLabQuestion()->getVipEvaluation());
		$form->addItem($hidden_eval);

		#$this->addQuestionFormCommandButtons($form);
		$form->addCommandButton("save", $this->lng->txt("save"));
		
		$editor_form = new ilViPLabEditorFormGUI($this->getPlugin()->txt('editor'), 'editor', $this->getViPLabQuestion());
		$editor_form->showEditor($this->getViPLabQuestion()->getVipSubId() && $a_show_editor);
		
		$form->addItem($editor_form);
		return $form;
	}

	/**
	 * Initialize applet editor
	 * 
	 * TODO: dependencies
	 */
	protected function initEditor()
	{
		$form = $this->initEditQuestionForm();
		
		if(!$form->checkInput())
		{
			$form->setValuesByPost();
			$this->tpl->setOnScreenMessage('failure', $this->lng->txt('err_check_input'), true);
			$this->editQuestion($form);
			return;
		}

		// form valid
		$this->writeVipLabQuestionFromForm($form);
		
		$this->getViPLabQuestion()->deleteSubParticipant();
		$this->addSubParticipant();
		
		$this->getViPLabQuestion()->deleteExercise();
		$this->createExercise();
		
		// initialize form again with editor
		$form = $this->initEditQuestionForm(TRUE);

		$this->getViPLabQuestion()->saveToDb();
		
		$this->editQuestion($form);
	}

    /**
     * Bugfix of the reference implementation \ilObjTest::isPreviousSolutionReuseEnabled which errors out for
     * $active_id = 0, which in turn is passed by \ilObjTestGUI::printObject.
     *
     * TODO: remove upon the bugfix of \ilObjTest::isPreviousSolutionReuseEnabled.
     *
     * @param $active_id
     * @return bool
     */
    public function isPreviousSolutionReuseEnabled($active_id): bool
    {
        global $DIC;
        $ilDB = $DIC["ilDB"];
        $ilUser = $DIC["ilUser"];

        $result = $ilDB->queryF(
            "SELECT tst_tests.use_previous_answers FROM tst_tests, tst_active WHERE tst_tests.test_id = tst_active.test_fi AND tst_active.active_id = %s",
            ["integer"],
            [$active_id]
        );

        if ($result->numRows()) {
            $row = $ilDB->fetchAssoc($result);
            return $row["use_previous_answers"] === '1' && $ilUser->getPref("tst_use_previous_answers") === '1';
        }

        return false;
    }
	
	/**
	 * Create a new solution on ecs for the client, using data from ilias database.
	 *
	 * @param
	 *        	int active_id the id of the test
	 * @param
	 *        	int pass
	 * @param
	 *        	bool force solution generation even for empty solutions
	 * @return int
	 */
	protected function createSolution($a_active_id, $a_pass = null, $a_force_empty_solution = true)
	{
		$sol_arr = $this->getViPLabQuestion()->getUserSolutionPreferingIntermediate($a_active_id, $a_pass);
		
		include_once "./Modules/Test/classes/class.ilObjTest.php";

		if ($this->isPreviousSolutionReuseEnabled($a_active_id) && count($sol_arr) == 0)
		{
			$a_pass = $a_pass ? $a_pass - 1 : $a_pass;
			$sol_arr = $this->getViPLabQuestion()->getSolutionValues($a_active_id, $a_pass, true);
		}
		
		ilLoggerFactory::getLogger('viplab')->debug(print_r($sol_arr, true));
		
		$sol = isset($sol_arr[0]['value2']) ? (string) $sol_arr[0]['value2'] : '';
		if (strlen($sol) || $a_force_empty_solution)
		{
			// create the solution on ecs
			return $this->getViPLabQuestion()->createSolution($sol);
		}
		return 0;
	}

	/**
	 * Create a new solution
	 * @return int
	 */
	protected function createEvaluation()
	{
		return $this->getViPLabQuestion()->createEvaluation();
	}

    /**
     * Create a new solution
     * @throws ilECSConnectorException
     */
	protected function createResult($a_active_id, $a_pass): int
    {
		return $this->getViPLabQuestion()->createResult($a_active_id, $a_pass);
	}

	/**
	 * Create exercise
	 */
	protected function createExercise()
	{
		$this->getViPLabQuestion()->createExercise();
	}
	

	protected function addSubParticipant()
	{
		$this->getViPLabQuestion()->addSubParticipant();
	}

	/**
	 * Show edit question form
	 * @param ilPropertyFormGUI $form
	 */
	protected function editQuestion(ilPropertyFormGUI $form = null)
	{
		$this->getQuestionTemplate();

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initEditQuestionForm();

		}
		$this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
	}
	
	/**
	 * Save question
	 */
	public function save(): void
	{
		$this->getViPLabQuestion()->deleteSubParticipant();
		$this->getViPLabQuestion()->deleteExercise();
		
		$form = $this->initEditQuestionForm();
		if($form->checkInput())
		{
			$this->writeVipLabQuestionFromForm($form);
			parent::save();
		}
		else
		{
			$this->tpl->setOnScreenMessage('failure', $this->lng->txt('err_check_input'));
			$form->setValuesByPost();
			$this->editQuestion($form);
		}		
	}
	
	/**
	 * Save and return
	 */
	public function saveReturn(): void
	{
		$this->getViPLabQuestion()->deleteSubParticipant();
		$this->getViPLabQuestion()->deleteExercise();

		$form = $this->initEditQuestionForm();
		if($form->checkInput())
		{
			$this->writeVipLabQuestionFromForm($form);
			parent::saveReturn();
		}
		else
		{
			$this->tpl->setOnScreenMessage('failure', $this->lng->txt('err_check_input'));
			$form->setValuesByPost();
			$this->editQuestion($form);
		}
	}
	
	/**
	 * Set the VipLab Question attributes to the Input of the form.
	 */
	public function writeVipLabQuestionFromForm(ilPropertyFormGUI $form)
	{
		$viplabQuestion = $this->getViPLabQuestion();
		$viplabQuestion->setTitle($form->getInput('title'));
		$viplabQuestion->setComment($form->getInput('comment'));
		$viplabQuestion->setAuthor($form->getInput('author'));
		$viplabQuestion->setQuestion($form->getInput('question'));
		$viplabQuestion->setPoints($form->getInput('points'));

		// ilHiddenInputGUI->getInput eventually calls replacements in ilFormPropertyGUI->stripSlashesAddSpaceFallback
		// which modify the ViPLab exercise code. Hence, the POST variable is used instead of $form->getInput('vipexercise').
		$viplabQuestion->setVipExercise($_POST['vipexercise']);

		$evaluation = ilViPLabUtil::extractJsonFromCustomZip($form->getInput('vipevaluation'));
		$viplabQuestion->setVipEvaluation($evaluation);

		$viplabQuestion->setVipResultStorage($form->getInput('result_storing'));
		$viplabQuestion->setVipAutoScoring($form->getInput('auto_scoring'));

		ilLoggerFactory::getLogger('viplab')->debug(print_r($form->getInput('vipexercise'), true));

		$viplabQuestion->setVipLang($form->getInput('language'));
		return TRUE;
	}
	
	/**
	 * Write post 
	 * @param boolean $always
	 * @return int
	 */
	public function writePostData($always = false): int
	{
		return 0;
	}

	// preview 
	/**
	 * Preview of question
	 * @param boolean $a_show_question_only
	 * @param boolean $showInlineFeedback
	 * @return string the preview as html string
	 */
	public function getPreview($a_show_question_only = FALSE, $showInlineFeedback = FALSE)
	{
		global $DIC;
		$tpl = $DIC->ui()->mainTemplate();
		include_once './Services/UICore/classes/class.ilTemplate.php';
		$template = $this->getPlugin()->getTemplate('tpl.il_as_viplab_preview.html');
		
		$template->setVariable(
			'QUESTIONTEXT',
			ilLegacyFormElementsUtil::prepareTextareaOutput($this->getViPLabQuestion()->getQuestion(), true)
		);
		
		$settings = ilViPLabSettings::getInstance();
		$this->addSubParticipant();
		$this->createExercise();
		
		$template->setVariable('INSTANCE_ID', $this->getViPLabQuestion()->getId());
		$template->setVariable('VIP_EXERCISE',  ilECSExerciseConnector::RESOURCE_PATH.'/'.$this->getViPLabQuestion()->getVipExerciseId());
		$template->setVariable('VIP_ECS_URL', $settings->getECSServer()->getServerURI());
		$template->setVariable('VIP_COOKIE',$this->getViPLabQuestion()->getVipCookie());
		$template->setVariable('VIP_MID',$settings->getLanguageMid($this->getViPLabQuestion()->getVipLang()));
		$template->setVariable('ROOT', $this->getPlugin()->getDirectory());
		$template->setVariable('INITJS',$this->getPlugin()->getDirectory().'/templates');

		// Determine whether the editor can be displayed immediately, i.e. without the start button.
		if ($a_show_question_only) {
			$template->setVariable('IMMEDIATE_INIT');
		} else {
			$template->setVariable('DEFERRED_INIT');
			$template->setVariable('EDITOR_START', $this->getPlugin()->txt('editor_start'));
		}

		$preview = $template->get();
		$preview = !$a_show_question_only ? $this->getILIASPage($preview) : $preview;

		$tpl->addJavaScript($this->getPlugin()->getDirectory().'/js/question_init.js');
		
		return $preview;
	}

    /**
     * @param $active_id
     * @param $pass
     * @param $is_question_postponed
     * @param $user_post_solutions
     * @param $show_specific_inline_feedback
     * @return string
     * @throws ilTemplateException
     */
	public function getTestOutput($active_id, $pass, $is_question_postponed, $user_post_solutions, $show_specific_inline_feedback): string
    {
		global $DIC;
		$tpl = $DIC->ui()->mainTemplate();
		
		$settings = ilViPLabSettings::getInstance();
		$this->addSubParticipant();
		$this->createExercise();

		ilLoggerFactory::getLogger('viplab')->debug('VipCookie: '. $this->getViPLabQuestion()->getVipCookie());
		
		$atpl = $this->getPlugin()->getTemplate('tpl.applet_question.html');

		// What happens if has no solution, answers questions => and clicks "Calculate"?
		$sol_id = $this->createSolution($active_id, $pass, false);

		
		$atpl->setVariable('QUESTIONTEXT', ilLegacyFormElementsUtil::prepareTextareaOutput($this->getViPLabQuestion()->getQuestion(), true));
		$atpl->setVariable('VIP_ECS_URL', $settings->getECSServer()->getServerURI());
		$atpl->setVariable('VIP_COOKIE',$this->getViPLabQuestion()->getVipCookie());
		$atpl->setVariable('VIP_MID',$settings->getLanguageMid($this->getViPLabQuestion()->getVipLang()));
		$atpl->setVariable('VIP_EXERCISE',  ilECSExerciseConnector::RESOURCE_PATH.'/'.$this->getViPLabQuestion()->getVipExerciseId());
		
		if($sol_id)
		{
			$atpl->setVariable('VIP_SOLUTION', ilECSSolutionConnector::RESOURCE_PATH.'/'.$sol_id);
		}
		

		$atpl->setVariable('INITJS',$this->getPlugin()->getDirectory().'/templates');
		
		
		$atpl->setVariable('VIP_STORED_EXERCISE', $this->getViPLabQuestion()->getVipExerciseId());
		$atpl->setVariable('VIP_STORED_PARTICIPANT',$this->getViPLabQuestion()->getVipSubId());
		
		// add solution 
		if($sol_id)
		{
			$atpl->setVariable('VIP_STORED_SOLUTION',$sol_id);
		}
		
		$pageoutput = $this->outQuestionPage("", $is_question_postponed, $active_id, $atpl->get());
		
		$tpl->addJavaScript($this->getPlugin()->getDirectory().'/js/question_init.js');
		return $pageoutput;
	}


    /**
     * Show solution output
     * @param integer $active_id The active id
     * @param integer $pass The test pass
     * @param boolean $graphicalOutput Show visual feedback for right/wrong answers
     * @param boolean $result_output Show the reached points for parts of the question
     * @param boolean $show_question_only Show the question without the ILIAS content around
     * @param boolean $show_feedback Show the question feedback
     * @param boolean $show_correct_solution Show the correct solution instead of the user solution
     * @param boolean $show_manual_scoring Show specific information for the manual scoring output
     * @param boolean $show_question_text
     * @return string The solution output of the question as HTML code
     * @throws ilECSConnectorException
     */
	public function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE, $result_output = FALSE, $show_question_only = TRUE, $show_feedback = FALSE, $show_correct_solution = FALSE, $show_manual_scoring = FALSE, $show_question_text = TRUE): string
	{
		if ($show_correct_solution) {
			return $this->getGenericFeedbackOutputForCorrectSolution();
		}

		// In case that the active_id is an empty string...
		// E.g., passed by Modules/Test/classes/class.ilObjTestGUI.php:printobject() for print views.
		$active_id = $active_id ?: 0;

		// In case of feedbacks, post reviews, etc. show the readonly editor to the student.
		// Otherwise, use the solution output for teachers containing more options.
		switch ($this->ctrl->getCmd()) {
			case 'show':
			case 'outCorrectSolution':
			case 'outUserPassDetails':
			case 'outUserListOfAnswerPasses':
				// student template
				$soltpl = $this->getPlugin()->getTemplate('tpl.applet_solution.html');
				break;
			default:
				// teacher template
				$soltpl = $this->getPlugin()->getTemplate('tpl.viplab_solution_output.html');
		}

		$soltpl->setVariable(
			'SOLUTION_TXT',
			ilLegacyFormElementsUtil::prepareTextareaOutput($this->object->getQuestion(), true)
		);
		
		$this->getViPLabQuestion()->deleteSubParticipant();
		$this->addSubParticipant();

		$this->getViPLabQuestion()->deleteExercise();
		$this->createExercise();

		$sol_id = $this->createSolution($active_id, $pass, true);
		$eva_id = $this->createEvaluation();

		$settings = ilViPLabSettings::getInstance();

		$soltpl->setVariable('INSTANCE_ID',$active_id . '-' . $this->getViPLabQuestion()->getId());
		$soltpl->setVariable('ACTIVE_ID',$active_id);
		$soltpl->setVariable('VIP_APP_ID',$this->getViPLabQuestion()->getId());
		$soltpl->setVariable('VIP_ECS_URL', $settings->getECSServer()->getServerURI());
		$soltpl->setVariable('VIP_COOKIE',$this->getViPLabQuestion()->getVipCookie());
		$soltpl->setVariable('VIP_MID',$settings->getLanguageMid($this->getViPLabQuestion()->getVipLang()));
		$soltpl->setVariable('VIP_EXERCISE',  ilECSExerciseConnector::RESOURCE_PATH.'/'.$this->getViPLabQuestion()->getVipExerciseId());
		$soltpl->setVariable('VIP_EVALUATION', ilECSEvaluationConnector::RESOURCE_PATH.'/'.$eva_id);
		$soltpl->setVariable('ROOT', $this->getPlugin()->getDirectory());
		$soltpl->setVariable('INITJS',$this->getPlugin()->getDirectory().'/templates');

		// `active_id` makes up the solution id for the resource on the ECS. However, `active_id` is not provided in
		// Modules/Test/classes/class.ilObjTestGUI.php:printobject() thus omitting the solution for printing.
		$sol_path = ilECSSolutionConnector::RESOURCE_PATH.'/'.$sol_id;
		$sol_path_val = $this->ctrl->getCmd() != 'print' ? $sol_path : '';
		$soltpl->setVariable('VIP_SOLUTION',  $sol_path_val);

		if($this->getViPLabQuestion()->getVipResultStorage())
		{
			$res_id = $this->createResult($active_id, $pass);
			$vip_res_path = $res_id ? ilECSVipResultConnector::RESOURCE_PATH . '/' . $res_id : '';
			$soltpl->setVariable('VIP_RESULT', $vip_res_path);
		}
		else
		{
			$soltpl->setVariable('VIP_RESULT');
		}

		$soltpl->setVariable('VIP_LANG',$this->getViPLabQuestion()->getVipLang(true));
		$soltpl->setVariable('VIP_STORED_EXERCISE', $this->getViPLabQuestion()->getVipExerciseId());
		$soltpl->setVariable('VIP_STORED_PARTICIPANT',$this->getViPLabQuestion()->getVipSubId());

		// Determine whether the editor can be displayed immediately, i.e. without the start button.
		$immediate_init = $this->ctrl->getCmd() == 'getAnswerDetail' || $this->ctrl->getCmd() == 'outCorrectSolution';
		if ($immediate_init) {
			$soltpl->setVariable('IMMEDIATE_INIT');
		} else {
			$soltpl->setVariable('DEFERRED_INIT');
			$soltpl->setVariable('EDITOR_START', $this->getPlugin()->txt('editor_start'));
		}

		$qst_txt = $soltpl->get();
		
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");

        $feedback = '';
        if ($show_feedback) {
            if (!$this->isTestPresentationContext()) {
                $fb = $this->getGenericFeedbackOutput((int) $active_id, $pass);
                $feedback .= strlen($fb) ? $fb : '';
            }
        }
        if (strlen($feedback)) {
            $solutiontemplate->setVariable(
                "FEEDBACK",
                ilLegacyFormElementsUtil::prepareTextareaOutput($feedback, true)
            );
        }

		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $qst_txt);
		$solutionoutput = $solutiontemplate->get(); 
		if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = $this->getILIASPage($solutionoutput);
		}

		return $solutionoutput;
	}

	public function getSpecificFeedbackOutput($userSolution): string
	{
		return "";
	}

}