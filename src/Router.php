<?php

namespace SunflowerFuchs\FakeSso;

use Exception;
use InvalidArgumentException;

class Router {
	/**
	 * Routes all traffic
	 *
	 * @throws Exception
	 */
	public static function start() : void {
		$url = rtrim(strtolower(str_replace('?' . ($_SERVER['QUERY_STRING'] ?? ''), '', $_SERVER['REQUEST_URI'] ?? '')), '/');

		switch ($url) {
			case '/authorize':
				self::showAuthPage();
				break;
			case '/token':
				self::getToken();
				break;
			case '/me':
				self::getUserInfo();
				break;
			default:
				self::showHelpPage();
				break;
		}
	}

	/**
	 * Prints the access token as a json
	 *
	 * @throws Exception
	 */
	protected static function getToken() : void {
		$code = trim($_POST['code'] ?? '');
		if (empty($code)) {
			throw new InvalidArgumentException('No code given.');
		}

		$user  = new User($code);
		$token = $user->getToken();

		header('Content-Type: application/json');
		echo "{\"access_token\":\"${token}\"}";
	}

	/**
	 * Prints out the user info as a json
	 *
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	protected static function getUserInfo() : void {
		$token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
		if (empty($token)) {
			throw new InvalidArgumentException('No authorization header passed');
		}

		$user  = User::getByToken($token);
		$userInfo = [
		    'sub' => $user->id,
		    'name' => $user->name,
		    'email' => $user->email,

            // we also fake additional fields to impersonate other sso providers

            // sent by e.g. linkedin
            'id' => $user->id,
        ];

		header('Content-Type: application/json');
		echo json_encode($userInfo);
	}

	/**
	 * Prints out the authorization form page
	 */
	protected static function showAuthPage() : void {
		$redirect_uri = $_GET['redirect_uri'] ?? '';
		$state        = $_GET['state'] ?? '';
		if (empty($redirect_uri)) {
			throw new InvalidArgumentException('No redirect uri given.');
		}

		$known = User::getAllIdentifiers();
		$data  = '';
		foreach ($known as $id) {
			$data .= "<option>${id}</option>";
		}

		echo <<<AUTH
<html lang="en">
<body>
	<div style="display: flex; align-items: center; justify-content: center; height: 100vh;">
		<form method="get" action="${redirect_uri}" style="margin: auto; width: auto; height: auto;">
			<fieldset>
				<legend>Fake SSO Login</legend>
				<input type="hidden" name="state" value="${state}">
				<label for="code">Identifier:</label><br />
				<input type="text" name="code" id="code" list="known"><br /><br />
				<datalist id="known">
				${data}
				</datalist>
				<input type="submit" value="Submit">
			</fieldset>
		</form>
	</div>
</body>
</html>
AUTH;
	}

	/**
	 * Prints out the help page
	 */
	protected static function showHelpPage() : void {
		$port   = '';
		$host   = $_SERVER['HTTP_HOST'] ?? '';
		$colPos = strpos($host, ':');
		if ($colPos !== false) {
			$port = substr($host, $colPos);
		}

		echo <<<HELP
<html lang="en">
<body>
	<div style="display: flex; align-items: center; justify-content: center; height: 100vh;">
		<div style="margin: auto; width: auto; height: auto;">
			<h1>Fake SSO Provider</h1>
			<p>Endpoints:</p>
			<ul>
				<li>http://host.docker.internal${port}/authorize</li>
				<li>http://host.docker.internal${port}/token</li>
				<li>http://host.docker.internal${port}/me</li>
			</ul>
		</div>
	</div>
</body>
</html>
HELP;
	}
}
