<?php
include_once("./Services/COPage/classes/class.ilPageComponentPluginGUI.php");

/**
 * Quick login/register modalbox user interface
 *
 *
 * DEV NOTES
 * 1. There is no description of which button type should be used. I'm using the standard without customization.
 * 2. viewcontrol "mode" was my first thinking.
 *
 * @author Jesús López <lopez@leifos.com>
 * @version $Id$
 * @ilCtrl_isCalledBy ilQuickSignUpPluginGUI: ilPCPluggedGUI
 */
class ilQuickSignUpPluginGUI extends ilPageComponentPluginGUI
{

	const MD_LOGIN_VIEW = 1;
	const MD_REGISTER_VIEW = 2;

	var $login_success = false;
	var $login_message = "";

	var $register_success = false;
	var $register_message = "";

	function executeCommand()
	{
		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass();

		switch($next_class)
		{
			default:
				// perform valid commands
				$cmd = $ilCtrl->getCmd();
				if (in_array($cmd, array("create", "save", "edit", "edit2", "update", "cancel")))
				{
					$this->$cmd();
				}
				break;
		}
	}

	/**
	 * Get HTML for element
	 *
	 * @param string $a_mode (edit, presentation, preview, offline)s
	 * @return string $html
	 */
	function getElementHTML($a_mode, array $a_properties, $a_plugin_version)
	{

		//globals
		global $DIC;
		$f = $DIC->ui()->factory();
		$r = $DIC->ui()->renderer();
		$ctrl = $DIC->ctrl();
		$user = $DIC->user();
		$url = $_SERVER['REQUEST_URI'];

		//validation TODO when finish: uncomment this validation and add more if needed. We want to show this button only for non logged users.
		//if($user->id) {
		//	return "";
		//}

		//template
		$pl = $this->getPlugin();
		$tpl = $pl->getTemplate("tpl.content.html");


		$page = $_GET["page"];


		//FIRST LOAD
		if ($page == "")
		{

			$modal = $f->modal()->roundtrip("Modal Title", $f->legacy("b"));
			$asyncUrl = $url . '&page=login&replaceSignal=' . $modal->getReplaceContentSignal()->getId();
			$modal = $modal->withAsyncRenderUrl($asyncUrl);
			$button = $f->button()->standard("Sign In", '#')
				->withOnClick($modal->getShowSignal());
			$content = $r->render([$modal, $button]);
			return $content;

		}
		//WITH ASYNC
		else
		{
			$signalId = $_GET['replaceSignal'];

			include_once './Services/Authentication/classes/class.ilAuthStatus.php';
			$status = ilAuthStatus::getInstance();

			$replaceSignal = new \ILIAS\UI\Implementation\Component\Modal\ReplaceContentSignal($signalId);

			//Only show the buttons if ILIAS allows to create new registrations
			if (ilRegistrationSettings::_lookupRegistrationType() != IL_REG_DISABLED) {
				$button1 = $f->button()->standard('Login', '#')
					->withOnClick($replaceSignal->withAsyncRenderUrl($url . '&page=login&replaceSignal=' . $replaceSignal->getId()));
				$button2 = $f->button()->standard('Registration', '#')
					->withOnClick($replaceSignal->withAsyncRenderUrl($url . '&page=register&replaceSignal=' . $replaceSignal->getId()));
			} else {
				$button1 =  $button2 = $f->legacy("");
			}

			if ($page == "login")
			{
				switch($status->getStatus())
				{
					case ilAuthStatus::STATUS_AUTHENTICATED:
						$legacy_content = $this->login_message;

					case ilAuthStatus::STATUS_AUTHENTICATION_FAILED:
						$this->login_message = $status->getTranslatedReason();
						//todo remove inline css and use the ilias sendFailure css
						$css = "background-color:red; color:white; margin:10px 0; padding:10px;";
						$legacy_content = "<div style='".$css."'>".$this->login_message."</div>".$this->getLoginForm()->getHTML();
				}
				if($legacy_content == "")
				{
					$legacy_content = $this->getLoginForm()->getHTML();
				}

				//$legacy_content .= $this->getPasswordAssistance;
				$legacy = $f->legacy($legacy_content);

				$modal = $f->modal()->roundtrip("Login", [$button1, $button2, $legacy]);
			}
			if ($page == "register")
			{
				$legacy = $f->legacy("<p>The Registration Page</p>");
				$modal = $f->modal()->roundtrip("Registration", [$button1, $button2, $legacy]);
			}

			//$modal = $modal->withContent([$button1, $button2]);
			echo $r->renderAsync([$modal]);
			exit;
		}
	}

	function getLoginForm()
	{

		global $tpl, $lng, $ilCtrl;

		$form = $this->initFormLogin();
		//$form->setValuesByPost();
		ilLoggerFactory::getRootLogger()->debug("html form => ".$form->getHTML());
		return $form;
	}

	function getRegisterForm()
	{
		return "Register";
	}

	/**
	 * Create
	 *
	 * @param
	 * @return
	 */
	function insert()
	{
		global $tpl;

		$form = $this->initForm(true);
		$tpl->setContent($form->getHTML());
	}

	/**
	 * Save new pc example element
	 */
	public function create()
	{
		global $tpl, $lng, $ilCtrl;

		$form = $this->initForm(true);
		if ($form->checkInput())
		{
			$properties = array(
				"value_1" => $form->getInput("val1"),
				"value_2" => $form->getInput("val2")
			);
			if ($this->createElement($properties))
			{
				ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHtml());
	}

	/**
	 * Edit
	 *
	 * @param
	 * @return
	 */
	function edit()
	{
		global $tpl;

		$this->setTabs("edit");

		$form = $this->initForm();
		$tpl->setContent($form->getHTML());
	}

	/**
	 * Update
	 *
	 * @param
	 * @return
	 */
	function update()
	{
		global $tpl, $lng, $ilCtrl;

		$form = $this->initForm(true);
		if ($form->checkInput())
		{
			$properties = array(
				"value_1" => $form->getInput("val1"),
				"value_2" => $form->getInput("val2")
			);
			if ($this->updateElement($properties))
			{
				ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHtml());

	}


	/**
	 * Init editing form
	 *
	 * @param        int        $a_mode        Edit Mode
	 */
	public function initForm($a_create = false)
	{
		global $lng, $ilCtrl;

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		// value one
		$v1 = new ilTextInputGUI($this->getPlugin()->txt("text"), "val1");
		$v1->setMaxLength(40);
		$v1->setSize(40);
		$v1->setRequired(true);
		$form->addItem($v1);

		// value two
		$v2 = new ilTextInputGUI($this->getPlugin()->txt("color"), "val2");
		$v2->setMaxLength(40);
		$v2->setSize(40);
		$form->addItem($v2);

		if (!$a_create)
		{
			$prop = $this->getProperties();
			$v1->setValue($prop["value_1"]);
			$v2->setValue($prop["value_2"]);
		}

		// save and cancel commands
		if ($a_create)
		{
			$this->addCreationButton($form);
			$form->addCommandButton("cancel", $lng->txt("cancel"));
			$form->setTitle($this->getPlugin()->txt("cmd_insert"));
		}
		else
		{
			$form->addCommandButton("update", $lng->txt("save"));
			$form->addCommandButton("cancel", $lng->txt("cancel"));
			$form->setTitle($this->getPlugin()->txt("edit_ex_el"));
		}

		$form->setFormAction($ilCtrl->getFormAction($this));

		return $form;
	}

	/**
	 * Cancel
	 */
	function cancel()
	{
		$this->returnToParent();
	}

	/**
	 * Set tabs
	 *
	 * @param
	 * @return
	 */
	function setTabs($a_active)
	{
		global $ilTabs, $ilCtrl;

		$pl = $this->getPlugin();

		$ilTabs->addTab("edit", $pl->txt("settings_1"),
			$ilCtrl->getLinkTarget($this, "edit"));

		$ilTabs->addTab("edit2", $pl->txt("settings_2"),
			$ilCtrl->getLinkTarget($this, "edit2"));

		$ilTabs->activateTab($a_active);
	}

	/**
	 * More settings editing
	 *
	 * @param
	 * @return
	 */
	function edit2()
	{
		global $tpl;

		$this->setTabs("edit2");

		ilUtil::sendInfo($this->getPlugin()->txt("more_editing"));
	}


	/**
	 * @return ilPropertyFormGUI
	 */
	function initFormLogin()
	{
		//global $lng, $ilCtrl;
		global $DIC;

		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		//$form->setFormAction($ctrl->getFormAction($this));
		$form->setName("formlogin");
		$form->setShowTopButtons(false);

		$ti = new ilTextInputGUI($lng->txt("username"), "username");
		$ti->setSize(20);
		$ti->setRequired(true);
		$form->addItem($ti);

		$pi = new ilPasswordInputGUI($lng->txt("password"), "password");
		$pi->setUseStripSlashes(false);
		$pi->setRetype(false);
		$pi->setSkipSyntaxCheck(true);
		$pi->setSize(20);
		$pi->setDisableHtmlAutoComplete(false);
		$pi->setRequired(true);
		$form->addItem($pi);

		//async
		$form->addCommandButton("standardAuthentication", $lng->txt("log_in"));

		return $form;
	}

	protected function standardAuthentication()
	{
		global $DIC;
		$auth_session = $DIC['ilAuthSession'];
		$form = $this->initFormLogin();
		if($form->checkInput())
		{
			include_once './Services/Authentication/classes/Frontend/class.ilAuthFrontendCredentials.php';
			$credentials = new ilAuthFrontendCredentials();
			$credentials->setUsername($form->getInput('username'));
			$credentials->setPassword($form->getInput('password'));

			include_once './Services/Authentication/classes/Provider/class.ilAuthProviderFactory.php';
			$provider_factory = new ilAuthProviderFactory();
			$providers = $provider_factory->getProviders($credentials);

			//todo we can delete this status
			include_once './Services/Authentication/classes/class.ilAuthStatus.php';
			$status = ilAuthStatus::getInstance();

			include_once './Services/Authentication/classes/Frontend/class.ilAuthFrontendFactory.php';
			$frontend_factory = new ilAuthFrontendFactory();
			$frontend_factory->setContext(ilAuthFrontendFactory::CONTEXT_STANDARD_FORM);
			$frontend = $frontend_factory->getFrontend(
				$auth_session,
				$status,
				$credentials,
				$providers
			);

			$frontend->authenticate();

			//todo: we could put this messages and returns directly when check the auth status in gethtml
			switch($status->getStatus())
			{
				case ilAuthStatus::STATUS_AUTHENTICATED:
					return $this->login_success = true;

				/**
				 *
				 * TODO:
				 *
				 * Activation code status
				 *
				 * Migration required status
				 *
				 */

				case ilAuthStatus::STATUS_AUTHENTICATION_FAILED:
					$this->login_message = $status->getTranslatedReason();
					return $this->login_success = false;
			}
		}

		$this->login_success = $this->lng->txt('err_wrong_login');
		return $this->login_success = false;
	}

	/**
	 * TODO remove this: If we have the tabs we don't need this method nor getAlreadyUsingIlias
	 * @return string with link to the register
	 */
	public function getNewToIlias()
	{
		global $DIC;
		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();

		if (ilRegistrationSettings::_lookupRegistrationType() != IL_REG_DISABLED)
		{
			return $lng->txt("registration").$ctrl->getLinkTargetByClass("ilaccountregistrationgui", "");
		}
	}

	public function getAlreadyUsingIlias()
	{
		//TODO nothing.
	}

	//TODO. tpl
	//TODO ??? exec command + * @ilCtrl_Calls ilQuickSignUpPluginGUI: ilPasswordAssistanceGUI
	public function getPasswordAssistance()
	{
		global $DIC;

		$il_setting = $DIC->settings();
		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();
		// allow password assistance? Surpress option if Authmode is not local database
		if ($il_setting->get("password_assistance"))
		{
			$msg_password = $lng->txt("forgot_password")." ".$ctrl->getLinkTargetByClass("ilpasswordassistancegui", "");
			$msg_username = $lng->txt("forgot_username")." ".$ctrl->getLinkTargetByClass("ilpasswordassistancegui", "showUsernameAssistanceForm");

			return $msg_password."<br>".$msg_username;
		}

		return "";

	}



}
?>