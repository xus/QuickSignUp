<?php
include_once("./Services/COPage/classes/class.ilPageComponentPluginGUI.php");

/**
 * Quick login/register modalbox user interface
 * DEV NOTES
 * 1. There is no description of which button type should be used. I'm using the standard without customization.
 * 2. viewcontrol "mode" was my first thinking.
 * 3. Round-Trip rules:
 *       Round-Trip modals MUST contain at least two buttons at the bottom of the modals: a button
 *       to cancel (right) the workflow and a button to finish or reach the next step in the workflow (left).
 *     2: >
 * In the registration form we are not showing the available domains if limited. But we are
 * taking care about it when validate the form.
 *
 *TODO: Rename the new html containers following a name patter.
 *TODO: try to move all the HTML to templates
 *TODO: If fields empty we are getting the something is wrong message.
 *TODO: (save user registration)Should we take care about this auto generated pass?
 * NOTICE: Using this submit buton in the modal actions whe are losing the "Key Enter"submit.
 * Possible implementation for this:
 *     if (e.which == 13) {
 *		$('form#login').submit();
 *		return false;
 *		}
 *
 * @author Jesús López <lopez@leifos.com>
 * @version $Id$
 * @ilCtrl_isCalledBy ilQuickSignUpPluginGUI: ilPCPluggedGUI
 * @ilCtrl_Calls ilQuickSignUpPluginGUI: ilPasswordAssistanceGUI, ilStartUpGUI
 *
 */
class ilQuickSignUpPluginGUI extends ilPageComponentPluginGUI
{
	const MD_LOGIN_VIEW = 1;
	const MD_REGISTER_VIEW = 2;

	var $register_success = false;
	var $register_message = "";
	var $tab_option = self::MD_LOGIN_VIEW;

	var $globals_init = false;

	/**
	 * @var ilCtrl
	 */
	var $ctrl;

	/**
	 * @var ilObjUser
	 */
	var $user;

	/**
	 * @var ilTemplate
	 */
	var $tpl;

	/**
	 * @var ilLanguage
	 */
	var $lng;

	/**
	 * @var \ILIAS\UI\Factory
	 */
	var $ui_factory;

	/**
	 * @var \ILIAS\UI\Renderer
	 */
	var $ui_renderer;

	/**
	 * @var ilSetting
	 */
	var $settings;

	//we probably can create a PHP unique id
	var $form_login_id = "form_login_pl_qs";
	var $form_register_id = "form_register_pl_qs";

	//var $modal_id = "";

	/**
	 * global vars initialization.
	 */
	function initialization()
	{
		global $DIC, $tpl;

		$this->ctrl = $DIC->ctrl();
		$this->user = $DIC->user();
		$this->tpl = $tpl;
		$this->lng = $DIC->language();
		$this->ui_factory = $DIC->ui()->factory();
		$this->ui_renderer = $DIC->ui()->renderer();
		$this->settings = $DIC->settings();

		$this->globals_init = true;

		$this->tpl->addCss("./Customizing/global/plugins/Services/COPage/PageComponent/QuickSignUp/templates/custom.css");

	}

	/**
	 * @return mixed
	 */
	function executeCommand()
	{
		if(!$this->globals_init) {
			$this->initialization();
		}
		$next_class = $this->ctrl->getNextClass();

		switch ($next_class)
		{
			/*case "ilstartupgui":
				require_once("Services/Init/classes/class.ilStartUpGUI.php");
				return $this->ctrl->forwardCommand(new ilStartUpGUI());*/

			case "ilpasswordassistancegui":
				require_once("Services/Init/classes/class.ilPasswordAssistanceGUI.php");
				return $this->ctrl->forwardCommand(new ilPasswordAssistanceGUI());

			default:
				// perform valid commands
				$cmd = $this->ctrl->getCmd();
				if (in_array($cmd, array("create", "save", "edit", "edit2", "update", "cancel", "loginView", "test", "register","standardAuthentication", "jumpToPasswordAssistance", "jumpToNameAssistance", "showTermsOfService", "saveRegistration")))
				{
					$this->$cmd();
				}
				else {
					die($this->lng->txt("something_wrong"));
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
		if(!$this->globals_init) {
			$this->initialization();
		}
		//If the user is not anonymous exit.
		if(!$this->user->isAnonymous()) {
			return "";
		}

		$modal = $this->ui_factory->modal()->roundtrip("Modal Title", $this->ui_factory->legacy(""));
		$this->ctrl->setParameter($this, "replaceSignal", $modal->getReplaceContentSignal()->getId());

		//$this->modal_id = $modal->getReplaceContentSignal()->getId();

		$modal = $modal->withAsyncRenderUrl($this->getLoginUrl());
		$button = $this->ui_factory->button()->standard($this->getPlugin()->txt("sign_in"), '#')
			->withOnClick($modal->getShowSignal());
		$content = $this->ui_renderer->render([$modal, $button]);

		return $content;
	}

	/**
	 * Show navigation
	 * @param
	 * @return
	 */
	function getNavigation()
	{
		$replaceSignal = new \ILIAS\UI\Implementation\Component\Modal\ReplaceContentSignal($_GET["replaceSignal"]);

		$login_url = $this->getLoginUrl();
		$register_url = $this->getRegisterUrl();

		//Only show the buttons if ILIAS allows to create new registrations
		if (ilRegistrationSettings::_lookupRegistrationType() != IL_REG_DISABLED)
		{
			if($this->tab_option == self::MD_LOGIN_VIEW) {
				$button1 = $this->ui_factory->button()->shy($this->lng->txt("login"), '#')->withUnavailableAction();
				$button2 = $this->ui_factory->button()->shy($this->getPlugin()->txt("registration"), '#')
					->withOnClick($replaceSignal->withAsyncRenderUrl($register_url));
			} else {
				$button1 = $this->ui_factory->button()->shy($this->lng->txt("login"), '#')
					->withOnClick($replaceSignal->withAsyncRenderUrl($login_url));
				$button2 = $this->ui_factory->button()->shy($this->getPlugin()->txt("registration"), '#')->withUnavailableAction();
			}

			$html_nav = "<div id='il_qsu_plugin_navigation' class='row'><div class='col-sm-6'>".
				$this->ui_renderer->render($button1)."</div><div class='col-sm-6'>".
				$this->ui_renderer->render($button2)."</div>".
				"</div>";
			return $this->ui_renderer->render($this->ui_factory->legacy($html_nav));
		}

		return array($this->ui_factory->legacy(""));
	}

	/**
	 * Get login screen
	 */
	function loginView()
	{
		//$this->modal_id = $_GET['replaceSignal'];
		//$this->ctrl->saveParameter($this, 'replaceSignal');
		$this->ctrl->setParameter($this, "replaceSignal", $_GET['replaceSignal']);

		$this->setTabOption(self::MD_LOGIN_VIEW);

		include_once './Services/Authentication/classes/class.ilAuthStatus.php';
		$status = ilAuthStatus::getInstance();

		$current_status = $status->getStatus();

		$legacy_content = "";
		switch ($current_status)
		{
			/* //Decided not to show this message.
			case ilAuthStatus::STATUS_AUTHENTICATED:
				$auth_result = array(
					"status" => "ok",
					"html" => "welcome_back"
				);
				echo json_encode($auth_result);
				exit;
			*/
			case ilAuthStatus::STATUS_AUTHENTICATION_FAILED:
				$legacy_content = $this->getNavigation();
				$legacy_content .= "<div class='error_message'>" . $status->getTranslatedReason() . "</div>" . $this->getLoginForm()->getHTML();
				$legacy_content .= $this->appendJS($this->getLoginUrl(), "form_login_modal_plugin");
				$legacy_content .= " ".$this->getPasswordAssistance();
				$auth_result = array(
					"status" => "ko",
					"html" => $legacy_content
				);
				echo json_encode($auth_result);
				exit;
		}

		if($current_status !=ilAuthStatus::STATUS_AUTHENTICATED && $legacy_content == "")
		{
			$legacy_content = $this->getLoginForm()->getHTML();
			$legacy_content .= " ".$this->getPasswordAssistance();
			$legacy_content .= $this->appendJS($this->getLoginValidationUrl(), "form_login_modal_plugin");
		}

		$modal_content = $this->getNavigation();
		$modal_content .= $legacy_content;
		$embed_content = $this->embedTheContent($modal_content);

		// Build a submit button (action button) for the modal footer
		$form_id = "form_login_modal_plugin";
		$submit = $this->ui_factory->button()->primary($this->lng->txt("submit"), '#')
			->withOnLoadCode(function($id) use ($form_id) {
				return "$('#{$id}').click(function() { $('#{$form_id}').submit(); return false; });";
			});

		$modal = $this->ui_factory->modal()->roundtrip("Login", $this->ui_factory->legacy($embed_content))->withCancelButtonLabel($this->lng->txt('close'))->withActionButtons([$submit]);
		echo $this->ui_renderer->renderAsync([$modal]);
		exit;
	}

	/**
	 * Get register screen
	 * //todo lang vars
	 */
	function register()
	{
		//$this->modal_id = $_GET['replaceSignal'];

		//$this->ctrl->setParameter($this, "replaceSignal", $this->modal_id);
		$this->ctrl->setParameter($this, "replaceSignal", $_GET['replaceSignal']);

		$this->setTabOption(self::MD_REGISTER_VIEW);

		include_once './Services/Authentication/classes/class.ilAuthStatus.php';
		$status = ilAuthStatus::getInstance();

		$current_status = $status->getStatus();
		$legacy_content = "";

		switch($current_status)
		{
			case ilAuthStatus::STATUS_AUTHENTICATION_FAILED:
				$legacy_content = $this->getNavigation();
				$legacy_content .= "<div class='error_message'>" . $status->getTranslatedReason() . "</div>" . $this->getRegisterForm()->getHTML();
				$legacy_content .= $this->appendJS($this->getRegisterUrl(), "form_register_modal_plugin");
				$legacy_content .= " ".$this->getPasswordAssistance();
				$auth_result = array(
					"status" => "ko",
					"html" => $legacy_content
				);
				echo json_encode($auth_result);
				exit;
		}

		//get default form.
		if($current_status !=ilAuthStatus::STATUS_AUTHENTICATED && $legacy_content == "")
		{
			$legacy_content = $this->getRegisterForm()->getHTML();
			$legacy_content .= $this->appendJS($this->getRegisterValidationURL(), "form_register_modal_plugin");
		}

		$modal_content = $this->getNavigation();
		$modal_content .= $legacy_content;
		$modal_content .= $this->getTermsOfService();
		$embed_content = $this->embedTheContent($modal_content);

		// Build a submit button (action button) for the modal footer
		$form_id = "form_register_modal_plugin";

		$submit = $this->ui_factory->button()->primary($this->lng->txt('register'), '#')
			->withOnLoadCode(function($id) use ($form_id) {
				return "$('#{$id}').click(function() { $('#{$form_id}').submit(); return false; });";
			});

		$modal = $this->ui_factory->modal()->roundtrip($this->lng->txt('registration'), $this->ui_factory->legacy($embed_content))->withCancelButtonLabel($this->lng->txt('close'))->withActionButtons([$submit]);

		$this->ctrl->saveParameter($this, "replaceSignal");

		echo $this->ui_renderer->renderAsync([$modal]);
		exit;
	}

	//todo: if nothing special to do, delete this method and use only initFormLogin.
	function getLoginForm()
	{
		$form = $this->initFormLogin();

		return $form;
	}

	function getRegisterForm()
	{
		$form = $this->initFormRegister();

		return $form;
	}

	/**
	 * Create
	 *
	 * @param
	 * @return
	 */
	function insert()
	{
		$form = $this->initForm(true);
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * Save new pc example element
	 */
	public function create()
	{
		$form = $this->initForm(true);
		if ($form->checkInput())
		{
			$properties = array(
				"value_1" => $form->getInput("val1"),
				"value_2" => $form->getInput("val2")
			);
			if ($this->createElement($properties))
			{
				ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$this->tpl->setContent($form->getHtml());
	}

	/**
	 * Edit
	 *
	 * @param
	 * @return
	 */
	function edit()
	{
		$this->setTabs("edit");

		$form = $this->initForm();
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * Update
	 *
	 * @param
	 * @return
	 */
	function update()
	{
		$form = $this->initForm(true);
		if ($form->checkInput())
		{
			$properties = array(
				"value_1" => $form->getInput("val1"),
				"value_2" => $form->getInput("val2")
			);
			if ($this->updateElement($properties))
			{
				ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$this->tpl->setContent($form->getHtml());

	}

	/**
	 * Init editing form
	 *
	 * @param        int        $a_mode        Edit Mode
	 */
	public function initForm($a_create = false)
	{
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
			$form->addCommandButton("cancel", $this->lng->txt("cancel"));
			$form->setTitle($this->getPlugin()->txt("cmd_insert"));
		}
		else
		{
			$form->addCommandButton("update", $this->lng->txt("save"));
			$form->addCommandButton("cancel", $this->lng->txt("cancel"));
			$form->setTitle($this->getPlugin()->txt("edit_ex_el"));
		}

		$form->setFormAction($this->ctrl->getFormAction($this));

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
		global $ilTabs;

		$pl = $this->getPlugin();

		$ilTabs->addTab("edit", $pl->txt("settings_1"),
			$this->ctrl->getLinkTarget($this, "edit"));

		$ilTabs->addTab("edit2", $pl->txt("settings_2"),
			$this->ctrl->getLinkTarget($this, "edit2"));

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
		$this->setTabs("edit2");

		ilUtil::sendInfo($this->getPlugin()->txt("more_editing"));
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	function initFormLogin()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$form->setFormAction($this->ctrl->getFormAction($this));
		//todo: we can use $form->setId(uniqid('form'));
		$form->setId("login_modal_plugin");
		$form->setShowTopButtons(false);

		$ti = new ilTextInputGUI($this->lng->txt("username"), "username");
		$ti->setSize(20);
		$ti->setRequired(true);
		$form->addItem($ti);

		$pi = new ilPasswordInputGUI($this->lng->txt("password"), "password");
		$pi->setUseStripSlashes(false);
		$pi->setRetype(false);
		$pi->setSkipSyntaxCheck(true);
		$pi->setSize(20);
		$pi->setDisableHtmlAutoComplete(false);
		$pi->setRequired(true);
		$form->addItem($pi);

		return $form;
	}

	/**
	 * It performs the authentication using the form values and calls the login modal again.
	 */
	function standardAuthentication()
	{
		$form = $this->initFormLogin();

		if($form->checkInput()) {
			$this->login($form->getInput("username"), $form->getInput('password'));
		}

		$this->loginView();
	}

	/**
	 * @return string HTML with the text + link to Terms and Conditions
	 */
	public function getTermsOfService()
	{
		//redirect the user to the terms and conditions.
		require_once './Services/TermsOfService/classes/class.ilTermsOfServiceSignableDocumentFactory.php';
		$document = ilTermsOfServiceSignableDocumentFactory::getByLanguageObject($this->lng);

		if(ilTermsOfServiceHelper::isEnabled() && $document->exists())
		{
			//todo lang vars
			//Button can't used because targed _blank is needed .
			//$btn = $this->ui_factory->button()->shy($this->lng->txt("usr_agreement"), $this->ctrl->getLinkTarget($this, "showTermsOfService"));
			$link = $this->ui_factory->link()->standard($this->lng->txt("usr_agreement"), $this->ctrl->getLinkTarget($this, "showTermsOfService"))->withOpenInNewViewport(true);
			$terms_text = "<p id='terms_qsu_plugin'>".$this->getPlugin()->txt("creating_accept_terms");
			$terms_text .= " ".$this->ui_renderer->render($link)."</p>";

			return $terms_text;
		}
	}

	/**
	 * @return string with the password assistance links
	 */
	public function getPasswordAssistance()
	{
		if ($this->settings->get("password_assistance"))
		{
			$link_pass = $this->ui_factory->button()->shy($this->lng->txt("forgot_password"), $this->ctrl->getLinkTarget($this, "jumpToPasswordAssistance"));
			$link_name = $this->ui_factory->button()->shy($this->lng->txt("forgot_username"), $this->ctrl->getLinkTarget($this, "jumpToNameAssistance"));

			return $this->ui_renderer->render($link_pass)."&nbsp;&nbsp;".$this->ui_renderer->render($link_name);
		}

		return "";
	}

	public function jumpToPasswordAssistance()
	{
		$this->ctrl->redirectByClass(array("ilstartupgui", "ilpasswordassistancegui"),"");

	}

	public function jumpToNameAssistance()
	{
		$this->ctrl->redirectByClass(array("ilstartupgui", "ilpasswordassistancegui"),"showUsernameAssistanceForm");
	}

	/*public function jumpToTermsOfService()
	{

		$this->ctrl->initBaseClass("ilStartUpGUI");

		$this->ctrl->setCmdClass("ilstartupgui");

		$this->ctrl->setCmd("showTermsOfService");

		$this->executeCommand();
	}*/

	/**
	 * Ctrl link to the login form
	 * @return string
	 */
	protected function getLoginUrl()
	{
		$pl = $this->getPlugin();
		return $this->ctrl->getLinkTargetByClass(array($pl->getPageGUIClass(), "ilpcpluggedgui", "ilquicksignupplugingui"), "loginView",
			"", true);
	}

	/**
	 * Ctrl link to the login validation.
	 * @return string
	 */
	protected function getLoginValidationUrl()
	{
		$pl = $this->getPlugin();
		return $this->ctrl->getLinkTargetByClass(array($pl->getPageGUIClass(), "ilpcpluggedgui", "ilquicksignupplugingui"), "standardAuthentication",
			"", true);
	}

	/**
	 * Ctrl link to register form
	 * @return string
	 */
	protected function getRegisterUrl()
	{
		$pl = $this->getPlugin();
		return $this->ctrl->getLinkTargetByClass(array($pl->getPageGUIClass(), "ilpcpluggedgui", "ilquicksignupplugingui"), "register",
			"", true);
	}

	/**
	 * Ctrl link to user register validation.
	 * @return string
	 */
	protected function getRegisterValidationURL()
	{
		$pl = $this->getPlugin();
		return $this->ctrl->getLinkTargetByClass(array($pl->getPageGUIClass(), "ilpcpluggedgui", "ilquicksignupplugingui"), "saveRegistration",
			"", true);
	}

	/**
	 * @param $a_url string ajax url
	 * @param $a_form_id string form id
	 * @return string
	 */
	public function appendJS($a_url, $a_form_id)
	{
		$js = "<script>
			var form_id = '".$a_form_id."';
			/*alert('append with form id => ' + form_id);*/
			$('#'+form_id).on('submit', function(e) {
				var post_url = '".$a_url."';
				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: post_url,
					data: $(this).serialize(),
					dataType: 'json',
					success: function(result) {
						if(result['status'] === 'ok') {
						    /*setTimeout(function(){ location.reload() }, 1000);*/
						    location.reload();
						} else {
						    $('#quick_sign_up_modal_content').html(result['html']);
						}
					 },
					error: function(result) {
					    console.log('error->'+result.responseText);
					    /*todo: remove the register/login button + lang var*/
						$('.modal-body').html('".$this->lng->txt('something_wrong')."');
					 }
				});
			});
		</script>";

		return $js;
	}

	/**
	 * This tab option is about the login, register buttons.
	 * @param $a_view
	 */
	public function setTabOption($a_view)
	{
		$this->tab_option = $a_view;
	}

	public function initFormRegister()
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setId("register_modal_plugin");
		$form->setShowTopButtons(false);

		$ti = new ilTextInputGUI($this->lng->txt("username"), "username");
		$ti->setSize(20);
		$ti->setRequired(true);
		$ti->setMaxLength(30);
		$form->addItem($ti);

		$ti = new ilEMailInputGUI($this->lng->txt("email"), "usr_email");
		$ti->setSize(50);
		$ti->setRequired(true);
		$ti->setMaxLength(100);
		$form->addItem($ti);

		$pi = new ilPasswordInputGUI($this->lng->txt("password"), "usr_password");
		$pi->setUseStripSlashes(false);
		$pi->setRetype(true);
		$pi->setSkipSyntaxCheck(true);
		$pi->setSize(20);
		$pi->setDisableHtmlAutoComplete(false);
		$pi->setRequired(true);
		$form->addItem($pi);

		//TODO remove this after fix the action button JS.
		$form->addCommandButton('saveRegistration', $this->lng->txt('register'));


		return $form;
	}

	function saveRegistration()
	{
		ilLoggerFactory::getRootLogger()->debug("");
		//need this for the email domains.
		$registration_settings = new ilRegistrationSettings();

		$form = $this->getRegisterForm();

		$form_valid = $form->checkInput();

		// validate email against restricted domains
		$email = $form->getInput("usr_email");
		if($email)
		{
			// #10366
			$domains = array();
			foreach($registration_settings->getAllowedDomains() as $item)
			{
				if(trim($item))
				{
					$domains[] = $item;
				}
			}
			if(sizeof($domains))
			{
				$mail_valid = false;
				foreach($domains as $domain)
				{
					$domain = str_replace("*", "~~~", $domain);
					$domain = preg_quote($domain);
					$domain = str_replace("~~~", ".+", $domain);
					if(preg_match("/^".$domain."$/", $email, $hit))
					{
						$mail_valid = true;
						break;
					}
				}
				if(!$mail_valid)
				{
					$mail_obj = $form->getItemByPostVar('usr_email');
					$mail_obj->setAlert(sprintf($this->lng->txt("reg_email_domains"),
						implode(", ", $domains)));
					$form_valid = false;
				}
			}
		}

		$error_lng_var = '';
		if(
			//!$this->registration_settings->passwordGenerationEnabled() &&
			!ilUtil::isPasswordValidForUserContext($form->getInput('usr_password'), $form->getInput('username'), $error_lng_var)
		)
		{
			$passwd_obj = $form->getItemByPostVar('usr_password');
			$passwd_obj->setAlert($this->lng->txt($error_lng_var));
			$form_valid = false;
		}

		//role
		//Last role from available roles for registration.(first one can be Administrator)
		$role_data = array_pop(ilObjRole::_lookupRegisterAllowed());
		$valid_role = $role_data['id'];

		//Admin configuration: User role assigned automatically.
		//include_once 'Services/Registration/classes/class.ilRegistrationEmailRoleAssignments.php';
		//$registration_role = new ilRegistrationRoleAssignments();
		//$valid_role = (int)$registration_role->getDefaultRole();

		ilLoggerFactory::getRootLogger()->debug("DEFAULT ROLE => ".$valid_role);

		//no valid role could be determined
		if (!$valid_role)
		{
			$form_valid = false;
		}

		// validate username
		$login_obj = $form->getItemByPostVar('username');
		$login = $form->getInput("username");
		if (!ilUtil::isLogin($login))
		{
			$login_obj->setAlert($this->lng->txt("login_invalid"));
			$form_valid = false;
		}
		else if (ilObjUser::_loginExists($login))
		{
			$login_obj->setAlert($this->lng->txt("login_exists"));
			$form_valid = false;
		}
		else if ((int)$this->settings->get('allow_change_loginname') &&
			(int)$this->settings->get('reuse_of_loginnames') == 0 &&
			ilObjUser::_doesLoginnameExistInHistory($login))
		{
			$login_obj->setAlert($this->lng->txt('login_exists'));
			$form_valid = false;
		}

		//resolution
		if(!$form_valid)
		{
			$form->setValuesByPost();
			$this->tab_option = self::MD_REGISTER_VIEW;
			$html = $this->getNavigation();
			if(!$valid_role){
				$html .= "<div id='quick_sign_up_modal_error' class='error_message'>".$this->lng->txt("registration_no_valid_role")."</div>";
			}
			$html .= $form->getHTML();
			$auth_result = array(
				"status" => "ko",
				"html" => $html
			);
			echo json_encode($auth_result);
			exit;
		}
		else
		{
			$user_data = array(
				"username" => $form->getInput("username"),
				"email" => $form->getInput("usr_email"),
				"password" => $form->getInput("usr_password")
			);
			//create user
			if($this->createUser($valid_role, $user_data))
			{
				//return status ok, html empty
				$auth_result = array(
					"status" => "ok",
					"html" => ""
				);
				echo json_encode($auth_result);
				exit;
			}
			else
			{
				$html = $this->getNavigation();
				$html .= "<div id='quick_sign_up_modal_error' class='error_message'>".$this->lng->txt("registration_can_not_register")."</div>";
				$html .= $form->getHTML();
				$auth_result = array(
					"status" => "ko",
					"html" => $html
				);
				echo json_encode($auth_result);
				exit;
			}

		}
	}

	protected function createUser($a_role, $a_user_data)
	{
		if(!$a_role)
		{
			global $ilias;
			$ilias->raiseError("Invalid role selection in registration".
				", IP: ".$_SERVER["REMOTE_ADDR"], $ilias->error_obj->FATAL);
		}

		$user_object = new ilObjUser();

		include_once("./Services/User/classes/class.ilUserProfile.php");
		$up = new ilUserProfile();
		$up->setMode(ilUserProfile::MODE_REGISTRATION);

		$user_object->setLogin($a_user_data["username"]);
		$user_object->setEmail($a_user_data["email"]);
		$user_object->setPasswd($a_user_data["password"]);

		//TODO: ASK MATHIAS ABOUT THE FIRST AND LAST NAME.
		//TODO: ASK STEFAN ABOUT THE TIMELIMIT.

		if($user_object->create()) {

			/*Mandatory configuration*/
			$user_object->setActive(true);
			$user_object->setTimeLimitUnlimited(true);
			$user_object->setFirstname($a_user_data["username"]);
			$user_object->setLastname($a_user_data["username"]);
			//accept terms of service
			$date_time = new ilDateTime( time(),IL_CAL_UNIX);
			$user_object->setAgreeDate($date_time);

			//store user in usr_data
			$user_object->saveAsNew();

			//send mail notification
			$this->sendRegistrationEmail($user_object);

			//login
			$this->login($a_user_data['username'], $a_user_data['password']);

			return true;
		}
		else
		{
			return false;
		}
	}

	public function embedTheContent($a_content)
	{
		return "<div id='quick_sign_up_modal_content'>".$a_content."</div>";
	}

	/**
	 * TODO: Working here!
	 * Show terms of service
	 */
	function showTermsOfService()
	{
		require_once './Services/TermsOfService/classes/class.ilTermsOfServiceSignableDocumentFactory.php';
		$document = ilTermsOfServiceSignableDocumentFactory::getByLanguageObject($this->lng);
		$content = $document->getContent();
		if($content != "")
		{
			$custom_tpl = new ilTemplate("./Customizing/global/plugins/Services/COPage/PageComponent/QuickSignUp/templates/default/tpl.content.html", true, true);
			$custom_tpl->setCurrentBlock("terms");
			$custom_tpl->setVariable("CONTENT", $content);
			$custom_tpl->parseCurrentBlock();
			$custom_tpl->show();
		}
		//avoid redirect
		exit;
	}

	/**
	 * @param $a_user_object ilObjUser
	 */
	public function sendRegistrationEmail($a_user_object)
	{
		// try individual account mail in user administration
		include_once("Services/Mail/classes/class.ilAccountMail.php");
		include_once './Services/User/classes/class.ilObjUserFolder.php';
		include_once "Services/Mail/classes/class.ilMimeMail.php";

		$senderFactory = $GLOBALS["DIC"]["mail.mime.sender.factory"];

		$mmail = new ilMimeMail();
		$mmail->From($senderFactory->system());
		$mmail->To($a_user_object->getEmail());

		// mail subject
		$subject = $this->lng->txt("reg_mail_subject");

		// mail body
		$body = $this->lng->txt("reg_mail_body_salutation")." ".$a_user_object->getLogin().",\n\n".
			$this->lng->txt("reg_mail_body_text1")."\n\n".
			$this->lng->txt("reg_mail_body_text2")."\n".
			ILIAS_HTTP_PATH."/login.php?client_id=".CLIENT_ID."\n";
		$body .= $this->lng->txt("login").": ".$a_user_object->getLogin()."\n";

		$body.= "\n";

		$body .= ($this->lng->txt("reg_mail_body_text3")."\n\r");
		$body .= $a_user_object->getProfileAsString($this->lng);
		$mmail->Subject($subject);
		$mmail->Body($body);
		$mmail->Send();

	}

	/**
	 * @param $a_name string username
	 * @param $a_pass string pass
	 */
	protected function login($a_name, $a_pass)
	{
		global $DIC;
		$auth_session = $DIC['ilAuthSession'];

		//login the user
		include_once './Services/Authentication/classes/Frontend/class.ilAuthFrontendCredentials.php';
		$credentials = new ilAuthFrontendCredentials();
		$credentials->setUsername($a_name);
		$credentials->setPassword($a_pass);

		include_once './Services/Authentication/classes/Provider/class.ilAuthProviderFactory.php';
		$provider_factory = new ilAuthProviderFactory();
		$providers = $provider_factory->getProviders($credentials);

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
	}
}
