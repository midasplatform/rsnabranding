<?php


/** Auth controller for the rsnabranding module */
class RSNABranding_AuthController extends RSNABranding_AppController
{

    public $_forms = array('User');
    public $_models = array('User', 'Setting');

    /** Login action */
    public function loginAction()
    {
        $request = $this->getRequest();
        $this->Form->User->uri = $request->getRequestUri();
        $form = $this->Form->User->createLoginForm();
        $this->view->form = $this->getFormAsArray($form);
        $this->view->header = 'Login';
        if ($this->_request->isPost()) {
            $this->disableView();
            $previousUri = $this->getParam('previousuri');
            if ($form->isValid($request->getPost())) {
                try {
                    $notifications = array(); // initialize first in case of exception
                    $notifications = Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_AUTHENTICATION',
                        array('email' => $form->getValue('email'), 'password' => $form->getValue('password'))
                    );
                } catch (Zend_Exception $exc) {
                    $this->getLogger()->crit($exc->getMessage());
                }
                $authModule = false;
                foreach ($notifications as $user) {
                    if ($user) {
                        $userDao = $user;
                        $authModule = true;
                        break;
                    }
                }

                if (!$authModule) {
                    $userDao = $this->User->getByEmail($form->getValue('email'));
                    if ($userDao === false) {
                        echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));

                        return;
                    }
                }

                $instanceSalt = Zend_Registry::get('configGlobal')->get('password_prefix', false);

                // Necessary for upgrades to 3.4.1.
                if ($instanceSalt === false) {
                    $instanceSalt = Zend_Registry::get('configGlobal')->password->prefix;
                }

                $currentVersion = UtilityComponent::getCurrentModuleVersion('core');
                if ($currentVersion === false) {
                    throw new Zend_Exception('Core version is undefined.');
                }
                // We have to have this so that an admin can log in to upgrade from version < 3.2.12 to >= 3.2.12.
                // Version 3.2.12 introduced the new password hashing and storage system.
                if (!$authModule && version_compare($currentVersion, '3.2.12', '>=')) {
                    $passwordHash = hash(
                        $userDao->getHashAlg(),
                        $instanceSalt . $userDao->getSalt() . $form->getValue('password')
                    );
                    $coreAuth = $this->User->hashExists($passwordHash);
                } elseif (!$authModule) {
                    $passwordHash = md5($instanceSalt . $form->getValue('password'));
                    $coreAuth = $this->User->legacyAuthenticate($userDao, $instanceSalt, $form->getValue('password'));
                }

                if ($authModule || $coreAuth) {
                    $notifications = Zend_Registry::get('notifier')->callback(
                        'CALLBACK_CORE_AUTH_INTERCEPT',
                        array('user' => $userDao));
                    foreach ($notifications as $value) {
                        if ($value['override'] && $value['response']) {
                            echo $value['response'];
                            return;
                        }
                    }
                    if (!$authModule && version_compare($currentVersion, '3.2.12', '>=') && $userDao->getSalt() == '') {
                        $passwordHash = $this->User->convertLegacyPasswordHash($userDao, $form->getValue('password'));
                    }
                    $remember = $form->getValue('remerberMe');
                    if (!$authModule && isset($remember) && $remember == 1) {
                        setcookie(
                            MIDAS_USER_COOKIE_NAME,
                            $userDao->getKey() . '-' . $passwordHash,
                            $date->add($interval)->getTimestamp(),
                            '/',
                            $request->getHttpHost(),
                            (int)Zend_Registry::get('configGlobal')->get('cookie_secure', 1) === 1,
                            true
                        );
                    } else {
                        setcookie(
                            MIDAS_USER_COOKIE_NAME,
                            null,
                            $date->sub($interval)->getTimestamp(),
                            '/',
                            $request->getHttpHost(),
                            (int)Zend_Registry::get('configGlobal')->get('cookie_secure', 1) === 1,
                            true
                        );
                        Zend_Session::start();
                        $user = new Zend_Session_Namespace('Auth_User');
                        $user->setExpirationSeconds(60 * (int)Zend_Registry::get('configGlobal')->get('session_lifetime', 20));
                        $user->Dao = $userDao;
                        $user->lock();
                    }
                }
                $this->getLogger()->debug(__METHOD__ . ' Log in : ' . $userDao->getFullName());

                if (isset($previousUri) && !empty($previousUri) && (!empty($this->view->webroot)) && strpos(
                        $previousUri,
                        'logout'
                    ) === false
                ) {
                    $redirect = $previousUri;
                } else {
                    $redirect = $this->view->webroot;
                }
                echo JsonComponent::encode(array('status' => true, 'redirect' => $redirect));
            } else {
                echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));
            }
        }

        $this->view->allowPasswordReset = (int)$this->Setting->getValueByNameWithDefault('allow_password_reset', 0) === 1;
        $this->view->closeRegistration = (int)$this->Setting->getValueByNameWithDefault('close_registration', 1) === 1;
    }

    /** Register a user */
    public function registerAction()
    {
        if ((int) $this->Setting->getValueByNameWithDefault('close_registration', 1) === 1) {
            throw new Zend_Exception('New user registration is disabled.');
        }
        $form = $this->Form->User->createRegisterForm();
        $affiliation = new Zend_Form_Element_Text('affiliation');
        $affiliation->setRequired(true)->setAttrib('maxLength', 255);
        $roles = array(
            'Please select a role',
            'Academic Researcher',
            'Algorithm Developer',
            'Cancer Researcher',
            'Clinician',
            'Computer Scientist',
            'Data Manager',
            'Engineer',
            'General Public',
            'Informatician',
            'Medical Imaging Researcher',
            'Medical Physicist',
            'Project Principal Investigator',
            'Software Developer',
            'Other (please specify below)'
        );

        $role = new Zend_Form_Element_Select('role');
        $role->setLabel('Please select a position/role')->setMultiOptions($roles);
        $other = new Zend_Form_Element_Text('other');
        $other->setAttrib('maxLength', 255);
        $form->setAction($this->view->webroot.'/rsnabranding/auth/register');
        $form->addElements(array($affiliation, $role, $other));
        if ($this->_request->isPost() && $form->isValid($this->getRequest()->getPost())
        ) {
            if ($this->User->getByEmail(strtolower($form->getValue('email'))) !== false
            ) {
                throw new Zend_Exception('User already exists.');
            }

            $addressVerification = (int) $this->Setting->getValueByName('address_verification', 'mail');

            if ($addressVerification !== 1) {
                if (!headers_sent()) {
                    session_start();
                }
                $this->userSession->Dao = $this->User->createUser(
                    trim($form->getValue('email')),
                    $form->getValue('password1'),
                    trim($form->getValue('firstname')),
                    trim($form->getValue('lastname'))
                );
                $this->userSession->Dao->setCompany($form->getValue('affiliation'));
                $this->userSession->Dao->setBiography($roles[(int)$form->getValue('role')].' '.$form->getValue('other'));
                $this->User->save($this->userSession->Dao);
                session_write_close();

                $this->redirect('/');
            } else {
                $email = strtolower(trim($form->getValue('email')));
                $pendingUser = $this->PendingUser->createPendingUser(
                    $email,
                    $form->getValue('firstname'),
                    $form->getValue('lastname'),
                    $form->getValue('password1')
                );

                $subject = 'User Registration';
                $url = $this->getServerURL().$this->view->webroot.'/user/verifyemail?email='.$email;
                $url .= '&authKey='.$pendingUser->getAuthKey();
                $body = 'You have created an account on Midas Platform.<br/><br/>';
                $body .= '<a href="'.$url.'">Click here</a> to verify your email and complete registration.<br/><br/>';
                $body .= 'If you did not initiate this registration, please disregard this email.<br/><br/>';

                $result = Zend_Registry::get('notifier')->callback(
                    'CALLBACK_CORE_SEND_MAIL_MESSAGE',
                    array(
                        'to' => $email,
                        'subject' => $subject,
                        'html' => $body,
                        'event' => 'user_verify',
                    )
                );

                if ($result) {
                    $this->redirect('/user/emailsent');
                }
            }
        }
        $this->view->form = $this->getFormAsArray($form);
        $this->view->header = 'Register';
        //$this->disableLayout();
        $this->view->jsonRegister = JsonComponent::encode(
            array(
                'MessageNotValid' => $this->t('The email is not valid'),
                'MessageNotAvailable' => $this->t('That email is already registered'),
                'MessagePassword' => $this->t('Password too short'),
                'MessagePasswords' => $this->t('The passwords are not the same'),
                'MessageLastname' => $this->t('Please set your lastname'),
                'MessageTerms' => $this->t('Please validate the terms of service'),
                'MessageFirstname' => $this->t('Please set your firstname'),
            )
        );
    }

}
