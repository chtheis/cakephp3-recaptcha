<?php
declare(strict_types=1);

namespace Recaptcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Http\Client;

/**
 * Recaptcha component
 */
class RecaptchaComponent extends Component
{
    /**
     * Default config
     *
     * These are merged with user-provided config when the component is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        // This is test only key/secret
        'version' => 2,
        'sitekey' => 'sitekey',
        'secret' => 'secret',
        'theme' => 'light',
        'type' => 'image',
        'enable' => true,
        'lang' => 'en',
        'size' => 'normal',
        'scoreThreshold' => 0.5,
        'httpClientOptions' => [],
    ];

    /**
     * initialize
     *
     * @param array $config config
     * @return void
     */
    public function initialize(array $config) : void
    {
        if (empty($config)) {
            $config = Configure::read('Recaptcha', []);
        }

        $this->setConfig($config);
        $this->_registry->getController()->viewBuilder()->addHelpers(['Recaptcha.Recaptcha' => $this->_config]);
    }

    /**
     * verify recaptcha
     *
     * @return bool
     */
    public function verify()
    {
        if (!$this->_config['enable']) {
            return true;
        }

        $controller = $this->_registry->getController();
        if ($controller->getRequest()->getData('g-recaptcha-response')) {
            $response = json_decode($this->apiCall());

            if (isset($response->success) && $response->success) {
                return true;
            } elseif (isset($response->score) && $response->score >= floatval($this->_config['scoreThreshold'])) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Call reCAPTCHA API to verify
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function apiCall(): string
    {
        $controller = $this->_registry->getController();
        $client = new Client($this->_config['httpClientOptions']);
        $data = [
            'secret' => $this->_config['secret'],
            'response' => $controller->getRequest()->getData('g-recaptcha-response'),
            'remoteip' => $controller->getRequest()->clientIp(),
        ];

        return (string)$client->post('https://www.google.com/recaptcha/api/siteverify', $data)->getBody();
    }
}
