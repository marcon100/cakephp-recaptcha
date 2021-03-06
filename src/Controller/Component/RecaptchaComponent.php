<?php
/**
 * Recaptcha Component
 *
 * @author   cake17
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://cake17.github.io/
 *
 */
namespace Recaptcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Recaptcha\Lib\ReCaptcha;

class RecaptchaComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Initialize config data and properties.
     *
     * @param array $config The config data.
     *
     * @return void
     */
    public function initialize(array $config)
    {
        $controller = $this->_registry->getController();
        // Add the helper on the fly
        $controller->helpers[] = 'Recaptcha.Recaptcha';
    }

    /**
     * startup callback
     *
     * @param \Cake\Event\Event $event Event.
     *
     * @return mix void
     */
    public function startup(Event $event)
    {
        $controller = $event->subject();

        // Register API keys at https://www.google.com/recaptcha/admin
        $siteKey = Configure::read('Recaptcha.siteKey');
        $secret = Configure::read('Recaptcha.secret');
        // reCAPTCHA supported 40+ languages listed here: https://developers.google.com/recaptcha/docs/language
        $lang = Configure::read('Recaptcha.defaultLang');
        // either light or dark
        $theme = Configure::read('Recaptcha.defaultTheme');
        // either image or audio
        $type = Configure::read('Recaptcha.defaultType');

        $host = $controller->request->env("REMOTE_ADDR");

        // The response from reCAPTCHA
        $resp = null;
        // The error code from reCAPTCHA, if any
        $error = null;
        $reCaptcha = new ReCaptcha($secret);

        // send siteKey, lang, theme and type from config to helper
        $controller->helpers['Recaptcha.Recaptcha']['siteKey'] = $siteKey;
        $controller->helpers['Recaptcha.Recaptcha']['lang'] = $lang;
        $controller->helpers['Recaptcha.Recaptcha']['theme'] = $theme;
        $controller->helpers['Recaptcha.Recaptcha']['type'] = $type;

        if ($controller->request->is(['post', 'put'])) {
            // if Recaptcha is not checked
            if (isset($controller->request->data["g-recaptcha-response"]) && empty($controller->request->data["g-recaptcha-response"])) {
                $controller->Flash->error(__d('recaptcha', 'Please check Recaptcha Box.'));
                return $controller->redirect($controller->referer());
            }

            if (isset($controller->request->data["g-recaptcha-response"]) && !empty($controller->request->data["g-recaptcha-response"])) {
                $gRecaptchaResponse = $controller->request->data["g-recaptcha-response"];
                $resp = $reCaptcha->verifyResponse(
                    $host,
                    $gRecaptchaResponse
                );
                // if verification is incorrect,
                if ($resp != null && !$resp->success) {
                    $controller->Flash->error(__d('recaptcha', 'Please check Recaptcha Box.'));
                    return $controller->redirect($controller->referer());
                }
            }
        }
    }
}
