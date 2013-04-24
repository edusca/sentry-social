<?php namespace Cartalyst\SentrySocial\Controllers;
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

use App;
use Config;
use Exception;
use Illuminate\Routing\Controllers\Controller;
use Input;
use URL;
use Redirect;
use Sentry;
use SentrySocial;
use View;

class OAuthController extends Controller {

	/**
	 * Lists all available services to authenticate with.
	 *
	 * @return Illuminate\View\View
	 */
	public function getIndex()
	{
		$connections = array();

		foreach (Config::get('cartalyst/sentry-social::services.connections') as $serviceName => $connection)
		{
			if ( ! $connection['key'] or ! $connection['secret']) continue;

			if ( ! isset($connection['service'])) $connection['service'] = $serviceName;
			if ( ! isset($connection['name'])) $connection['name'] = $connection['service'];

			$connections[] = $connection;
		}

		return View::make('cartalyst/sentry-social::oauth/index', compact('connections'));
	}

	/**
	 * Shows a link to authenticate a service.
	 *
	 * @param  string  $serviceName
	 * @return string
	 */
	public function getAuthorize($serviceName)
	{
		$service = SentrySocial::make($serviceName, URL::to("oauth/callback/{$serviceName}"));

		return Redirect::to((string) $service->getAuthorizationUri());
	}

	/**
	 * Handles authentication
	 *
	 * @param  string  $serviceName
	 * @return mixed
	 */
	public function getCallback($serviceName)
	{
		$service = SentrySocial::make($serviceName, URL::to("oauth/callback/{$serviceName}"));

		// If we have an access code
		if ($code = Input::get('code'))
		{
			try
			{
				if (SentrySocial::authenticate($service, $code))
				{
					return Redirect::to('oauth/authenticated');
				}
			}
			catch (Exception $e)
			{
				return Redirect::to('oauth')->withErrors($e->getMessage());
			}
		}

		App::abort(404);
	}

	/**
	 * Returns the "authenticated" view which simply shows the
	 * authenticated user.
	 *
	 * @return mixed
	 */
	public function getAuthenticated()
	{
		if ( ! Sentry::check())
		{
			return Redirect::to('oauth')->withErrors('Not authenticated yet.');
		}

		$user = Sentry::getUser();

		return View::make('cartalyst/sentry-social::oauth/authenticated', compact('user'));
	}

}
