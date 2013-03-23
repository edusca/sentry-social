<?php namespace Cartalyst\SentrySocial;
/**
 * Part of the Sentry Social package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Sentry
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Exception\Exception as OAuthException;
use OAuth\Common\Service\ServiceInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth1\Service\OAuth1ServiceInterface;
use OAuth\OAuth1\Signature\Signature as OAuth1Signature;
use OAuth\OAuth2\Service\OAuth2ServiceInterface;

class ServiceFactory extends \OAuth\ServiceFactory {

	/**
	 * An array of custom OAuth2 services.
	 *
	 * @var array
	 */
	protected $oauth2Services = array();

	/**
	 * An array of custom OAuth1 services.
	 *
	 * @var array
	 */
	protected $oauth1Services = array();

	/**
	 * @param $serviceName string name of service to create
	 * @param Common\Consumer\Credentials $credentials
	 * @param Common\Storage\TokenStorageInterface $storage
	 * @param array|null $scopes If creating an oauth2 service, array of scopes
	 * @return ServiceInterface
	 * @throws Common\Exception\Exception
	 */
	public function createService($serviceName, Credentials $credentials, TokenStorageInterface $storage, $scopes = array())
	{
		// Try an OAuth2 service first
        if (isset($this->oauth2Services[$serviceName]))
        {
        	$className = $this->oauth2Services[$serviceName];

            // Resolve scopes from the service
            $resolvedScopes = array();
            $reflClass = new \ReflectionClass($className);
            $constants = $reflClass->getConstants();

            foreach ($scopes as $scope)
            {
                $key = strtoupper('SCOPE_'.$scope);

                if (array_key_exists($key, $constants))
                {
                    $resolvedScopes[] = $constants[$key];
                }
                else
                {
                    $resolvedScopes[] = $scope;
                }
            }

            return new $className($credentials, $this->httpClient, $storage, $resolvedScopes);
        }

        // Now, try an OAuth 1 service
        if (isset($this->oauth1Services[$serviceName]))
        {
        	$className = $this->oauth1Services[$serviceName];

        	if( ! empty($scopes))
        	{
                throw new OAuthException('Scopes passed to ServiceFactory::createService but an OAuth1 service was requested.');
            }

            $signature = new OAuth1Signature($credentials);

            return new $className($credentials, $this->httpClient, $storage, $signature);
        }

        return parent::createService($serviceName, $credentials, $storage, $scopes);
	}

	/**
	 * Register a custom OAuth2 service with the Service Factory.
	 *
	 * @param  string  $className
	 * @return void
	 */
	public function registerOauth2Service($className)
	{
		$this->oauth2Services[$this->getServiceName($className)] = $className;
	}

	/**
	 * Register a custom OAuth1 service with the Service Factory.
	 *
	 * @param  string  $className
	 * @return void
	 */
	public function registerOauth1Service($className)
	{
		$this->oauth1Services[$this->getServiceName($className)] = $className;
	}

	/**
	 * Extracts the service name from the given class name.
	 *
	 * @param  string  $className
	 * @return string
	 */
	protected function getServiceName($className)
	{
		return basename(str_replace('\\', '/', $className));
	}

}
