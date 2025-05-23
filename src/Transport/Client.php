<?php

namespace Smalot\Cups\Transport;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Uri;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Smalot\Cups\CupsException;

/**
 * Class Client
 *
 * @package Smalot\Cups\Transport
 */
class Client implements ClientInterface
{
    const SOCKET_URL = 'unix:///var/run/cups/cups.sock';

    const AUTHTYPE_BASIC = 'basic';

    const AUTHTYPE_DIGEST = 'digest';

    /**
     * @var ClientInterface
     */
    protected $http_client;

    /**
     * @var string
     */
    protected $auth_type;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * Client constructor.
     *
     * @param null|string $username
     * @param null|string $password
     * @param array       $socket_client_options
     */
    public function __construct(string $username = null, string $password = null, array $socket_client_options = [])
    {
        if (!is_null($username)) {
            $this->username = $username;
        }

        if (!is_null($password)) {
            $this->password = $password;
        }

        if (empty($socket_client_options['remote_socket'])) {
            $socket_client_options['remote_socket'] = self::SOCKET_URL;
        }

        $socket_client = new GuzzleClient($socket_client_options);
        $host = preg_match('/unix:\/\//', $socket_client_options['remote_socket']) ? 'http://localhost:631' : $socket_client_options['remote_socket'];
        // Make sure any tcp:// type hosts are switched to http://, we do this to allow tcp:// to be used for backwards compatibility.
        $host = str_replace('tcp://', 'http://', $host);
        $this->http_client = new PluginClient(
          $socket_client, [
            new ErrorPlugin(),
            new ContentLengthPlugin(),
            new DecoderPlugin(),
            new AddHostPlugin(new Uri($host)),
          ]
        );

        $this->auth_type = self::AUTHTYPE_BASIC;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return $this
     */
    public function setAuthentication(string $username, string $password): Client
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * @param string $auth_type
     *
     * @return $this
     */
    public function setAuthType(string $auth_type): Client
    {
        $this->auth_type = $auth_type;

        return $this;
    }

    /**
     * (@inheritdoc}
     *
     * @throws CupsException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->username || $this->password) {
            switch ($this->auth_type) {
                case self::AUTHTYPE_BASIC:
                    $pass = base64_encode($this->username.':'.$this->password);
                    $authentication = 'Basic '.$pass;
                    break;

                case self::AUTHTYPE_DIGEST:
                    throw new CupsException('Auth type not supported');

                default:
                    throw new CupsException('Unknown auth type');
            }

            $request = $request->withHeader('Authorization', $authentication);
        }

        return $this->http_client->sendRequest($request);
    }
}
