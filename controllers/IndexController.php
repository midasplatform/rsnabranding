<?php


/** Index controller for the statistics module */
class RSNABranding_IndexController extends RSNABranding_AppController
{

    public function indexAction()
    {
    }

    /** Login action */
    public function loginAction()
    {
        $request = $this->getRequest();
        $this->Form->User->uri = $request->getRequestUri();
        $form = $this->Form->User->createLoginForm();
        $this->view->form = $this->getFormAsArray($form);
        $this->disableLayout();
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
                if (!$authModule && version_compare($currentVersion, '3.2.12', '>=')
                ) {
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
                        array('user' => $userDao)
                    );
                    foreach ($notifications as $value) {
                        if ($value['override'] && $value['response']) {
                            echo $value['response'];

                            return;
                        }
                    }
                    if (!$authModule && version_compare($currentVersion, '3.2.12', '>=') && $userDao->getSalt() == ''
                    ) {
                        $passwordHash = $this->User->convertLegacyPasswordHash($userDao, $form->getValue('password'));
                    }
                    $remember = $form->getValue('remerberMe');
                    if (!$this->isTestingEnv()) {
                        $date = new DateTime();
                        $interval = new DateInterval('P1M');
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
                        $redirect = $this->view->webroot . '/feed?first=true';
                    }
                    echo JsonComponent::encode(array('status' => true, 'redirect' => $redirect));
                } else {
                    echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));
                }
            } else {
                echo JsonComponent::encode(array('status' => false, 'message' => 'Invalid email or password'));
            }
        }

        $this->view->allowPasswordReset = (int)$this->Setting->getValueByNameWithDefault('allow_password_reset', 0) === 1;
        $this->view->closeRegistration = (int)$this->Setting->getValueByNameWithDefault('close_registration', 1) === 1;
    }
}