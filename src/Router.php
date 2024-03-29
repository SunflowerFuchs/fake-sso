<?php
declare(strict_types=1);

namespace SunflowerFuchs\FakeSso;

use Exception;
use InvalidArgumentException;

class Router
{
    /**
     * Routes all traffic
     *
     * @throws SsoException
     */
    public static function start(): void
    {
        $url = rtrim(strtolower(str_replace('?' . ($_SERVER['QUERY_STRING'] ?? ''), '', $_SERVER['REQUEST_URI'] ?? '')),
            '/');

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
            case '':
            case '/':
                self::showHelpPage();
                break;
            default:
                throw new SsoException('Not found.', 404);
        }
    }

    /**
     * Returns the base url for the current request
     *
     * @return string
     */
    protected static function getBaseUrl(): string
    {
        $protocol = 'http';
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            $protocol = 'https';
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'host.docker.internal';
        return "${protocol}://${host}";
    }

    /**
     * Prints the access token as a json
     *
     * @throws SsoException
     */
    protected static function getToken(): void
    {
        if (!empty(Config::clientSecret())) {
            if (Config::clientSecret() !== ($_POST['client_secret'] ?? false)) {
                throw new SsoException('Invalid client_secret', 401);
            }
        }

        $code = trim($_POST['code'] ?? '');
        if (empty($code)) {
            throw new SsoException('No code given.', 400);
        }

        $user = new User($code);
        $token = $user->getToken();

        header('Content-Type: application/json');
        echo "{\"access_token\":\"${token}\"}";
    }

    /**
     * Prints out the user info as a json
     *
     * @throws InvalidArgumentException
     * @throws SsoException
     */
    protected static function getUserInfo(): void
    {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (empty($token)) {
            throw new SsoException('No authorization header passed.', 401);
        }

        $user = User::getByToken($token);
        $userInfo = [
            'sub' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        // we also fake additional fields to impersonate other sso providers
        if (Config::additionalFields()) {
            $userInfo += [
                // sent by e.g. linkedin
                'id' => $user->id
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($userInfo);
    }

    /**
     * Prints out the authorization form page
     * @throws InvalidArgumentException
     * @throws SsoException
     * @throws Exception
     */
    protected static function showAuthPage(): void
    {
        $redirect_uri = $_GET['redirect_uri'] ?? '';
        $state = $_GET['state'] ?? '';
        if (empty($redirect_uri)) {
            throw new SsoException('No redirect uri given.', 400);
        }

        $knownUsers = '';
        if (Config::showKnown()) {
            foreach (User::getAllIdentifiers() as $id) {
                $knownUsers .= "<option>${id}</option>";
            }
            if (!empty($knownUsers)) {
                $knownUsers = "<datalist id=\"known\">${knownUsers}</datalist>";
            }
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
				${knownUsers}
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
    protected static function showHelpPage(): void
    {
        $baseUrl = self::getBaseUrl();

        echo <<<HELP
<html lang="en">
<body>
	<div style="display: flex; align-items: center; justify-content: center; height: 100vh;">
		<div style="margin: auto; width: auto; height: auto;">
			<h1>Fake SSO Provider</h1>
			<p>Endpoints:</p>
			<ul>
				<li>${baseUrl}/authorize</li>
				<li>${baseUrl}/token</li>
				<li>${baseUrl}/me</li>
			</ul>
		</div>
	</div>
</body>
</html>
HELP;
    }
}
